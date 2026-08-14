<?php
/**
 * Payout methods.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defines how affiliates can be paid, and validates the details each method
 * needs.
 *
 * A method is a key plus a set of fields. Everything else — the dashboard
 * form, the request modal, the admin summary — is generated from these
 * definitions, so adding a method means adding one entry here.
 */
final class Directorist_Affiliate_Payout_Methods {
	/**
	 * Settings service.
	 *
	 * @var Directorist_Affiliate_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Directorist_Affiliate_Settings $settings Settings.
	 */
	public function __construct( Directorist_Affiliate_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Every method the plugin knows about.
	 *
	 * @return array<string,array{label:string,hint:string,fields:array<string,array{label:string,type:string,required:bool,hint:string}>}>
	 */
	public function all(): array {
		$methods = array(
			'paypal' => array(
				'label'  => __( 'PayPal', 'directorist-affiliate' ),
				'hint'   => __( 'Paid to your PayPal account.', 'directorist-affiliate' ),
				'fields' => array(
					'paypal_email' => array(
						'label'    => __( 'PayPal email', 'directorist-affiliate' ),
						'type'     => 'email',
						'required' => true,
						'hint'     => __( 'The email address your PayPal account is registered to.', 'directorist-affiliate' ),
					),
				),
			),
			'bank'   => array(
				'label'  => __( 'Bank transfer', 'directorist-affiliate' ),
				'hint'   => __( 'Paid straight into your bank account.', 'directorist-affiliate' ),
				'fields' => array(
					'account_name'   => array(
						'label'    => __( 'Account holder name', 'directorist-affiliate' ),
						'type'     => 'text',
						'required' => true,
						'hint'     => __( 'Exactly as it appears on the account.', 'directorist-affiliate' ),
					),
					'bank_name'      => array(
						'label'    => __( 'Bank name', 'directorist-affiliate' ),
						'type'     => 'text',
						'required' => true,
						'hint'     => '',
					),
					'account_number' => array(
						'label'    => __( 'Account number or IBAN', 'directorist-affiliate' ),
						'type'     => 'text',
						'required' => true,
						'hint'     => '',
					),
					'routing_number' => array(
						'label'    => __( 'Routing, SWIFT or BIC', 'directorist-affiliate' ),
						'type'     => 'text',
						'required' => false,
						'hint'     => __( 'Optional, but usually needed for international transfers.', 'directorist-affiliate' ),
					),
				),
			),
			'cash'   => array(
				'label'  => __( 'Cash', 'directorist-affiliate' ),
				'hint'   => __( 'Collected in person or arranged by phone.', 'directorist-affiliate' ),
				'fields' => array(
					'phone' => array(
						'label'    => __( 'Phone number', 'directorist-affiliate' ),
						'type'     => 'tel',
						'required' => true,
						'hint'     => __( 'So we can reach you to arrange the handover.', 'directorist-affiliate' ),
					),
				),
			),
		);

		/**
		 * Filters the payout methods the plugin supports.
		 *
		 * @param array<string,array<string,mixed>> $methods Method definitions.
		 */
		return (array) apply_filters( 'directorist_affiliate_payout_methods', $methods );
	}

	/**
	 * Methods the admin has switched on.
	 *
	 * Falls back to every method when the setting is empty, so an accidental
	 * "save with nothing ticked" cannot silently disable payouts.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function enabled(): array {
		$all       = $this->all();
		$permitted = (array) $this->settings->get( 'payout_methods', array_keys( $all ) );
		$enabled   = array_intersect_key( $all, array_flip( $permitted ) );

		return $enabled ? $enabled : $all;
	}

	/**
	 * Whether a method key is currently offered.
	 *
	 * @param string $method Method key.
	 *
	 * @return bool
	 */
	public function is_enabled( string $method ): bool {
		return array_key_exists( $method, $this->enabled() );
	}

	/**
	 * Label for a method, even one no longer offered.
	 *
	 * @param string $method Method key.
	 *
	 * @return string
	 */
	public function label( string $method ): string {
		$all = $this->all();

		return $all[ $method ]['label'] ?? ( $method ? ucfirst( $method ) : __( 'Not set', 'directorist-affiliate' ) );
	}

	/**
	 * Fields a method asks for.
	 *
	 * @param string $method Method key.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function fields( string $method ): array {
		$all = $this->all();

		return $all[ $method ]['fields'] ?? array();
	}

	/**
	 * Validate and clean the details submitted for a method.
	 *
	 * @param string              $method Method key.
	 * @param array<string,mixed> $raw Raw details, keyed by field.
	 *
	 * @return array{valid:bool,details:array<string,string>,message:string}
	 */
	public function validate( string $method, array $raw ): array {
		if ( ! $this->is_enabled( $method ) ) {
			return array(
				'valid'   => false,
				'details' => array(),
				'message' => __( 'Choose how you would like to be paid.', 'directorist-affiliate' ),
			);
		}

		$details = array();

		foreach ( $this->fields( $method ) as $key => $field ) {
			$value = isset( $raw[ $key ] ) ? wp_unslash( $raw[ $key ] ) : '';
			$value = is_scalar( $value ) ? (string) $value : '';
			$value = $this->sanitize_value( $value, (string) $field['type'] );

			if ( ! empty( $field['required'] ) && '' === $value ) {
				return array(
					'valid'   => false,
					'details' => array(),
					'message' => sprintf(
						/* translators: %s: name of the missing field. */
						__( 'Please fill in "%s".', 'directorist-affiliate' ),
						$field['label']
					),
				);
			}

			if ( 'email' === $field['type'] && '' !== $value && ! is_email( $value ) ) {
				return array(
					'valid'   => false,
					'details' => array(),
					'message' => sprintf(
						/* translators: %s: name of the field holding an invalid email. */
						__( '"%s" does not look like a valid email address.', 'directorist-affiliate' ),
						$field['label']
					),
				);
			}

			if ( '' !== $value ) {
				$details[ $key ] = $value;
			}
		}

		return array(
			'valid'   => true,
			'details' => $details,
			'message' => '',
		);
	}

	/**
	 * Whether stored details satisfy a method's required fields.
	 *
	 * @param string               $method Method key.
	 * @param array<string,string> $details Stored details.
	 *
	 * @return bool
	 */
	public function is_complete( string $method, array $details ): bool {
		if ( ! $method || ! $this->is_enabled( $method ) ) {
			return false;
		}

		foreach ( $this->fields( $method ) as $key => $field ) {
			if ( ! empty( $field['required'] ) && empty( $details[ $key ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * One-line description of where a payment is going.
	 *
	 * @param string               $method Method key.
	 * @param array<string,string> $details Stored details.
	 *
	 * @return string
	 */
	public function summary( string $method, array $details ): string {
		if ( ! $method ) {
			return __( 'No payout method set', 'directorist-affiliate' );
		}

		$parts = array();

		foreach ( $this->fields( $method ) as $key => $field ) {
			if ( ! empty( $details[ $key ] ) ) {
				$parts[] = $details[ $key ];
			}
		}

		if ( ! $parts ) {
			return $this->label( $method );
		}

		return sprintf(
			/* translators: 1: payout method name, 2: the account details. */
			__( '%1$s — %2$s', 'directorist-affiliate' ),
			$this->label( $method ),
			implode( ', ', $parts )
		);
	}

	/**
	 * Decode details stored as JSON.
	 *
	 * @param mixed $stored Stored value.
	 *
	 * @return array<string,string>
	 */
	public function decode( $stored ): array {
		if ( is_array( $stored ) ) {
			return $stored;
		}

		$decoded = json_decode( (string) $stored, true );

		return is_array( $decoded ) ? array_map( 'strval', $decoded ) : array();
	}

	/**
	 * The contact email implied by a set of details, if any.
	 *
	 * Keeps the affiliate's `payout_email` column meaningful for methods that
	 * carry an email, so existing screens and exports still work.
	 *
	 * @param string               $method Method key.
	 * @param array<string,string> $details Details.
	 *
	 * @return string
	 */
	public function contact_email( string $method, array $details ): string {
		foreach ( $this->fields( $method ) as $key => $field ) {
			if ( 'email' === $field['type'] && ! empty( $details[ $key ] ) ) {
				return (string) $details[ $key ];
			}
		}

		return '';
	}

	/**
	 * Sanitize one field value by its declared type.
	 *
	 * @param string $value Raw value.
	 * @param string $type Field type.
	 *
	 * @return string
	 */
	private function sanitize_value( string $value, string $type ): string {
		switch ( $type ) {
			case 'email':
				return sanitize_email( $value );

			case 'tel':
				// Digits and the punctuation phone numbers legitimately use.
				return trim( preg_replace( '/[^0-9+()\-\s.]/', '', $value ) );

			default:
				return sanitize_text_field( $value );
		}
	}
}

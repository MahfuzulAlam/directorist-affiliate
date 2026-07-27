<?php
/**
 * Affiliate application processing.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Validates and stores affiliate applications.
 *
 * Shared by the front-end form (shortcode fallback + AJAX) and the
 * admin "Add Affiliate" form (admin_init fallback + AJAX).
 */
final class Directorist_Affiliate_Registration {
	/**
	 * Affiliate repository.
	 *
	 * @var Directorist_Affiliate_Affiliate
	 */
	private $affiliate;

	/**
	 * Email service.
	 *
	 * @var Directorist_Affiliate_Email
	 */
	private $email;

	/**
	 * Constructor.
	 *
	 * @param Directorist_Affiliate_Affiliate $affiliate Affiliate repository.
	 * @param Directorist_Affiliate_Email     $email Email service.
	 */
	public function __construct( Directorist_Affiliate_Affiliate $affiliate, Directorist_Affiliate_Email $email ) {
		$this->affiliate = $affiliate;
		$this->email     = $email;
	}

	/**
	 * Process a public application submission.
	 *
	 * The caller is responsible for nonce verification.
	 *
	 * @param array<string,mixed> $request Raw request data (e.g. $_POST).
	 *
	 * @return array{success:bool,message:string}
	 */
	public function process_public( array $request ): array {
		$submitted_message = __( 'Your affiliate application was submitted and is pending review.', 'directorist-affiliate' );

		// Honeypot: bots fill the hidden field; pretend success without saving.
		if ( ! empty( $request['da_hp'] ) ) {
			return array(
				'success' => true,
				'message' => $submitted_message,
			);
		}

		$fields = $this->sanitize_fields( $request );

		if ( ! $fields['name'] || ! is_email( $fields['email'] ) || ! is_email( $fields['payout_email'] ) || ( ! $fields['website'] && ! $fields['promotional_method'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Please complete all required fields with valid values.', 'directorist-affiliate' ),
			);
		}

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			if ( get_user_by( 'email', $fields['email'] ) ) {
				return array(
					'success' => false,
					'message' => __( 'An account already exists with this email address. Please log in before applying.', 'directorist-affiliate' ),
				);
			}

			$user_id = $this->affiliate->register_user( $fields['name'], $fields['email'] );

			if ( is_wp_error( $user_id ) ) {
				return array(
					'success' => false,
					'message' => $user_id->get_error_message(),
				);
			}
		}

		if ( $this->affiliate->get_by_user_id( $user_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'You already have an affiliate application.', 'directorist-affiliate' ),
			);
		}

		$affiliate_id = $this->affiliate->create(
			array(
				'user_id'            => $user_id,
				'payout_email'       => $fields['payout_email'],
				'website'            => $fields['website'],
				'promotional_method' => $fields['promotional_method'],
				'application_note'   => $fields['application_note'],
			)
		);

		if ( ! $affiliate_id ) {
			return array(
				'success' => false,
				'message' => __( 'Unable to submit your application. Please try again.', 'directorist-affiliate' ),
			);
		}

		$affiliate = $this->affiliate->get( $affiliate_id );

		if ( $affiliate ) {
			$this->email->new_application( $affiliate );
		}

		return array(
			'success' => true,
			'message' => $submitted_message,
		);
	}

	/**
	 * Process an admin "Add Affiliate" submission.
	 *
	 * The caller is responsible for nonce and capability checks.
	 *
	 * @param array<string,mixed> $request Raw request data (e.g. $_POST).
	 *
	 * @return array{success:bool,notice:string}
	 */
	public function process_admin( array $request ): array {
		$fields = $this->sanitize_fields( $request );
		$status = isset( $request['status'] ) ? sanitize_key( wp_unslash( $request['status'] ) ) : 'approved';

		if ( ! $fields['name'] || ! is_email( $fields['email'] ) || ! is_email( $fields['payout_email'] ) || ! in_array( $status, $this->affiliate->statuses(), true ) ) {
			return array(
				'success' => false,
				'notice'  => 'invalid_affiliate',
			);
		}

		$user = get_user_by( 'email', $fields['email'] );

		if ( $user ) {
			$user_id = (int) $user->ID;
		} else {
			$user_id = $this->affiliate->register_user( $fields['name'], $fields['email'] );

			if ( is_wp_error( $user_id ) ) {
				return array(
					'success' => false,
					'notice'  => 'user_create_failed',
				);
			}
		}

		if ( $this->affiliate->get_by_user_id( $user_id ) ) {
			return array(
				'success' => false,
				'notice'  => 'affiliate_exists',
			);
		}

		$affiliate_id = $this->affiliate->create(
			array(
				'user_id'            => $user_id,
				'status'             => $status,
				'payout_email'       => $fields['payout_email'],
				'website'            => $fields['website'],
				'promotional_method' => $fields['promotional_method'],
				'application_note'   => $fields['application_note'],
			)
		);

		if ( ! $affiliate_id ) {
			return array(
				'success' => false,
				'notice'  => 'affiliate_create_failed',
			);
		}

		$affiliate = $this->affiliate->get( $affiliate_id );

		if ( $affiliate && in_array( $status, array( 'approved', 'rejected' ), true ) ) {
			$this->email->application_status( $affiliate, $status );
		}

		return array(
			'success' => true,
			'notice'  => 'affiliate_created',
		);
	}

	/**
	 * Human-readable message for an admin notice key.
	 *
	 * @param string $notice Notice key.
	 *
	 * @return string Empty string for unknown keys.
	 */
	public function admin_message( string $notice ): string {
		$messages = array(
			'affiliate_created'       => __( 'Affiliate created successfully.', 'directorist-affiliate' ),
			'affiliate_updated'       => __( 'Affiliate status updated.', 'directorist-affiliate' ),
			'invalid_affiliate'       => __( 'Please provide a valid name, email, payout email, and status.', 'directorist-affiliate' ),
			'user_create_failed'      => __( 'Could not create the WordPress user for this affiliate.', 'directorist-affiliate' ),
			'affiliate_exists'        => __( 'This WordPress user is already registered as an affiliate.', 'directorist-affiliate' ),
			'affiliate_create_failed' => __( 'Could not create the affiliate record.', 'directorist-affiliate' ),
		);

		return $messages[ $notice ] ?? '';
	}

	/**
	 * Sanitize shared application fields.
	 *
	 * @param array<string,mixed> $request Raw request data.
	 *
	 * @return array{name:string,email:string,website:string,promotional_method:string,payout_email:string,application_note:string}
	 */
	private function sanitize_fields( array $request ): array {
		return array(
			'name'               => isset( $request['name'] ) ? sanitize_text_field( wp_unslash( $request['name'] ) ) : '',
			'email'              => isset( $request['email'] ) ? sanitize_email( wp_unslash( $request['email'] ) ) : '',
			'website'            => isset( $request['website'] ) ? esc_url_raw( wp_unslash( $request['website'] ) ) : '',
			'promotional_method' => isset( $request['promotional_method'] ) ? sanitize_text_field( wp_unslash( $request['promotional_method'] ) ) : '',
			'payout_email'       => isset( $request['payout_email'] ) ? sanitize_email( wp_unslash( $request['payout_email'] ) ) : '',
			'application_note'   => isset( $request['application_note'] ) ? sanitize_textarea_field( wp_unslash( $request['application_note'] ) ) : '',
		);
	}
}

<?php
/**
 * Settings service.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads and sanitizes plugin settings.
 */
final class Directorist_Affiliate_Settings {
	/**
	 * Option name.
	 */
	private const OPTION = 'directorist_affiliate_settings';

	/**
	 * Get default settings.
	 *
	 * @return array<string,mixed>
	 */
	public function defaults(): array {
		return array(
			'enabled'                    => 1,
			'ref_param'                  => 'ref',
			'cookie_duration'            => 30,
			'attribution_model'          => 'first_click',
			'enable_applications'        => 1,
			'applications_require_login' => 0,
			'registration_page'          => 0,
			'dashboard_page'             => 0,
			'enable_registration'        => 1,
			'registration_amount'        => '0.00',
			'registration_credit_on'     => 'registration',
			'registration_user_types'    => array( 'author', 'general' ),
			'enable_listing'             => 1,
			'listing_amount'             => '0.00',
			'listing_trigger'            => 'submission',
			'listing_directory_types'    => array(),
			'enable_plan_commission'     => 1,
			'plan_commission_type'       => 'percentage',
			'plan_commission_value'      => '0.00',
			'enable_featured_commission' => 1,
			'featured_commission_type'   => 'percentage',
			'featured_commission_value'  => '0.00',
			'auto_approve_commissions'   => 0,
			'minimum_payout'             => '0.00',
			'payout_methods'             => array( 'paypal', 'bank', 'cash' ),
			'payout_instructions'        => '',
			'notify_admin_application'   => 1,
			'notify_affiliate_status'    => 1,
			'notify_affiliate_referral'  => 1,
			'notify_admin_payout_request' => 1,
			'notify_affiliate_payout'    => 1,
			'email_templates'            => array(),
			'anonymize_ip'               => 0,
			'delete_data_on_uninstall'   => 0,
		);
	}

	/**
	 * Get all settings.
	 *
	 * @return array<string,mixed>
	 */
	public function all(): array {
		$options = get_option( self::OPTION, array() );

		return wp_parse_args( is_array( $options ) ? $options : array(), $this->defaults() );
	}

	/**
	 * Get one setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 *
	 * @return mixed
	 */
	public function get( string $key, $default = null ) {
		$options = $this->all();

		return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}

	/**
	 * Whether affiliate system is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) absint( $this->get( 'enabled', 1 ) );
	}

	/**
	 * Save sanitized settings.
	 *
	 * @param array<string,mixed> $raw Raw settings.
	 *
	 * @return void
	 */
	public function save( array $raw ): void {
		update_option( self::OPTION, $this->sanitize( $raw ), false );
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string,mixed> $raw Raw settings.
	 *
	 * @return array<string,mixed>
	 */
	public function sanitize( array $raw ): array {
		$defaults = $this->defaults();
		$trigger  = isset( $raw['listing_trigger'] ) ? sanitize_key( wp_unslash( $raw['listing_trigger'] ) ) : $defaults['listing_trigger'];

		if ( ! in_array( $trigger, array( 'submission', 'publish' ), true ) ) {
			$trigger = 'submission';
		}

		$ref_param = isset( $raw['ref_param'] ) ? sanitize_key( wp_unslash( $raw['ref_param'] ) ) : $defaults['ref_param'];
		$ref_param = $ref_param ? $ref_param : 'ref';

		$credit_on = isset( $raw['registration_credit_on'] ) ? sanitize_key( wp_unslash( $raw['registration_credit_on'] ) ) : $defaults['registration_credit_on'];

		if ( ! in_array( $credit_on, array( 'registration', 'verification' ), true ) ) {
			$credit_on = 'registration';
		}

		$attribution = isset( $raw['attribution_model'] ) ? sanitize_key( wp_unslash( $raw['attribution_model'] ) ) : $defaults['attribution_model'];

		if ( ! in_array( $attribution, array( 'first_click', 'last_click' ), true ) ) {
			$attribution = 'first_click';
		}

		return array(
			'enabled'                    => empty( $raw['enabled'] ) ? 0 : 1,
			'ref_param'                  => $ref_param,
			'cookie_duration'            => min( 3650, max( 1, absint( $raw['cookie_duration'] ?? $defaults['cookie_duration'] ) ) ),
			'attribution_model'          => $attribution,
			'enable_applications'        => empty( $raw['enable_applications'] ) ? 0 : 1,
			'applications_require_login' => empty( $raw['applications_require_login'] ) ? 0 : 1,
			'registration_page'          => absint( $raw['registration_page'] ?? 0 ),
			'dashboard_page'             => absint( $raw['dashboard_page'] ?? 0 ),
			'enable_registration'        => empty( $raw['enable_registration'] ) ? 0 : 1,
			'registration_amount'        => $this->sanitize_amount( $raw['registration_amount'] ?? $defaults['registration_amount'] ),
			'registration_credit_on'     => $credit_on,
			'registration_user_types'    => $this->sanitize_user_types( $raw['registration_user_types'] ?? $defaults['registration_user_types'] ),
			'enable_listing'             => empty( $raw['enable_listing'] ) ? 0 : 1,
			'listing_amount'             => $this->sanitize_amount( $raw['listing_amount'] ?? $defaults['listing_amount'] ),
			'listing_trigger'            => $trigger,
			'listing_directory_types'    => $this->sanitize_directory_types( $raw['listing_directory_types'] ?? array() ),
			'enable_plan_commission'     => empty( $raw['enable_plan_commission'] ) ? 0 : 1,
			'plan_commission_type'       => $this->sanitize_commission_type( $raw['plan_commission_type'] ?? $defaults['plan_commission_type'] ),
			'plan_commission_value'      => $this->sanitize_commission_value(
				$raw['plan_commission_value'] ?? $defaults['plan_commission_value'],
				$this->sanitize_commission_type( $raw['plan_commission_type'] ?? $defaults['plan_commission_type'] )
			),
			'enable_featured_commission' => empty( $raw['enable_featured_commission'] ) ? 0 : 1,
			'featured_commission_type'   => $this->sanitize_commission_type( $raw['featured_commission_type'] ?? $defaults['featured_commission_type'] ),
			'featured_commission_value'  => $this->sanitize_commission_value(
				$raw['featured_commission_value'] ?? $defaults['featured_commission_value'],
				$this->sanitize_commission_type( $raw['featured_commission_type'] ?? $defaults['featured_commission_type'] )
			),
			'auto_approve_commissions'   => empty( $raw['auto_approve_commissions'] ) ? 0 : 1,
			'minimum_payout'             => $this->sanitize_amount( $raw['minimum_payout'] ?? $defaults['minimum_payout'] ),
			'payout_methods'             => $this->sanitize_payout_methods( $raw['payout_methods'] ?? $defaults['payout_methods'] ),
			'payout_instructions'        => isset( $raw['payout_instructions'] ) ? sanitize_textarea_field( wp_unslash( $raw['payout_instructions'] ) ) : '',
			'notify_admin_application'   => empty( $raw['notify_admin_application'] ) ? 0 : 1,
			'notify_affiliate_status'    => empty( $raw['notify_affiliate_status'] ) ? 0 : 1,
			'notify_affiliate_referral'  => empty( $raw['notify_affiliate_referral'] ) ? 0 : 1,
			'notify_admin_payout_request' => empty( $raw['notify_admin_payout_request'] ) ? 0 : 1,
			'notify_affiliate_payout'    => empty( $raw['notify_affiliate_payout'] ) ? 0 : 1,
			'email_templates'            => $this->sanitize_email_templates( $raw['email_templates'] ?? array() ),
			'anonymize_ip'               => empty( $raw['anonymize_ip'] ) ? 0 : 1,
			'delete_data_on_uninstall'   => empty( $raw['delete_data_on_uninstall'] ) ? 0 : 1,
		);
	}

	/**
	 * Sanitize submitted email template overrides.
	 *
	 * @param mixed $raw Raw value.
	 *
	 * @return array<string,array{subject:string,body:string}>
	 */
	private function sanitize_email_templates( $raw ): array {
		if ( ! class_exists( 'Directorist_Affiliate_Email_Templates' ) ) {
			return array();
		}

		$templates = new Directorist_Affiliate_Email_Templates( $this );

		return $templates->sanitize( $raw );
	}

	/**
	 * Sanitize the directory types that earn a listing commission.
	 *
	 * Selecting every existing type is stored as "all" rather than as a list
	 * of IDs, so a directory type added later is included automatically
	 * instead of silently earning nothing.
	 *
	 * @param mixed $types Raw value.
	 *
	 * @return int[]
	 */
	private function sanitize_directory_types( $types ): array {
		$clean = array_values( array_unique( array_filter( array_map( 'absint', (array) $types ) ) ) );

		if ( ! $clean || ! class_exists( 'Directorist_Affiliate_Directorist_Integration' ) ) {
			return $clean;
		}

		$known = array_keys( Directorist_Affiliate_Directorist_Integration::directory_types() );

		return $known && ! array_diff( $known, $clean ) ? array() : $clean;
	}

	/**
	 * Sanitize which Directorist user types earn a registration commission.
	 *
	 * @param mixed $types Raw value.
	 *
	 * @return string[]
	 */
	private function sanitize_user_types( $types ): array {
		$known = array( 'author', 'general' );
		$clean = array_values( array_intersect( $known, array_map( 'sanitize_key', (array) $types ) ) );

		// Never leave zero types selected: that would silently stop all
		// registration commissions with no visible cause.
		return $clean ? $clean : $known;
	}

	/**
	 * Sanitize the list of offered payout methods.
	 *
	 * @param mixed $methods Raw value.
	 *
	 * @return string[]
	 */
	private function sanitize_payout_methods( $methods ): array {
		$known = array( 'paypal', 'bank', 'cash' );
		$clean = array_values( array_intersect( $known, array_map( 'sanitize_key', (array) $methods ) ) );

		// Never leave zero methods enabled: that would silently block payouts.
		return $clean ? $clean : $known;
	}

	/**
	 * Sanitize a commission type.
	 *
	 * @param mixed $type Raw type.
	 *
	 * @return string
	 */
	private function sanitize_commission_type( $type ): string {
		$type = sanitize_key( is_string( $type ) ? wp_unslash( $type ) : '' );

		return in_array( $type, array( 'percentage', 'fixed' ), true ) ? $type : 'percentage';
	}

	/**
	 * Sanitize a commission value; percentages are capped at 100.
	 *
	 * @param mixed  $value Raw value.
	 * @param string $type Commission type.
	 *
	 * @return string
	 */
	private function sanitize_commission_value( $value, string $type ): string {
		$value = (float) $this->sanitize_amount( $value );

		if ( 'percentage' === $type ) {
			$value = min( 100, $value );
		}

		return number_format( $value, 2, '.', '' );
	}

	/**
	 * Sanitize a decimal amount.
	 *
	 * @param mixed $amount Raw amount.
	 *
	 * @return string
	 */
	public function sanitize_amount( $amount ): string {
		$amount = is_string( $amount ) ? wp_unslash( $amount ) : $amount;
		$amount = preg_replace( '/[^0-9.]/', '', (string) $amount );

		return number_format( max( 0, (float) $amount ), 2, '.', '' );
	}
}

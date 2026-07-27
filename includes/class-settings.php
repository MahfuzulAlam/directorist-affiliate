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
			'enable_registration'        => 1,
			'registration_amount'        => '0.00',
			'enable_listing'             => 1,
			'listing_amount'             => '0.00',
			'listing_trigger'            => 'submission',
			'enable_plan_commission'     => 1,
			'plan_commission_type'       => 'percentage',
			'plan_commission_value'      => '0.00',
			'enable_featured_commission' => 1,
			'featured_commission_type'   => 'percentage',
			'featured_commission_value'  => '0.00',
			'auto_approve_commissions'   => 0,
			'minimum_payout'             => '0.00',
			'payout_instructions'        => '',
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

		$attribution = isset( $raw['attribution_model'] ) ? sanitize_key( wp_unslash( $raw['attribution_model'] ) ) : $defaults['attribution_model'];

		if ( ! in_array( $attribution, array( 'first_click', 'last_click' ), true ) ) {
			$attribution = 'first_click';
		}

		return array(
			'enabled'                    => empty( $raw['enabled'] ) ? 0 : 1,
			'ref_param'                  => $ref_param,
			'cookie_duration'            => max( 1, absint( $raw['cookie_duration'] ?? $defaults['cookie_duration'] ) ),
			'attribution_model'          => $attribution,
			'enable_registration'        => empty( $raw['enable_registration'] ) ? 0 : 1,
			'registration_amount'        => $this->sanitize_amount( $raw['registration_amount'] ?? $defaults['registration_amount'] ),
			'enable_listing'             => empty( $raw['enable_listing'] ) ? 0 : 1,
			'listing_amount'             => $this->sanitize_amount( $raw['listing_amount'] ?? $defaults['listing_amount'] ),
			'listing_trigger'            => $trigger,
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
			'payout_instructions'        => isset( $raw['payout_instructions'] ) ? sanitize_textarea_field( wp_unslash( $raw['payout_instructions'] ) ) : '',
			'anonymize_ip'               => empty( $raw['anonymize_ip'] ) ? 0 : 1,
			'delete_data_on_uninstall'   => empty( $raw['delete_data_on_uninstall'] ) ? 0 : 1,
		);
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

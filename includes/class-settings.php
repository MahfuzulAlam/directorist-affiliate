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
			'enabled'             => 1,
			'ref_param'           => 'ref',
			'cookie_duration'     => 30,
			'enable_registration' => 1,
			'registration_amount' => '0.00',
			'enable_listing'      => 1,
			'listing_amount'      => '0.00',
			'listing_trigger'     => 'submission',
			'minimum_payout'      => '0.00',
			'payout_instructions' => '',
			'anonymize_ip'        => 0,
			'delete_data_on_uninstall' => 0,
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

		return array(
			'enabled'             => empty( $raw['enabled'] ) ? 0 : 1,
			'ref_param'           => $ref_param,
			'cookie_duration'     => max( 1, absint( $raw['cookie_duration'] ?? $defaults['cookie_duration'] ) ),
			'enable_registration' => empty( $raw['enable_registration'] ) ? 0 : 1,
			'registration_amount' => $this->sanitize_amount( $raw['registration_amount'] ?? $defaults['registration_amount'] ),
			'enable_listing'      => empty( $raw['enable_listing'] ) ? 0 : 1,
			'listing_amount'      => $this->sanitize_amount( $raw['listing_amount'] ?? $defaults['listing_amount'] ),
			'listing_trigger'     => $trigger,
			'minimum_payout'      => $this->sanitize_amount( $raw['minimum_payout'] ?? $defaults['minimum_payout'] ),
			'payout_instructions' => isset( $raw['payout_instructions'] ) ? sanitize_textarea_field( wp_unslash( $raw['payout_instructions'] ) ) : '',
			'anonymize_ip'        => empty( $raw['anonymize_ip'] ) ? 0 : 1,
			'delete_data_on_uninstall' => empty( $raw['delete_data_on_uninstall'] ) ? 0 : 1,
		);
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

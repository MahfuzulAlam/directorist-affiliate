<?php
/**
 * Commission service.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Determines fixed commission values.
 */
final class Directorist_Affiliate_Commission {
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
	 * Get registration commission amount.
	 *
	 * @return float|null
	 */
	public function registration_amount(): ?float {
		if ( ! $this->settings->is_enabled() || ! absint( $this->settings->get( 'enable_registration', 1 ) ) ) {
			return null;
		}

		$amount = (float) $this->settings->get( 'registration_amount', '0.00' );

		/**
		 * Filters the fixed commission for a referred user registration.
		 *
		 * @param float $amount Commission amount.
		 */
		return (float) apply_filters( 'directorist_affiliate_registration_commission', $amount );
	}

	/**
	 * Get listing commission amount for a trigger.
	 *
	 * @param string $trigger Trigger.
	 *
	 * @return float|null
	 */
	public function listing_amount( string $trigger ): ?float {
		if ( ! $this->settings->is_enabled() || ! absint( $this->settings->get( 'enable_listing', 1 ) ) ) {
			return null;
		}

		if ( $trigger !== $this->settings->get( 'listing_trigger', 'submission' ) ) {
			return null;
		}

		$amount = (float) $this->settings->get( 'listing_amount', '0.00' );

		/**
		 * Filters the fixed commission for a referred listing.
		 *
		 * @param float  $amount Commission amount.
		 * @param string $trigger Trigger that fired (submission|publish).
		 */
		return (float) apply_filters( 'directorist_affiliate_listing_commission', $amount, $trigger );
	}
}

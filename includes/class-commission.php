<?php
/**
 * Commission service.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Determines commission values for every referral event.
 *
 * Order-based events (plan/featured purchases) support fixed or percentage
 * commissions and are automatically disabled when the extension or
 * monetization feature they depend on is not active.
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
	 * Format a money amount with the site's Directorist currency.
	 *
	 * Falls back to a plain localized number when Directorist's price helper
	 * is unavailable. Returns plain text — escape at the point of output.
	 *
	 * @param float $amount Amount.
	 *
	 * @return string
	 */
	public static function format_money( float $amount ): string {
		if ( function_exists( 'directorist_price' ) ) {
			return (string) directorist_price( $amount, false );
		}

		return number_format_i18n( $amount, 2 );
	}

	/**
	 * The program's live terms, for display to prospective affiliates.
	 *
	 * Only events that are enabled (and whose dependency is active) appear,
	 * so the signup page always states what is actually payable today.
	 *
	 * @return array<int,array{label:string,value:string}>
	 */
	public function program_terms(): array {
		$terms = array();

		if ( absint( $this->settings->get( 'enable_plan_commission', 1 ) ) && self::is_pricing_plans_active() ) {
			$terms[] = array(
				'label' => __( 'Plan purchases', 'directorist-affiliate' ),
				'value' => $this->rate_label( 'plan_commission_type', 'plan_commission_value' ),
			);
		}

		if ( absint( $this->settings->get( 'enable_featured_commission', 1 ) ) && self::is_featured_monetization_active() ) {
			$terms[] = array(
				'label' => __( 'Featured listings', 'directorist-affiliate' ),
				'value' => $this->rate_label( 'featured_commission_type', 'featured_commission_value' ),
			);
		}

		$registration = (float) $this->settings->get( 'registration_amount', '0.00' );

		if ( absint( $this->settings->get( 'enable_registration', 1 ) ) && $registration > 0 ) {
			$terms[] = array(
				'label' => __( 'New sign-ups', 'directorist-affiliate' ),
				'value' => self::format_money( $registration ),
			);
		}

		$listing = (float) $this->settings->get( 'listing_amount', '0.00' );

		if ( absint( $this->settings->get( 'enable_listing', 1 ) ) && $listing > 0 ) {
			$terms[] = array(
				'label' => __( 'New listings', 'directorist-affiliate' ),
				'value' => self::format_money( $listing ),
			);
		}

		/**
		 * Filters the program terms shown to prospective affiliates.
		 *
		 * @param array<int,array{label:string,value:string}> $terms Terms.
		 */
		return (array) apply_filters( 'directorist_affiliate_program_terms', $terms );
	}

	/**
	 * Render one event's rate as a display string.
	 *
	 * @param string $type_key Settings key holding the commission type.
	 * @param string $value_key Settings key holding the commission value.
	 *
	 * @return string
	 */
	private function rate_label( string $type_key, string $value_key ): string {
		$value = (float) $this->settings->get( $value_key, '0.00' );

		if ( 'percentage' === $this->settings->get( $type_key, 'percentage' ) ) {
			/* translators: %s: commission percentage. */
			return sprintf( __( '%s%%', 'directorist-affiliate' ), number_format_i18n( $value, ( floor( $value ) === $value ) ? 0 : 2 ) );
		}

		return self::format_money( $value );
	}

	/**
	 * Whether a pricing plans extension is active.
	 *
	 * Supports Directorist Pricing Plans v4+ and the legacy fee manager.
	 *
	 * @return bool
	 */
	public static function is_pricing_plans_active(): bool {
		return class_exists( 'DirectoristPricingPlan' ) || class_exists( 'ATBDP_Pricing_Plans' ) || defined( 'DIRECTORIST_PRICING_PLANS_FILE' );
	}

	/**
	 * Whether Directorist featured-listing monetization is active.
	 *
	 * @return bool
	 */
	public static function is_featured_monetization_active(): bool {
		if ( ! function_exists( 'directorist_is_monetization_enabled' ) || ! function_exists( 'directorist_is_featured_listing_enabled' ) ) {
			return false;
		}

		return directorist_is_monetization_enabled() && directorist_is_featured_listing_enabled();
	}

	/**
	 * Initial status for new referrals, honoring the auto-approve setting.
	 *
	 * @return string
	 */
	public function default_referral_status(): string {
		return absint( $this->settings->get( 'auto_approve_commissions', 0 ) ) ? 'approved' : 'pending';
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

	/**
	 * Commission for a referred pricing-plan purchase.
	 *
	 * Returns null (event disabled) when the affiliate system is off, the
	 * event is disabled, or no pricing plans extension is active.
	 *
	 * @param float $order_total Paid order total.
	 *
	 * @return float|null
	 */
	public function plan_amount( float $order_total ): ?float {
		if ( ! $this->settings->is_enabled() || ! absint( $this->settings->get( 'enable_plan_commission', 1 ) ) ) {
			return null;
		}

		if ( ! self::is_pricing_plans_active() ) {
			return null;
		}

		$amount = $this->compute(
			(string) $this->settings->get( 'plan_commission_type', 'percentage' ),
			(float) $this->settings->get( 'plan_commission_value', '0.00' ),
			$order_total
		);

		/**
		 * Filters the commission for a referred pricing-plan purchase.
		 *
		 * @param float $amount Commission amount.
		 * @param float $order_total Paid order total.
		 */
		return (float) apply_filters( 'directorist_affiliate_plan_commission', $amount, $order_total );
	}

	/**
	 * Commission for a referred featured-listing purchase.
	 *
	 * Returns null (event disabled) when the affiliate system is off, the
	 * event is disabled, or featured-listing monetization is not active.
	 *
	 * @param float $order_total Paid order total.
	 *
	 * @return float|null
	 */
	public function featured_amount( float $order_total ): ?float {
		if ( ! $this->settings->is_enabled() || ! absint( $this->settings->get( 'enable_featured_commission', 1 ) ) ) {
			return null;
		}

		if ( ! self::is_featured_monetization_active() ) {
			return null;
		}

		$amount = $this->compute(
			(string) $this->settings->get( 'featured_commission_type', 'percentage' ),
			(float) $this->settings->get( 'featured_commission_value', '0.00' ),
			$order_total
		);

		/**
		 * Filters the commission for a referred featured-listing purchase.
		 *
		 * @param float $amount Commission amount.
		 * @param float $order_total Paid order total.
		 */
		return (float) apply_filters( 'directorist_affiliate_featured_commission', $amount, $order_total );
	}

	/**
	 * Compute a fixed or percentage commission.
	 *
	 * @param string $type 'percentage' or 'fixed'.
	 * @param float  $value Rate (percent) or flat amount.
	 * @param float  $order_total Order total.
	 *
	 * @return float
	 */
	private function compute( string $type, float $value, float $order_total ): float {
		if ( 'percentage' === $type ) {
			return round( max( 0, $order_total ) * ( max( 0, $value ) / 100 ), 6 );
		}

		return max( 0, $value );
	}
}

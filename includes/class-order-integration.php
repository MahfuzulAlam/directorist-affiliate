<?php
/**
 * Order integration.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates commissions from paid Directorist orders and reverses them on
 * refund/cancellation.
 *
 * Two order systems are supported:
 * - "directorist": the order repository introduced in Directorist 8.8+
 *   (used by Pricing Plans v4 plan purchases and the featured-listing
 *   checkout), via `directorist_after_order_create/update`.
 * - "legacy": classic `atbdp_orders` posts, via `atbdp_order_completed`
 *   and `atbdp_order_status_changed`.
 */
final class Directorist_Affiliate_Order_Integration {
	private const SOURCE_DIRECTORIST = 'directorist';
	private const SOURCE_LEGACY      = 'legacy';

	/**
	 * Order statuses that reverse a commission.
	 */
	private const REVERSAL_STATUSES = array( 'refunded', 'cancelled', 'failed', 'expired' );

	/**
	 * Plugin service container.
	 *
	 * @var Directorist_Affiliate_Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Directorist_Affiliate_Plugin $plugin Plugin instance.
	 */
	public function __construct( Directorist_Affiliate_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Directorist 8.8+ order repository.
		add_action( 'directorist_after_order_create', array( $this, 'handle_order_created' ), 20 );
		add_action( 'directorist_after_order_update', array( $this, 'handle_order_updated' ), 20 );

		// Legacy atbdp_orders checkout.
		add_action( 'atbdp_order_completed', array( $this, 'handle_legacy_order_completed' ), 20, 2 );
		add_action( 'atbdp_order_status_changed', array( $this, 'handle_legacy_status_changed' ), 20, 3 );
	}

	/**
	 * Handle order creation (covers orders created directly as paid).
	 *
	 * @param object $dto Order DTO.
	 *
	 * @return void
	 */
	public function handle_order_created( $dto ): void {
		if ( 'paid' === $this->dto_status( $dto ) ) {
			$this->record_from_dto( $dto );
		}
	}

	/**
	 * Handle order updates: record on paid, reverse on refund/cancel.
	 *
	 * @param object $dto Order DTO.
	 *
	 * @return void
	 */
	public function handle_order_updated( $dto ): void {
		$status = $this->dto_status( $dto );

		if ( 'paid' === $status ) {
			$this->record_from_dto( $dto );
			return;
		}

		if ( in_array( $status, self::REVERSAL_STATUSES, true ) ) {
			$order_id = $this->dto_value( $dto, 'id' );

			if ( $order_id ) {
				$this->reverse( (int) $order_id, self::SOURCE_DIRECTORIST, $status );
			}
		}
	}

	/**
	 * Legacy checkout completed an order.
	 *
	 * @param int $order_id Order ID.
	 * @param int $listing_id Listing ID.
	 *
	 * @return void
	 */
	public function handle_legacy_order_completed( $order_id, $listing_id = 0 ): void {
		$this->record_from_legacy_order( absint( $order_id ), absint( $listing_id ) );
	}

	/**
	 * Legacy admin changed an order's payment status.
	 *
	 * @param string $new_status New status.
	 * @param string $old_status Old status.
	 * @param int    $order_id Order ID.
	 *
	 * @return void
	 */
	public function handle_legacy_status_changed( $new_status, $old_status, $order_id ): void {
		$new_status = sanitize_key( (string) $new_status );
		$order_id   = absint( $order_id );

		if ( ! $order_id ) {
			return;
		}

		if ( 'completed' === $new_status ) {
			$listing_id = absint( get_post_meta( $order_id, '_listing_id', true ) );
			$this->record_from_legacy_order( $order_id, $listing_id );
			return;
		}

		if ( in_array( $new_status, self::REVERSAL_STATUSES, true ) ) {
			$this->reverse( $order_id, self::SOURCE_LEGACY, $new_status );
		}
	}

	/**
	 * Record a commission from a new-system order DTO.
	 *
	 * @param object $dto Order DTO.
	 *
	 * @return void
	 */
	private function record_from_dto( $dto ): void {
		$order_id = absint( (int) $this->dto_value( $dto, 'id' ) );
		$buyer_id = absint( (int) $this->dto_value( $dto, 'user_id' ) );

		if ( ! $order_id || ! $buyer_id ) {
			return;
		}

		$ref_type    = (string) $this->dto_value( $dto, 'ref_type' );
		$is_featured = (bool) $this->dto_value( $dto, 'is_featured_listing' );

		if ( 'pricing_plan' === $ref_type ) {
			$event = 'plan_purchase';
		} elseif ( 'featured_listing' === $ref_type || $is_featured ) {
			$event = 'featured_purchase';
		} else {
			return;
		}

		$total = $this->dto_total( $dto );

		$this->record_commission(
			array(
				'event'        => $event,
				'order_id'     => $order_id,
				'order_source' => self::SOURCE_DIRECTORIST,
				'order_total'  => $total,
				'buyer_id'     => $buyer_id,
				'listing_id'   => absint( (int) $this->dto_value( $dto, 'listing_id' ) ),
			)
		);
	}

	/**
	 * Record a commission from a legacy atbdp_orders post.
	 *
	 * @param int $order_id Order ID.
	 * @param int $listing_id Listing ID.
	 *
	 * @return void
	 */
	private function record_from_legacy_order( int $order_id, int $listing_id ): void {
		if ( ! $order_id ) {
			return;
		}

		$order = get_post( $order_id );

		if ( ! $order || ( defined( 'ATBDP_ORDER_POST_TYPE' ) && ATBDP_ORDER_POST_TYPE !== $order->post_type ) ) {
			return;
		}

		if ( get_post_meta( $order_id, '_fm_plans', true ) ) {
			$event = 'plan_purchase';
		} elseif ( get_post_meta( $order_id, '_featured', true ) ) {
			$event = 'featured_purchase';
		} else {
			return;
		}

		$this->record_commission(
			array(
				'event'        => $event,
				'order_id'     => $order_id,
				'order_source' => self::SOURCE_LEGACY,
				'order_total'  => (float) get_post_meta( $order_id, '_amount', true ),
				'buyer_id'     => absint( $order->post_author ),
				'listing_id'   => $listing_id ? $listing_id : absint( get_post_meta( $order_id, '_listing_id', true ) ),
			)
		);
	}

	/**
	 * Create the referral record for a paid order.
	 *
	 * @param array<string,mixed> $context Order context.
	 *
	 * @return void
	 */
	private function record_commission( array $context ): void {
		// Revenue events only: free orders never earn a commission.
		if ( $context['order_total'] <= 0 ) {
			return;
		}

		// Cheap duplicate check before resolving anything else.
		if ( $this->plugin->referral->get_by_order( (int) $context['order_id'], $context['order_source'] ) ) {
			return;
		}

		$amount = 'plan_purchase' === $context['event']
			? $this->plugin->commission->plan_amount( (float) $context['order_total'] )
			: $this->plugin->commission->featured_amount( (float) $context['order_total'] );

		// Null means the event (or its required extension) is disabled.
		if ( null === $amount ) {
			return;
		}

		$affiliate = $this->resolve_affiliate( (int) $context['buyer_id'] );

		if ( ! $affiliate ) {
			return;
		}

		$referral_id = $this->plugin->referral->create(
			array(
				'affiliate_id'      => (int) $affiliate->id,
				'referral_type'     => $context['event'],
				'referred_user_id'  => (int) $context['buyer_id'],
				'listing_id'        => (int) $context['listing_id'],
				'order_id'          => (int) $context['order_id'],
				'order_source'      => $context['order_source'],
				'order_total'       => (float) $context['order_total'],
				'commission_amount' => $amount,
				'status'            => $this->plugin->commission->default_referral_status(),
				'notes'             => sprintf(
					/* translators: 1: order source, 2: order ID. */
					__( 'Created from paid %1$s order #%2$d.', 'directorist-affiliate' ),
					$context['order_source'],
					(int) $context['order_id']
				),
			)
		);

		if ( ! $referral_id ) {
			return;
		}

		$visit_id = absint( get_user_meta( (int) $context['buyer_id'], '_directorist_affiliate_visit_id', true ) );
		$visit_id = $visit_id ? $visit_id : $this->plugin->tracking->get_cookie_visit_id();

		$this->plugin->tracking->mark_converted( $visit_id, (int) $context['buyer_id'], (int) $context['listing_id'] );

		$referral = $this->plugin->referral->get( $referral_id );

		if ( $referral ) {
			$this->plugin->email->referral_created( $affiliate, $referral );
		}
	}

	/**
	 * Reverse the commission of a refunded/cancelled order.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $order_source Order source.
	 * @param string $order_status New order status.
	 *
	 * @return void
	 */
	private function reverse( int $order_id, string $order_source, string $order_status ): void {
		$referral = $this->plugin->referral->get_by_order( $order_id, $order_source );

		if ( ! $referral || in_array( $referral->status, array( 'cancelled', 'refunded' ), true ) ) {
			return;
		}

		$new_status = 'refunded' === $order_status ? 'refunded' : 'cancelled';

		if ( ! $this->plugin->referral->update_status( (int) $referral->id, $new_status ) ) {
			return;
		}

		/**
		 * Fires after a referral is reversed because its order was
		 * refunded, cancelled, failed, or expired.
		 *
		 * @param int    $referral_id Referral ID.
		 * @param string $new_status New referral status (cancelled|refunded).
		 * @param string $order_status Order status that triggered the reversal.
		 */
		do_action( 'directorist_affiliate_referral_reversed', (int) $referral->id, $new_status, $order_status );
	}

	/**
	 * Resolve the credited affiliate for a buyer.
	 *
	 * The tracking cookie wins, but only when the current session belongs to
	 * the buyer — admin-side events (offline-payment approval, status edits)
	 * must attribute via the mapping persisted at registration/conversion,
	 * never via the admin's own browser cookie. Self-referrals never credit.
	 *
	 * @param int $buyer_id Buyer user ID.
	 *
	 * @return object|null Approved affiliate row or null.
	 */
	private function resolve_affiliate( int $buyer_id ) {
		$candidates = array();

		if ( get_current_user_id() === $buyer_id ) {
			$candidates[] = $this->plugin->tracking->get_cookie_affiliate_id();
		}

		$candidates[] = absint( get_user_meta( $buyer_id, '_directorist_affiliate_id', true ) );

		foreach ( array_filter( $candidates ) as $affiliate_id ) {
			$affiliate = $this->plugin->affiliate->get( (int) $affiliate_id );

			if ( ! $affiliate || 'approved' !== $affiliate->status ) {
				continue;
			}

			if ( ! empty( $affiliate->user_id ) && (int) $affiliate->user_id === $buyer_id ) {
				continue;
			}

			// Persist the mapping (first credit wins) so later order updates
			// processed outside the buyer's session still attribute correctly.
			add_user_meta( $buyer_id, '_directorist_affiliate_id', (int) $affiliate->id, true );

			$visit_id = $this->plugin->tracking->get_cookie_visit_id();

			if ( $visit_id ) {
				add_user_meta( $buyer_id, '_directorist_affiliate_visit_id', $visit_id, true );
			}

			return $affiliate;
		}

		return null;
	}

	/**
	 * Read a DTO value defensively.
	 *
	 * @param object $dto Order DTO.
	 * @param string $field Field name.
	 *
	 * @return mixed Null when the field is unavailable.
	 */
	private function dto_value( $dto, string $field ) {
		if ( ! is_object( $dto ) ) {
			return null;
		}

		$getter = 'get_' . $field;

		if ( ! method_exists( $dto, $getter ) ) {
			return null;
		}

		if ( method_exists( $dto, 'is_initialized' ) && ! $dto->is_initialized( $field ) ) {
			return null;
		}

		return $dto->{$getter}();
	}

	/**
	 * Read the order status from a DTO.
	 *
	 * @param object $dto Order DTO.
	 *
	 * @return string
	 */
	private function dto_status( $dto ): string {
		return sanitize_key( (string) $this->dto_value( $dto, 'status' ) );
	}

	/**
	 * Resolve the paid total from a DTO, mirroring core's total calculation.
	 *
	 * @param object $dto Order DTO.
	 *
	 * @return float
	 */
	private function dto_total( $dto ): float {
		$sub_total = $this->dto_value( $dto, 'sub_total' );

		if ( null !== $sub_total && function_exists( 'directorist_compute_order_total_amount' ) ) {
			return (float) directorist_compute_order_total_amount(
				(float) $sub_total,
				(float) $this->dto_value( $dto, 'tax_rate' ),
				(string) $this->dto_value( $dto, 'tax_type' ),
				(float) $this->dto_value( $dto, 'coupon_discount' ),
				(string) $this->dto_value( $dto, 'coupon_discount_type' )
			);
		}

		return (float) $this->dto_value( $dto, 'amount' );
	}
}

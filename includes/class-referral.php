<?php
/**
 * Referral repository.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores and updates referral records.
 */
final class Directorist_Affiliate_Referral {
	/**
	 * Referral statuses.
	 *
	 * @return string[]
	 */
	public function statuses(): array {
		return array( 'pending', 'approved', 'rejected', 'paid', 'cancelled', 'refunded' );
	}

	/**
	 * Referral types.
	 *
	 * @return string[]
	 */
	public function types(): array {
		return array( 'user_registration', 'listing_submission', 'plan_purchase', 'featured_purchase' );
	}

	/**
	 * Translated label for a referral type.
	 *
	 * @param string $type Referral type.
	 *
	 * @return string
	 */
	public function type_label( string $type ): string {
		$labels = array(
			'user_registration'  => __( 'User registration', 'directorist-affiliate' ),
			'listing_submission' => __( 'Listing submission', 'directorist-affiliate' ),
			'plan_purchase'      => __( 'Plan purchase', 'directorist-affiliate' ),
			'featured_purchase'  => __( 'Featured listing purchase', 'directorist-affiliate' ),
		);

		return $labels[ $type ] ?? ucwords( str_replace( '_', ' ', $type ) );
	}

	/**
	 * Translated label for a referral status.
	 *
	 * @param string $status Referral status.
	 *
	 * @return string
	 */
	public function status_label( string $status ): string {
		$labels = array(
			'pending'   => __( 'Pending', 'directorist-affiliate' ),
			'approved'  => __( 'Approved', 'directorist-affiliate' ),
			'rejected'  => __( 'Rejected', 'directorist-affiliate' ),
			'paid'      => __( 'Paid', 'directorist-affiliate' ),
			'cancelled' => __( 'Cancelled', 'directorist-affiliate' ),
			'refunded'  => __( 'Refunded', 'directorist-affiliate' ),
		);

		return $labels[ $status ] ?? ucfirst( $status );
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public function table(): string {
		global $wpdb;

		return $wpdb->prefix . 'directorist_affiliate_referrals';
	}

	/**
	 * Create a referral if it does not already exist.
	 *
	 * @param array<string,mixed> $data Referral data.
	 *
	 * @return int Existing or new referral ID.
	 */
	public function create( array $data ): int {
		global $wpdb;

		$affiliate_id = absint( $data['affiliate_id'] ?? 0 );
		$type         = sanitize_key( $data['referral_type'] ?? '' );
		$user_id      = ! empty( $data['referred_user_id'] ) ? absint( $data['referred_user_id'] ) : null;
		$listing_id   = ! empty( $data['listing_id'] ) ? absint( $data['listing_id'] ) : null;
		$order_id     = ! empty( $data['order_id'] ) ? absint( $data['order_id'] ) : null;
		$order_source = isset( $data['order_source'] ) ? sanitize_key( $data['order_source'] ) : '';
		$status       = sanitize_key( $data['status'] ?? 'pending' );
		$status       = in_array( $status, $this->statuses(), true ) ? $status : 'pending';

		if ( ! $affiliate_id || ! in_array( $type, $this->types(), true ) ) {
			return 0;
		}

		$existing = $this->find_duplicate( $affiliate_id, $type, $user_id, $listing_id, $order_id, $order_source );

		if ( $existing ) {
			return (int) $existing->id;
		}

		$inserted = $wpdb->insert(
			$this->table(),
			array(
				'affiliate_id'      => $affiliate_id,
				'referral_type'     => $type,
				'referred_user_id'  => $user_id,
				'listing_id'        => $listing_id,
				'order_id'          => $order_id,
				'order_source'      => $order_source,
				'order_total'       => isset( $data['order_total'] ) ? (float) $data['order_total'] : null,
				'commission_amount' => (float) ( $data['commission_amount'] ?? 0 ),
				'status'            => $status,
				'date_created'      => current_time( 'mysql' ),
				'date_approved'     => 'approved' === $status ? current_time( 'mysql' ) : null,
				'date_paid'         => null,
				'notes'             => sanitize_textarea_field( $data['notes'] ?? '' ),
			),
			array( '%d', '%s', '%d', '%d', '%d', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return 0;
		}

		$referral_id = (int) $wpdb->insert_id;

		/**
		 * Fires after a new referral record is created.
		 *
		 * Not fired when an existing duplicate referral is returned.
		 *
		 * @param int    $referral_id Referral ID.
		 * @param int    $affiliate_id Affiliate ID.
		 * @param string $type Referral type.
		 */
		do_action( 'directorist_affiliate_referral_created', $referral_id, $affiliate_id, $type );

		return $referral_id;
	}

	/**
	 * Find duplicate referral for same conversion event.
	 *
	 * Order-based referrals are deduplicated per order regardless of the
	 * affiliate, so one paid order can never yield two commissions.
	 *
	 * @param int      $affiliate_id Affiliate ID.
	 * @param string   $type Referral type.
	 * @param int|null $user_id User ID.
	 * @param int|null $listing_id Listing ID.
	 * @param int|null $order_id Order ID.
	 * @param string   $order_source Order source (directorist|legacy).
	 *
	 * @return object|null
	 */
	public function find_duplicate( int $affiliate_id, string $type, ?int $user_id, ?int $listing_id, ?int $order_id = null, string $order_source = '' ) {
		global $wpdb;

		if ( in_array( $type, array( 'plan_purchase', 'featured_purchase' ), true ) ) {
			return $order_id ? $this->get_by_order( $order_id, $order_source ) : null;
		}

		if ( 'user_registration' === $type && $user_id ) {
			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$this->table()} WHERE affiliate_id = %d AND referral_type = %s AND referred_user_id = %d LIMIT 1",
					$affiliate_id,
					$type,
					$user_id
				)
			);
		}

		if ( 'listing_submission' === $type && $listing_id ) {
			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$this->table()} WHERE affiliate_id = %d AND referral_type = %s AND listing_id = %d LIMIT 1",
					$affiliate_id,
					$type,
					$listing_id
				)
			);
		}

		return null;
	}

	/**
	 * Get referral linked to an order.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $order_source Order source (directorist|legacy).
	 *
	 * @return object|null
	 */
	public function get_by_order( int $order_id, string $order_source = '' ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE order_id = %d AND order_source = %s LIMIT 1",
				$order_id,
				sanitize_key( $order_source )
			)
		);
	}

	/**
	 * Get referral by ID.
	 *
	 * @param int $referral_id Referral ID.
	 *
	 * @return object|null
	 */
	public function get( int $referral_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id = %d", $referral_id )
		);
	}

	/**
	 * List referrals.
	 *
	 * @param array<string,mixed> $args Query args.
	 *
	 * @return object[]
	 */
	public function list( array $args = array() ): array {
		global $wpdb;

		$where  = '1=1';
		$params = array();

		if ( ! empty( $args['affiliate_id'] ) ) {
			$where   .= ' AND affiliate_id = %d';
			$params[] = absint( $args['affiliate_id'] );
		}

		if ( ! empty( $args['status'] ) && in_array( $args['status'], $this->statuses(), true ) ) {
			$where   .= ' AND status = %s';
			$params[] = sanitize_key( $args['status'] );
		}

		$limit    = isset( $args['limit'] ) ? absint( $args['limit'] ) : 50;
		$offset   = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;
		$params[] = $limit;
		$params[] = $offset;

		$sql = "SELECT * FROM {$this->table()} WHERE {$where} ORDER BY date_created DESC LIMIT %d OFFSET %d";

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Update referral status.
	 *
	 * @param int    $referral_id Referral ID.
	 * @param string $status Status.
	 *
	 * @return bool
	 */
	public function update_status( int $referral_id, string $status ): bool {
		global $wpdb;

		if ( ! in_array( $status, $this->statuses(), true ) ) {
			return false;
		}

		$data   = array( 'status' => $status );
		$format = array( '%s' );

		if ( 'approved' === $status ) {
			$data['date_approved'] = current_time( 'mysql' );
			$format[]              = '%s';
		}

		if ( 'paid' === $status ) {
			$data['date_paid'] = current_time( 'mysql' );
			$format[]          = '%s';
		}

		return false !== $wpdb->update(
			$this->table(),
			$data,
			array( 'id' => absint( $referral_id ) ),
			$format,
			array( '%d' )
		);
	}

	/**
	 * Count referrals.
	 *
	 * @param string $status Optional status.
	 * @param int    $affiliate_id Optional affiliate ID.
	 *
	 * @return int
	 */
	public function count( string $status = '', int $affiliate_id = 0 ): int {
		global $wpdb;

		$where  = '1=1';
		$params = array();

		if ( $status && in_array( $status, $this->statuses(), true ) ) {
			$where   .= ' AND status = %s';
			$params[] = $status;
		}

		if ( $affiliate_id ) {
			$where   .= ' AND affiliate_id = %d';
			$params[] = $affiliate_id;
		}

		$sql = "SELECT COUNT(*) FROM {$this->table()} WHERE {$where}";

		if ( $params ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Sum commission amounts by optional affiliate/status.
	 *
	 * @param string $status Optional status.
	 * @param int    $affiliate_id Optional affiliate ID.
	 *
	 * @return float
	 */
	public function sum_commission( string $status = '', int $affiliate_id = 0 ): float {
		global $wpdb;

		$where  = '1=1';
		$params = array();

		if ( $status && in_array( $status, $this->statuses(), true ) ) {
			$where   .= ' AND status = %s';
			$params[] = $status;
		}

		if ( $affiliate_id ) {
			$where   .= ' AND affiliate_id = %d';
			$params[] = $affiliate_id;
		}

		$sql = "SELECT COALESCE(SUM(commission_amount), 0) FROM {$this->table()} WHERE {$where}";

		if ( $params ) {
			return (float) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		}

		return (float) $wpdb->get_var( $sql );
	}
}

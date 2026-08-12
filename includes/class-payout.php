<?php
/**
 * Payout service.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles manual payout records.
 */
final class Directorist_Affiliate_Payout {
	/**
	 * Referral repository.
	 *
	 * @var Directorist_Affiliate_Referral
	 */
	private $referral;

	/**
	 * Affiliate repository.
	 *
	 * @var Directorist_Affiliate_Affiliate
	 */
	private $affiliate;

	/**
	 * Constructor.
	 *
	 * @param Directorist_Affiliate_Referral  $referral Referral repository.
	 * @param Directorist_Affiliate_Affiliate $affiliate Affiliate repository.
	 */
	public function __construct( Directorist_Affiliate_Referral $referral, Directorist_Affiliate_Affiliate $affiliate ) {
		$this->referral  = $referral;
		$this->affiliate = $affiliate;
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public function table(): string {
		global $wpdb;

		return $wpdb->prefix . 'directorist_affiliate_payouts';
	}

	/**
	 * Create manual payout and mark referrals paid.
	 *
	 * @param int     $affiliate_id Affiliate ID.
	 * @param int[]   $referral_ids Referral IDs.
	 * @param string  $payout_email Payout email.
	 * @param string  $notes Notes.
	 *
	 * @return int
	 */
	public function mark_paid( int $affiliate_id, array $referral_ids, string $payout_email = '', string $notes = '' ): int {
		global $wpdb;

		$referral_ids = array_filter( array_map( 'absint', $referral_ids ) );

		if ( ! $affiliate_id || empty( $referral_ids ) ) {
			return 0;
		}

		// Only referrals that are approved AND belong to this affiliate are
		// recorded and flipped to paid; anything else in the list is ignored.
		$valid_ids = array();
		$amount    = 0.0;

		foreach ( $referral_ids as $referral_id ) {
			$referral = $this->referral->get( $referral_id );

			if ( ! $referral || (int) $referral->affiliate_id !== $affiliate_id || 'approved' !== $referral->status ) {
				continue;
			}

			$valid_ids[] = $referral_id;
			$amount     += (float) $referral->commission_amount;
		}

		if ( empty( $valid_ids ) || $amount <= 0 ) {
			return 0;
		}

		$inserted = $wpdb->insert(
			$this->table(),
			array(
				'affiliate_id'   => $affiliate_id,
				'amount'         => $amount,
				'status'         => 'paid',
				'payment_method' => 'manual',
				'payout_email'   => sanitize_email( $payout_email ),
				'referral_ids'   => implode( ',', $valid_ids ),
				'date_created'   => current_time( 'mysql' ),
				'date_paid'      => current_time( 'mysql' ),
				'notes'          => sanitize_textarea_field( $notes ),
			),
			array( '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return 0;
		}

		$payout_id = (int) $wpdb->insert_id;

		foreach ( $valid_ids as $referral_id ) {
			$this->referral->update_status( $referral_id, 'paid' );
		}

		/**
		 * Fires after a manual payout is recorded.
		 *
		 * @param int   $payout_id Payout ID.
		 * @param int   $affiliate_id Affiliate ID.
		 * @param float $amount Total amount paid.
		 * @param int[] $valid_ids Referral IDs covered by this payout.
		 */
		do_action( 'directorist_affiliate_payout_recorded', $payout_id, $affiliate_id, $amount, $valid_ids );

		return $payout_id;
	}

	/**
	 * Mark a batch of approved referrals paid, one payout per affiliate.
	 *
	 * Affiliates whose selected total is below the minimum are skipped.
	 *
	 * @param int[] $referral_ids Referral IDs.
	 * @param float $minimum Minimum payout per affiliate (0 = no minimum).
	 *
	 * @return array{paid:int,skipped:int}
	 */
	public function mark_paid_bulk( array $referral_ids, float $minimum = 0 ): array {
		$referral_ids = array_filter( array_map( 'absint', $referral_ids ) );

		$grouped = array();
		$totals  = array();

		foreach ( $referral_ids as $referral_id ) {
			$referral = $this->referral->get( $referral_id );

			if ( ! $referral || 'approved' !== $referral->status ) {
				continue;
			}

			$affiliate_id = (int) $referral->affiliate_id;

			$grouped[ $affiliate_id ][] = $referral_id;
			$totals[ $affiliate_id ]    = ( $totals[ $affiliate_id ] ?? 0.0 ) + (float) $referral->commission_amount;
		}

		$paid    = 0;
		$skipped = 0;

		foreach ( $grouped as $affiliate_id => $ids ) {
			if ( $minimum > 0 && $totals[ $affiliate_id ] < $minimum ) {
				$skipped++;
				continue;
			}

			$affiliate = $this->affiliate->get( (int) $affiliate_id );
			$payout_id = $this->mark_paid(
				(int) $affiliate_id,
				$ids,
				$affiliate ? $affiliate->payout_email : '',
				__( 'Manual payout marked from admin.', 'directorist-affiliate' )
			);

			if ( $payout_id ) {
				$paid++;
			}
		}

		return array(
			'paid'    => $paid,
			'skipped' => $skipped,
		);
	}

	/**
	 * List payout records.
	 *
	 * @param array<string,mixed> $args Query args (affiliate_id, email, date_from, date_to, limit, offset).
	 *
	 * @return object[]
	 */
	public function list( array $args = array() ): array {
		global $wpdb;

		list( $where, $params ) = $this->build_where( $args );

		$params[] = isset( $args['limit'] ) ? absint( $args['limit'] ) : 50;
		$params[] = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE {$where} ORDER BY date_created DESC LIMIT %d OFFSET %d", $params )
		);
	}

	/**
	 * Count payout records matching the same filters as list().
	 *
	 * @param array<string,mixed> $args Query args.
	 *
	 * @return int
	 */
	public function count( array $args = array() ): int {
		global $wpdb;

		list( $where, $params ) = $this->build_where( $args );

		$sql = "SELECT COUNT(*) FROM {$this->table()} WHERE {$where}";

		if ( $params ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Sum payout amounts matching the same filters as list().
	 *
	 * @param array<string,mixed> $args Query args.
	 *
	 * @return float
	 */
	public function sum( array $args = array() ): float {
		global $wpdb;

		list( $where, $params ) = $this->build_where( $args );

		$sql = "SELECT COALESCE(SUM(amount), 0) FROM {$this->table()} WHERE {$where}";

		if ( $params ) {
			return (float) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		}

		return (float) $wpdb->get_var( $sql );
	}

	/**
	 * List payout records for one affiliate.
	 *
	 * @param int $affiliate_id Affiliate ID.
	 * @param int $limit Limit.
	 *
	 * @return object[]
	 */
	public function list_by_affiliate( int $affiliate_id, int $limit = 20 ): array {
		return $this->list(
			array(
				'affiliate_id' => $affiliate_id,
				'limit'        => $limit,
			)
		);
	}

	/**
	 * Build the shared WHERE clause for list()/count()/sum().
	 *
	 * Date filters run against `date_paid` when present, falling back to
	 * `date_created`, so "paid in March" means what an admin expects.
	 *
	 * @param array<string,mixed> $args Query args.
	 *
	 * @return array{0:string,1:array<int,mixed>} WHERE fragment and its params.
	 */
	private function build_where( array $args ): array {
		global $wpdb;

		$where  = '1=1';
		$params = array();

		if ( ! empty( $args['affiliate_id'] ) ) {
			$where   .= ' AND affiliate_id = %d';
			$params[] = absint( $args['affiliate_id'] );
		}

		if ( ! empty( $args['email'] ) ) {
			$where   .= ' AND payout_email LIKE %s';
			$params[] = '%' . $wpdb->esc_like( sanitize_text_field( (string) $args['email'] ) ) . '%';
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where   .= ' AND COALESCE(date_paid, date_created) >= %s';
			$params[] = (string) $args['date_from'];
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where   .= ' AND COALESCE(date_paid, date_created) <= %s';
			$params[] = (string) $args['date_to'];
		}

		return array( $where, $params );
	}
}

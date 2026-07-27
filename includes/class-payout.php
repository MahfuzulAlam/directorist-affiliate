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

		$amount = 0.0;

		foreach ( $referral_ids as $referral_id ) {
			$referral = $this->referral->get( $referral_id );

			if ( ! $referral || (int) $referral->affiliate_id !== $affiliate_id || 'approved' !== $referral->status ) {
				continue;
			}

			$amount += (float) $referral->commission_amount;
		}

		if ( $amount <= 0 ) {
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
				'referral_ids'   => implode( ',', $referral_ids ),
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

		foreach ( $referral_ids as $referral_id ) {
			$this->referral->update_status( $referral_id, 'paid' );
		}

		/**
		 * Fires after a manual payout is recorded.
		 *
		 * @param int   $payout_id Payout ID.
		 * @param int   $affiliate_id Affiliate ID.
		 * @param float $amount Total amount paid.
		 * @param int[] $referral_ids Referral IDs covered.
		 */
		do_action( 'directorist_affiliate_payout_recorded', $payout_id, $affiliate_id, $amount, $referral_ids );

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
	 * @param int $limit Limit.
	 *
	 * @return object[]
	 */
	public function list( int $limit = 50 ): array {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table()} ORDER BY date_created DESC LIMIT %d", absint( $limit ) )
		);
	}
}

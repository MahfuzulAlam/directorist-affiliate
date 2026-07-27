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
	 * Constructor.
	 *
	 * @param Directorist_Affiliate_Referral $referral Referral repository.
	 */
	public function __construct( Directorist_Affiliate_Referral $referral ) {
		$this->referral = $referral;
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

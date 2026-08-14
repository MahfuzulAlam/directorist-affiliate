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
	 * Allowed payout statuses.
	 *
	 * `requested` is a claim from an affiliate that no money has moved for
	 * yet; `paid` is a real payment; `rejected` is a declined claim.
	 *
	 * @return string[]
	 */
	public function statuses(): array {
		return array( 'requested', 'paid', 'rejected' );
	}

	/**
	 * Translated label for a payout status.
	 *
	 * @param string $status Payout status.
	 *
	 * @return string
	 */
	public function status_label( string $status ): string {
		$labels = array(
			'requested' => __( 'Requested', 'directorist-affiliate' ),
			'paid'      => __( 'Paid', 'directorist-affiliate' ),
			'rejected'  => __( 'Rejected', 'directorist-affiliate' ),
		);

		return $labels[ $status ] ?? ucfirst( $status );
	}

	/**
	 * The affiliate's open payout request, if any.
	 *
	 * @param int $affiliate_id Affiliate ID.
	 *
	 * @return object|null
	 */
	public function open_request( int $affiliate_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE affiliate_id = %d AND status = 'requested' ORDER BY id DESC LIMIT 1",
				absint( $affiliate_id )
			)
		);
	}

	/**
	 * Record a payout request from an affiliate.
	 *
	 * The request covers the affiliate's currently approved commissions and
	 * records which ones, but deliberately leaves them `approved` — no money
	 * has moved, and an admin may still reject it.
	 *
	 * @param object               $affiliate Affiliate row.
	 * @param string               $method Payout method key.
	 * @param array<string,string> $details Validated method details.
	 * @param string               $note Optional message to the admin.
	 * @param float                $minimum Minimum payout, or 0 for none.
	 *
	 * @return array{success:bool,message:string,payout_id:int}
	 */
	public function request( $affiliate, string $method, array $details, string $note, float $minimum = 0 ): array {
		global $wpdb;

		$affiliate_id = (int) $affiliate->id;

		$fail = function ( string $message ): array {
			return array(
				'success'   => false,
				'message'   => $message,
				'payout_id' => 0,
			);
		};

		if ( 'approved' !== $affiliate->status ) {
			return $fail( __( 'Only approved affiliates can request a payout.', 'directorist-affiliate' ) );
		}

		if ( $this->open_request( $affiliate_id ) ) {
			return $fail( __( 'You already have a payout request waiting to be processed.', 'directorist-affiliate' ) );
		}

		$referrals = $this->referral->list(
			array(
				'affiliate_id' => $affiliate_id,
				'status'       => 'approved',
				'limit'        => 1000,
			)
		);

		$referral_ids = array();
		$amount       = 0.0;

		foreach ( $referrals as $referral ) {
			$referral_ids[] = (int) $referral->id;
			$amount        += (float) $referral->commission_amount;
		}

		if ( $amount <= 0 ) {
			return $fail( __( 'You have no approved commissions to be paid yet.', 'directorist-affiliate' ) );
		}

		$contact_email = '';

		foreach ( $details as $value ) {
			if ( is_email( $value ) ) {
				$contact_email = $value;
				break;
			}
		}

		if ( ! $contact_email ) {
			$contact_email = (string) $affiliate->payout_email;
		}

		if ( $minimum > 0 && $amount < $minimum ) {
			return $fail(
				sprintf(
					/* translators: %s: minimum payout amount. */
					__( 'You need at least %s in approved commissions before requesting a payout.', 'directorist-affiliate' ),
					Directorist_Affiliate_Commission::format_money( $minimum )
				)
			);
		}

		// Snapshot the method and details: the affiliate may change their bank
		// account later, and this payout must still record how it was paid.
		$inserted = $wpdb->insert(
			$this->table(),
			array(
				'affiliate_id'   => $affiliate_id,
				'amount'         => $amount,
				'status'         => 'requested',
				'payment_method' => sanitize_key( $method ),
				'payout_email'   => sanitize_email( $contact_email ),
				'payout_details' => (string) wp_json_encode( $details ),
				'referral_ids'   => implode( ',', $referral_ids ),
				'date_created'   => current_time( 'mysql' ),
				'date_paid'      => null,
				'notes'          => sanitize_textarea_field( $note ),
			),
			array( '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return $fail( __( 'Could not record your request. Please try again.', 'directorist-affiliate' ) );
		}

		$payout_id = (int) $wpdb->insert_id;

		/**
		 * Fires after an affiliate requests a payout.
		 *
		 * @param int   $payout_id Payout ID.
		 * @param int   $affiliate_id Affiliate ID.
		 * @param float $amount Requested amount.
		 */
		do_action( 'directorist_affiliate_payout_requested', $payout_id, $affiliate_id, $amount );

		return array(
			'success'   => true,
			'message'   => __( 'Your payout request has been sent. We will email you once it is processed.', 'directorist-affiliate' ),
			'payout_id' => $payout_id,
		);
	}

	/**
	 * Pay an outstanding request.
	 *
	 * The amount is recalculated from the referrals that are *still* approved,
	 * because a commission can be refunded between request and payment.
	 *
	 * @param int $payout_id Payout ID.
	 *
	 * @return array{success:bool,message:string,amount:float}
	 */
	public function fulfil( int $payout_id ): array {
		global $wpdb;

		$payout = $this->get( $payout_id );

		if ( ! $payout || 'requested' !== $payout->status ) {
			return array(
				'success' => false,
				'message' => __( 'That payout request is no longer open.', 'directorist-affiliate' ),
				'amount'  => 0.0,
			);
		}

		$valid_ids = array();
		$amount    = 0.0;

		foreach ( $this->referral_ids( $payout ) as $referral_id ) {
			$referral = $this->referral->get( $referral_id );

			if ( ! $referral || (int) $referral->affiliate_id !== (int) $payout->affiliate_id || 'approved' !== $referral->status ) {
				continue;
			}

			$valid_ids[] = $referral_id;
			$amount     += (float) $referral->commission_amount;
		}

		if ( empty( $valid_ids ) || $amount <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'None of the commissions in this request are payable any more. Reject it instead.', 'directorist-affiliate' ),
				'amount'  => 0.0,
			);
		}

		$updated = $wpdb->update(
			$this->table(),
			array(
				'status'       => 'paid',
				'amount'       => $amount,
				'referral_ids' => implode( ',', $valid_ids ),
				'date_paid'    => current_time( 'mysql' ),
			),
			array( 'id' => $payout_id ),
			array( '%s', '%f', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return array(
				'success' => false,
				'message' => __( 'Could not update the payout. Please try again.', 'directorist-affiliate' ),
				'amount'  => 0.0,
			);
		}

		foreach ( $valid_ids as $referral_id ) {
			$this->referral->update_status( $referral_id, 'paid' );
		}

		/**
		 * Fires after a requested payout is paid.
		 *
		 * @param int   $payout_id Payout ID.
		 * @param int   $affiliate_id Affiliate ID.
		 * @param float $amount Amount actually paid.
		 * @param int[] $valid_ids Referral IDs covered.
		 */
		do_action( 'directorist_affiliate_payout_recorded', $payout_id, (int) $payout->affiliate_id, $amount, $valid_ids );

		return array(
			'success' => true,
			'message' => __( 'Payout marked as paid.', 'directorist-affiliate' ),
			'amount'  => $amount,
		);
	}

	/**
	 * Reject an outstanding request, leaving its commissions payable.
	 *
	 * @param int    $payout_id Payout ID.
	 * @param string $note Reason shown to the affiliate.
	 *
	 * @return bool
	 */
	public function reject( int $payout_id, string $note = '' ): bool {
		global $wpdb;

		$payout = $this->get( $payout_id );

		if ( ! $payout || 'requested' !== $payout->status ) {
			return false;
		}

		$rejected = $wpdb->update(
			$this->table(),
			array(
				'status' => 'rejected',
				'notes'  => sanitize_textarea_field( $note ),
			),
			array( 'id' => $payout_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $rejected ) {
			return false;
		}

		/**
		 * Fires after a payout request is rejected.
		 *
		 * @param int $payout_id Payout ID.
		 * @param int $affiliate_id Affiliate ID.
		 */
		do_action( 'directorist_affiliate_payout_rejected', $payout_id, (int) $payout->affiliate_id );

		return true;
	}

	/**
	 * Get one payout record.
	 *
	 * @param int $payout_id Payout ID.
	 *
	 * @return object|null
	 */
	public function get( int $payout_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id = %d", absint( $payout_id ) )
		);
	}

	/**
	 * Referral IDs covered by a payout row.
	 *
	 * @param object $payout Payout row.
	 *
	 * @return int[]
	 */
	public function referral_ids( $payout ): array {
		return array_values( array_filter( array_map( 'absint', explode( ',', (string) ( $payout->referral_ids ?? '' ) ) ) ) );
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

		if ( ! empty( $args['status'] ) && in_array( $args['status'], $this->statuses(), true ) ) {
			$where   .= ' AND status = %s';
			$params[] = sanitize_key( $args['status'] );
		}

		// A list of statuses, e.g. everything except open requests.
		if ( ! empty( $args['status__in'] ) && is_array( $args['status__in'] ) ) {
			$allowed = array_values( array_intersect( $args['status__in'], $this->statuses() ) );

			if ( $allowed ) {
				$where   .= ' AND status IN ( ' . implode( ', ', array_fill( 0, count( $allowed ), '%s' ) ) . ' )';
				$params   = array_merge( $params, $allowed );
			}
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

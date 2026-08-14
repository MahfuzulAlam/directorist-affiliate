<?php
/**
 * CSV exports for the admin list screens.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Streams the rows a list screen is currently showing as CSV.
 *
 * The export deliberately reuses the screen's own filter parsing and query
 * building (see filters() and query_args()), so an export can never disagree
 * with the table it was launched from. Rows are fetched in batches and written
 * straight to the output stream, so a large directory exports completely
 * instead of being silently capped.
 */
final class Directorist_Affiliate_Admin_Export {
	/**
	 * Rows fetched per batch while streaming.
	 */
	private const BATCH = 500;

	/**
	 * Datasets that can be exported, and the extra filter keys each reads.
	 */
	private const DATASETS = array(
		'affiliates' => array( 'status', 's' ),
		'referrals'  => array( 'status', 'type' ),
		'visits'     => array( 'converted' ),
		'payouts'    => array( 'status', 'email' ),
	);

	/**
	 * Plugin container.
	 *
	 * @var Directorist_Affiliate_Plugin
	 */
	private $plugin;

	/**
	 * Referral totals per affiliate, fetched once per export.
	 *
	 * @var array<int,object>|null
	 */
	private $referral_stats = null;

	/**
	 * Visit totals per affiliate, fetched once per export.
	 *
	 * @var array<int,object>|null
	 */
	private $visit_stats = null;

	/**
	 * Constructor.
	 *
	 * @param Directorist_Affiliate_Plugin $plugin Plugin container.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Whether a dataset key is one this class can export.
	 *
	 * @param string $dataset Dataset key.
	 *
	 * @return bool
	 */
	public static function is_dataset( string $dataset ): bool {
		return isset( self::DATASETS[ $dataset ] );
	}

	/**
	 * Extra filter keys a dataset reads from the request.
	 *
	 * @param string $dataset Dataset key.
	 *
	 * @return string[]
	 */
	public static function filter_keys( string $dataset ): array {
		return self::DATASETS[ $dataset ] ?? array();
	}

	/**
	 * Read the shared list filters out of the request.
	 *
	 * Single source of truth for both the list screens and the exports — if
	 * these drifted apart, an export would quietly return different rows than
	 * the table the admin was looking at.
	 *
	 * @param string[] $keys Extra scalar filter keys to read, e.g. 'status'.
	 *
	 * @return array<string,mixed>
	 */
	public static function filters( array $keys = array() ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only list filters.
		$range = Directorist_Affiliate_Date_Range::from_request( $_GET );

		$filters = array(
			'affiliate_id' => isset( $_GET['affiliate'] ) ? absint( $_GET['affiliate'] ) : 0,
			'range'        => $range['preset'],
			'from'         => $range['from'],
			'to'           => $range['to'],
		);

		foreach ( $keys as $key ) {
			$filters[ $key ] = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		list( $filters['date_from'], $filters['date_to'] ) = Directorist_Affiliate_Date_Range::resolve(
			$filters['range'],
			$filters['from'],
			$filters['to']
		);

		return $filters;
	}

	/**
	 * Turn a filter set into repository query args for one dataset.
	 *
	 * @param string              $dataset Dataset key.
	 * @param array<string,mixed> $filters Filters from filters().
	 *
	 * @return array<string,mixed>
	 */
	public static function query_args( string $dataset, array $filters ): array {
		$shared = array(
			'date_from' => $filters['date_from'] ?? '',
			'date_to'   => $filters['date_to'] ?? '',
		);

		switch ( $dataset ) {
			case 'affiliates':
				return array_merge(
					$shared,
					array(
						'status' => $filters['status'] ?? '',
						'search' => $filters['s'] ?? '',
					)
				);

			case 'referrals':
				return array_merge(
					$shared,
					array(
						'affiliate_id'  => $filters['affiliate_id'] ?? 0,
						'status'        => $filters['status'] ?? '',
						'referral_type' => $filters['type'] ?? '',
					)
				);

			case 'visits':
				$converted = $filters['converted'] ?? '';

				return array_merge(
					$shared,
					array(
						'affiliate_id' => $filters['affiliate_id'] ?? 0,
						'converted'    => in_array( $converted, array( '0', '1' ), true ) ? $converted : '',
					)
				);

			case 'payouts':
				return array_merge(
					$shared,
					array(
						'affiliate_id' => $filters['affiliate_id'] ?? 0,
						'status'       => $filters['status'] ?? '',
						'email'        => $filters['email'] ?? '',
					)
				);
		}

		return $shared;
	}

	/**
	 * Build a nonce-protected export URL that carries the current filters.
	 *
	 * @param string               $dataset Dataset key.
	 * @param array<string,string> $carry Query vars describing the current view.
	 * @param string               $page Tab to return to; defaults to the dataset's own.
	 *
	 * @return string
	 */
	public static function url( string $dataset, array $carry = array(), string $page = '' ): string {
		$args = array_merge( $carry, array( 'directorist_affiliate_export' => $dataset ) );

		return wp_nonce_url(
			Directorist_Affiliate_Admin::page_url( $page ? $page : $dataset, $args ),
			'directorist_affiliate_export_' . $dataset
		);
	}

	/**
	 * Export the requested dataset, if this request asked for one.
	 *
	 * @return void
	 */
	public function maybe_handle(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified below.
		$dataset = isset( $_GET['directorist_affiliate_export'] ) ? sanitize_key( wp_unslash( $_GET['directorist_affiliate_export'] ) ) : '';

		if ( ! self::is_dataset( $dataset ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to export this data.', 'directorist-affiliate' ), 403 );
		}

		check_admin_referer( 'directorist_affiliate_export_' . $dataset );

		$filters = self::filters( self::filter_keys( $dataset ) );

		$this->send_headers( $dataset );
		$this->stream( $dataset, self::query_args( $dataset, $filters ) );

		exit;
	}

	/**
	 * Send the CSV download headers.
	 *
	 * @param string $dataset Dataset key.
	 *
	 * @return void
	 */
	private function send_headers( string $dataset ): void {
		nocache_headers();

		// Anything already buffered would corrupt the file.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		$filename = sprintf(
			'directorist-affiliate-%s-%s.csv',
			$dataset,
			gmdate( 'Y-m-d' )
		);

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
	}

	/**
	 * Write every matching row to the output stream, in batches.
	 *
	 * @param string              $dataset Dataset key.
	 * @param array<string,mixed> $args Repository query args.
	 *
	 * @return void
	 */
	private function stream( string $dataset, array $args ): void {
		$output = fopen( 'php://output', 'w' );

		if ( false === $output ) {
			return;
		}

		// Excel needs the BOM to read UTF-8 names correctly.
		fwrite( $output, "\xEF\xBB\xBF" );
		fputcsv( $output, $this->columns( $dataset ) );

		$offset = 0;

		do {
			$rows = $this->fetch( $dataset, array_merge( $args, array( 'limit' => self::BATCH, 'offset' => $offset ) ) );

			if ( ! $rows ) {
				break;
			}

			$affiliates = 'affiliates' === $dataset
				? array()
				: $this->plugin->affiliate->get_many( wp_list_pluck( $rows, 'affiliate_id' ) );

			foreach ( $rows as $row ) {
				fputcsv( $output, array_map( array( $this, 'escape' ), $this->row( $dataset, $row, $affiliates ) ) );
			}

			$offset += self::BATCH;
		} while ( count( $rows ) === self::BATCH );

		fclose( $output );
	}

	/**
	 * Fetch one batch from the repository behind a dataset.
	 *
	 * @param string              $dataset Dataset key.
	 * @param array<string,mixed> $args Query args including limit/offset.
	 *
	 * @return object[]
	 */
	private function fetch( string $dataset, array $args ): array {
		switch ( $dataset ) {
			case 'affiliates':
				return $this->plugin->affiliate->list( $args );

			case 'referrals':
				return $this->plugin->referral->list( $args );

			case 'visits':
				return $this->plugin->tracking->list( $args );

			case 'payouts':
				return $this->plugin->payout->list( $args );
		}

		return array();
	}

	/**
	 * Header row for a dataset.
	 *
	 * @param string $dataset Dataset key.
	 *
	 * @return string[]
	 */
	private function columns( string $dataset ): array {
		switch ( $dataset ) {
			case 'affiliates':
				return array(
					'id',
					'name',
					'status',
					'referral_code',
					'referral_url',
					'user_id',
					'user_login',
					'user_email',
					'payout_email',
					'payout_method',
					'payout_details',
					'website',
					'promotional_method',
					'application_note',
					'visits',
					'converted_visits',
					'referrals',
					'pending_amount',
					'approved_amount',
					'paid_amount',
					'date_created',
					'date_updated',
				);

			case 'referrals':
				return array(
					'id',
					'affiliate_id',
					'affiliate_name',
					'affiliate_email',
					'referral_type',
					'referral_type_label',
					'status',
					'commission_amount',
					'order_id',
					'order_source',
					'order_total',
					'referred_user_id',
					'referred_user_email',
					'listing_id',
					'listing_title',
					'notes',
					'date_created',
					'date_approved',
					'date_paid',
				);

			case 'visits':
				return array(
					'id',
					'affiliate_id',
					'affiliate_name',
					'referral_code',
					'converted',
					'landing_url',
					'referrer_url',
					'ip_address',
					'user_agent',
					'referred_user_id',
					'listing_id',
					'date_created',
				);

			case 'payouts':
				return array(
					'id',
					'affiliate_id',
					'affiliate_name',
					'amount',
					'status',
					'payment_method',
					'payment_method_label',
					'payout_email',
					'payout_details',
					'referral_ids',
					'referral_count',
					'notes',
					'date_created',
					'date_paid',
				);
		}

		return array();
	}

	/**
	 * Build one CSV row.
	 *
	 * @param string               $dataset Dataset key.
	 * @param object               $row Database row.
	 * @param array<int,object>    $affiliates Affiliates indexed by id.
	 *
	 * @return array<int,string|int|float|null>
	 */
	private function row( string $dataset, $row, array $affiliates ): array {
		switch ( $dataset ) {
			case 'affiliates':
				return $this->affiliate_row( $row );

			case 'referrals':
				return $this->referral_row( $row, $affiliates );

			case 'visits':
				return $this->visit_row( $row, $affiliates );

			case 'payouts':
				return $this->payout_row( $row, $affiliates );
		}

		return array();
	}

	/**
	 * One affiliate, with its lifetime totals.
	 *
	 * @param object $row Affiliate row.
	 *
	 * @return array<int,string|int|float|null>
	 */
	private function affiliate_row( $row ): array {
		$user  = ! empty( $row->user_id ) ? get_user_by( 'id', (int) $row->user_id ) : null;
		$stats = $this->affiliate_stats( (int) $row->id );

		return array(
			$row->id,
			$this->plugin->affiliate->get_name( $row ),
			$row->status,
			$row->referral_code,
			$this->referral_url( (string) $row->referral_code ),
			$row->user_id,
			$user ? $user->user_login : '',
			$user ? $user->user_email : '',
			$row->payout_email,
			$this->plugin->payout_methods->label( (string) $row->payout_method ),
			$this->plugin->payout_methods->summary(
				(string) $row->payout_method,
				$this->plugin->payout_methods->decode( $row->payout_details )
			),
			$row->website,
			$row->promotional_method,
			$row->application_note,
			$stats['visits'],
			$stats['converted_visits'],
			$stats['referrals'],
			$stats['pending_amount'],
			$stats['approved_amount'],
			$stats['paid_amount'],
			$row->date_created,
			$row->date_updated,
		);
	}

	/**
	 * Lifetime counters for one affiliate.
	 *
	 * Both sources are grouped queries run once for the whole export, not one
	 * lookup per row.
	 *
	 * @param int $affiliate_id Affiliate id.
	 *
	 * @return array<string,int|float>
	 */
	private function affiliate_stats( int $affiliate_id ): array {
		if ( null === $this->referral_stats ) {
			$this->referral_stats = $this->plugin->referral->stats_by_affiliate();
			$this->visit_stats    = $this->plugin->tracking->stats_by_affiliate();
		}

		$referrals = $this->referral_stats[ $affiliate_id ] ?? null;
		$visits    = $this->visit_stats[ $affiliate_id ] ?? null;

		return array(
			'visits'           => $visits ? (int) $visits->total_visits : 0,
			'converted_visits' => $visits ? (int) $visits->converted_visits : 0,
			'referrals'        => $referrals ? (int) $referrals->total_referrals : 0,
			'pending_amount'   => $referrals ? (float) $referrals->pending_commission : 0.0,
			'approved_amount'  => $referrals ? (float) $referrals->approved_commission : 0.0,
			'paid_amount'      => $referrals ? (float) $referrals->paid_commission : 0.0,
		);
	}

	/**
	 * One referral, with the names behind its ids.
	 *
	 * @param object            $row Referral row.
	 * @param array<int,object> $affiliates Affiliates indexed by id.
	 *
	 * @return array<int,string|int|float|null>
	 */
	private function referral_row( $row, array $affiliates ): array {
		$affiliate = $affiliates[ (int) $row->affiliate_id ] ?? null;
		$user      = ! empty( $row->referred_user_id ) ? get_user_by( 'id', (int) $row->referred_user_id ) : null;

		return array(
			$row->id,
			$row->affiliate_id,
			$affiliate ? $this->plugin->affiliate->get_name( $affiliate ) : '',
			$affiliate ? $affiliate->payout_email : '',
			$row->referral_type,
			$this->plugin->referral->type_label( (string) $row->referral_type ),
			$row->status,
			$row->commission_amount,
			$row->order_id,
			$row->order_source,
			$row->order_total,
			$row->referred_user_id,
			$user ? $user->user_email : '',
			$row->listing_id,
			! empty( $row->listing_id ) ? (string) get_the_title( (int) $row->listing_id ) : '',
			$row->notes,
			$row->date_created,
			$row->date_approved,
			$row->date_paid,
		);
	}

	/**
	 * One visit, including the columns the table has no room for.
	 *
	 * @param object            $row Visit row.
	 * @param array<int,object> $affiliates Affiliates indexed by id.
	 *
	 * @return array<int,string|int|float|null>
	 */
	private function visit_row( $row, array $affiliates ): array {
		$affiliate = $affiliates[ (int) $row->affiliate_id ] ?? null;

		return array(
			$row->id,
			$row->affiliate_id,
			$affiliate ? $this->plugin->affiliate->get_name( $affiliate ) : '',
			$row->referral_code,
			absint( $row->converted ) ? 'yes' : 'no',
			$row->landing_url,
			$row->referrer_url,
			$row->ip_address,
			$row->user_agent,
			$row->referred_user_id,
			$row->listing_id,
			$row->date_created,
		);
	}

	/**
	 * One payout, with the method and details it was paid by.
	 *
	 * @param object            $row Payout row.
	 * @param array<int,object> $affiliates Affiliates indexed by id.
	 *
	 * @return array<int,string|int|float|null>
	 */
	private function payout_row( $row, array $affiliates ): array {
		$affiliate = $affiliates[ (int) $row->affiliate_id ] ?? null;
		$ids       = $this->plugin->payout->referral_ids( $row );

		return array(
			$row->id,
			$row->affiliate_id,
			$affiliate ? $this->plugin->affiliate->get_name( $affiliate ) : '',
			$row->amount,
			$row->status,
			$row->payment_method,
			$this->plugin->payout_methods->label( (string) $row->payment_method ),
			$row->payout_email,
			$this->plugin->payout_methods->summary(
				(string) $row->payment_method,
				$this->plugin->payout_methods->decode( $row->payout_details )
			),
			implode( ',', $ids ),
			count( $ids ),
			$row->notes,
			$row->date_created,
			$row->date_paid,
		);
	}

	/**
	 * The affiliate's referral link, using the configured query parameter.
	 *
	 * @param string $code Referral code.
	 *
	 * @return string
	 */
	private function referral_url( string $code ): string {
		return add_query_arg(
			rawurlencode( (string) $this->plugin->settings->get( 'ref_param', 'ref' ) ),
			rawurlencode( $code ),
			home_url( '/' )
		);
	}

	/**
	 * Neutralize spreadsheet formula injection in a CSV cell.
	 *
	 * Cells starting with =, +, -, @, tab, or CR are prefixed with an
	 * apostrophe so spreadsheet apps treat them as text, never formulas.
	 *
	 * @param mixed $value Cell value.
	 *
	 * @return string
	 */
	private function escape( $value ): string {
		$value = (string) $value;

		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $value;
		}

		return $value;
	}
}

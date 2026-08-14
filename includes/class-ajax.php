<?php
/**
 * AJAX endpoints.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles AJAX submissions for every plugin form.
 *
 * Each form also has a non-JavaScript fallback (shortcode POST handler or
 * admin_init handler) that runs the same underlying services.
 */
final class Directorist_Affiliate_Ajax {
	/**
	 * Plugin instance.
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
	 * Register AJAX hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_ajax_directorist_affiliate_register', array( $this, 'register_affiliate' ) );
		add_action( 'wp_ajax_nopriv_directorist_affiliate_register', array( $this, 'register_affiliate' ) );
		add_action( 'wp_ajax_directorist_affiliate_add_affiliate', array( $this, 'add_affiliate' ) );
		add_action( 'wp_ajax_directorist_affiliate_save_settings', array( $this, 'save_settings' ) );
		add_action( 'wp_ajax_directorist_affiliate_mark_paid', array( $this, 'mark_paid' ) );

		// Link builder — logged-in affiliates only, so no nopriv counterpart.
		add_action( 'wp_ajax_directorist_affiliate_search_content', array( $this, 'search_content' ) );
		add_action( 'wp_ajax_directorist_affiliate_custom_link', array( $this, 'custom_link' ) );
		add_action( 'wp_ajax_directorist_affiliate_request_payout', array( $this, 'request_payout' ) );
		add_action( 'wp_ajax_directorist_affiliate_save_payout_method', array( $this, 'save_payout_method' ) );
	}

	/**
	 * Affiliate asks to be paid their approved commissions.
	 *
	 * @return void
	 */
	public function request_payout(): void {
		$affiliate = $this->guard_affiliate( 'directorist_affiliate_request_payout', 'directorist_affiliate_nonce' );
		$note      = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

		// Submitted details win; otherwise fall back to the saved default. One
		// of the two must be complete, which is what "if not default they need
		// to submit those information" comes down to.
		$resolved = $this->resolve_payout_method( $affiliate, $_POST );

		if ( ! $resolved['valid'] ) {
			wp_send_json_error( array( 'message' => $resolved['message'] ), 400 );
		}

		$result = $this->plugin->payout->request(
			$affiliate,
			$resolved['method'],
			$resolved['details'],
			$note,
			(float) $this->plugin->settings->get( 'minimum_payout', '0.00' )
		);

		if ( ! $result['success'] ) {
			wp_send_json_error( array( 'message' => $result['message'] ), 400 );
		}

		// Remember it, so the next request needs no re-entry.
		$this->store_payout_method( $affiliate, $resolved['method'], $resolved['details'] );

		$payout = $this->plugin->payout->get( $result['payout_id'] );

		if ( $payout ) {
			$this->plugin->email->payout_requested( $affiliate, $payout );
		}

		wp_send_json_success( array( 'message' => $result['message'] ) );
	}

	/**
	 * Save an affiliate's default payout method from the dashboard.
	 *
	 * @return void
	 */
	public function save_payout_method(): void {
		$affiliate = $this->guard_affiliate( 'directorist_affiliate_payout_method', 'directorist_affiliate_nonce' );
		$method    = isset( $_POST['payout_method'] ) ? sanitize_key( wp_unslash( $_POST['payout_method'] ) ) : '';
		$raw       = isset( $_POST['payout_details'] ) && is_array( $_POST['payout_details'] ) ? $_POST['payout_details'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field by Payout_Methods::validate().

		$check = $this->plugin->payout_methods->validate( $method, $raw );

		if ( ! $check['valid'] ) {
			wp_send_json_error( array( 'message' => $check['message'] ), 400 );
		}

		$this->store_payout_method( $affiliate, $method, $check['details'] );

		wp_send_json_success(
			array(
				'message' => __( 'Payout details saved.', 'directorist-affiliate' ),
				'summary' => $this->plugin->payout_methods->summary( $method, $check['details'] ),
			)
		);
	}

	/**
	 * Work out which payout method a request should use.
	 *
	 * @param object              $affiliate Affiliate row.
	 * @param array<string,mixed> $request Raw request data.
	 *
	 * @return array{valid:bool,method:string,details:array<string,string>,message:string}
	 */
	private function resolve_payout_method( $affiliate, array $request ): array {
		$methods  = $this->plugin->payout_methods;
		$saved    = $methods->decode( $affiliate->payout_details ?? '' );
		$stored   = (string) ( $affiliate->payout_method ?? '' );
		$method   = isset( $request['payout_method'] ) ? sanitize_key( wp_unslash( $request['payout_method'] ) ) : '';
		$supplied = isset( $request['payout_details'] ) && is_array( $request['payout_details'] ) ? $request['payout_details'] : array();

		// Nothing submitted: the saved default must be usable on its own.
		if ( ! $method ) {
			if ( $methods->is_complete( $stored, $saved ) ) {
				return array(
					'valid'   => true,
					'method'  => $stored,
					'details' => $saved,
					'message' => '',
				);
			}

			return array(
				'valid'   => false,
				'method'  => '',
				'details' => array(),
				'message' => __( 'Choose how you would like to be paid and fill in the details.', 'directorist-affiliate' ),
			);
		}

		// Re-using the saved method without re-typing its details.
		if ( $method === $stored && ! $supplied && $methods->is_complete( $stored, $saved ) ) {
			return array(
				'valid'   => true,
				'method'  => $stored,
				'details' => $saved,
				'message' => '',
			);
		}

		$check = $methods->validate( $method, $supplied );

		return array(
			'valid'   => $check['valid'],
			'method'  => $method,
			'details' => $check['details'],
			'message' => $check['message'],
		);
	}

	/**
	 * Persist an affiliate's payout method as their default.
	 *
	 * @param object               $affiliate Affiliate row.
	 * @param string               $method Method key.
	 * @param array<string,string> $details Validated details.
	 *
	 * @return void
	 */
	private function store_payout_method( $affiliate, string $method, array $details ): void {
		$data = array(
			'payout_method'  => $method,
			'payout_details' => $details,
		);

		// Keep payout_email meaningful for methods that carry an address.
		$contact = $this->plugin->payout_methods->contact_email( $method, $details );

		if ( $contact ) {
			$data['payout_email'] = $contact;
		}

		$this->plugin->affiliate->update( (int) $affiliate->id, $data );
	}

	/**
	 * Link builder: search published content of one type by title.
	 *
	 * @return void
	 */
	public function search_content(): void {
		$affiliate = $this->guard_affiliate( 'directorist_affiliate_link_builder' );

		$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

		if ( ! $this->plugin->link_search->is_valid_type( $type ) ) {
			wp_send_json_error( array( 'message' => __( 'Choose what you want to link to.', 'directorist-affiliate' ) ), 400 );
		}

		// Cap the term so a pathological string never reaches the query.
		$results = $this->plugin->link_search->search( $type, mb_substr( $term, 0, 100 ), (string) $affiliate->referral_code );

		wp_send_json_success(
			array(
				'results' => $results,
				'message' => $results
					? ''
					: __( 'Nothing matched. Try a different word from the title.', 'directorist-affiliate' ),
			)
		);
	}

	/**
	 * Link builder: validate a hand-typed URL and return its referral link.
	 *
	 * @return void
	 */
	public function custom_link(): void {
		$affiliate = $this->guard_affiliate( 'directorist_affiliate_link_builder' );

		$url    = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		$result = $this->plugin->link_search->build_custom_link( $url, (string) $affiliate->referral_code );

		if ( ! $result['valid'] ) {
			wp_send_json_error( array( 'message' => $result['message'] ), 400 );
		}

		wp_send_json_success(
			array(
				'link' => $result['link'],
				'url'  => $result['url'],
			)
		);
	}

	/**
	 * Verify nonce and approved-affiliate status for front-end endpoints.
	 *
	 * Sends a JSON error and exits on failure; returns the affiliate row so
	 * the referral code is always taken from the database, never the request.
	 *
	 * @param string $action Nonce action.
	 * @param string $field Request field holding the nonce.
	 *
	 * @return object Approved affiliate row.
	 */
	private function guard_affiliate( string $action, string $field = 'nonce' ): object {
		if ( ! check_ajax_referer( $action, $field, false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed. Please reload the page and try again.', 'directorist-affiliate' ) ),
				403
			);
		}

		$affiliate = is_user_logged_in()
			? $this->plugin->affiliate->get_by_user_id( get_current_user_id() )
			: null;

		if ( ! $affiliate || 'approved' !== $affiliate->status ) {
			wp_send_json_error(
				array( 'message' => __( 'This is only available to approved affiliates.', 'directorist-affiliate' ) ),
				403
			);
		}

		return $affiliate;
	}

	/**
	 * Front-end affiliate application.
	 *
	 * @return void
	 */
	public function register_affiliate(): void {
		if ( ! check_ajax_referer( 'directorist_affiliate_register', 'directorist_affiliate_nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed. Please reload the page and try again.', 'directorist-affiliate' ) ),
				403
			);
		}

		$result = $this->plugin->registration->process_public( $_POST );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		}

		wp_send_json_error( array( 'message' => $result['message'] ) );
	}

	/**
	 * Admin: add affiliate.
	 *
	 * @return void
	 */
	public function add_affiliate(): void {
		$this->guard_admin( 'directorist_affiliate_add_affiliate' );

		$result  = $this->plugin->registration->process_admin( $_POST );
		$message = $this->plugin->registration->admin_message( $result['notice'] );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $message ) );
		}

		wp_send_json_error( array( 'message' => $message ) );
	}

	/**
	 * Admin: save settings.
	 *
	 * @return void
	 */
	public function save_settings(): void {
		$this->guard_admin( 'directorist_affiliate_save_settings' );

		$this->plugin->settings->save( $_POST );

		wp_send_json_success(
			array( 'message' => __( 'Settings saved.', 'directorist-affiliate' ) )
		);
	}

	/**
	 * Admin: bulk mark referrals paid.
	 *
	 * @return void
	 */
	public function mark_paid(): void {
		$this->guard_admin( 'directorist_affiliate_mark_paid' );

		$referral_ids = isset( $_POST['referral_ids'] ) && is_array( $_POST['referral_ids'] )
			? array_map( 'absint', wp_unslash( $_POST['referral_ids'] ) )
			: array();

		if ( empty( $referral_ids ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Select at least one referral to mark as paid.', 'directorist-affiliate' ) )
			);
		}

		$result = $this->plugin->payout->mark_paid_bulk(
			$referral_ids,
			(float) $this->plugin->settings->get( 'minimum_payout', '0.00' )
		);

		if ( ! $result['paid'] && ! $result['skipped'] ) {
			wp_send_json_error(
				array( 'message' => __( 'No payouts recorded. Only approved referrals can be paid.', 'directorist-affiliate' ) )
			);
		}

		$message = sprintf(
			/* translators: %s: number of payouts. */
			_n( '%s payout recorded.', '%s payouts recorded.', $result['paid'], 'directorist-affiliate' ),
			number_format_i18n( $result['paid'] )
		);

		if ( $result['skipped'] > 0 ) {
			$message .= ' ' . sprintf(
				/* translators: %s: number of affiliates. */
				_n(
					'%s affiliate was skipped for being below the minimum payout.',
					'%s affiliates were skipped for being below the minimum payout.',
					$result['skipped'],
					'directorist-affiliate'
				),
				number_format_i18n( $result['skipped'] )
			);
		}

		wp_send_json_success(
			array(
				'message' => $message,
				'paid'    => $result['paid'],
				'skipped' => $result['skipped'],
			)
		);
	}

	/**
	 * Verify capability and nonce for admin endpoints; sends JSON error and exits on failure.
	 *
	 * @param string $action Nonce action.
	 *
	 * @return void
	 */
	private function guard_admin( string $action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You are not allowed to do that.', 'directorist-affiliate' ) ),
				403
			);
		}

		if ( ! check_ajax_referer( $action, false, false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed. Please reload the page and try again.', 'directorist-affiliate' ) ),
				403
			);
		}
	}
}

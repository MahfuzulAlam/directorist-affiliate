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

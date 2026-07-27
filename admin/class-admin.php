<?php
/**
 * Admin screens.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin menu, assets, and screen rendering.
 *
 * Form/action processing lives in Directorist_Affiliate_Admin_Actions.
 */
final class Directorist_Affiliate_Admin {
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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public function admin_menu(): void {
		$capability = 'manage_options';

		add_menu_page(
			__( 'Directorist Affiliate', 'directorist-affiliate' ),
			__( 'Directorist Affiliate', 'directorist-affiliate' ),
			$capability,
			'directorist-affiliate',
			array( $this, 'dashboard_page' ),
			'dashicons-networking',
			56
		);

		add_submenu_page( 'directorist-affiliate', __( 'Dashboard', 'directorist-affiliate' ), __( 'Dashboard', 'directorist-affiliate' ), $capability, 'directorist-affiliate', array( $this, 'dashboard_page' ) );
		add_submenu_page( 'directorist-affiliate', __( 'Affiliates', 'directorist-affiliate' ), __( 'Affiliates', 'directorist-affiliate' ), $capability, 'directorist-affiliate-affiliates', array( $this, 'affiliates_page' ) );
		add_submenu_page( 'directorist-affiliate', __( 'Referrals', 'directorist-affiliate' ), __( 'Referrals', 'directorist-affiliate' ), $capability, 'directorist-affiliate-referrals', array( $this, 'referrals_page' ) );
		add_submenu_page( 'directorist-affiliate', __( 'Visits', 'directorist-affiliate' ), __( 'Visits', 'directorist-affiliate' ), $capability, 'directorist-affiliate-visits', array( $this, 'visits_page' ) );
		add_submenu_page( 'directorist-affiliate', __( 'Payouts', 'directorist-affiliate' ), __( 'Payouts', 'directorist-affiliate' ), $capability, 'directorist-affiliate-payouts', array( $this, 'payouts_page' ) );
		add_submenu_page( 'directorist-affiliate', __( 'Settings', 'directorist-affiliate' ), __( 'Settings', 'directorist-affiliate' ), $capability, 'directorist-affiliate-settings', array( $this, 'settings_page' ) );
	}

	/**
	 * Register admin assets.
	 *
	 * @param string $hook Hook suffix.
	 *
	 * @return void
	 */
	public function admin_assets( string $hook ): void {
		if ( false === strpos( $hook, 'directorist-affiliate' ) ) {
			return;
		}

		wp_enqueue_style(
			'directorist-affiliate-admin',
			DIRECTORIST_AFFILIATE_URL . 'assets/css/directorist-affiliate.css',
			array(),
			DIRECTORIST_AFFILIATE_VERSION
		);

		wp_enqueue_script(
			'directorist-affiliate-admin',
			DIRECTORIST_AFFILIATE_URL . 'assets/js/directorist-affiliate.js',
			array(),
			DIRECTORIST_AFFILIATE_VERSION,
			true
		);

		wp_localize_script(
			'directorist-affiliate-admin',
			'directoristAffiliate',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'submittingLabel' => __( 'Submitting…', 'directorist-affiliate' ),
				'genericError'    => __( 'Something went wrong. Please try again.', 'directorist-affiliate' ),
			)
		);
	}

	/**
	 * Dashboard page.
	 *
	 * @return void
	 */
	public function dashboard_page(): void {
		$this->render(
			'dashboard.php',
			array(
				'total_affiliates'    => $this->plugin->affiliate->count(),
				'pending_affiliates'  => $this->plugin->affiliate->count( 'pending' ),
				'total_visits'        => $this->plugin->tracking->count(),
				'total_referrals'     => $this->plugin->referral->count(),
				'pending_commission'  => $this->plugin->referral->sum_commission( 'pending' ),
				'approved_commission' => $this->plugin->referral->sum_commission( 'approved' ),
				'paid_commission'     => $this->plugin->referral->sum_commission( 'paid' ),
			)
		);
	}

	/**
	 * Affiliates page.
	 *
	 * @return void
	 */
	public function affiliates_page(): void {
		$selected_affiliate_id = isset( $_GET['view_affiliate'] ) ? absint( $_GET['view_affiliate'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice                = isset( $_GET['directorist_affiliate_notice'] ) ? sanitize_key( wp_unslash( $_GET['directorist_affiliate_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->render(
			'affiliates.php',
			array(
				'affiliates'         => $this->plugin->affiliate->list( array( 'limit' => 100 ) ),
				'selected_affiliate' => $selected_affiliate_id ? $this->plugin->affiliate->get( $selected_affiliate_id ) : null,
				'notice'             => $notice,
				'plugin'             => $this->plugin,
			)
		);
	}

	/**
	 * Referrals page.
	 *
	 * @return void
	 */
	public function referrals_page(): void {
		$this->render(
			'referrals.php',
			array(
				'referrals' => $this->plugin->referral->list( array( 'limit' => 100 ) ),
				'notice'    => isset( $_GET['directorist_affiliate_notice'] ) ? sanitize_key( wp_unslash( $_GET['directorist_affiliate_notice'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'plugin'    => $this->plugin,
			)
		);
	}

	/**
	 * Visits page.
	 *
	 * @return void
	 */
	public function visits_page(): void {
		$this->render(
			'visits.php',
			array(
				'visits' => $this->plugin->tracking->list( 100 ),
				'plugin' => $this->plugin,
			)
		);
	}

	/**
	 * Payouts page.
	 *
	 * @return void
	 */
	public function payouts_page(): void {
		$approved = $this->plugin->referral->list(
			array(
				'status' => 'approved',
				'limit'  => 500,
			)
		);

		$this->render(
			'payouts.php',
			array(
				'approved_referrals' => $approved,
				'payouts'            => $this->plugin->payout->list( 100 ),
				'minimum_payout'     => (float) $this->plugin->settings->get( 'minimum_payout', '0.00' ),
				'paid_count'         => isset( $_GET['directorist_affiliate_paid'] ) ? absint( $_GET['directorist_affiliate_paid'] ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'skipped_count'      => isset( $_GET['directorist_affiliate_skipped'] ) ? absint( $_GET['directorist_affiliate_skipped'] ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'plugin'             => $this->plugin,
			)
		);
	}

	/**
	 * Settings page.
	 *
	 * @return void
	 */
	public function settings_page(): void {
		$this->render(
			'settings.php',
			array(
				'settings' => $this->plugin->settings->all(),
			)
		);
	}

	/**
	 * Render an admin view.
	 *
	 * @param string              $view View file name inside admin/views/.
	 * @param array<string,mixed> $context Context.
	 *
	 * @return void
	 */
	private function render( string $view, array $context = array() ): void {
		Directorist_Affiliate_View::output( 'admin/views/' . $view, $context );
	}
}

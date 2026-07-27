<?php
/**
 * Shortcodes.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Frontend shortcodes for affiliate registration and dashboard.
 */
final class Directorist_Affiliate_Shortcodes {
	/**
	 * Plugin instance.
	 *
	 * @var Directorist_Affiliate_Plugin
	 */
	private $plugin;

	/**
	 * Result of the registration POST for this request, if any.
	 *
	 * Acts as a once-guard: themes and SEO plugins can render shortcodes
	 * several times per request, and the submission must only be processed once.
	 *
	 * @var array{success:bool,message:string}|null
	 */
	private $registration_result = null;

	/**
	 * Constructor.
	 *
	 * @param Directorist_Affiliate_Plugin $plugin Plugin instance.
	 */
	public function __construct( Directorist_Affiliate_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register shortcodes and the no-JS form fallback.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( 'directorist_affiliate_registration', array( $this, 'registration_shortcode' ) );
		add_shortcode( 'directorist_affiliate_dashboard', array( $this, 'dashboard_shortcode' ) );
		add_action( 'template_redirect', array( $this, 'capture_registration_post' ) );
	}

	/**
	 * Process the non-JS registration POST early, before any rendering.
	 *
	 * @return void
	 */
	public function capture_registration_post(): void {
		if ( empty( $_POST['directorist_affiliate_register'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		$this->handle_registration_post();
	}

	/**
	 * Registration shortcode.
	 *
	 * @return string
	 */
	public function registration_shortcode(): string {
		wp_enqueue_style( 'directorist-affiliate' );
		wp_enqueue_script( 'directorist-affiliate' );

		if ( ! empty( $_POST['directorist_affiliate_register'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$this->handle_registration_post();
		}

		$user    = wp_get_current_user();
		$context = array(
			'message'      => $this->registration_result ? $this->registration_result['message'] : '',
			'message_type' => $this->registration_result ? ( $this->registration_result['success'] ? 'success' : 'error' ) : '',
			'user'         => $user instanceof WP_User ? $user : null,
		);

		return $this->render( 'registration-form.php', $context );
	}

	/**
	 * Dashboard shortcode.
	 *
	 * @return string
	 */
	public function dashboard_shortcode(): string {
		wp_enqueue_style( 'directorist-affiliate' );
		wp_enqueue_script( 'directorist-affiliate' );

		if ( ! is_user_logged_in() ) {
			return '<div class="directorist-affiliate-notice">' . esc_html__( 'Please log in to view your affiliate dashboard.', 'directorist-affiliate' ) . '</div>';
		}

		$affiliate = $this->plugin->affiliate->get_by_user_id( get_current_user_id() );

		if ( ! $affiliate ) {
			return '<div class="directorist-affiliate-notice">' . esc_html__( 'You have not applied for the affiliate program yet.', 'directorist-affiliate' ) . '</div>';
		}

		$referrals = $this->plugin->referral->list(
			array(
				'affiliate_id' => (int) $affiliate->id,
				'limit'        => 20,
			)
		);

		return $this->render(
			'affiliate-dashboard.php',
			array(
				'plugin'               => $this->plugin,
				'affiliate'            => $affiliate,
				'referrals'            => $referrals,
				'visits'               => $this->plugin->tracking->count( (int) $affiliate->id ),
				'total_referrals'      => $this->plugin->referral->count( '', (int) $affiliate->id ),
				'pending_commission'   => $this->plugin->referral->sum_commission( 'pending', (int) $affiliate->id ),
				'approved_commission'  => $this->plugin->referral->sum_commission( 'approved', (int) $affiliate->id ),
				'paid_commission'      => $this->plugin->referral->sum_commission( 'paid', (int) $affiliate->id ),
				'referral_url'         => add_query_arg( $this->plugin->settings->get( 'ref_param', 'ref' ), $affiliate->referral_code, home_url( '/' ) ),
				'payout_instructions'  => $this->plugin->settings->get( 'payout_instructions', '' ),
			)
		);
	}

	/**
	 * Handle the registration POST exactly once per request.
	 *
	 * @return void
	 */
	private function handle_registration_post(): void {
		if ( null !== $this->registration_result ) {
			return;
		}

		if ( ! isset( $_POST['directorist_affiliate_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['directorist_affiliate_nonce'] ) ), 'directorist_affiliate_register' ) ) {
			$this->registration_result = array(
				'success' => false,
				'message' => __( 'Security check failed. Please try again.', 'directorist-affiliate' ),
			);

			return;
		}

		$this->registration_result = $this->plugin->registration->process_public( $_POST );
	}

	/**
	 * Render a public view.
	 *
	 * @param string              $view View file name inside public/views/.
	 * @param array<string,mixed> $context Context.
	 *
	 * @return string
	 */
	private function render( string $view, array $context = array() ): string {
		return Directorist_Affiliate_View::render( 'public/views/' . $view, $context );
	}
}

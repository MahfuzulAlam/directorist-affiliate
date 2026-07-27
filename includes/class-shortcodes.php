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
	 * Constructor.
	 *
	 * @param Directorist_Affiliate_Plugin $plugin Plugin instance.
	 */
	public function __construct( Directorist_Affiliate_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register shortcodes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( 'directorist_affiliate_registration', array( $this, 'registration_shortcode' ) );
		add_shortcode( 'directorist_affiliate_dashboard', array( $this, 'dashboard_shortcode' ) );
	}

	/**
	 * Registration shortcode.
	 *
	 * @return string
	 */
	public function registration_shortcode(): string {
		wp_enqueue_style( 'directorist-affiliate' );

		$message = $this->maybe_handle_registration();
		$user    = wp_get_current_user();
		$context = array(
			'message' => $message,
			'user'    => $user instanceof WP_User ? $user : null,
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
	 * Handle registration POST.
	 *
	 * @return string
	 */
	private function maybe_handle_registration(): string {
		if ( empty( $_POST['directorist_affiliate_register'] ) ) {
			return '';
		}

		if ( ! isset( $_POST['directorist_affiliate_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['directorist_affiliate_nonce'] ) ), 'directorist_affiliate_register' ) ) {
			return __( 'Security check failed. Please try again.', 'directorist-affiliate' );
		}

		// Honeypot: bots fill the hidden field; pretend success without saving.
		if ( ! empty( $_POST['da_hp'] ) ) {
			return __( 'Your affiliate application was submitted and is pending review.', 'directorist-affiliate' );
		}

		$name               = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email              = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$website            = isset( $_POST['website'] ) ? esc_url_raw( wp_unslash( $_POST['website'] ) ) : '';
		$promotional_method = isset( $_POST['promotional_method'] ) ? sanitize_text_field( wp_unslash( $_POST['promotional_method'] ) ) : '';
		$payout_email       = isset( $_POST['payout_email'] ) ? sanitize_email( wp_unslash( $_POST['payout_email'] ) ) : '';
		$application_note   = isset( $_POST['application_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['application_note'] ) ) : '';

		if ( ! $name || ! is_email( $email ) || ! is_email( $payout_email ) || ( ! $website && ! $promotional_method ) ) {
			return __( 'Please complete all required fields with valid values.', 'directorist-affiliate' );
		}

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			$user = get_user_by( 'email', $email );

			if ( $user ) {
				return __( 'An account already exists with this email address. Please log in before applying.', 'directorist-affiliate' );
			}

			$user_id = $this->plugin->affiliate->register_user( $name, $email );

			if ( is_wp_error( $user_id ) ) {
				return $user_id->get_error_message();
			}
		}

		$existing = $this->plugin->affiliate->get_by_user_id( $user_id );

		if ( $existing ) {
			return __( 'You already have an affiliate application.', 'directorist-affiliate' );
		}

		$affiliate_id = $this->plugin->affiliate->create(
			array(
				'user_id'            => $user_id,
				'payout_email'       => $payout_email,
				'website'            => $website,
				'promotional_method' => $promotional_method,
				'application_note'   => $application_note,
			)
		);

		if ( ! $affiliate_id ) {
			return __( 'Unable to submit your application. Please try again.', 'directorist-affiliate' );
		}

		$affiliate = $this->plugin->affiliate->get( $affiliate_id );

		if ( $affiliate ) {
			$this->plugin->email->new_application( $affiliate );
		}

		return __( 'Your affiliate application was submitted and is pending review.', 'directorist-affiliate' );
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

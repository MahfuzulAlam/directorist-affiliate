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
		add_shortcode( 'directorist_affiliate_link', array( $this, 'link_shortcode' ) );
		add_action( 'template_redirect', array( $this, 'capture_registration_post' ) );
		add_action( 'template_redirect', array( $this, 'redirect_existing_affiliate' ), 5 );
	}

	/**
	 * Send an existing affiliate from the application page to their dashboard.
	 *
	 * Runs on template_redirect because a shortcode renders inside the_content,
	 * by which point headers are already sent.
	 *
	 * @return void
	 */
	public function redirect_existing_affiliate(): void {
		if ( is_admin() || wp_doing_ajax() || ! is_singular() || ! is_user_logged_in() ) {
			return;
		}

		// A POST is a submission in flight; let it finish before redirecting.
		if ( ! empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		$post = get_post();

		if ( ! $post || ! has_shortcode( (string) $post->post_content, 'directorist_affiliate_registration' ) ) {
			return;
		}

		// A page showing both blocks already gives them their dashboard.
		if ( has_shortcode( (string) $post->post_content, 'directorist_affiliate_dashboard' ) ) {
			return;
		}

		if ( ! $this->plugin->affiliate->get_by_user_id( get_current_user_id() ) ) {
			return;
		}

		$url = $this->dashboard_url();

		// Never bounce a page to itself.
		if ( ! $url || untrailingslashit( $url ) === untrailingslashit( (string) get_permalink( $post ) ) ) {
			return;
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Enqueue and localize the link builder script.
	 *
	 * @param string $code Affiliate referral code.
	 *
	 * @return void
	 */
	private function enqueue_link_builder( string $code ): void {
		wp_enqueue_script( 'directorist-affiliate-link-builder' );

		wp_localize_script(
			'directorist-affiliate-link-builder',
			'directoristAffiliateLinkBuilder',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'directorist_affiliate_link_builder' ),
				'minLength' => Directorist_Affiliate_Link_Search::MIN_TERM_LENGTH,
				'homeLink'  => $this->referral_url( $code ),
				'shareText' => sprintf(
					/* translators: %s: site name. */
					__( 'I recommend %s — take a look:', 'directorist-affiliate' ),
					get_bloginfo( 'name' )
				),
				'i18n'      => array(
					'searching'  => __( 'Searching…', 'directorist-affiliate' ),
					'noResults'  => __( 'Nothing matched. Try a different word from the title.', 'directorist-affiliate' ),
					'error'      => __( 'Could not search right now. Please try again.', 'directorist-affiliate' ),
					'typeMore'   => sprintf(
						/* translators: %d: minimum number of characters. */
						_n( 'Type at least %d character.', 'Type at least %d characters.', Directorist_Affiliate_Link_Search::MIN_TERM_LENGTH, 'directorist-affiliate' ),
						Directorist_Affiliate_Link_Search::MIN_TERM_LENGTH
					),
					'resultsFound' => __( '%d results available. Use the arrow keys to choose one.', 'directorist-affiliate' ),
					'checking'   => __( 'Checking the address…', 'directorist-affiliate' ),
				),
			)
		);
	}

	/**
	 * URL of the page carrying the affiliate dashboard.
	 *
	 * Prefers the page chosen in settings, then Directorist's own user
	 * dashboard, which hosts the Affiliate tab.
	 *
	 * @return string Empty string when no dashboard destination exists.
	 */
	public function dashboard_url(): string {
		$page_id = absint( $this->plugin->settings->get( 'dashboard_page', 0 ) );
		$url     = '';

		if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
			$url = (string) get_permalink( $page_id );
		}

		if ( ! $url && function_exists( 'get_directorist_option' ) ) {
			$directorist_dashboard = absint( get_directorist_option( 'user_dashboard', 0 ) );

			if ( $directorist_dashboard ) {
				$url = (string) get_permalink( $directorist_dashboard );
			}
		}

		/**
		 * Filters where affiliates are sent to view their dashboard.
		 *
		 * @param string $url Dashboard URL, or '' when none is configured.
		 */
		return (string) apply_filters( 'directorist_affiliate_dashboard_url', $url );
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

		if ( ! $this->plugin->registration->applications_open() ) {
			return $this->notice( __( 'Affiliate applications are currently closed. Please check back later.', 'directorist-affiliate' ) );
		}

		if ( $this->plugin->registration->requires_login() && ! is_user_logged_in() ) {
			return $this->notice(
				sprintf(
					/* translators: %s: login link. */
					__( 'Please %s to apply for the affiliate program.', 'directorist-affiliate' ),
					'<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'log in', 'directorist-affiliate' ) . '</a>'
				),
				true
			);
		}

		// An existing applicant is normally redirected on template_redirect;
		// this covers the cases a redirect cannot reach — no dashboard page
		// configured, or the shortcode rendered outside post content.
		if ( is_user_logged_in() ) {
			$existing = $this->plugin->affiliate->get_by_user_id( get_current_user_id() );

			if ( $existing ) {
				$dashboard = $this->dashboard_url();

				if ( $dashboard ) {
					return $this->notice(
						sprintf(
							/* translators: %s: link to the affiliate dashboard. */
							__( 'You have already applied to the affiliate program. %s', 'directorist-affiliate' ),
							'<a href="' . esc_url( $dashboard ) . '">' . esc_html__( 'Go to your dashboard', 'directorist-affiliate' ) . '</a>'
						),
						true
					);
				}

				return $this->notice( __( 'You have already applied to the affiliate program. Your dashboard shows the current status.', 'directorist-affiliate' ) );
			}
		}

		if ( ! empty( $_POST['directorist_affiliate_register'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$this->handle_registration_post();
		}

		$user    = wp_get_current_user();
		$context = array(
			'message'         => $this->registration_result ? $this->registration_result['message'] : '',
			'message_type'    => $this->registration_result ? ( $this->registration_result['success'] ? 'success' : 'error' ) : '',
			'user'            => $user instanceof WP_User ? $user : null,
			'terms'           => $this->plugin->commission->program_terms(),
			'cookie_duration' => absint( $this->plugin->settings->get( 'cookie_duration', 30 ) ),
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
			return $this->notice( __( 'Please log in to view your affiliate dashboard.', 'directorist-affiliate' ) );
		}

		$affiliate = $this->plugin->affiliate->get_by_user_id( get_current_user_id() );

		if ( ! $affiliate ) {
			return $this->notice( __( 'You have not applied for the affiliate program yet.', 'directorist-affiliate' ) );
		}

		$referrals = $this->plugin->referral->list(
			array(
				'affiliate_id' => (int) $affiliate->id,
				'limit'        => 20,
			)
		);

		// The link builder is only usable by approved affiliates, so its
		// script loads only for them — and only on this shortcode's page.
		if ( 'approved' === $affiliate->status ) {
			$this->enqueue_link_builder( (string) $affiliate->referral_code );
		}

		return $this->render(
			'affiliate-dashboard.php',
			array(
				'plugin'              => $this->plugin,
				'affiliate'           => $affiliate,
				'referrals'           => $referrals,
				'link_types'          => $this->plugin->link_search->types(),
				'payouts'             => $this->plugin->payout->list_by_affiliate( (int) $affiliate->id, 20 ),
				'visits'              => $this->plugin->tracking->count( array( 'affiliate_id' => (int) $affiliate->id ) ),
				'converted_visits'    => $this->plugin->tracking->count(
					array(
						'affiliate_id' => (int) $affiliate->id,
						'converted'    => 1,
					)
				),
				'total_referrals'     => $this->plugin->referral->count( array( 'affiliate_id' => (int) $affiliate->id ) ),
				'pending_commission'  => $this->plugin->referral->sum_commission( 'pending', (int) $affiliate->id ),
				'approved_commission' => $this->plugin->referral->sum_commission( 'approved', (int) $affiliate->id ),
				'paid_commission'     => $this->plugin->referral->sum_commission( 'paid', (int) $affiliate->id ),
				'referral_url'        => $this->referral_url( (string) $affiliate->referral_code ),
				'payout_instructions' => $this->plugin->settings->get( 'payout_instructions', '' ),
				'minimum_payout'      => (float) $this->plugin->settings->get( 'minimum_payout', '0.00' ),
				'open_request'        => $this->plugin->payout->open_request( (int) $affiliate->id ),
				'payout_methods'      => $this->plugin->payout_methods->enabled(),
				'payout_method'       => (string) ( $affiliate->payout_method ?? '' ),
				'payout_details'      => $this->plugin->payout_methods->decode( $affiliate->payout_details ?? '' ),
				'payout_ready'        => $this->plugin->payout_methods->is_complete(
					(string) ( $affiliate->payout_method ?? '' ),
					$this->plugin->payout_methods->decode( $affiliate->payout_details ?? '' )
				),
				'payout_summary'      => $this->plugin->payout_methods->summary(
					(string) ( $affiliate->payout_method ?? '' ),
					$this->plugin->payout_methods->decode( $affiliate->payout_details ?? '' )
				),
				'cookie_duration'     => absint( $this->plugin->settings->get( 'cookie_duration', 30 ) ),
				'site_name'           => get_bloginfo( 'name' ),
			)
		);
	}

	/**
	 * Referral link shortcode: [directorist_affiliate_link page="add-listing"].
	 *
	 * Renders the current affiliate's link to a known Directorist page, or an
	 * empty string when the visitor is not an approved affiliate.
	 *
	 * @param array<string,string>|string $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function link_shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'page' => 'home',
				'text' => '',
				'url'  => '',
			),
			is_array( $atts ) ? $atts : array(),
			'directorist_affiliate_link'
		);

		if ( ! is_user_logged_in() ) {
			return '';
		}

		$affiliate = $this->plugin->affiliate->get_by_user_id( get_current_user_id() );

		if ( ! $affiliate || 'approved' !== $affiliate->status ) {
			return '';
		}

		$base = $atts['url'] ? esc_url_raw( $atts['url'] ) : $this->page_url( sanitize_key( $atts['page'] ) );

		// Only same-site destinations may carry the referral code.
		if ( ! $base || 0 !== strpos( $base, home_url() ) ) {
			$base = home_url( '/' );
		}

		$url  = $this->referral_url( (string) $affiliate->referral_code, $base );
		$text = $atts['text'] ? sanitize_text_field( $atts['text'] ) : $url;

		return sprintf(
			'<a class="directorist-affiliate-inline-link" href="%1$s">%2$s</a>',
			esc_url( $url ),
			esc_html( $text )
		);
	}

	/**
	 * Append the referral code to a URL.
	 *
	 * @param string $code Referral code.
	 * @param string $base Base URL; defaults to the site home.
	 *
	 * @return string
	 */
	private function referral_url( string $code, string $base = '' ): string {
		return add_query_arg(
			rawurlencode( (string) $this->plugin->settings->get( 'ref_param', 'ref' ) ),
			rawurlencode( $code ),
			$base ? $base : home_url( '/' )
		);
	}

	/**
	 * Resolve a named Directorist page to its URL.
	 *
	 * @param string $page Page key (home|add-listing|all-listings|dashboard|checkout).
	 *
	 * @return string Empty string when the page is unknown or not configured.
	 */
	private function page_url( string $page ): string {
		if ( 'home' === $page ) {
			return home_url( '/' );
		}

		$option_map = array(
			'add-listing'  => 'add_listing_page',
			'all-listings' => 'all_listing_page',
			'dashboard'    => 'user_dashboard',
			'checkout'     => 'checkout_page',
		);

		if ( ! isset( $option_map[ $page ] ) || ! function_exists( 'get_directorist_option' ) ) {
			return '';
		}

		$page_id = absint( get_directorist_option( $option_map[ $page ], 0 ) );

		return $page_id ? (string) get_permalink( $page_id ) : '';
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
	 * Wrap a message in the plugin's front-end notice markup.
	 *
	 * @param string $message Message text.
	 * @param bool   $allow_links Whether the message contains safe anchor markup.
	 *
	 * @return string
	 */
	private function notice( string $message, bool $allow_links = false ): string {
		$content = $allow_links
			? wp_kses( $message, array( 'a' => array( 'href' => array() ) ) )
			: esc_html( $message );

		return '<div class="directorist-affiliate-wrap"><div class="directorist-affiliate-notice">' . $content . '</div></div>';
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

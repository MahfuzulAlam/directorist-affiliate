<?php
/**
 * Admin screens.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Single tabbed "Affiliate" page under the Directorist admin menu.
 *
 * Form/action processing lives in Directorist_Affiliate_Admin_Actions.
 */
final class Directorist_Affiliate_Admin {
	/**
	 * Page slug.
	 */
	public const PAGE_SLUG = 'directorist-affiliate';

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
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect_legacy_pages' ) );
	}

	/**
	 * Directorist parent menu slug (the listings CPT menu).
	 *
	 * @return string
	 */
	public static function parent_slug(): string {
		$post_type = defined( 'ATBDP_POST_TYPE' ) ? ATBDP_POST_TYPE : 'at_biz_dir';

		return 'edit.php?post_type=' . $post_type;
	}

	/**
	 * Build the URL of the Affiliate page (optionally for a tab).
	 *
	 * @param string               $tab Tab slug.
	 * @param array<string,mixed>  $args Extra query args.
	 *
	 * @return string
	 */
	public static function page_url( string $tab = '', array $args = array() ): string {
		$query = array(
			'post_type' => defined( 'ATBDP_POST_TYPE' ) ? ATBDP_POST_TYPE : 'at_biz_dir',
			'page'      => self::PAGE_SLUG,
		);

		if ( $tab ) {
			$query['tab'] = sanitize_key( $tab );
		}

		return add_query_arg( array_merge( $query, $args ), admin_url( 'edit.php' ) );
	}

	/**
	 * Tab definitions.
	 *
	 * @return array<string,array{label:string,icon:string,renderer:string}>
	 */
	private function tabs(): array {
		return array(
			'dashboard' => array(
				'label'    => __( 'Dashboard', 'directorist-affiliate' ),
				'icon'     => 'dashicons-chart-bar',
				'renderer' => 'render_dashboard_tab',
			),
			'affiliates' => array(
				'label'    => __( 'Affiliates', 'directorist-affiliate' ),
				'icon'     => 'dashicons-groups',
				'renderer' => 'render_affiliates_tab',
			),
			'referrals' => array(
				'label'    => __( 'Referrals', 'directorist-affiliate' ),
				'icon'     => 'dashicons-money-alt',
				'renderer' => 'render_referrals_tab',
			),
			'visits' => array(
				'label'    => __( 'Visits', 'directorist-affiliate' ),
				'icon'     => 'dashicons-visibility',
				'renderer' => 'render_visits_tab',
			),
			'payouts' => array(
				'label'    => __( 'Payouts', 'directorist-affiliate' ),
				'icon'     => 'dashicons-bank',
				'renderer' => 'render_payouts_tab',
			),
			'settings' => array(
				'label'    => __( 'Settings', 'directorist-affiliate' ),
				'icon'     => 'dashicons-admin-generic',
				'renderer' => 'render_settings_tab',
			),
		);
	}

	/**
	 * Currently requested tab.
	 *
	 * @return string
	 */
	private function current_tab(): string {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return array_key_exists( $tab, $this->tabs() ) ? $tab : 'dashboard';
	}

	/**
	 * Register the Affiliate page under the Directorist menu.
	 *
	 * @return void
	 */
	public function admin_menu(): void {
		add_submenu_page(
			self::parent_slug(),
			__( 'Affiliate', 'directorist-affiliate' ),
			__( 'Affiliate', 'directorist-affiliate' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Redirect bookmarks to the removed standalone pages to their tab.
	 *
	 * @return void
	 */
	public function maybe_redirect_legacy_pages(): void {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$legacy = array(
			'directorist-affiliate-affiliates' => 'affiliates',
			'directorist-affiliate-referrals'  => 'referrals',
			'directorist-affiliate-visits'     => 'visits',
			'directorist-affiliate-payouts'    => 'payouts',
			'directorist-affiliate-settings'   => 'settings',
		);

		if ( ! isset( $legacy[ $page ] ) ) {
			return;
		}

		$args = array();

		foreach ( wp_unslash( $_GET ) as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $key, array( 'page', 'post_type', 'tab' ), true ) || ! is_scalar( $value ) ) {
				continue;
			}

			$args[ sanitize_key( $key ) ] = sanitize_text_field( (string) $value );
		}

		wp_safe_redirect( self::page_url( $legacy[ $page ], $args ) );
		exit;
	}

	/**
	 * Register admin assets.
	 *
	 * @param string $hook Hook suffix.
	 *
	 * @return void
	 */
	public function admin_assets( string $hook ): void {
		if ( false === strpos( $hook, self::PAGE_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'directorist-affiliate-admin',
			DIRECTORIST_AFFILIATE_URL . 'assets/css/directorist-affiliate.css',
			array( 'dashicons' ),
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
	 * Render the tabbed page shell and the active tab.
	 *
	 * @return void
	 */
	public function render_page(): void {
		$tabs    = $this->tabs();
		$current = $this->current_tab();
		?>
		<div class="wrap directorist-affiliate-admin">
			<div class="directorist-affiliate-shell">
				<div class="directorist-affiliate-admin-header">
					<h1>
						<?php esc_html_e( 'Affiliate', 'directorist-affiliate' ); ?>
						<span class="directorist-affiliate-version"><?php echo esc_html( 'v' . DIRECTORIST_AFFILIATE_VERSION ); ?></span>
					</h1>
					<p class="directorist-affiliate-admin-tagline"><?php esc_html_e( 'Referral tracking and commissions for your directory.', 'directorist-affiliate' ); ?></p>
				</div>
				<hr class="wp-header-end" />

				<nav class="directorist-affiliate-tab-nav" aria-label="<?php esc_attr_e( 'Affiliate sections', 'directorist-affiliate' ); ?>">
					<?php foreach ( $tabs as $slug => $tab ) : ?>
						<a href="<?php echo esc_url( self::page_url( $slug ) ); ?>" class="directorist-affiliate-tab-link<?php echo $slug === $current ? ' is-active' : ''; ?>"<?php echo $slug === $current ? ' aria-current="page"' : ''; ?>>
							<span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>" aria-hidden="true"></span>
							<?php echo esc_html( $tab['label'] ); ?>
						</a>
					<?php endforeach; ?>
				</nav>

				<div class="directorist-affiliate-tab-panel">
					<?php $this->{$tabs[ $current ]['renderer']}(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Dashboard tab.
	 *
	 * @return void
	 */
	private function render_dashboard_tab(): void {
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
	 * Affiliates tab.
	 *
	 * @return void
	 */
	private function render_affiliates_tab(): void {
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
	 * Referrals tab.
	 *
	 * @return void
	 */
	private function render_referrals_tab(): void {
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
	 * Visits tab.
	 *
	 * @return void
	 */
	private function render_visits_tab(): void {
		$this->render(
			'visits.php',
			array(
				'visits' => $this->plugin->tracking->list( 100 ),
				'plugin' => $this->plugin,
			)
		);
	}

	/**
	 * Payouts tab.
	 *
	 * @return void
	 */
	private function render_payouts_tab(): void {
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
	 * Settings tab.
	 *
	 * @return void
	 */
	private function render_settings_tab(): void {
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

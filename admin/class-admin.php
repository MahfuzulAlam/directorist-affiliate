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
	 * Rows per page on list tabs.
	 */
	public const PER_PAGE = 20;

	/**
	 * Hook suffix of the Affiliate screen, used to scope asset loading.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

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
		$this->hook_suffix = (string) add_submenu_page(
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
		// Exact hook match: these assets load on the Affiliate screen and nowhere else.
		if ( ! $this->hook_suffix || $hook !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'directorist-affiliate-admin',
			DIRECTORIST_AFFILIATE_URL . 'assets/css/directorist-affiliate-admin.css',
			array( 'dashicons' ),
			DIRECTORIST_AFFILIATE_VERSION
		);

		wp_enqueue_script(
			'directorist-affiliate-admin',
			DIRECTORIST_AFFILIATE_URL . 'assets/js/directorist-affiliate.js',
			array(),
			DIRECTORIST_AFFILIATE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
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
		$tabs      = $this->tabs();
		$current   = $this->current_tab();
		$is_active = $this->plugin->settings->is_enabled();
		$pending   = $this->plugin->affiliate->count( 'pending' );
		?>
		<div class="wrap directorist-affiliate-admin">
			<div class="directorist-affiliate-shell">
				<div class="directorist-affiliate-admin-header">
					<div>
						<h1>
							<?php esc_html_e( 'Affiliate', 'directorist-affiliate' ); ?>
							<span class="directorist-affiliate-version"><?php echo esc_html( 'v' . DIRECTORIST_AFFILIATE_VERSION ); ?></span>
						</h1>
						<p class="directorist-affiliate-admin-tagline"><?php esc_html_e( 'Referral tracking and commissions for your directory.', 'directorist-affiliate' ); ?></p>
					</div>
					<a class="directorist-affiliate-admin-status<?php echo $is_active ? ' is-on' : ''; ?>" href="<?php echo esc_url( self::page_url( 'settings' ) ); ?>">
						<span class="directorist-affiliate-dot" aria-hidden="true"></span>
						<?php echo $is_active ? esc_html__( 'Program active', 'directorist-affiliate' ) : esc_html__( 'Program disabled', 'directorist-affiliate' ); ?>
					</a>
				</div>
				<hr class="wp-header-end" />

				<nav class="directorist-affiliate-tab-nav" aria-label="<?php esc_attr_e( 'Affiliate sections', 'directorist-affiliate' ); ?>">
					<?php foreach ( $tabs as $slug => $tab ) : ?>
						<a href="<?php echo esc_url( self::page_url( $slug ) ); ?>" class="directorist-affiliate-tab-link<?php echo $slug === $current ? ' is-active' : ''; ?>"<?php echo $slug === $current ? ' aria-current="page"' : ''; ?>>
							<span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>" aria-hidden="true"></span>
							<?php echo esc_html( $tab['label'] ); ?>
							<?php if ( 'affiliates' === $slug && $pending > 0 ) : ?>
								<span class="directorist-affiliate-badge is-pending"><?php echo esc_html( number_format_i18n( $pending ) ); ?></span>
							<?php endif; ?>
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
	 * Current 1-based page number from the URL.
	 *
	 * @return int
	 */
	private function current_paged(): int {
		$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return max( 1, $paged );
	}

	/**
	 * Read the shared list filters out of the request.
	 *
	 * Returns both the raw selections (echoed back into the form and carried
	 * through pagination links) and the resolved date bounds for the query.
	 *
	 * @param string[] $keys Extra scalar filter keys to read, e.g. 'status'.
	 *
	 * @return array<string,mixed>
	 */
	private function request_filters( array $keys = array() ): array {
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
	 * Reduce a filter set to the non-empty query args that pagination must keep.
	 *
	 * @param array<string,mixed>  $filters Filters from request_filters().
	 * @param array<string,string> $map Filter key => query var name.
	 *
	 * @return array<string,string>
	 */
	private function pagination_args( array $filters, array $map ): array {
		$args = array();

		foreach ( $map as $key => $query_var ) {
			if ( ! empty( $filters[ $key ] ) ) {
				$args[ $query_var ] = (string) $filters[ $key ];
			}
		}

		return $args;
	}

	/**
	 * Query vars every list filter bar shares.
	 *
	 * @return array<string,string>
	 */
	private function base_filter_map(): array {
		return array(
			'affiliate_id' => 'affiliate',
			'range'        => Directorist_Affiliate_Date_Range::PARAM_PRESET,
			'from'         => Directorist_Affiliate_Date_Range::PARAM_FROM,
			'to'           => Directorist_Affiliate_Date_Range::PARAM_TO,
		);
	}

	/**
	 * Build pagination-links markup for a tab.
	 *
	 * @param string               $tab Tab slug.
	 * @param int                  $total Total rows.
	 * @param int                  $paged Current page.
	 * @param array<string,string> $args Extra query args preserved across pages.
	 *
	 * @return string Escaped markup (safe via paginate_links) or empty string.
	 */
	public static function pagination( string $tab, int $total, int $paged, array $args = array() ): string {
		$total_pages = (int) ceil( $total / self::PER_PAGE );

		if ( $total_pages < 2 ) {
			return '';
		}

		$links = paginate_links(
			array(
				'base'      => self::page_url( $tab, array_merge( $args, array( 'paged' => '%#%' ) ) ),
				'format'    => '',
				'current'   => $paged,
				'total'     => $total_pages,
				'mid_size'  => 2,
				'prev_text' => '‹',
				'next_text' => '›',
			)
		);

		if ( ! $links ) {
			return '';
		}

		return '<div class="directorist-affiliate-pagination">' . $links . '</div>';
	}

	/**
	 * Dashboard tab.
	 *
	 * @return void
	 */
	private function render_dashboard_tab(): void {
		$recent_referrals = $this->plugin->referral->list( array( 'limit' => 8 ) );

		$this->render(
			'dashboard.php',
			array(
				'total_affiliates'    => $this->plugin->affiliate->count(),
				'pending_affiliates'  => $this->plugin->affiliate->count( 'pending' ),
				'total_visits'        => $this->plugin->tracking->count(),
				'converted_visits'    => $this->plugin->tracking->count( array( 'converted' => 1 ) ),
				'total_referrals'     => $this->plugin->referral->count(),
				'pending_commission'  => $this->plugin->referral->sum_commission( 'pending' ),
				'approved_commission' => $this->plugin->referral->sum_commission( 'approved' ),
				'paid_commission'     => $this->plugin->referral->sum_commission( 'paid' ),
				'recent_referrals'    => $recent_referrals,
				'affiliates_map'      => $this->plugin->affiliate->get_many( wp_list_pluck( $recent_referrals, 'affiliate_id' ) ),
				'plugin'              => $this->plugin,
			)
		);
	}

	/**
	 * Affiliates tab.
	 *
	 * @return void
	 */
	private function render_affiliates_tab(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only list filters.
		$selected_affiliate_id = isset( $_GET['view_affiliate'] ) ? absint( $_GET['view_affiliate'] ) : 0;
		$notice                = isset( $_GET['directorist_affiliate_notice'] ) ? sanitize_key( wp_unslash( $_GET['directorist_affiliate_notice'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$filters = $this->request_filters( array( 'status', 's' ) );
		$paged   = $this->current_paged();

		$query = array(
			'status'    => $filters['status'],
			'search'    => $filters['s'],
			'date_from' => $filters['date_from'],
			'date_to'   => $filters['date_to'],
		);

		$this->render(
			'affiliates.php',
			array(
				'affiliates'         => $this->plugin->affiliate->list(
					array_merge(
						$query,
						array(
							'limit'  => self::PER_PAGE,
							'offset' => ( $paged - 1 ) * self::PER_PAGE,
						)
					)
				),
				'referral_stats'     => $this->plugin->referral->stats_by_affiliate(),
				'selected_affiliate' => $selected_affiliate_id ? $this->plugin->affiliate->get( $selected_affiliate_id ) : null,
				'notice'             => $notice,
				'filters'            => $filters,
				'pagination_args'    => $this->pagination_args(
					$filters,
					array_merge(
						$this->base_filter_map(),
						array(
							'status' => 'status',
							's'      => 's',
						)
					)
				),
				'total'              => $this->plugin->affiliate->count( $query ),
				'paged'              => $paged,
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
		$notice  = isset( $_GET['directorist_affiliate_notice'] ) ? sanitize_key( wp_unslash( $_GET['directorist_affiliate_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filters = $this->request_filters( array( 'status', 'type' ) );
		$paged   = $this->current_paged();

		$query = array(
			'affiliate_id'  => $filters['affiliate_id'],
			'status'        => $filters['status'],
			'referral_type' => $filters['type'],
			'date_from'     => $filters['date_from'],
			'date_to'       => $filters['date_to'],
		);

		$total     = $this->plugin->referral->count( $query );
		$referrals = $this->plugin->referral->list(
			array_merge(
				$query,
				array(
					'limit'  => self::PER_PAGE,
					'offset' => ( $paged - 1 ) * self::PER_PAGE,
				)
			)
		);

		$this->render(
			'referrals.php',
			array(
				'referrals'       => $referrals,
				'affiliates_map'  => $this->plugin->affiliate->get_many( wp_list_pluck( $referrals, 'affiliate_id' ) ),
				'affiliate_list'  => $this->plugin->affiliate->options(),
				'notice'          => $notice,
				'filters'         => $filters,
				'pagination_args' => $this->pagination_args(
					$filters,
					array_merge(
						$this->base_filter_map(),
						array(
							'status' => 'status',
							'type'   => 'type',
						)
					)
				),
				'total'           => $total,
				'paged'           => $paged,
				'plugin'          => $this->plugin,
			)
		);
	}

	/**
	 * Visits tab.
	 *
	 * @return void
	 */
	private function render_visits_tab(): void {
		$filters = $this->request_filters( array( 'converted' ) );
		$paged   = $this->current_paged();

		$query = array(
			'affiliate_id' => $filters['affiliate_id'],
			'converted'    => in_array( $filters['converted'], array( '0', '1' ), true ) ? $filters['converted'] : '',
			'date_from'    => $filters['date_from'],
			'date_to'      => $filters['date_to'],
		);

		$visits = $this->plugin->tracking->list(
			array_merge(
				$query,
				array(
					'limit'  => self::PER_PAGE,
					'offset' => ( $paged - 1 ) * self::PER_PAGE,
				)
			)
		);

		$this->render(
			'visits.php',
			array(
				'visits'          => $visits,
				'affiliates_map'  => $this->plugin->affiliate->get_many( wp_list_pluck( $visits, 'affiliate_id' ) ),
				'affiliate_list'  => $this->plugin->affiliate->options(),
				'filters'         => $filters,
				'pagination_args' => $this->pagination_args(
					$filters,
					array_merge( $this->base_filter_map(), array( 'converted' => 'converted' ) )
				),
				'total'           => $this->plugin->tracking->count( $query ),
				'paged'           => $paged,
				'plugin'          => $this->plugin,
			)
		);
	}

	/**
	 * Payouts tab.
	 *
	 * @return void
	 */
	private function render_payouts_tab(): void {
		$section = $this->current_payout_section();
		$context = array(
			'section'        => $section,
			'sections'       => $this->payout_sections(),
			'minimum_payout' => (float) $this->plugin->settings->get( 'minimum_payout', '0.00' ),
			'paid_count'     => isset( $_GET['directorist_affiliate_paid'] ) ? absint( $_GET['directorist_affiliate_paid'] ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'skipped_count'  => isset( $_GET['directorist_affiliate_skipped'] ) ? absint( $_GET['directorist_affiliate_skipped'] ) : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'plugin'         => $this->plugin,
		);

		if ( 'history' === $section ) {
			$filters = $this->request_filters( array( 'email' ) );
			$paged   = $this->current_paged();

			$query = array(
				'affiliate_id' => $filters['affiliate_id'],
				'email'        => $filters['email'],
				'date_from'    => $filters['date_from'],
				'date_to'      => $filters['date_to'],
			);

			$payouts = $this->plugin->payout->list(
				array_merge(
					$query,
					array(
						'limit'  => self::PER_PAGE,
						'offset' => ( $paged - 1 ) * self::PER_PAGE,
					)
				)
			);

			$context = array_merge(
				$context,
				array(
					'payouts'         => $payouts,
					'affiliates_map'  => $this->plugin->affiliate->get_many( wp_list_pluck( $payouts, 'affiliate_id' ) ),
					'affiliate_list'  => $this->plugin->affiliate->options(),
					'filters'         => $filters,
					'pagination_args' => $this->pagination_args(
						$filters,
						array_merge( $this->base_filter_map(), array( 'email' => 'email' ) )
					) + array( 'section' => 'history' ),
					'total'           => $this->plugin->payout->count( $query ),
					'total_paid'      => $this->plugin->payout->sum( $query ),
					'paged'           => $paged,
				)
			);

			$this->render( 'payouts-history.php', $context );

			return;
		}

		$approved = $this->plugin->referral->list(
			array(
				'status' => 'approved',
				'limit'  => 500,
			)
		);

		$this->render(
			'payouts-unpaid.php',
			array_merge(
				$context,
				array(
					'approved_referrals' => $approved,
					'affiliates_map'     => $this->plugin->affiliate->get_many( wp_list_pluck( $approved, 'affiliate_id' ) ),
				)
			)
		);
	}

	/**
	 * Sub-tabs of the Payouts screen.
	 *
	 * @return array<string,string>
	 */
	private function payout_sections(): array {
		return array(
			'unpaid'  => __( 'Unpaid approved commissions', 'directorist-affiliate' ),
			'history' => __( 'Payout history', 'directorist-affiliate' ),
		);
	}

	/**
	 * Currently requested Payouts sub-tab.
	 *
	 * @return string
	 */
	private function current_payout_section(): string {
		$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : 'unpaid'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return array_key_exists( $section, $this->payout_sections() ) ? $section : 'unpaid';
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
				'updated'  => isset( $_GET['updated'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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

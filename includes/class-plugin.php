<?php
/**
 * Main plugin bootstrap.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads dependencies and registers plugin services.
 */
final class Directorist_Affiliate_Plugin {
	/**
	 * Plugin instance.
	 *
	 * @var Directorist_Affiliate_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Settings service.
	 *
	 * @var Directorist_Affiliate_Settings
	 */
	public $settings;

	/**
	 * Affiliate repository.
	 *
	 * @var Directorist_Affiliate_Affiliate
	 */
	public $affiliate;

	/**
	 * Visit tracking service.
	 *
	 * @var Directorist_Affiliate_Tracking
	 */
	public $tracking;

	/**
	 * Referral repository.
	 *
	 * @var Directorist_Affiliate_Referral
	 */
	public $referral;

	/**
	 * Commission service.
	 *
	 * @var Directorist_Affiliate_Commission
	 */
	public $commission;

	/**
	 * Payout service.
	 *
	 * @var Directorist_Affiliate_Payout
	 */
	public $payout;

	/**
	 * Email service.
	 *
	 * @var Directorist_Affiliate_Email
	 */
	public $email;

	/**
	 * Shortcodes service.
	 *
	 * @var Directorist_Affiliate_Shortcodes
	 */
	public $shortcodes;

	/**
	 * Link builder search service.
	 *
	 * @var Directorist_Affiliate_Link_Search
	 */
	public $link_search;

	/**
	 * Registration service.
	 *
	 * @var Directorist_Affiliate_Registration
	 */
	public $registration;

	/**
	 * Register bootstrap hooks.
	 *
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'plugins_loaded', array( __CLASS__, 'check_dependency' ), 1 );
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'directorist_loaded', array( __CLASS__, 'instance' ) );
	}

	/**
	 * Get plugin instance.
	 *
	 * @return Directorist_Affiliate_Plugin
	 */
	public static function instance(): Directorist_Affiliate_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->maybe_upgrade();
			self::$instance->init_services();
			self::$instance->register_hooks();
		}

		return self::$instance;
	}

	/**
	 * Run schema upgrades when the stored DB version is outdated.
	 *
	 * @return void
	 */
	private function maybe_upgrade(): void {
		$installed = (string) get_option( 'directorist_affiliate_db_version', '0' );

		if ( version_compare( $installed, DIRECTORIST_AFFILIATE_DB_VERSION, '<' ) ) {
			Directorist_Affiliate_Activator::create_tables();
		}
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public static function load_textdomain(): void {
		load_plugin_textdomain( 'directorist-affiliate', false, dirname( DIRECTORIST_AFFILIATE_BASENAME ) . '/languages' );
	}

	/**
	 * Show dependency notices when Directorist is missing or unsupported.
	 *
	 * @return void
	 */
	public static function check_dependency(): void {
		if ( ! function_exists( 'ATBDP' ) || ! defined( 'ATBDP_VERSION' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'missing_directorist_notice' ) );
			return;
		}

		if ( version_compare( ATBDP_VERSION, DIRECTORIST_AFFILIATE_MIN_DIRECTORIST, '<' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'old_directorist_notice' ) );
		}
	}

	/**
	 * Print missing dependency notice.
	 *
	 * @return void
	 */
	public static function missing_directorist_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'Directorist - Affiliate requires Directorist to be installed and active.', 'directorist-affiliate' )
		);
	}

	/**
	 * Print unsupported Directorist version notice.
	 *
	 * @return void
	 */
	public static function old_directorist_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: minimum Directorist version. */
					__( 'Directorist - Affiliate requires Directorist %s or newer.', 'directorist-affiliate' ),
					DIRECTORIST_AFFILIATE_MIN_DIRECTORIST
				)
			)
		);
	}

	/**
	 * Create service instances.
	 *
	 * Class files load on demand via Directorist_Affiliate_Autoloader.
	 *
	 * @return void
	 */
	private function init_services(): void {
		$this->settings     = new Directorist_Affiliate_Settings();
		$this->affiliate    = new Directorist_Affiliate_Affiliate();
		$this->referral     = new Directorist_Affiliate_Referral();
		$this->tracking     = new Directorist_Affiliate_Tracking( $this->settings, $this->affiliate );
		$this->commission   = new Directorist_Affiliate_Commission( $this->settings );
		$this->payout       = new Directorist_Affiliate_Payout( $this->referral, $this->affiliate );
		$this->email        = new Directorist_Affiliate_Email( $this->settings );
		$this->registration = new Directorist_Affiliate_Registration( $this->affiliate, $this->email, $this->settings );
		$this->link_search  = new Directorist_Affiliate_Link_Search( $this->settings );
		$this->shortcodes   = new Directorist_Affiliate_Shortcodes( $this );
	}

	/**
	 * Register service hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		if ( version_compare( ATBDP_VERSION, DIRECTORIST_AFFILIATE_MIN_DIRECTORIST, '<' ) ) {
			return;
		}

		( new Directorist_Affiliate_Public( $this ) )->register();
		$this->shortcodes->register();
		( new Directorist_Affiliate_Directorist_Integration( $this ) )->register();
		( new Directorist_Affiliate_Order_Integration( $this ) )->register();
		( new Directorist_Affiliate_Ajax( $this ) )->register();

		if ( is_admin() ) {
			( new Directorist_Affiliate_Admin( $this ) )->register();
			( new Directorist_Affiliate_Admin_Actions( $this ) )->register();
		}
	}
}

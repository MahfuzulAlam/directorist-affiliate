<?php
/**
 * Public hooks.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Frontend assets and tracking.
 */
final class Directorist_Affiliate_Public {
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
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		$this->plugin->tracking->register();
	}

	/**
	 * Register public assets.
	 *
	 * @return void
	 */
	public function register_assets(): void {
		wp_register_style(
			'directorist-affiliate',
			DIRECTORIST_AFFILIATE_URL . 'assets/css/directorist-affiliate.css',
			array(),
			DIRECTORIST_AFFILIATE_VERSION
		);

		wp_register_script(
			'directorist-affiliate',
			DIRECTORIST_AFFILIATE_URL . 'assets/js/directorist-affiliate.js',
			array(),
			DIRECTORIST_AFFILIATE_VERSION,
			true
		);

		wp_localize_script(
			'directorist-affiliate',
			'directoristAffiliate',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'submittingLabel' => __( 'Submitting…', 'directorist-affiliate' ),
				'genericError'    => __( 'Something went wrong. Please try again.', 'directorist-affiliate' ),
			)
		);
	}
}

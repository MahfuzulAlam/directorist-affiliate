<?php
/**
 * Class autoloader.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Classmap autoloader so classes load only when first used.
 *
 * The bootstrap classes (Plugin, Activator, Deactivator) are required
 * eagerly in the main plugin file because they run before/at activation.
 */
final class Directorist_Affiliate_Autoloader {
	/**
	 * Class → file map, relative to the plugin root.
	 */
	private const CLASSMAP = array(
		'Directorist_Affiliate_Settings'                => 'includes/class-settings.php',
		'Directorist_Affiliate_Affiliate'               => 'includes/class-affiliate.php',
		'Directorist_Affiliate_Referral'                => 'includes/class-referral.php',
		'Directorist_Affiliate_Tracking'                => 'includes/class-tracking.php',
		'Directorist_Affiliate_Commission'              => 'includes/class-commission.php',
		'Directorist_Affiliate_Payout'                  => 'includes/class-payout.php',
		'Directorist_Affiliate_Email'                   => 'includes/class-email.php',
		'Directorist_Affiliate_View'                    => 'includes/class-view.php',
		'Directorist_Affiliate_Shortcodes'              => 'includes/class-shortcodes.php',
		'Directorist_Affiliate_Directorist_Integration' => 'includes/class-directorist-integration.php',
		'Directorist_Affiliate_Admin'                   => 'admin/class-admin.php',
		'Directorist_Affiliate_Admin_Actions'           => 'admin/class-admin-actions.php',
		'Directorist_Affiliate_Public'                  => 'public/class-public.php',
	);

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Load a mapped class file.
	 *
	 * @param string $class_name Fully qualified class name.
	 *
	 * @return void
	 */
	public static function autoload( string $class_name ): void {
		if ( isset( self::CLASSMAP[ $class_name ] ) ) {
			require_once DIRECTORIST_AFFILIATE_DIR . self::CLASSMAP[ $class_name ];
		}
	}
}

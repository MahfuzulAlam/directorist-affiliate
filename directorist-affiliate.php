<?php
/**
 * Plugin Name: Directorist - Affiliate
 * Plugin URI: https://wpxplore.com/tools/directorist-affiliate/
 * Description: Affiliate tracking and commissions for Directorist — referrals for registrations, listing submissions, and paid plan/featured orders.
 * Version: 1.8.0
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Requires Plugins: directorist
 * Author: wpXplore
 * Author URI: https://wpxplore.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: directorist-affiliate
 * Domain Path: /languages
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

define( 'DIRECTORIST_AFFILIATE_VERSION', '1.8.0' );
define( 'DIRECTORIST_AFFILIATE_DB_VERSION', '0.2.0' );
define( 'DIRECTORIST_AFFILIATE_MIN_DIRECTORIST', '8.7.3' );
define( 'DIRECTORIST_AFFILIATE_FILE', __FILE__ );
define( 'DIRECTORIST_AFFILIATE_DIR', plugin_dir_path( __FILE__ ) );
define( 'DIRECTORIST_AFFILIATE_URL', plugin_dir_url( __FILE__ ) );
define( 'DIRECTORIST_AFFILIATE_BASENAME', plugin_basename( __FILE__ ) );

require_once DIRECTORIST_AFFILIATE_DIR . 'includes/class-autoloader.php';
require_once DIRECTORIST_AFFILIATE_DIR . 'includes/class-plugin.php';
require_once DIRECTORIST_AFFILIATE_DIR . 'includes/class-activator.php';
require_once DIRECTORIST_AFFILIATE_DIR . 'includes/class-deactivator.php';

Directorist_Affiliate_Autoloader::register();

register_activation_hook( __FILE__, array( 'Directorist_Affiliate_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Directorist_Affiliate_Deactivator', 'deactivate' ) );

Directorist_Affiliate_Plugin::boot();

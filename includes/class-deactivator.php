<?php
/**
 * Plugin deactivation.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles deactivation.
 */
final class Directorist_Affiliate_Deactivator {
	/**
	 * Deactivate plugin.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}

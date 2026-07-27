<?php
/**
 * Plugin activation.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles install-time table and option setup.
 */
final class Directorist_Affiliate_Activator {
	/**
	 * Activate plugin.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( 'directorist/directorist-base.php' ) ) {
			deactivate_plugins( DIRECTORIST_AFFILIATE_BASENAME );
			wp_die(
				esc_html__( 'Directorist - Affiliate requires Directorist to be active.', 'directorist-affiliate' ),
				esc_html__( 'Missing dependency', 'directorist-affiliate' ),
				array( 'back_link' => true )
			);
		}

		self::create_tables();
		self::create_options();
	}

	/**
	 * Create custom database tables.
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$affiliates      = $wpdb->prefix . 'directorist_affiliates';
		$visits          = $wpdb->prefix . 'directorist_affiliate_visits';
		$referrals       = $wpdb->prefix . 'directorist_affiliate_referrals';
		$payouts         = $wpdb->prefix . 'directorist_affiliate_payouts';

		$sql = "
CREATE TABLE {$affiliates} (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	user_id bigint(20) unsigned DEFAULT NULL,
	status varchar(20) NOT NULL DEFAULT 'pending',
	referral_code varchar(64) NOT NULL,
	payout_email varchar(100) NOT NULL DEFAULT '',
	website varchar(255) NOT NULL DEFAULT '',
	promotional_method varchar(255) NOT NULL DEFAULT '',
	application_note longtext NULL,
	date_created datetime NOT NULL,
	date_updated datetime NOT NULL,
	PRIMARY KEY  (id),
	UNIQUE KEY referral_code (referral_code),
	KEY user_id (user_id),
	KEY status (status)
) {$charset_collate};
CREATE TABLE {$visits} (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	affiliate_id bigint(20) unsigned NOT NULL,
	referral_code varchar(64) NOT NULL,
	landing_url text NOT NULL,
	referrer_url text NULL,
	ip_address varchar(100) NOT NULL DEFAULT '',
	user_agent text NULL,
	converted tinyint(1) NOT NULL DEFAULT 0,
	referred_user_id bigint(20) unsigned DEFAULT NULL,
	listing_id bigint(20) unsigned DEFAULT NULL,
	date_created datetime NOT NULL,
	PRIMARY KEY  (id),
	KEY affiliate_id (affiliate_id),
	KEY referral_code (referral_code),
	KEY converted (converted),
	KEY date_created (date_created)
) {$charset_collate};
CREATE TABLE {$referrals} (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	affiliate_id bigint(20) unsigned NOT NULL,
	referral_type varchar(40) NOT NULL,
	referred_user_id bigint(20) unsigned DEFAULT NULL,
	listing_id bigint(20) unsigned DEFAULT NULL,
	commission_amount decimal(18,6) NOT NULL DEFAULT 0,
	status varchar(20) NOT NULL DEFAULT 'pending',
	date_created datetime NOT NULL,
	date_approved datetime DEFAULT NULL,
	date_paid datetime DEFAULT NULL,
	notes longtext NULL,
	PRIMARY KEY  (id),
	KEY affiliate_id (affiliate_id),
	KEY referral_type (referral_type),
	KEY referred_user_id (referred_user_id),
	KEY listing_id (listing_id),
	KEY status (status)
) {$charset_collate};
CREATE TABLE {$payouts} (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	affiliate_id bigint(20) unsigned NOT NULL,
	amount decimal(18,6) NOT NULL DEFAULT 0,
	status varchar(20) NOT NULL DEFAULT 'paid',
	payment_method varchar(80) NOT NULL DEFAULT 'manual',
	payout_email varchar(100) NOT NULL DEFAULT '',
	referral_ids longtext NULL,
	date_created datetime NOT NULL,
	date_paid datetime DEFAULT NULL,
	notes longtext NULL,
	PRIMARY KEY  (id),
	KEY affiliate_id (affiliate_id),
	KEY status (status)
) {$charset_collate};
";

		dbDelta( $sql );
		update_option( 'directorist_affiliate_db_version', DIRECTORIST_AFFILIATE_DB_VERSION, false );
	}

	/**
	 * Create default plugin options.
	 *
	 * @return void
	 */
	private static function create_options(): void {
		if ( false !== get_option( 'directorist_affiliate_settings', false ) ) {
			return;
		}

		add_option(
			'directorist_affiliate_settings',
			array(
				'enabled'                    => 1,
				'ref_param'                  => 'ref',
				'cookie_duration'            => 30,
				'enable_registration'        => 1,
				'registration_amount'        => '0.00',
				'enable_listing'             => 1,
				'listing_amount'             => '0.00',
				'listing_trigger'            => 'submission',
				'minimum_payout'             => '0.00',
				'payout_instructions'        => '',
			),
			'',
			false
		);
	}
}

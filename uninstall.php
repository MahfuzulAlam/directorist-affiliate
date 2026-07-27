<?php
/**
 * Plugin uninstall cleanup.
 *
 * Data is only removed when the "Delete data on uninstall" setting is enabled,
 * so accidental uninstalls never destroy affiliate records.
 *
 * @package DirectoristAffiliate
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$directorist_affiliate_settings = get_option( 'directorist_affiliate_settings', array() );

if ( empty( $directorist_affiliate_settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

$directorist_affiliate_tables = array(
	$wpdb->prefix . 'directorist_affiliates',
	$wpdb->prefix . 'directorist_affiliate_visits',
	$wpdb->prefix . 'directorist_affiliate_referrals',
	$wpdb->prefix . 'directorist_affiliate_payouts',
);

foreach ( $directorist_affiliate_tables as $directorist_affiliate_table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$directorist_affiliate_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
}

delete_option( 'directorist_affiliate_settings' );
delete_option( 'directorist_affiliate_db_version' );

delete_metadata( 'user', 0, '_directorist_affiliate_id', '', true );
delete_metadata( 'user', 0, '_directorist_affiliate_visit_id', '', true );

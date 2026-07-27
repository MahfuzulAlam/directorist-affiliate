<?php
/**
 * Admin dashboard.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;
?>
<h2><?php esc_html_e( 'Affiliates & traffic', 'directorist-affiliate' ); ?></h2>
	<div class="directorist-affiliate-summary">
		<div><strong><?php esc_html_e( 'Total affiliates', 'directorist-affiliate' ); ?></strong><span><?php echo esc_html( number_format_i18n( (int) $total_affiliates ) ); ?></span></div>
		<div><strong><?php esc_html_e( 'Pending affiliates', 'directorist-affiliate' ); ?></strong><span><?php echo esc_html( number_format_i18n( (int) $pending_affiliates ) ); ?></span></div>
		<div><strong><?php esc_html_e( 'Total visits', 'directorist-affiliate' ); ?></strong><span><?php echo esc_html( number_format_i18n( (int) $total_visits ) ); ?></span></div>
		<div><strong><?php esc_html_e( 'Total referrals', 'directorist-affiliate' ); ?></strong><span><?php echo esc_html( number_format_i18n( (int) $total_referrals ) ); ?></span></div>
	</div>

	<h2><?php esc_html_e( 'Commissions', 'directorist-affiliate' ); ?></h2>
	<div class="directorist-affiliate-summary">
		<div><strong><?php esc_html_e( 'Pending commission', 'directorist-affiliate' ); ?></strong><span><?php echo esc_html( number_format_i18n( (float) $pending_commission, 2 ) ); ?></span></div>
		<div><strong><?php esc_html_e( 'Approved commission', 'directorist-affiliate' ); ?></strong><span><?php echo esc_html( number_format_i18n( (float) $approved_commission, 2 ) ); ?></span></div>
		<div><strong><?php esc_html_e( 'Paid commission', 'directorist-affiliate' ); ?></strong><span><?php echo esc_html( number_format_i18n( (float) $paid_commission, 2 ) ); ?></span></div>
	</div>

<?php
/**
 * Admin dashboard.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

$directorist_affiliate_conversion_rate = $total_visits > 0 ? ( $converted_visits / $total_visits ) * 100 : 0;
?>
<div class="directorist-affiliate-stat-grid">
	<div class="directorist-affiliate-stat">
		<span class="directorist-affiliate-stat-icon is-accent dashicons dashicons-groups" aria-hidden="true"></span>
		<div>
			<strong><?php esc_html_e( 'Affiliates', 'directorist-affiliate' ); ?></strong>
			<span class="directorist-affiliate-stat-value"><?php echo esc_html( number_format_i18n( (int) $total_affiliates ) ); ?></span>
			<?php if ( $pending_affiliates > 0 ) : ?>
				<a class="directorist-affiliate-stat-meta" href="<?php echo esc_url( Directorist_Affiliate_Admin::page_url( 'affiliates', array( 'status' => 'pending' ) ) ); ?>">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: number of pending applications. */
							_n( '%s application awaiting review', '%s applications awaiting review', (int) $pending_affiliates, 'directorist-affiliate' ),
							number_format_i18n( (int) $pending_affiliates )
						)
					);
					?>
				</a>
			<?php else : ?>
				<span class="directorist-affiliate-stat-meta"><?php esc_html_e( 'No pending applications', 'directorist-affiliate' ); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<div class="directorist-affiliate-stat">
		<span class="directorist-affiliate-stat-icon is-info dashicons dashicons-visibility" aria-hidden="true"></span>
		<div>
			<strong><?php esc_html_e( 'Link visits', 'directorist-affiliate' ); ?></strong>
			<span class="directorist-affiliate-stat-value"><?php echo esc_html( number_format_i18n( (int) $total_visits ) ); ?></span>
			<span class="directorist-affiliate-stat-meta">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: conversion rate percentage. */
						__( '%s%% converted', 'directorist-affiliate' ),
						number_format_i18n( $directorist_affiliate_conversion_rate, 1 )
					)
				);
				?>
			</span>
		</div>
	</div>

	<div class="directorist-affiliate-stat">
		<span class="directorist-affiliate-stat-icon is-success dashicons dashicons-money-alt" aria-hidden="true"></span>
		<div>
			<strong><?php esc_html_e( 'Referrals', 'directorist-affiliate' ); ?></strong>
			<span class="directorist-affiliate-stat-value"><?php echo esc_html( number_format_i18n( (int) $total_referrals ) ); ?></span>
			<span class="directorist-affiliate-stat-meta"><?php esc_html_e( 'All conversion events', 'directorist-affiliate' ); ?></span>
		</div>
	</div>
</div>

<h2 class="directorist-affiliate-section-title"><?php esc_html_e( 'Commissions', 'directorist-affiliate' ); ?></h2>
<div class="directorist-affiliate-stat-grid">
	<div class="directorist-affiliate-stat">
		<span class="directorist-affiliate-stat-icon is-warning dashicons dashicons-clock" aria-hidden="true"></span>
		<div>
			<strong><?php esc_html_e( 'Pending', 'directorist-affiliate' ); ?></strong>
			<span class="directorist-affiliate-stat-value"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $pending_commission ) ); ?></span>
			<span class="directorist-affiliate-stat-meta"><?php esc_html_e( 'Awaiting your review', 'directorist-affiliate' ); ?></span>
		</div>
	</div>
	<div class="directorist-affiliate-stat">
		<span class="directorist-affiliate-stat-icon is-accent dashicons dashicons-yes-alt" aria-hidden="true"></span>
		<div>
			<strong><?php esc_html_e( 'Approved', 'directorist-affiliate' ); ?></strong>
			<span class="directorist-affiliate-stat-value"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $approved_commission ) ); ?></span>
			<a class="directorist-affiliate-stat-meta" href="<?php echo esc_url( Directorist_Affiliate_Admin::page_url( 'payouts' ) ); ?>"><?php esc_html_e( 'Ready for payout →', 'directorist-affiliate' ); ?></a>
		</div>
	</div>
	<div class="directorist-affiliate-stat">
		<span class="directorist-affiliate-stat-icon is-success dashicons dashicons-bank" aria-hidden="true"></span>
		<div>
			<strong><?php esc_html_e( 'Paid out', 'directorist-affiliate' ); ?></strong>
			<span class="directorist-affiliate-stat-value"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $paid_commission ) ); ?></span>
			<span class="directorist-affiliate-stat-meta"><?php esc_html_e( 'Lifetime payouts', 'directorist-affiliate' ); ?></span>
		</div>
	</div>
</div>

<h2 class="directorist-affiliate-section-title"><?php esc_html_e( 'Recent referrals', 'directorist-affiliate' ); ?></h2>
<div class="directorist-affiliate-card directorist-affiliate-table-card">
	<table class="directorist-affiliate-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Affiliate', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Type', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Commission', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Status', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Date', 'directorist-affiliate' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $recent_referrals ) : ?>
				<?php foreach ( $recent_referrals as $referral ) : ?>
					<?php $affiliate = $affiliates_map[ (int) $referral->affiliate_id ] ?? null; ?>
					<tr>
						<td><?php echo esc_html( $affiliate ? $plugin->affiliate->get_name( $affiliate ) : __( 'Unknown', 'directorist-affiliate' ) ); ?></td>
						<td><?php echo esc_html( $plugin->referral->type_label( (string) $referral->referral_type ) ); ?></td>
						<td><strong><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $referral->commission_amount ) ); ?></strong></td>
						<td><span class="directorist-affiliate-badge is-<?php echo esc_attr( sanitize_html_class( $referral->status ) ); ?>"><?php echo esc_html( $plugin->referral->status_label( (string) $referral->status ) ); ?></span></td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $referral->date_created ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="5">
						<div class="directorist-affiliate-empty">
							<span class="dashicons dashicons-share" aria-hidden="true"></span>
							<p><?php esc_html_e( 'No referrals yet. Approved affiliates start earning as soon as their visitors convert.', 'directorist-affiliate' ); ?></p>
						</div>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
<?php if ( $recent_referrals ) : ?>
	<p class="directorist-affiliate-view-all"><a href="<?php echo esc_url( Directorist_Affiliate_Admin::page_url( 'referrals' ) ); ?>"><?php esc_html_e( 'View all referrals →', 'directorist-affiliate' ); ?></a></p>
<?php endif; ?>

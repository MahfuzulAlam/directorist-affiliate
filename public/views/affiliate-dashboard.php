<?php
/**
 * Affiliate dashboard.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

$directorist_affiliate_total = (float) $pending_commission + (float) $approved_commission + (float) $paid_commission;
$directorist_affiliate_rate  = $visits > 0 ? ( (int) $total_referrals / (int) $visits ) * 100 : 0;
?>
<div class="directorist-affiliate-wrap directorist-affiliate-dashboard">
	<div class="directorist-affiliate-hero">
		<div class="directorist-affiliate-hero-main">
			<span class="directorist-affiliate-hero-label"><?php esc_html_e( 'Total earnings', 'directorist-affiliate' ); ?></span>
			<span class="directorist-affiliate-hero-value"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( $directorist_affiliate_total ) ); ?></span>
			<span class="directorist-affiliate-badge is-<?php echo esc_attr( sanitize_html_class( $affiliate->status ) ); ?>"><?php echo esc_html( $plugin->affiliate->status_label( (string) $affiliate->status ) ); ?></span>
		</div>

		<?php if ( 'approved' === $affiliate->status ) : ?>
			<div class="directorist-affiliate-hero-link">
				<label for="directorist-affiliate-referral-url"><?php esc_html_e( 'Your referral link', 'directorist-affiliate' ); ?></label>
				<div class="directorist-affiliate-link-group">
					<input id="directorist-affiliate-referral-url" type="text" readonly value="<?php echo esc_url( $referral_url ); ?>" onfocus="this.select();" />
					<button type="button" class="directorist-affiliate-copy" data-target="directorist-affiliate-referral-url" data-copied-label="<?php esc_attr_e( 'Copied!', 'directorist-affiliate' ); ?>">
						<?php esc_html_e( 'Copy', 'directorist-affiliate' ); ?>
					</button>
				</div>
				<p class="directorist-affiliate-hint"><?php esc_html_e( 'Share this link anywhere you promote the site. Visitors who arrive through it are credited to you.', 'directorist-affiliate' ); ?></p>
			</div>
		<?php elseif ( 'pending' === $affiliate->status ) : ?>
			<div class="directorist-affiliate-notice"><?php esc_html_e( 'Your application is being reviewed. Your referral link will appear here once you are approved.', 'directorist-affiliate' ); ?></div>
		<?php elseif ( 'suspended' === $affiliate->status ) : ?>
			<div class="directorist-affiliate-notice directorist-affiliate-notice--error"><?php esc_html_e( 'Your affiliate account is suspended. New referrals are not being tracked. Please contact the site owner.', 'directorist-affiliate' ); ?></div>
		<?php else : ?>
			<div class="directorist-affiliate-notice directorist-affiliate-notice--error"><?php esc_html_e( 'Your affiliate application was not approved.', 'directorist-affiliate' ); ?></div>
		<?php endif; ?>
	</div>

	<div class="directorist-affiliate-stat-grid">
		<div class="directorist-affiliate-stat">
			<strong><?php esc_html_e( 'Visits', 'directorist-affiliate' ); ?></strong>
			<span class="directorist-affiliate-stat-value"><?php echo esc_html( number_format_i18n( (int) $visits ) ); ?></span>
			<span class="directorist-affiliate-stat-meta">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: conversion rate percentage. */
						__( '%s%% conversion', 'directorist-affiliate' ),
						number_format_i18n( $directorist_affiliate_rate, 1 )
					)
				);
				?>
			</span>
		</div>
		<div class="directorist-affiliate-stat">
			<strong><?php esc_html_e( 'Referrals', 'directorist-affiliate' ); ?></strong>
			<span class="directorist-affiliate-stat-value"><?php echo esc_html( number_format_i18n( (int) $total_referrals ) ); ?></span>
			<span class="directorist-affiliate-stat-meta"><?php esc_html_e( 'Conversions credited', 'directorist-affiliate' ); ?></span>
		</div>
		<div class="directorist-affiliate-stat">
			<strong><?php esc_html_e( 'Pending', 'directorist-affiliate' ); ?></strong>
			<span class="directorist-affiliate-stat-value"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $pending_commission ) ); ?></span>
			<span class="directorist-affiliate-stat-meta"><?php esc_html_e( 'Awaiting review', 'directorist-affiliate' ); ?></span>
		</div>
		<div class="directorist-affiliate-stat">
			<strong><?php esc_html_e( 'Approved', 'directorist-affiliate' ); ?></strong>
			<span class="directorist-affiliate-stat-value"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $approved_commission ) ); ?></span>
			<span class="directorist-affiliate-stat-meta"><?php esc_html_e( 'Due to be paid', 'directorist-affiliate' ); ?></span>
		</div>
		<div class="directorist-affiliate-stat">
			<strong><?php esc_html_e( 'Paid', 'directorist-affiliate' ); ?></strong>
			<span class="directorist-affiliate-stat-value"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $paid_commission ) ); ?></span>
			<span class="directorist-affiliate-stat-meta"><?php esc_html_e( 'Already received', 'directorist-affiliate' ); ?></span>
		</div>
	</div>

	<?php if ( 'approved' === $affiliate->status && ! empty( $link_targets ) ) : ?>
		<div class="directorist-affiliate-card directorist-affiliate-link-builder">
			<h3><?php esc_html_e( 'Link builder', 'directorist-affiliate' ); ?></h3>
			<p class="directorist-affiliate-hint"><?php esc_html_e( 'Send visitors straight to a specific page with your code attached.', 'directorist-affiliate' ); ?></p>
			<div class="directorist-affiliate-link-builder-row">
				<label class="screen-reader-text" for="directorist-affiliate-link-target"><?php esc_html_e( 'Destination page', 'directorist-affiliate' ); ?></label>
				<select id="directorist-affiliate-link-target" data-da-link-select data-da-link-output="directorist-affiliate-built-link">
					<?php foreach ( $link_targets as $target_url => $target_label ) : ?>
						<option value="<?php echo esc_url( $target_url ); ?>"><?php echo esc_html( $target_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="directorist-affiliate-link-group">
				<label class="screen-reader-text" for="directorist-affiliate-built-link"><?php esc_html_e( 'Generated referral link', 'directorist-affiliate' ); ?></label>
				<input id="directorist-affiliate-built-link" type="text" readonly value="<?php echo esc_url( (string) array_key_first( $link_targets ) ); ?>" onfocus="this.select();" />
				<button type="button" class="directorist-affiliate-copy" data-target="directorist-affiliate-built-link" data-copied-label="<?php esc_attr_e( 'Copied!', 'directorist-affiliate' ); ?>">
					<?php esc_html_e( 'Copy', 'directorist-affiliate' ); ?>
				</button>
			</div>
		</div>
	<?php endif; ?>

	<div class="directorist-affiliate-card directorist-affiliate-payout-card">
		<h3><?php esc_html_e( 'Payout details', 'directorist-affiliate' ); ?></h3>
		<p>
			<strong><?php esc_html_e( 'Payout email', 'directorist-affiliate' ); ?>:</strong>
			<?php echo esc_html( $affiliate->payout_email ); ?>
		</p>
		<?php if ( ! empty( $payout_instructions ) ) : ?>
			<p class="directorist-affiliate-hint"><?php echo nl2br( esc_html( $payout_instructions ) ); ?></p>
		<?php endif; ?>
	</div>

	<h3 class="directorist-affiliate-section-title"><?php esc_html_e( 'Referral history', 'directorist-affiliate' ); ?></h3>
	<div class="directorist-affiliate-card directorist-affiliate-table-card">
		<table class="directorist-affiliate-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Event', 'directorist-affiliate' ); ?></th>
					<th class="is-num"><?php esc_html_e( 'Commission', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Status', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Date', 'directorist-affiliate' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $referrals ) : ?>
					<?php foreach ( $referrals as $referral ) : ?>
						<tr>
							<td data-label="<?php esc_attr_e( 'Event', 'directorist-affiliate' ); ?>"><?php echo esc_html( $plugin->referral->type_label( (string) $referral->referral_type ) ); ?></td>
							<td class="is-num" data-label="<?php esc_attr_e( 'Commission', 'directorist-affiliate' ); ?>"><strong><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $referral->commission_amount ) ); ?></strong></td>
							<td data-label="<?php esc_attr_e( 'Status', 'directorist-affiliate' ); ?>"><span class="directorist-affiliate-badge is-<?php echo esc_attr( sanitize_html_class( $referral->status ) ); ?>"><?php echo esc_html( $plugin->referral->status_label( (string) $referral->status ) ); ?></span></td>
							<td data-label="<?php esc_attr_e( 'Date', 'directorist-affiliate' ); ?>"><?php echo esc_html( mysql2date( get_option( 'date_format' ), $referral->date_created ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="4">
							<div class="directorist-affiliate-empty">
								<p><?php esc_html_e( 'No referrals yet. Share your link to get started.', 'directorist-affiliate' ); ?></p>
							</div>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ( ! empty( $payouts ) ) : ?>
		<h3 class="directorist-affiliate-section-title"><?php esc_html_e( 'Payout history', 'directorist-affiliate' ); ?></h3>
		<div class="directorist-affiliate-card directorist-affiliate-table-card">
			<table class="directorist-affiliate-table">
				<thead>
					<tr>
						<th class="is-num"><?php esc_html_e( 'Amount', 'directorist-affiliate' ); ?></th>
						<th><?php esc_html_e( 'Method', 'directorist-affiliate' ); ?></th>
						<th><?php esc_html_e( 'Date paid', 'directorist-affiliate' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $payouts as $payout ) : ?>
						<tr>
							<td class="is-num" data-label="<?php esc_attr_e( 'Amount', 'directorist-affiliate' ); ?>"><strong><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $payout->amount ) ); ?></strong></td>
							<td data-label="<?php esc_attr_e( 'Method', 'directorist-affiliate' ); ?>"><?php echo esc_html( ucfirst( (string) $payout->payment_method ) ); ?></td>
							<td data-label="<?php esc_attr_e( 'Date paid', 'directorist-affiliate' ); ?>"><?php echo esc_html( $payout->date_paid ? mysql2date( get_option( 'date_format' ), $payout->date_paid ) : '—' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

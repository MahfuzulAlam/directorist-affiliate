<?php
/**
 * Affiliate dashboard.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="directorist-affiliate-wrap directorist-affiliate-dashboard">
	<div class="directorist-affiliate-summary">
		<div>
			<strong><?php esc_html_e( 'Status', 'directorist-affiliate' ); ?></strong>
			<span><span class="directorist-affiliate-badge is-<?php echo esc_attr( sanitize_html_class( $affiliate->status ) ); ?>"><?php echo esc_html( $affiliate->status ); ?></span></span>
		</div>
		<div><strong><?php esc_html_e( 'Visits', 'directorist-affiliate' ); ?></strong><span><?php echo esc_html( number_format_i18n( (int) $visits ) ); ?></span></div>
		<div><strong><?php esc_html_e( 'Referrals', 'directorist-affiliate' ); ?></strong><span><?php echo esc_html( number_format_i18n( (int) $total_referrals ) ); ?></span></div>
		<div><strong><?php esc_html_e( 'Pending', 'directorist-affiliate' ); ?></strong><span><?php echo esc_html( number_format_i18n( (float) $pending_commission, 2 ) ); ?></span></div>
		<div><strong><?php esc_html_e( 'Approved', 'directorist-affiliate' ); ?></strong><span><?php echo esc_html( number_format_i18n( (float) $approved_commission, 2 ) ); ?></span></div>
		<div><strong><?php esc_html_e( 'Paid', 'directorist-affiliate' ); ?></strong><span><?php echo esc_html( number_format_i18n( (float) $paid_commission, 2 ) ); ?></span></div>
	</div>

	<?php if ( 'approved' === $affiliate->status ) : ?>
		<p>
			<label for="directorist-affiliate-referral-url"><strong><?php esc_html_e( 'Referral link', 'directorist-affiliate' ); ?></strong></label>
		</p>
		<div class="directorist-affiliate-link-group">
			<input id="directorist-affiliate-referral-url" type="text" readonly value="<?php echo esc_url( $referral_url ); ?>" onfocus="this.select();" />
			<button type="button" class="directorist-affiliate-copy" data-target="directorist-affiliate-referral-url" data-copied-label="<?php esc_attr_e( 'Copied!', 'directorist-affiliate' ); ?>">
				<?php esc_html_e( 'Copy link', 'directorist-affiliate' ); ?>
			</button>
		</div>
		<p><small><?php esc_html_e( 'Share this link anywhere you promote the site. Sign-ups and listings from your visitors are credited to you.', 'directorist-affiliate' ); ?></small></p>
	<?php elseif ( 'pending' === $affiliate->status ) : ?>
		<div class="directorist-affiliate-notice"><?php esc_html_e( 'Your application is being reviewed. Your referral link will appear here once you are approved.', 'directorist-affiliate' ); ?></div>
	<?php endif; ?>

	<p>
		<strong><?php esc_html_e( 'Payout email', 'directorist-affiliate' ); ?>:</strong>
		<?php echo esc_html( $affiliate->payout_email ); ?>
	</p>

	<?php if ( ! empty( $payout_instructions ) ) : ?>
		<p><?php echo nl2br( esc_html( $payout_instructions ) ); ?></p>
	<?php endif; ?>

	<h3><?php esc_html_e( 'Referral history', 'directorist-affiliate' ); ?></h3>
	<div class="directorist-affiliate-table-wrap">
		<table class="directorist-affiliate-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Type', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Amount', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Status', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Date', 'directorist-affiliate' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $referrals ) : ?>
					<?php foreach ( $referrals as $referral ) : ?>
						<tr>
							<td><?php echo esc_html( str_replace( '_', ' ', $referral->referral_type ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (float) $referral->commission_amount, 2 ) ); ?></td>
							<td><span class="directorist-affiliate-badge is-<?php echo esc_attr( sanitize_html_class( $referral->status ) ); ?>"><?php echo esc_html( $referral->status ); ?></span></td>
							<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $referral->date_created ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No referrals yet.', 'directorist-affiliate' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php
/**
 * Payouts → Unpaid approved commissions.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

$export_url = wp_nonce_url(
	Directorist_Affiliate_Admin::page_url( 'payouts', array( 'directorist_affiliate_export' => 'payouts' ) ),
	'directorist_affiliate_export_payouts'
);

$directorist_affiliate_outstanding = 0.0;

foreach ( $approved_referrals as $directorist_affiliate_referral ) {
	$directorist_affiliate_outstanding += (float) $directorist_affiliate_referral->commission_amount;
}

Directorist_Affiliate_View::partial(
	'payout-sections.php',
	array(
		'sections' => $sections,
		'section'  => $section,
	)
);
?>
<?php if ( null !== $paid_count && $paid_count > 0 ) : ?>
	<div class="notice notice-success is-dismissible">
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: number of payouts. */
					_n( '%s payout recorded.', '%s payouts recorded.', $paid_count, 'directorist-affiliate' ),
					number_format_i18n( $paid_count )
				)
			);
			?>
		</p>
	</div>
<?php endif; ?>

<?php if ( null !== $skipped_count && $skipped_count > 0 ) : ?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: number of affiliates, 2: minimum payout amount. */
					_n(
						'%1$s affiliate was skipped because the selected total is below the minimum payout of %2$s.',
						'%1$s affiliates were skipped because their selected totals are below the minimum payout of %2$s.',
						$skipped_count,
						'directorist-affiliate'
					),
					number_format_i18n( $skipped_count ),
					Directorist_Affiliate_Commission::format_money( $minimum_payout )
				)
			);
			?>
		</p>
	</div>
<?php endif; ?>

<div class="directorist-affiliate-toolbar">
	<div class="directorist-affiliate-toolbar-info">
		<span class="directorist-affiliate-toolbar-label"><?php esc_html_e( 'Outstanding balance', 'directorist-affiliate' ); ?></span>
		<span class="directorist-affiliate-toolbar-value"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( $directorist_affiliate_outstanding ) ); ?></span>
		<?php if ( $minimum_payout > 0 ) : ?>
			<span class="directorist-affiliate-toolbar-note">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: minimum payout amount. */
						__( 'Minimum per affiliate: %s', 'directorist-affiliate' ),
						Directorist_Affiliate_Commission::format_money( $minimum_payout )
					)
				);
				?>
			</span>
		<?php endif; ?>
	</div>
	<a class="button" href="<?php echo esc_url( $export_url ); ?>">
		<span class="dashicons dashicons-download" aria-hidden="true"></span>
		<?php esc_html_e( 'Export CSV', 'directorist-affiliate' ); ?>
	</a>
</div>

<form method="post" data-da-ajax="directorist_affiliate_mark_paid" data-da-success="reload">
	<?php wp_nonce_field( 'directorist_affiliate_mark_paid' ); ?>
	<input type="hidden" name="directorist_affiliate_mark_paid" value="1" />

	<div class="directorist-affiliate-card directorist-affiliate-table-card">
		<table class="directorist-affiliate-table">
			<thead>
				<tr>
					<th class="is-check">
						<input type="checkbox" data-da-check-all aria-label="<?php esc_attr_e( 'Select all commissions', 'directorist-affiliate' ); ?>" />
					</th>
					<th><?php esc_html_e( 'Affiliate', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Payout email', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Referral', 'directorist-affiliate' ); ?></th>
					<th class="is-num"><?php esc_html_e( 'Amount', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Date', 'directorist-affiliate' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $approved_referrals ) : ?>
					<?php foreach ( $approved_referrals as $referral ) : ?>
						<?php $affiliate = $affiliates_map[ (int) $referral->affiliate_id ] ?? null; ?>
						<tr>
							<td class="is-check"><input type="checkbox" name="referral_ids[]" value="<?php echo esc_attr( (string) (int) $referral->id ); ?>" aria-label="<?php esc_attr_e( 'Select commission', 'directorist-affiliate' ); ?>" /></td>
							<td><strong><?php echo esc_html( $affiliate ? $plugin->affiliate->get_name( $affiliate ) : __( 'Unknown', 'directorist-affiliate' ) ); ?></strong></td>
							<td><?php echo esc_html( $affiliate && $affiliate->payout_email ? $affiliate->payout_email : '—' ); ?></td>
							<td><?php echo esc_html( '#' . (int) $referral->id . ' · ' . $plugin->referral->type_label( (string) $referral->referral_type ) ); ?></td>
							<td class="is-num"><strong><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $referral->commission_amount ) ); ?></strong></td>
							<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $referral->date_created ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="6">
							<div class="directorist-affiliate-empty">
								<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
								<p><?php esc_html_e( 'Nothing awaiting payment. Approved commissions appear here.', 'directorist-affiliate' ); ?></p>
							</div>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ( $approved_referrals ) : ?>
		<p class="directorist-affiliate-form-actions">
			<button class="button button-primary" type="submit"><?php esc_html_e( 'Mark selected as paid', 'directorist-affiliate' ); ?></button>
		</p>
	<?php endif; ?>
</form>

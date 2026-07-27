<?php
/**
 * Payouts admin page.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

$export_url = wp_nonce_url(
	admin_url( 'admin.php?page=directorist-affiliate-payouts&directorist_affiliate_export=payouts' ),
	'directorist_affiliate_export_payouts'
);
?>
<div class="wrap directorist-affiliate-admin">
	<h1><?php esc_html_e( 'Payouts', 'directorist-affiliate' ); ?></h1>

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
						number_format_i18n( $minimum_payout, 2 )
					)
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<p><a class="button" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export approved payouts CSV', 'directorist-affiliate' ); ?></a></p>

	<h2><?php esc_html_e( 'Unpaid approved commissions', 'directorist-affiliate' ); ?></h2>

	<?php if ( $minimum_payout > 0 ) : ?>
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: minimum payout amount. */
					__( 'Minimum payout per affiliate: %s. Selections totalling less than this per affiliate are skipped.', 'directorist-affiliate' ),
					number_format_i18n( $minimum_payout, 2 )
				)
			);
			?>
		</p>
	<?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'directorist_affiliate_mark_paid' ); ?>
		<input type="hidden" name="directorist_affiliate_mark_paid" value="1" />
		<table class="widefat striped">
			<thead>
				<tr>
					<th><span class="screen-reader-text"><?php esc_html_e( 'Select', 'directorist-affiliate' ); ?></span></th>
					<th><?php esc_html_e( 'Affiliate', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Payout email', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Referral', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Amount', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Date', 'directorist-affiliate' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $approved_referrals ) : ?>
					<?php foreach ( $approved_referrals as $referral ) : ?>
						<?php $affiliate = $plugin->affiliate->get( (int) $referral->affiliate_id ); ?>
						<tr>
							<td><input type="checkbox" name="referral_ids[]" value="<?php echo esc_attr( $referral->id ); ?>" /></td>
							<td><?php echo esc_html( $affiliate ? $plugin->affiliate->get_name( $affiliate ) : __( 'Unknown', 'directorist-affiliate' ) ); ?></td>
							<td><?php echo esc_html( $affiliate ? $affiliate->payout_email : '' ); ?></td>
							<td>#<?php echo esc_html( (int) $referral->id ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (float) $referral->commission_amount, 2 ) ); ?></td>
							<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $referral->date_created ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No approved unpaid referrals found.', 'directorist-affiliate' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		<p><button class="button button-primary" type="submit"><?php esc_html_e( 'Mark selected as paid', 'directorist-affiliate' ); ?></button></p>
	</form>

	<h2><?php esc_html_e( 'Payout history', 'directorist-affiliate' ); ?></h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Affiliate', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Amount', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Payout email', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Date paid', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Notes', 'directorist-affiliate' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $payouts ) : ?>
				<?php foreach ( $payouts as $payout ) : ?>
					<?php $affiliate = $plugin->affiliate->get( (int) $payout->affiliate_id ); ?>
					<tr>
						<td><?php echo esc_html( $affiliate ? $plugin->affiliate->get_name( $affiliate ) : __( 'Unknown', 'directorist-affiliate' ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (float) $payout->amount, 2 ) ); ?></td>
						<td><?php echo esc_html( $payout->payout_email ); ?></td>
						<td><?php echo esc_html( $payout->date_paid ? mysql2date( get_option( 'date_format' ), $payout->date_paid ) : '-' ); ?></td>
						<td><?php echo esc_html( $payout->notes ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No payouts found.', 'directorist-affiliate' ); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

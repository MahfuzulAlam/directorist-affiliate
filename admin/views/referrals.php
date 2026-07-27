<?php
/**
 * Referrals admin page.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap directorist-affiliate-admin">
	<h1><?php esc_html_e( 'Referrals', 'directorist-affiliate' ); ?></h1>

	<?php if ( ! empty( $notice ) ) : ?>
		<?php if ( 'referral_updated' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Referral updated.', 'directorist-affiliate' ); ?></p></div>
		<?php elseif ( 'referral_not_approved' === $notice ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Only approved referrals can be marked as paid. Approve the referral first.', 'directorist-affiliate' ); ?></p></div>
		<?php endif; ?>
	<?php endif; ?>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Affiliate', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Type', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Referred user', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Listing', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Amount', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Status', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Date', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'directorist-affiliate' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $referrals ) : ?>
				<?php foreach ( $referrals as $referral ) : ?>
					<?php
					$affiliate = $plugin->affiliate->get( (int) $referral->affiliate_id );
					$user      = $referral->referred_user_id ? get_user_by( 'id', (int) $referral->referred_user_id ) : false;
					$base_url  = admin_url( 'admin.php?page=directorist-affiliate-referrals&referral_id=' . absint( $referral->id ) );
					?>
					<tr>
						<td><?php echo esc_html( $affiliate ? $plugin->affiliate->get_name( $affiliate ) : __( 'Unknown', 'directorist-affiliate' ) ); ?></td>
						<td><?php echo esc_html( str_replace( '_', ' ', $referral->referral_type ) ); ?></td>
						<td><?php echo esc_html( $user ? $user->user_email : '-' ); ?></td>
						<td>
							<?php if ( $referral->listing_id ) : ?>
								<a href="<?php echo esc_url( get_edit_post_link( (int) $referral->listing_id ) ); ?>"><?php echo esc_html( get_the_title( (int) $referral->listing_id ) ); ?></a>
							<?php else : ?>
								<?php echo esc_html( '-' ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( number_format_i18n( (float) $referral->commission_amount, 2 ) ); ?></td>
						<td><span class="directorist-affiliate-badge is-<?php echo esc_attr( sanitize_html_class( $referral->status ) ); ?>"><?php echo esc_html( $referral->status ); ?></span></td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $referral->date_created ) ); ?></td>
						<td class="directorist-affiliate-row-actions">
							<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'directorist_referral_action', 'approve', $base_url ), 'directorist_referral_action_' . absint( $referral->id ) ) ); ?>"><?php esc_html_e( 'Approve', 'directorist-affiliate' ); ?></a> |
							<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'directorist_referral_action', 'reject', $base_url ), 'directorist_referral_action_' . absint( $referral->id ) ) ); ?>"><?php esc_html_e( 'Reject', 'directorist-affiliate' ); ?></a> |
							<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'directorist_referral_action', 'paid', $base_url ), 'directorist_referral_action_' . absint( $referral->id ) ) ); ?>"><?php esc_html_e( 'Mark paid', 'directorist-affiliate' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No referrals found.', 'directorist-affiliate' ); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

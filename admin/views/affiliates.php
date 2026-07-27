<?php
/**
 * Affiliates admin page.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap directorist-affiliate-admin">
	<h1><?php esc_html_e( 'Affiliates', 'directorist-affiliate' ); ?></h1>

	<?php if ( ! empty( $notice ) ) : ?>
		<?php
		$success_notices = array( 'affiliate_created', 'affiliate_updated' );
		$messages        = array(
			'affiliate_created'       => __( 'Affiliate created successfully.', 'directorist-affiliate' ),
			'affiliate_updated'       => __( 'Affiliate status updated.', 'directorist-affiliate' ),
			'invalid_affiliate'       => __( 'Please provide a valid name, email, payout email, and status.', 'directorist-affiliate' ),
			'user_create_failed'      => __( 'Could not create the WordPress user for this affiliate.', 'directorist-affiliate' ),
			'affiliate_exists'        => __( 'This WordPress user is already registered as an affiliate.', 'directorist-affiliate' ),
			'affiliate_create_failed' => __( 'Could not create the affiliate record.', 'directorist-affiliate' ),
		);
		?>
		<?php if ( isset( $messages[ $notice ] ) ) : ?>
			<div class="notice <?php echo in_array( $notice, $success_notices, true ) ? 'notice-success' : 'notice-error'; ?> is-dismissible"><p><?php echo esc_html( $messages[ $notice ] ); ?></p></div>
		<?php endif; ?>
	<?php endif; ?>

	<div class="directorist-affiliate-detail">
		<h2><?php esc_html_e( 'Add Affiliate', 'directorist-affiliate' ); ?></h2>
		<form method="post" class="directorist-affiliate-admin-form">
			<?php wp_nonce_field( 'directorist_affiliate_add_affiliate' ); ?>
			<input type="hidden" name="directorist_affiliate_add_affiliate" value="1" />

			<div class="directorist-affiliate-admin-grid">
				<p>
					<label for="directorist-affiliate-admin-name"><?php esc_html_e( 'Name', 'directorist-affiliate' ); ?> <span class="required">*</span></label>
					<input id="directorist-affiliate-admin-name" type="text" name="name" required />
				</p>
				<p>
					<label for="directorist-affiliate-admin-email"><?php esc_html_e( 'Email', 'directorist-affiliate' ); ?> <span class="required">*</span></label>
					<input id="directorist-affiliate-admin-email" type="email" name="email" required />
				</p>
				<p>
					<label for="directorist-affiliate-admin-payout-email"><?php esc_html_e( 'Payout email', 'directorist-affiliate' ); ?> <span class="required">*</span></label>
					<input id="directorist-affiliate-admin-payout-email" type="email" name="payout_email" required />
				</p>
				<p>
					<label for="directorist-affiliate-admin-status"><?php esc_html_e( 'Status', 'directorist-affiliate' ); ?></label>
					<select id="directorist-affiliate-admin-status" name="status">
						<option value="approved"><?php esc_html_e( 'Approved', 'directorist-affiliate' ); ?></option>
						<option value="pending"><?php esc_html_e( 'Pending', 'directorist-affiliate' ); ?></option>
						<option value="rejected"><?php esc_html_e( 'Rejected', 'directorist-affiliate' ); ?></option>
						<option value="suspended"><?php esc_html_e( 'Suspended', 'directorist-affiliate' ); ?></option>
					</select>
				</p>
				<p>
					<label for="directorist-affiliate-admin-website"><?php esc_html_e( 'Website', 'directorist-affiliate' ); ?></label>
					<input id="directorist-affiliate-admin-website" type="url" name="website" />
				</p>
				<p>
					<label for="directorist-affiliate-admin-promotional-method"><?php esc_html_e( 'Promotional channel', 'directorist-affiliate' ); ?></label>
					<input id="directorist-affiliate-admin-promotional-method" type="text" name="promotional_method" />
				</p>
			</div>

			<p>
				<label for="directorist-affiliate-admin-application-note"><?php esc_html_e( 'Application note', 'directorist-affiliate' ); ?></label>
				<textarea id="directorist-affiliate-admin-application-note" name="application_note" rows="4"></textarea>
			</p>

			<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Add affiliate', 'directorist-affiliate' ); ?></button></p>
		</form>
	</div>

	<?php if ( ! empty( $selected_affiliate ) ) : ?>
		<div class="directorist-affiliate-detail">
			<h2><?php esc_html_e( 'Affiliate Details', 'directorist-affiliate' ); ?></h2>
			<p><strong><?php esc_html_e( 'Name', 'directorist-affiliate' ); ?>:</strong> <?php echo esc_html( $plugin->affiliate->get_name( $selected_affiliate ) ); ?></p>
			<p><strong><?php esc_html_e( 'Email', 'directorist-affiliate' ); ?>:</strong> <?php echo esc_html( $plugin->affiliate->get_email( $selected_affiliate ) ); ?></p>
			<p><strong><?php esc_html_e( 'Status', 'directorist-affiliate' ); ?>:</strong> <span class="directorist-affiliate-badge is-<?php echo esc_attr( sanitize_html_class( $selected_affiliate->status ) ); ?>"><?php echo esc_html( $selected_affiliate->status ); ?></span></p>
			<p><strong><?php esc_html_e( 'Payout email', 'directorist-affiliate' ); ?>:</strong> <?php echo esc_html( $selected_affiliate->payout_email ); ?></p>
			<p><strong><?php esc_html_e( 'Website', 'directorist-affiliate' ); ?>:</strong> <?php echo $selected_affiliate->website ? '<a href="' . esc_url( $selected_affiliate->website ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $selected_affiliate->website ) . '</a>' : esc_html__( 'Not provided', 'directorist-affiliate' ); ?></p>
			<p><strong><?php esc_html_e( 'Promotional channel', 'directorist-affiliate' ); ?>:</strong> <?php echo esc_html( $selected_affiliate->promotional_method ); ?></p>
			<p><strong><?php esc_html_e( 'Application note', 'directorist-affiliate' ); ?>:</strong><br><?php echo nl2br( esc_html( $selected_affiliate->application_note ) ); ?></p>
		</div>
	<?php endif; ?>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Email', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Status', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Referral code', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Total referrals', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Total commission', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Unpaid commission', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Date applied', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'directorist-affiliate' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $affiliates ) : ?>
				<?php foreach ( $affiliates as $affiliate ) : ?>
					<?php
					$base_url = admin_url( 'admin.php?page=directorist-affiliate-affiliates&affiliate_id=' . absint( $affiliate->id ) );
					?>
					<tr>
						<td>
							<strong><?php echo esc_html( $plugin->affiliate->get_name( $affiliate ) ); ?></strong>
							<?php if ( $affiliate->website ) : ?>
								<br><a href="<?php echo esc_url( $affiliate->website ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $affiliate->website ); ?></a>
							<?php endif; ?>
							<?php if ( $affiliate->application_note ) : ?>
								<br><small><?php echo esc_html( wp_trim_words( $affiliate->application_note, 18 ) ); ?></small>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $plugin->affiliate->get_email( $affiliate ) ); ?></td>
						<td><span class="directorist-affiliate-badge is-<?php echo esc_attr( sanitize_html_class( $affiliate->status ) ); ?>"><?php echo esc_html( $affiliate->status ); ?></span></td>
						<td><code><?php echo esc_html( $affiliate->referral_code ); ?></code></td>
						<td><?php echo esc_html( number_format_i18n( $plugin->referral->count( '', (int) $affiliate->id ) ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $plugin->referral->sum_commission( '', (int) $affiliate->id ), 2 ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $plugin->referral->sum_commission( 'approved', (int) $affiliate->id ), 2 ) ); ?></td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $affiliate->date_created ) ); ?></td>
						<td class="directorist-affiliate-row-actions">
							<a href="<?php echo esc_url( add_query_arg( 'view_affiliate', absint( $affiliate->id ), admin_url( 'admin.php?page=directorist-affiliate-affiliates' ) ) ); ?>"><?php esc_html_e( 'View details', 'directorist-affiliate' ); ?></a> |
							<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'directorist_affiliate_action', 'approve', $base_url ), 'directorist_affiliate_action_' . absint( $affiliate->id ) ) ); ?>"><?php esc_html_e( 'Approve', 'directorist-affiliate' ); ?></a> |
							<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'directorist_affiliate_action', 'reject', $base_url ), 'directorist_affiliate_action_' . absint( $affiliate->id ) ) ); ?>"><?php esc_html_e( 'Reject', 'directorist-affiliate' ); ?></a> |
							<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'directorist_affiliate_action', 'suspend', $base_url ), 'directorist_affiliate_action_' . absint( $affiliate->id ) ) ); ?>"><?php esc_html_e( 'Suspend', 'directorist-affiliate' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="9"><?php esc_html_e( 'No affiliates found.', 'directorist-affiliate' ); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

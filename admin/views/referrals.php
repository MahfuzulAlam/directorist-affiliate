<?php
/**
 * Referrals admin page.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

$directorist_affiliate_notices = array(
	'referral_updated'      => array( 'success', __( 'Referral updated.', 'directorist-affiliate' ) ),
	'referral_bulk_updated' => array( 'success', __( 'Selected referrals updated.', 'directorist-affiliate' ) ),
	'referral_bulk_paid'    => array( 'success', __( 'Payouts recorded for the selected referrals.', 'directorist-affiliate' ) ),
	'referral_not_approved' => array( 'error', __( 'Only approved referrals can be marked as paid. Approve the referral first.', 'directorist-affiliate' ) ),
	'referral_bulk_empty'   => array( 'error', __( 'Select at least one referral and a bulk action.', 'directorist-affiliate' ) ),
);
?>
<?php if ( ! empty( $notice ) && isset( $directorist_affiliate_notices[ $notice ] ) ) : ?>
	<div class="notice notice-<?php echo esc_attr( $directorist_affiliate_notices[ $notice ][0] ); ?> is-dismissible">
		<p><?php echo esc_html( $directorist_affiliate_notices[ $notice ][1] ); ?></p>
	</div>
<?php endif; ?>

<?php
$directorist_affiliate_status_options = array( '' => __( 'All statuses', 'directorist-affiliate' ) );

foreach ( $plugin->referral->statuses() as $directorist_affiliate_status_key ) {
	$directorist_affiliate_status_options[ $directorist_affiliate_status_key ] = $plugin->referral->status_label( $directorist_affiliate_status_key );
}

$directorist_affiliate_type_options = array( '' => __( 'All event types', 'directorist-affiliate' ) );

foreach ( $plugin->referral->types() as $directorist_affiliate_type_key ) {
	$directorist_affiliate_type_options[ $directorist_affiliate_type_key ] = $plugin->referral->type_label( $directorist_affiliate_type_key );
}

Directorist_Affiliate_View::partial(
	'filter-bar.php',
	array(
		'tab'            => 'referrals',
		'export'            => 'referrals',
		'filters'        => $filters,
		'affiliate_list' => $affiliate_list,
		'fields'         => array(
			array(
				'type'    => 'select',
				'name'    => 'type',
				'label'   => __( 'Filter by event type', 'directorist-affiliate' ),
				'options' => $directorist_affiliate_type_options,
				'value'   => $filters['type'],
			),
			array(
				'type'    => 'select',
				'name'    => 'status',
				'label'   => __( 'Filter by status', 'directorist-affiliate' ),
				'options' => $directorist_affiliate_status_options,
				'value'   => $filters['status'],
			),
		),
		'count_label'    => sprintf(
			/* translators: %s: number of referrals. */
			_n( '%s referral', '%s referrals', (int) $total, 'directorist-affiliate' ),
			number_format_i18n( (int) $total )
		),
	)
);
?>

<form method="post">
	<?php wp_nonce_field( 'directorist_affiliate_referral_bulk' ); ?>
	<input type="hidden" name="directorist_affiliate_referral_bulk" value="1" />

	<div class="directorist-affiliate-bulk-bar">
		<label class="screen-reader-text" for="directorist-affiliate-bulk-action"><?php esc_html_e( 'Bulk action', 'directorist-affiliate' ); ?></label>
		<select id="directorist-affiliate-bulk-action" name="bulk_action">
			<option value=""><?php esc_html_e( 'Bulk actions', 'directorist-affiliate' ); ?></option>
			<option value="approve"><?php esc_html_e( 'Approve', 'directorist-affiliate' ); ?></option>
			<option value="reject"><?php esc_html_e( 'Reject', 'directorist-affiliate' ); ?></option>
			<option value="paid"><?php esc_html_e( 'Mark as paid', 'directorist-affiliate' ); ?></option>
		</select>
		<button type="submit" class="button"><?php esc_html_e( 'Apply', 'directorist-affiliate' ); ?></button>
	</div>

	<div class="directorist-affiliate-card directorist-affiliate-table-card">
		<table class="directorist-affiliate-table">
			<thead>
				<tr>
					<th class="is-check">
						<input type="checkbox" data-da-check-all aria-label="<?php esc_attr_e( 'Select all referrals', 'directorist-affiliate' ); ?>" />
					</th>
					<th><?php esc_html_e( 'Affiliate', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Event', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Referred', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Source', 'directorist-affiliate' ); ?></th>
					<th class="is-num"><?php esc_html_e( 'Commission', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Status', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Date', 'directorist-affiliate' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'directorist-affiliate' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $referrals ) : ?>
					<?php foreach ( $referrals as $referral ) : ?>
						<?php
						$affiliate = $affiliates_map[ (int) $referral->affiliate_id ] ?? null;
						$user      = $referral->referred_user_id ? get_user_by( 'id', (int) $referral->referred_user_id ) : false;
						$base_url  = Directorist_Affiliate_Admin::page_url( 'referrals', array( 'referral_id' => absint( $referral->id ) ) );
						?>
						<tr>
							<td class="is-check"><input type="checkbox" name="referral_ids[]" value="<?php echo esc_attr( (string) (int) $referral->id ); ?>" aria-label="<?php esc_attr_e( 'Select referral', 'directorist-affiliate' ); ?>" /></td>
							<td>
								<strong><?php echo esc_html( $affiliate ? $plugin->affiliate->get_name( $affiliate ) : __( 'Unknown', 'directorist-affiliate' ) ); ?></strong>
								<?php if ( $affiliate ) : ?>
									<span class="directorist-affiliate-cell-sub"><code><?php echo esc_html( $affiliate->referral_code ); ?></code></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $plugin->referral->type_label( (string) $referral->referral_type ) ); ?></td>
							<td>
								<?php echo esc_html( $user ? $user->user_email : __( 'Guest', 'directorist-affiliate' ) ); ?>
								<?php if ( $referral->listing_id ) : ?>
									<a class="directorist-affiliate-cell-sub" href="<?php echo esc_url( (string) get_edit_post_link( (int) $referral->listing_id ) ); ?>"><?php echo esc_html( get_the_title( (int) $referral->listing_id ) ); ?></a>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $referral->order_id ) ) : ?>
									<?php if ( 'legacy' === $referral->order_source ) : ?>
										<a href="<?php echo esc_url( (string) get_edit_post_link( (int) $referral->order_id ) ); ?>"><?php echo esc_html( '#' . (int) $referral->order_id ); ?></a>
									<?php else : ?>
										<?php echo esc_html( '#' . (int) $referral->order_id ); ?>
									<?php endif; ?>
									<?php if ( null !== $referral->order_total ) : ?>
										<span class="directorist-affiliate-cell-sub"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $referral->order_total ) ); ?></span>
									<?php endif; ?>
								<?php else : ?>
									<span class="directorist-affiliate-muted"><?php echo esc_html__( 'Free event', 'directorist-affiliate' ); ?></span>
								<?php endif; ?>
							</td>
							<td class="is-num"><strong><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $referral->commission_amount ) ); ?></strong></td>
							<td><span class="directorist-affiliate-badge is-<?php echo esc_attr( sanitize_html_class( $referral->status ) ); ?>"><?php echo esc_html( $plugin->referral->status_label( (string) $referral->status ) ); ?></span></td>
							<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $referral->date_created ) ); ?></td>
							<td class="directorist-affiliate-row-actions">
								<?php if ( 'approved' !== $referral->status ) : ?>
									<a class="directorist-affiliate-action is-approve" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'directorist_referral_action', 'approve', $base_url ), 'directorist_referral_action_' . absint( $referral->id ) ) ); ?>"><?php esc_html_e( 'Approve', 'directorist-affiliate' ); ?></a>
								<?php endif; ?>
								<?php if ( 'rejected' !== $referral->status ) : ?>
									<a class="directorist-affiliate-action is-reject" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'directorist_referral_action', 'reject', $base_url ), 'directorist_referral_action_' . absint( $referral->id ) ) ); ?>"><?php esc_html_e( 'Reject', 'directorist-affiliate' ); ?></a>
								<?php endif; ?>
								<?php if ( 'approved' === $referral->status ) : ?>
									<a class="directorist-affiliate-action is-pay" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'directorist_referral_action', 'paid', $base_url ), 'directorist_referral_action_' . absint( $referral->id ) ) ); ?>"><?php esc_html_e( 'Mark paid', 'directorist-affiliate' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="9">
							<div class="directorist-affiliate-empty">
								<span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
								<p>
									<?php
									echo $filters['status'] || $filters['type'] || $filters['affiliate_id'] || $filters['range']
										? esc_html__( 'No referrals match the current filters.', 'directorist-affiliate' )
										: esc_html__( 'No referrals recorded yet.', 'directorist-affiliate' );
									?>
								</p>
							</div>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</form>

<?php
echo wp_kses_post( Directorist_Affiliate_Admin::pagination( 'referrals', (int) $total, (int) $paged, $pagination_args ) );
?>

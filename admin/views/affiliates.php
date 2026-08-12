<?php
/**
 * Affiliates admin page.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

$directorist_affiliate_statuses = $plugin->affiliate->statuses();
?>
<?php if ( ! empty( $notice ) ) : ?>
	<?php
	$notice_message = $plugin->registration->admin_message( $notice );
	$is_success     = in_array( $notice, array( 'affiliate_created', 'affiliate_updated' ), true );
	?>
	<?php if ( $notice_message ) : ?>
		<div class="notice <?php echo $is_success ? 'notice-success' : 'notice-error'; ?> is-dismissible"><p><?php echo esc_html( $notice_message ); ?></p></div>
	<?php endif; ?>
<?php endif; ?>

<dialog id="directorist-affiliate-add-modal" class="directorist-affiliate-modal" aria-labelledby="directorist-affiliate-add-modal-title">
	<div class="directorist-affiliate-modal-head">
		<h2 id="directorist-affiliate-add-modal-title"><?php esc_html_e( 'Add affiliate', 'directorist-affiliate' ); ?></h2>
		<button type="button" class="directorist-affiliate-modal-close" data-da-modal-close aria-label="<?php esc_attr_e( 'Close dialog', 'directorist-affiliate' ); ?>">
			<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
		</button>
	</div>
	<form method="post" class="directorist-affiliate-admin-form" data-da-ajax="directorist_affiliate_add_affiliate" data-da-success="reload">
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
					<?php foreach ( $directorist_affiliate_statuses as $status_key ) : ?>
						<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( 'approved', $status_key ); ?>><?php echo esc_html( $plugin->affiliate->status_label( $status_key ) ); ?></option>
					<?php endforeach; ?>
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

		<div class="directorist-affiliate-modal-actions">
			<button type="button" class="button" data-da-modal-close><?php esc_html_e( 'Cancel', 'directorist-affiliate' ); ?></button>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Add affiliate', 'directorist-affiliate' ); ?></button>
		</div>
	</form>
</dialog>

<?php if ( ! empty( $selected_affiliate ) ) : ?>
	<div class="directorist-affiliate-card directorist-affiliate-detail">
		<h2><?php esc_html_e( 'Affiliate details', 'directorist-affiliate' ); ?></h2>
		<dl class="directorist-affiliate-detail-list">
			<div><dt><?php esc_html_e( 'Name', 'directorist-affiliate' ); ?></dt><dd><?php echo esc_html( $plugin->affiliate->get_name( $selected_affiliate ) ); ?></dd></div>
			<div><dt><?php esc_html_e( 'Email', 'directorist-affiliate' ); ?></dt><dd><?php echo esc_html( $plugin->affiliate->get_email( $selected_affiliate ) ); ?></dd></div>
			<div><dt><?php esc_html_e( 'Status', 'directorist-affiliate' ); ?></dt><dd><span class="directorist-affiliate-badge is-<?php echo esc_attr( sanitize_html_class( $selected_affiliate->status ) ); ?>"><?php echo esc_html( $plugin->affiliate->status_label( (string) $selected_affiliate->status ) ); ?></span></dd></div>
			<div><dt><?php esc_html_e( 'Referral code', 'directorist-affiliate' ); ?></dt><dd><code><?php echo esc_html( $selected_affiliate->referral_code ); ?></code></dd></div>
			<div><dt><?php esc_html_e( 'Payout email', 'directorist-affiliate' ); ?></dt><dd><?php echo esc_html( $selected_affiliate->payout_email ); ?></dd></div>
			<div><dt><?php esc_html_e( 'Website', 'directorist-affiliate' ); ?></dt><dd><?php echo $selected_affiliate->website ? '<a href="' . esc_url( $selected_affiliate->website ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $selected_affiliate->website ) . '</a>' : esc_html__( 'Not provided', 'directorist-affiliate' ); ?></dd></div>
			<div><dt><?php esc_html_e( 'Promotional channel', 'directorist-affiliate' ); ?></dt><dd><?php echo esc_html( $selected_affiliate->promotional_method ? $selected_affiliate->promotional_method : __( 'Not provided', 'directorist-affiliate' ) ); ?></dd></div>
		</dl>
		<?php if ( $selected_affiliate->application_note ) : ?>
			<p class="directorist-affiliate-detail-note"><?php echo nl2br( esc_html( $selected_affiliate->application_note ) ); ?></p>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php
$directorist_affiliate_status_options = array( '' => __( 'All statuses', 'directorist-affiliate' ) );

foreach ( $directorist_affiliate_statuses as $status_key ) {
	$directorist_affiliate_status_options[ $status_key ] = $plugin->affiliate->status_label( $status_key );
}

Directorist_Affiliate_View::partial(
	'filter-bar.php',
	array(
		'tab'         => 'affiliates',
		'filters'     => $filters,
		'lead_button' => array(
			'modal' => 'directorist-affiliate-add-modal',
			'label' => __( 'Add affiliate', 'directorist-affiliate' ),
		),
		'fields'      => array(
			array(
				'type'    => 'select',
				'name'    => 'status',
				'label'   => __( 'Filter by status', 'directorist-affiliate' ),
				'options' => $directorist_affiliate_status_options,
				'value'   => $filters['status'],
			),
			array(
				'type'        => 'search',
				'name'        => 's',
				'label'       => __( 'Search affiliates', 'directorist-affiliate' ),
				'placeholder' => __( 'Search name, email, code…', 'directorist-affiliate' ),
				'value'       => $filters['s'],
			),
		),
		'count_label' => sprintf(
			/* translators: %s: number of affiliates. */
			_n( '%s affiliate', '%s affiliates', (int) $total, 'directorist-affiliate' ),
			number_format_i18n( (int) $total )
		),
	)
);
?>

<div class="directorist-affiliate-card directorist-affiliate-table-card">
	<table class="directorist-affiliate-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Affiliate', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Status', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Code', 'directorist-affiliate' ); ?></th>
				<th class="is-num"><?php esc_html_e( 'Referrals', 'directorist-affiliate' ); ?></th>
				<th class="is-num"><?php esc_html_e( 'Total earned', 'directorist-affiliate' ); ?></th>
				<th class="is-num"><?php esc_html_e( 'Unpaid', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Applied', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'directorist-affiliate' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $affiliates ) : ?>
				<?php foreach ( $affiliates as $affiliate ) : ?>
					<?php
					$stats    = $referral_stats[ (int) $affiliate->id ] ?? null;
					$base_url = Directorist_Affiliate_Admin::page_url( 'affiliates', array( 'affiliate_id' => absint( $affiliate->id ) ) );
					$actions  = array(
						'approve' => __( 'Approve', 'directorist-affiliate' ),
						'reject'  => __( 'Reject', 'directorist-affiliate' ),
						'suspend' => __( 'Suspend', 'directorist-affiliate' ),
					);

					// Hide the action that would repeat the affiliate's current status.
					$redundant = array(
						'approved'  => 'approve',
						'rejected'  => 'reject',
						'suspended' => 'suspend',
					);

					unset( $actions[ $redundant[ $affiliate->status ] ?? '' ] );
					?>
					<tr>
						<td>
							<strong><?php echo esc_html( $plugin->affiliate->get_name( $affiliate ) ); ?></strong>
							<span class="directorist-affiliate-cell-sub"><?php echo esc_html( $plugin->affiliate->get_email( $affiliate ) ); ?></span>
							<?php if ( $affiliate->website ) : ?>
								<a class="directorist-affiliate-cell-sub" href="<?php echo esc_url( $affiliate->website ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $affiliate->website ); ?></a>
							<?php endif; ?>
						</td>
						<td><span class="directorist-affiliate-badge is-<?php echo esc_attr( sanitize_html_class( $affiliate->status ) ); ?>"><?php echo esc_html( $plugin->affiliate->status_label( (string) $affiliate->status ) ); ?></span></td>
						<td><code><?php echo esc_html( $affiliate->referral_code ); ?></code></td>
						<td class="is-num">
							<?php if ( $stats && (int) $stats->total_referrals > 0 ) : ?>
								<a href="<?php echo esc_url( Directorist_Affiliate_Admin::page_url( 'referrals', array( 'for_affiliate' => absint( $affiliate->id ) ) ) ); ?>"><?php echo esc_html( number_format_i18n( (int) $stats->total_referrals ) ); ?></a>
							<?php else : ?>
								0
							<?php endif; ?>
						</td>
						<td class="is-num"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( $stats ? (float) $stats->total_commission : 0 ) ); ?></td>
						<td class="is-num"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( $stats ? (float) $stats->approved_commission : 0 ) ); ?></td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $affiliate->date_created ) ); ?></td>
						<td class="directorist-affiliate-row-actions">
							<a class="directorist-affiliate-action" href="<?php echo esc_url( Directorist_Affiliate_Admin::page_url( 'affiliates', array( 'view_affiliate' => absint( $affiliate->id ) ) ) ); ?>"><?php esc_html_e( 'Details', 'directorist-affiliate' ); ?></a>
							<?php foreach ( $actions as $action_key => $action_label ) : ?>
								<a class="directorist-affiliate-action is-<?php echo esc_attr( $action_key ); ?>" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'directorist_affiliate_action', $action_key, $base_url ), 'directorist_affiliate_action_' . absint( $affiliate->id ) ) ); ?>"><?php echo esc_html( $action_label ); ?></a>
							<?php endforeach; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="8">
						<div class="directorist-affiliate-empty">
							<span class="dashicons dashicons-groups" aria-hidden="true"></span>
							<p>
								<?php
								echo $filters['status'] || $filters['s'] || $filters['range']
									? esc_html__( 'No affiliates match the current filters.', 'directorist-affiliate' )
									: esc_html__( 'No affiliates yet. Share your registration page to start receiving applications.', 'directorist-affiliate' );
								?>
							</p>
						</div>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<?php
echo wp_kses_post( Directorist_Affiliate_Admin::pagination( 'affiliates', (int) $total, (int) $paged, $pagination_args ) );
?>

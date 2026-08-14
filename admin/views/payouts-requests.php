<?php
/**
 * Payouts → Requests.
 *
 * Open payout claims from affiliates. Nothing here has been paid: approving
 * pays the covered commissions, rejecting leaves them in the balance.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

Directorist_Affiliate_View::partial(
	'payout-sections.php',
	array(
		'sections' => $sections,
		'section'  => $section,
	)
);

$directorist_affiliate_requested_total = 0.0;

foreach ( $requests as $directorist_affiliate_request ) {
	$directorist_affiliate_requested_total += (float) $directorist_affiliate_request->amount;
}
$directorist_affiliate_notices = array(
	'payout_paid'      => array( 'success', __( 'Payout marked as paid and its commissions settled.', 'directorist-affiliate' ) ),
	'payout_rejected'  => array( 'warning', __( 'Payout request rejected. Those commissions stay in the affiliate\'s balance.', 'directorist-affiliate' ) ),
	'payout_unpayable' => array( 'error', __( 'None of the commissions in that request are payable any more — reject it instead.', 'directorist-affiliate' ) ),
	'payout_missing'   => array( 'error', __( 'That payout request no longer exists.', 'directorist-affiliate' ) ),
);

$directorist_affiliate_notice = isset( $_GET['directorist_affiliate_notice'] ) ? sanitize_key( wp_unslash( $_GET['directorist_affiliate_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<?php if ( isset( $directorist_affiliate_notices[ $directorist_affiliate_notice ] ) ) : ?>
	<div class="notice notice-<?php echo esc_attr( $directorist_affiliate_notices[ $directorist_affiliate_notice ][0] ); ?> is-dismissible">
		<p><?php echo esc_html( $directorist_affiliate_notices[ $directorist_affiliate_notice ][1] ); ?></p>
	</div>
<?php endif; ?>

<?php if ( $requests ) : ?>
	<div class="directorist-affiliate-toolbar">
		<div class="directorist-affiliate-toolbar-info">
			<span class="directorist-affiliate-toolbar-label"><?php esc_html_e( 'Requested', 'directorist-affiliate' ); ?></span>
			<span class="directorist-affiliate-toolbar-value"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( $directorist_affiliate_requested_total ) ); ?></span>
			<span class="directorist-affiliate-toolbar-note">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: number of open requests. */
						_n( '%s affiliate is waiting to be paid', '%s affiliates are waiting to be paid', (int) $total, 'directorist-affiliate' ),
						number_format_i18n( (int) $total )
					)
				);
				?>
			</span>
		</div>
	</div>
<?php endif; ?>

<div class="directorist-affiliate-card directorist-affiliate-table-card">
	<table class="directorist-affiliate-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Affiliate', 'directorist-affiliate' ); ?></th>
				<th class="is-num"><?php esc_html_e( 'Amount', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Pay by', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Covers', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Requested', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'directorist-affiliate' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $requests ) : ?>
				<?php foreach ( $requests as $request ) : ?>
					<?php
					$affiliate = $affiliates_map[ (int) $request->affiliate_id ] ?? null;
					$covers    = $plugin->payout->referral_ids( $request );
					$method    = (string) $request->payment_method;
					$details   = $plugin->payout_methods->decode( $request->payout_details ?? '' );
					$base_url  = Directorist_Affiliate_Admin::page_url( 'payouts', array( 'section' => 'requests' ) );
					?>
					<tr>
						<td>
							<strong><?php echo esc_html( $affiliate ? $plugin->affiliate->get_name( $affiliate ) : __( 'Unknown', 'directorist-affiliate' ) ); ?></strong>
							<?php if ( $affiliate ) : ?>
								<a class="directorist-affiliate-cell-sub" href="<?php echo esc_url( Directorist_Affiliate_Admin::page_url( 'referrals', array( 'affiliate' => absint( $affiliate->id ) ) ) ); ?>">
									<?php esc_html_e( 'View referrals', 'directorist-affiliate' ); ?>
								</a>
							<?php endif; ?>
						</td>
						<td class="is-num"><strong><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $request->amount ) ); ?></strong></td>
						<td>
							<strong><?php echo esc_html( $plugin->payout_methods->label( $method ) ); ?></strong>
							<?php foreach ( $details as $detail_value ) : ?>
								<span class="directorist-affiliate-cell-sub"><?php echo esc_html( $detail_value ); ?></span>
							<?php endforeach; ?>
							<?php if ( ! $details && $request->payout_email ) : ?>
								<span class="directorist-affiliate-cell-sub"><?php echo esc_html( $request->payout_email ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: number of commissions. */
									_n( '%s commission', '%s commissions', count( $covers ), 'directorist-affiliate' ),
									number_format_i18n( count( $covers ) )
								)
							);
							?>
							<?php if ( $request->notes ) : ?>
								<span class="directorist-affiliate-cell-sub"><?php echo esc_html( wp_trim_words( $request->notes, 14 ) ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $request->date_created ) ); ?></td>
						<td class="directorist-affiliate-row-actions">
							<a class="directorist-affiliate-action is-approve" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'directorist_payout_action' => 'pay', 'payout_id' => absint( $request->id ) ), $base_url ), 'directorist_payout_action_' . absint( $request->id ) ) ); ?>">
								<?php esc_html_e( 'Mark paid', 'directorist-affiliate' ); ?>
							</a>
							<a class="directorist-affiliate-action is-reject" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'directorist_payout_action' => 'reject', 'payout_id' => absint( $request->id ) ), $base_url ), 'directorist_payout_action_' . absint( $request->id ) ) ); ?>">
								<?php esc_html_e( 'Reject', 'directorist-affiliate' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="6">
						<div class="directorist-affiliate-empty">
							<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
							<p><?php esc_html_e( 'No open payout requests. Requests from affiliates appear here for approval.', 'directorist-affiliate' ); ?></p>
						</div>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<?php if ( $requests ) : ?>
	<p class="directorist-affiliate-view-all">
		<?php esc_html_e( 'Marking a request paid pays every commission it still covers; rejecting leaves them in the affiliate\'s balance.', 'directorist-affiliate' ); ?>
	</p>
<?php endif; ?>

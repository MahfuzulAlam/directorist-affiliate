<?php
/**
 * Payouts → Payout history.
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

Directorist_Affiliate_View::partial(
	'filter-bar.php',
	array(
		'tab'            => 'payouts',
		'export'            => 'payouts',
		'section'        => 'history',
		'filters'        => $filters,
		'affiliate_list' => $affiliate_list,
		'fields'         => array(
			array(
				'type'        => 'search',
				'name'        => 'email',
				'label'       => __( 'Search by payout email', 'directorist-affiliate' ),
				'placeholder' => __( 'Payout email…', 'directorist-affiliate' ),
				'value'       => $filters['email'],
			),
		),
		'count_label'    => sprintf(
			/* translators: %s: number of payouts. */
			_n( '%s payout', '%s payouts', (int) $total, 'directorist-affiliate' ),
			number_format_i18n( (int) $total )
		),
	)
);
?>
<div class="directorist-affiliate-toolbar">
	<div class="directorist-affiliate-toolbar-info">
		<span class="directorist-affiliate-toolbar-label"><?php esc_html_e( 'Total paid', 'directorist-affiliate' ); ?></span>
		<span class="directorist-affiliate-toolbar-value"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $total_paid ) ); ?></span>
		<span class="directorist-affiliate-toolbar-note">
			<?php
			echo esc_html(
				$filters['range'] || $filters['affiliate_id'] || $filters['email']
					? __( 'Matching the current filters', 'directorist-affiliate' )
					: __( 'All payouts on record', 'directorist-affiliate' )
			);
			?>
		</span>
	</div>
</div>

<div class="directorist-affiliate-card directorist-affiliate-table-card">
	<table class="directorist-affiliate-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Affiliate', 'directorist-affiliate' ); ?></th>
				<th class="is-num"><?php esc_html_e( 'Amount', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Status', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Paid by', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Commissions', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Date paid', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Notes', 'directorist-affiliate' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $payouts ) : ?>
				<?php foreach ( $payouts as $payout ) : ?>
					<?php
					$affiliate                    = $affiliates_map[ (int) $payout->affiliate_id ] ?? null;
					// referral_ids is a nullable column; older rows may have no list.
					$directorist_affiliate_covers = array_filter( array_map( 'absint', explode( ',', (string) ( $payout->referral_ids ?? '' ) ) ) );
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
						<td class="is-num"><strong><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $payout->amount ) ); ?></strong></td>
						<td><span class="directorist-affiliate-badge is-<?php echo esc_attr( sanitize_html_class( $payout->status ) ); ?>"><?php echo esc_html( $plugin->payout->status_label( (string) $payout->status ) ); ?></span></td>
						<td>
							<?php echo esc_html( $plugin->payout_methods->label( (string) $payout->payment_method ) ); ?>
							<?php if ( $payout->payout_email ) : ?>
								<span class="directorist-affiliate-cell-sub"><?php echo esc_html( $payout->payout_email ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: number of commissions covered by the payout. */
									_n( '%s commission', '%s commissions', count( $directorist_affiliate_covers ), 'directorist-affiliate' ),
									number_format_i18n( count( $directorist_affiliate_covers ) )
								)
							);
							?>
							<span class="directorist-affiliate-cell-sub"><?php echo esc_html( '#' . implode( ', #', $directorist_affiliate_covers ) ); ?></span>
						</td>
						<td><?php echo esc_html( $payout->date_paid ? mysql2date( get_option( 'date_format' ), $payout->date_paid ) : '—' ); ?></td>
						<td><span class="directorist-affiliate-muted"><?php echo esc_html( $payout->notes ); ?></span></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="7">
						<div class="directorist-affiliate-empty">
							<span class="dashicons dashicons-bank" aria-hidden="true"></span>
							<p>
								<?php
								echo $filters['range'] || $filters['affiliate_id'] || $filters['email']
									? esc_html__( 'No payouts match the current filters.', 'directorist-affiliate' )
									: esc_html__( 'No payouts recorded yet.', 'directorist-affiliate' );
								?>
							</p>
						</div>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<?php echo wp_kses_post( Directorist_Affiliate_Admin::pagination( 'payouts', (int) $total, (int) $paged, $pagination_args ) ); ?>

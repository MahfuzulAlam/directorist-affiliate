<?php
/**
 * Visits admin page.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

Directorist_Affiliate_View::partial(
	'filter-bar.php',
	array(
		'tab'            => 'visits',
		'filters'        => $filters,
		'affiliate_list' => $affiliate_list,
		'fields'         => array(
			array(
				'type'    => 'select',
				'name'    => 'converted',
				'label'   => __( 'Filter by conversion', 'directorist-affiliate' ),
				'options' => array(
					''  => __( 'Converted or not', 'directorist-affiliate' ),
					'1' => __( 'Converted only', 'directorist-affiliate' ),
					'0' => __( 'Not converted', 'directorist-affiliate' ),
				),
				'value'   => $filters['converted'],
			),
		),
		'count_label'    => sprintf(
			/* translators: %s: number of visits. */
			_n( '%s visit', '%s visits', (int) $total, 'directorist-affiliate' ),
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
				<th><?php esc_html_e( 'Landing page', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Came from', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'IP', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Date', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Converted', 'directorist-affiliate' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $visits ) : ?>
				<?php foreach ( $visits as $visit ) : ?>
					<?php
					$affiliate      = $affiliates_map[ (int) $visit->affiliate_id ] ?? null;
					$landing_path   = (string) wp_parse_url( $visit->landing_url, PHP_URL_PATH );
					$landing_label  = '' !== $landing_path && '/' !== $landing_path ? $landing_path : __( 'Home', 'directorist-affiliate' );
					$referrer_host  = $visit->referrer_url ? (string) wp_parse_url( $visit->referrer_url, PHP_URL_HOST ) : '';
					?>
					<tr>
						<td>
							<strong><?php echo esc_html( $affiliate ? $plugin->affiliate->get_name( $affiliate ) : __( 'Unknown', 'directorist-affiliate' ) ); ?></strong>
							<span class="directorist-affiliate-cell-sub"><code><?php echo esc_html( $visit->referral_code ); ?></code></span>
						</td>
						<td><a href="<?php echo esc_url( $visit->landing_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $landing_label ); ?></a></td>
						<td>
							<?php if ( $referrer_host ) : ?>
								<a href="<?php echo esc_url( $visit->referrer_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $referrer_host ); ?></a>
							<?php else : ?>
								<span class="directorist-affiliate-muted"><?php esc_html_e( 'Direct', 'directorist-affiliate' ); ?></span>
							<?php endif; ?>
						</td>
						<td><span class="directorist-affiliate-muted"><?php echo esc_html( $visit->ip_address ); ?></span></td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $visit->date_created ) ); ?></td>
						<td>
							<?php if ( $visit->converted ) : ?>
								<span class="directorist-affiliate-badge is-converted"><?php esc_html_e( 'Converted', 'directorist-affiliate' ); ?></span>
							<?php else : ?>
								<span class="directorist-affiliate-badge"><?php esc_html_e( 'No', 'directorist-affiliate' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="6">
						<div class="directorist-affiliate-empty">
							<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
							<p>
								<?php
								echo '' !== $filters['converted'] || $filters['affiliate_id'] || $filters['range']
									? esc_html__( 'No visits match the current filters.', 'directorist-affiliate' )
									: esc_html__( 'No referral link visits recorded yet.', 'directorist-affiliate' );
								?>
							</p>
						</div>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<?php echo wp_kses_post( Directorist_Affiliate_Admin::pagination( 'visits', (int) $total, (int) $paged, $pagination_args ) ); ?>

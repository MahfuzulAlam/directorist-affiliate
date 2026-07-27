<?php
/**
 * Visits admin page.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;
?>
<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Affiliate', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Landing page', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Referrer URL', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'IP', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Date', 'directorist-affiliate' ); ?></th>
				<th><?php esc_html_e( 'Converted', 'directorist-affiliate' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $visits ) : ?>
				<?php foreach ( $visits as $visit ) : ?>
					<?php $affiliate = $plugin->affiliate->get( (int) $visit->affiliate_id ); ?>
					<tr>
						<td><?php echo esc_html( $affiliate ? $plugin->affiliate->get_name( $affiliate ) : __( 'Unknown', 'directorist-affiliate' ) ); ?></td>
						<td><a href="<?php echo esc_url( $visit->landing_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( wp_trim_words( $visit->landing_url, 10 ) ); ?></a></td>
						<td><?php echo $visit->referrer_url ? '<a href="' . esc_url( $visit->referrer_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( wp_trim_words( $visit->referrer_url, 10 ) ) . '</a>' : esc_html__( 'Direct', 'directorist-affiliate' ); ?></td>
						<td><?php echo esc_html( $visit->ip_address ); ?></td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $visit->date_created ) ); ?></td>
						<td>
							<?php if ( $visit->converted ) : ?>
								<span class="directorist-affiliate-badge is-converted"><?php esc_html_e( 'Yes', 'directorist-affiliate' ); ?></span>
							<?php else : ?>
								<span class="directorist-affiliate-badge"><?php esc_html_e( 'No', 'directorist-affiliate' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No visits found.', 'directorist-affiliate' ); ?></td></tr>
			<?php endif; ?>
	</tbody>
</table>

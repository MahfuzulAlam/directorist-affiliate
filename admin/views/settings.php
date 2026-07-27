<?php
/**
 * Settings admin page.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap directorist-affiliate-admin">
	<h1><?php esc_html_e( 'Affiliate Settings', 'directorist-affiliate' ); ?></h1>
	<form method="post" data-da-ajax="directorist_affiliate_save_settings">
		<?php wp_nonce_field( 'directorist_affiliate_save_settings' ); ?>
		<input type="hidden" name="directorist_affiliate_save_settings" value="1" />

		<h2><?php esc_html_e( 'General', 'directorist-affiliate' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable affiliate system', 'directorist-affiliate' ); ?></th>
				<td><label><input type="checkbox" name="enabled" value="1" <?php checked( $settings['enabled'], 1 ); ?> /> <?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><label for="directorist-affiliate-ref-param"><?php esc_html_e( 'Referral URL parameter', 'directorist-affiliate' ); ?></label></th>
				<td><input id="directorist-affiliate-ref-param" type="text" class="regular-text" name="ref_param" value="<?php echo esc_attr( $settings['ref_param'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="directorist-affiliate-cookie-duration"><?php esc_html_e( 'Cookie duration', 'directorist-affiliate' ); ?></label></th>
				<td><input id="directorist-affiliate-cookie-duration" type="number" min="1" name="cookie_duration" value="<?php echo esc_attr( $settings['cookie_duration'] ); ?>" /> <?php esc_html_e( 'days', 'directorist-affiliate' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Anonymize visitor IP', 'directorist-affiliate' ); ?></th>
				<td>
					<label><input type="checkbox" name="anonymize_ip" value="1" <?php checked( $settings['anonymize_ip'], 1 ); ?> /> <?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></label>
					<p class="description"><?php esc_html_e( 'Store visit IP addresses with the last octet removed (recommended for GDPR compliance).', 'directorist-affiliate' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Registration Commission', 'directorist-affiliate' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable registration commission', 'directorist-affiliate' ); ?></th>
				<td><label><input type="checkbox" name="enable_registration" value="1" <?php checked( $settings['enable_registration'], 1 ); ?> /> <?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><label for="directorist-affiliate-registration-amount"><?php esc_html_e( 'Fixed commission amount', 'directorist-affiliate' ); ?></label></th>
				<td><input id="directorist-affiliate-registration-amount" type="text" name="registration_amount" value="<?php echo esc_attr( $settings['registration_amount'] ); ?>" /></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Listing Commission', 'directorist-affiliate' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable listing commission', 'directorist-affiliate' ); ?></th>
				<td><label><input type="checkbox" name="enable_listing" value="1" <?php checked( $settings['enable_listing'], 1 ); ?> /> <?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><label for="directorist-affiliate-listing-amount"><?php esc_html_e( 'Fixed commission amount', 'directorist-affiliate' ); ?></label></th>
				<td><input id="directorist-affiliate-listing-amount" type="text" name="listing_amount" value="<?php echo esc_attr( $settings['listing_amount'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="directorist-affiliate-listing-trigger"><?php esc_html_e( 'Commission trigger', 'directorist-affiliate' ); ?></label></th>
				<td>
					<select id="directorist-affiliate-listing-trigger" name="listing_trigger">
						<option value="submission" <?php selected( $settings['listing_trigger'], 'submission' ); ?>><?php esc_html_e( 'On listing submission', 'directorist-affiliate' ); ?></option>
						<option value="publish" <?php selected( $settings['listing_trigger'], 'publish' ); ?>><?php esc_html_e( 'On listing approval/publish', 'directorist-affiliate' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Payout', 'directorist-affiliate' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="directorist-affiliate-minimum-payout"><?php esc_html_e( 'Minimum payout amount', 'directorist-affiliate' ); ?></label></th>
				<td><input id="directorist-affiliate-minimum-payout" type="text" name="minimum_payout" value="<?php echo esc_attr( $settings['minimum_payout'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="directorist-affiliate-payout-instructions"><?php esc_html_e( 'Payout instructions', 'directorist-affiliate' ); ?></label></th>
				<td><textarea id="directorist-affiliate-payout-instructions" class="large-text" rows="5" name="payout_instructions"><?php echo esc_textarea( $settings['payout_instructions'] ); ?></textarea></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Advanced', 'directorist-affiliate' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Delete data on uninstall', 'directorist-affiliate' ); ?></th>
				<td>
					<label><input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( $settings['delete_data_on_uninstall'], 1 ); ?> /> <?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></label>
					<p class="description"><?php esc_html_e( 'Permanently remove all affiliate tables, settings, and related user meta when the plugin is deleted.', 'directorist-affiliate' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save settings', 'directorist-affiliate' ) ); ?>
	</form>
</div>

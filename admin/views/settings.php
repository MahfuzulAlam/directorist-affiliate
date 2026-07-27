<?php
/**
 * Settings admin tab.
 *
 * All sections live in one form; the sub-tab navigation only toggles
 * visibility, so every field is submitted together (AJAX or fallback POST).
 * Without JavaScript the sections render stacked.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

$directorist_affiliate_sections = array(
	'general'      => __( 'General', 'directorist-affiliate' ),
	'registration' => __( 'Registration Commission', 'directorist-affiliate' ),
	'listing'      => __( 'Listing Commission', 'directorist-affiliate' ),
	'orders'       => __( 'Order Commissions', 'directorist-affiliate' ),
	'payout'       => __( 'Payout', 'directorist-affiliate' ),
	'advanced'     => __( 'Advanced', 'directorist-affiliate' ),
);

$plan_available     = Directorist_Affiliate_Commission::is_pricing_plans_active();
$featured_available = Directorist_Affiliate_Commission::is_featured_monetization_active();
?>
<div class="directorist-affiliate-settings-tabs">
	<nav class="directorist-affiliate-subtab-nav" aria-label="<?php esc_attr_e( 'Settings sections', 'directorist-affiliate' ); ?>">
		<?php foreach ( $directorist_affiliate_sections as $section_key => $section_label ) : ?>
			<button type="button" class="directorist-affiliate-subtab-link" data-target="<?php echo esc_attr( $section_key ); ?>">
				<?php echo esc_html( $section_label ); ?>
			</button>
		<?php endforeach; ?>
	</nav>

	<form method="post" data-da-ajax="directorist_affiliate_save_settings">
		<?php wp_nonce_field( 'directorist_affiliate_save_settings' ); ?>
		<input type="hidden" name="directorist_affiliate_save_settings" value="1" />

		<div class="directorist-affiliate-settings-section" data-section="general">
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
					<th scope="row"><label for="directorist-affiliate-attribution-model"><?php esc_html_e( 'Attribution model', 'directorist-affiliate' ); ?></label></th>
					<td>
						<select id="directorist-affiliate-attribution-model" name="attribution_model">
							<option value="first_click" <?php selected( $settings['attribution_model'], 'first_click' ); ?>><?php esc_html_e( 'First click (recommended)', 'directorist-affiliate' ); ?></option>
							<option value="last_click" <?php selected( $settings['attribution_model'], 'last_click' ); ?>><?php esc_html_e( 'Last click', 'directorist-affiliate' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'First click: the first affiliate a visitor clicks keeps the credit until the cookie expires. Last click: every new affiliate link overwrites the previous credit.', 'directorist-affiliate' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Anonymize visitor IP', 'directorist-affiliate' ); ?></th>
					<td>
						<label><input type="checkbox" name="anonymize_ip" value="1" <?php checked( $settings['anonymize_ip'], 1 ); ?> /> <?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Store visit IP addresses with the last octet removed (recommended for GDPR compliance).', 'directorist-affiliate' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="directorist-affiliate-settings-section" data-section="registration">
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
		</div>

		<div class="directorist-affiliate-settings-section" data-section="listing">
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
		</div>

		<div class="directorist-affiliate-settings-section" data-section="orders">
			<h2><?php esc_html_e( 'Order Commissions (Paid Events)', 'directorist-affiliate' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Commissions on completed paid orders. Refunded or cancelled orders reverse their commission automatically.', 'directorist-affiliate' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Pricing plan purchases', 'directorist-affiliate' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enable_plan_commission" value="1" <?php checked( $settings['enable_plan_commission'], 1 ); ?> <?php disabled( ! $plan_available ); ?> />
							<?php esc_html_e( 'Pay commission when a referred user buys a pricing plan', 'directorist-affiliate' ); ?>
						</label>
						<?php if ( ! $plan_available ) : ?>
							<p class="description"><strong><?php esc_html_e( 'Inactive:', 'directorist-affiliate' ); ?></strong> <?php esc_html_e( 'requires the Directorist Pricing Plans extension. Activate it to enable this event.', 'directorist-affiliate' ); ?></p>
						<?php endif; ?>
						<p>
							<select name="plan_commission_type" <?php disabled( ! $plan_available ); ?>>
								<option value="percentage" <?php selected( $settings['plan_commission_type'], 'percentage' ); ?>><?php esc_html_e( 'Percentage of order total (%)', 'directorist-affiliate' ); ?></option>
								<option value="fixed" <?php selected( $settings['plan_commission_type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'directorist-affiliate' ); ?></option>
							</select>
							<input type="text" name="plan_commission_value" value="<?php echo esc_attr( $settings['plan_commission_value'] ); ?>" <?php disabled( ! $plan_available ); ?> />
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Featured listing purchases', 'directorist-affiliate' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enable_featured_commission" value="1" <?php checked( $settings['enable_featured_commission'], 1 ); ?> <?php disabled( ! $featured_available ); ?> />
							<?php esc_html_e( 'Pay commission when a referred user buys featured status', 'directorist-affiliate' ); ?>
						</label>
						<?php if ( ! $featured_available ) : ?>
							<p class="description"><strong><?php esc_html_e( 'Inactive:', 'directorist-affiliate' ); ?></strong> <?php esc_html_e( 'requires Directorist monetization with featured listings enabled (Directory Settings → Monetization).', 'directorist-affiliate' ); ?></p>
						<?php endif; ?>
						<p>
							<select name="featured_commission_type" <?php disabled( ! $featured_available ); ?>>
								<option value="percentage" <?php selected( $settings['featured_commission_type'], 'percentage' ); ?>><?php esc_html_e( 'Percentage of order total (%)', 'directorist-affiliate' ); ?></option>
								<option value="fixed" <?php selected( $settings['featured_commission_type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'directorist-affiliate' ); ?></option>
							</select>
							<input type="text" name="featured_commission_value" value="<?php echo esc_attr( $settings['featured_commission_value'] ); ?>" <?php disabled( ! $featured_available ); ?> />
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Auto-approve commissions', 'directorist-affiliate' ); ?></th>
					<td>
						<label><input type="checkbox" name="auto_approve_commissions" value="1" <?php checked( $settings['auto_approve_commissions'], 1 ); ?> /> <?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'New referrals are created as Approved (payable immediately) instead of Pending review.', 'directorist-affiliate' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="directorist-affiliate-settings-section" data-section="payout">
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
		</div>

		<div class="directorist-affiliate-settings-section" data-section="advanced">
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
		</div>

		<?php submit_button( __( 'Save settings', 'directorist-affiliate' ) ); ?>
	</form>
</div>

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
	'general'       => array(
		'label' => __( 'General', 'directorist-affiliate' ),
		'icon'  => 'dashicons-admin-generic',
	),
	'commissions'   => array(
		'label' => __( 'Commissions', 'directorist-affiliate' ),
		'icon'  => 'dashicons-money-alt',
	),
	'payout'        => array(
		'label' => __( 'Payout', 'directorist-affiliate' ),
		'icon'  => 'dashicons-bank',
	),
	'notifications' => array(
		'label' => __( 'Notifications', 'directorist-affiliate' ),
		'icon'  => 'dashicons-email-alt',
	),
	'advanced'      => array(
		'label' => __( 'Advanced', 'directorist-affiliate' ),
		'icon'  => 'dashicons-admin-tools',
	),
);

$plan_available     = Directorist_Affiliate_Commission::is_pricing_plans_active();
$featured_available = Directorist_Affiliate_Commission::is_featured_monetization_active();
?>
<?php if ( ! empty( $updated ) ) : ?>
	<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'directorist-affiliate' ); ?></p></div>
<?php endif; ?>

<div class="directorist-affiliate-settings-tabs">
	<nav class="directorist-affiliate-subtab-nav" aria-label="<?php esc_attr_e( 'Settings sections', 'directorist-affiliate' ); ?>">
		<?php foreach ( $directorist_affiliate_sections as $section_key => $section ) : ?>
			<button type="button" class="directorist-affiliate-subtab-link" data-target="<?php echo esc_attr( $section_key ); ?>">
				<span class="dashicons <?php echo esc_attr( $section['icon'] ); ?>" aria-hidden="true"></span>
				<?php echo esc_html( $section['label'] ); ?>
			</button>
		<?php endforeach; ?>
	</nav>

	<form method="post" data-da-ajax="directorist_affiliate_save_settings">
		<?php wp_nonce_field( 'directorist_affiliate_save_settings' ); ?>
		<input type="hidden" name="directorist_affiliate_save_settings" value="1" />

		<div class="directorist-affiliate-settings-section" data-section="general">
			<h2><?php esc_html_e( 'General', 'directorist-affiliate' ); ?></h2>

			<div class="directorist-affiliate-card directorist-affiliate-field-card">
				<div class="directorist-affiliate-field is-toggle">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-enabled"><?php esc_html_e( 'Affiliate system', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Master switch. When off, no visits are tracked and no commissions are recorded.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch">
							<input id="directorist-affiliate-enabled" type="checkbox" name="enabled" value="1" <?php checked( $settings['enabled'], 1 ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></span>
						</label>
					</div>
				</div>

				<div class="directorist-affiliate-field">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-ref-param"><?php esc_html_e( 'Referral URL parameter', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'The query key in referral links, e.g. ?ref=CODE.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<div class="directorist-affiliate-input-affix">
							<span class="directorist-affiliate-affix">?</span>
							<input id="directorist-affiliate-ref-param" type="text" name="ref_param" value="<?php echo esc_attr( $settings['ref_param'] ); ?>" />
							<span class="directorist-affiliate-affix">=CODE</span>
						</div>
					</div>
				</div>

				<div class="directorist-affiliate-field">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-cookie-duration"><?php esc_html_e( 'Cookie duration', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'How long a click stays credited to the affiliate. 30–90 days is typical.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<div class="directorist-affiliate-input-affix">
							<input id="directorist-affiliate-cookie-duration" type="number" min="1" max="3650" name="cookie_duration" value="<?php echo esc_attr( $settings['cookie_duration'] ); ?>" />
							<span class="directorist-affiliate-affix"><?php esc_html_e( 'days', 'directorist-affiliate' ); ?></span>
						</div>
					</div>
				</div>

				<div class="directorist-affiliate-field">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-attribution-model"><?php esc_html_e( 'Attribution model', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'First click keeps the original affiliate credited until the cookie expires. Last click lets a newer affiliate link take over.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<select id="directorist-affiliate-attribution-model" name="attribution_model">
							<option value="first_click" <?php selected( $settings['attribution_model'], 'first_click' ); ?>><?php esc_html_e( 'First click (recommended)', 'directorist-affiliate' ); ?></option>
							<option value="last_click" <?php selected( $settings['attribution_model'], 'last_click' ); ?>><?php esc_html_e( 'Last click', 'directorist-affiliate' ); ?></option>
						</select>
					</div>
				</div>

				<div class="directorist-affiliate-field is-toggle">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-enable-applications"><?php esc_html_e( 'Accept applications', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Show the application form and accept new submissions. Turn off to pause recruiting without disabling tracking.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch">
							<input id="directorist-affiliate-enable-applications" type="checkbox" name="enable_applications" value="1" <?php checked( $settings['enable_applications'], 1 ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></span>
						</label>
					</div>
				</div>

				<div class="directorist-affiliate-field">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-dashboard-page"><?php esc_html_e( 'Affiliate dashboard page', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'The page holding the [directorist_affiliate_dashboard] shortcode. Affiliates who have already applied are sent here instead of the application form. Leave unset to use the Directorist user dashboard.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'dashboard_page',
								'id'                => 'directorist-affiliate-dashboard-page',
								'selected'          => absint( $settings['dashboard_page'] ),
								'show_option_none'  => __( '— Use the Directorist dashboard —', 'directorist-affiliate' ),
								'option_none_value' => '0',
							)
						);
						?>
					</div>
				</div>

				<div class="directorist-affiliate-field is-toggle">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-require-login"><?php esc_html_e( 'Require login to apply', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Applicants must have a WordPress account. When off, applying creates an account automatically.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch">
							<input id="directorist-affiliate-require-login" type="checkbox" name="applications_require_login" value="1" <?php checked( $settings['applications_require_login'], 1 ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Required', 'directorist-affiliate' ); ?></span>
						</label>
					</div>
				</div>

				<div class="directorist-affiliate-field is-toggle">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-anonymize-ip"><?php esc_html_e( 'Anonymize visitor IP', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Store visit IPs with the last octet removed (recommended for GDPR compliance).', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch">
							<input id="directorist-affiliate-anonymize-ip" type="checkbox" name="anonymize_ip" value="1" <?php checked( $settings['anonymize_ip'], 1 ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></span>
						</label>
					</div>
				</div>
			</div>
		</div>

		<div class="directorist-affiliate-settings-section" data-section="commissions">
			<h2><?php esc_html_e( 'Commissions', 'directorist-affiliate' ); ?></h2>

			<div class="directorist-affiliate-card directorist-affiliate-field-card">
				<div class="directorist-affiliate-card-head">
					<h3><?php esc_html_e( 'Free events', 'directorist-affiliate' ); ?></h3>
					<p><?php esc_html_e( 'Flat rewards for non-monetary conversions.', 'directorist-affiliate' ); ?></p>
				</div>

				<div class="directorist-affiliate-field is-toggle">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-enable-registration"><?php esc_html_e( 'User registration', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Pay a flat amount when a referred visitor creates an account.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch">
							<input id="directorist-affiliate-enable-registration" type="checkbox" name="enable_registration" value="1" <?php checked( $settings['enable_registration'], 1 ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></span>
						</label>
						<div class="directorist-affiliate-input-affix">
							<label class="screen-reader-text" for="directorist-affiliate-registration-amount"><?php esc_html_e( 'Registration commission amount', 'directorist-affiliate' ); ?></label>
							<input id="directorist-affiliate-registration-amount" type="text" inputmode="decimal" name="registration_amount" value="<?php echo esc_attr( $settings['registration_amount'] ); ?>" />
							<span class="directorist-affiliate-affix"><?php esc_html_e( 'per signup', 'directorist-affiliate' ); ?></span>
						</div>
					</div>
				</div>

				<div class="directorist-affiliate-field">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-registration-credit-on"><?php esc_html_e( 'Credit the commission', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Wait for email verification to weed out throwaway signups. If Directorist email verification is switched off, the commission is credited on registration regardless.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<select id="directorist-affiliate-registration-credit-on" name="registration_credit_on">
							<option value="registration" <?php selected( $settings['registration_credit_on'], 'registration' ); ?>><?php esc_html_e( 'On registration', 'directorist-affiliate' ); ?></option>
							<option value="verification" <?php selected( $settings['registration_credit_on'], 'verification' ); ?>><?php esc_html_e( 'On email verification', 'directorist-affiliate' ); ?></option>
						</select>
						<?php if ( function_exists( 'directorist_is_email_verification_enabled' ) && ! directorist_is_email_verification_enabled() ) : ?>
							<p class="description"><strong><?php esc_html_e( 'Note:', 'directorist-affiliate' ); ?></strong> <?php esc_html_e( 'email verification is currently off in Directorist, so commissions credit on registration.', 'directorist-affiliate' ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<div class="directorist-affiliate-field">
					<div class="directorist-affiliate-field-label">
						<label><?php esc_html_e( 'Pay for these user types', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Directorist asks new users whether they are signing up to post listings or just to browse. Untick a type to stop paying for it. Leaving both unticked keeps both paid.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<?php
						$directorist_affiliate_user_types = array(
							'author'  => __( 'Author — signs up to post listings', 'directorist-affiliate' ),
							'general' => __( 'User — signs up to browse only', 'directorist-affiliate' ),
						);
						$directorist_affiliate_chosen_types = (array) $settings['registration_user_types'];
						?>
						<div class="directorist-affiliate-checkboxes">
							<?php foreach ( $directorist_affiliate_user_types as $directorist_affiliate_type_key => $directorist_affiliate_type_label ) : ?>
								<label class="directorist-affiliate-checkbox">
									<input type="checkbox" name="registration_user_types[]" value="<?php echo esc_attr( $directorist_affiliate_type_key ); ?>" <?php checked( in_array( $directorist_affiliate_type_key, $directorist_affiliate_chosen_types, true ) ); ?> />
									<span><?php echo esc_html( $directorist_affiliate_type_label ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<div class="directorist-affiliate-field is-toggle">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-enable-listing"><?php esc_html_e( 'Listing submission', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Pay a flat amount when a referred user adds a listing.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch">
							<input id="directorist-affiliate-enable-listing" type="checkbox" name="enable_listing" value="1" <?php checked( $settings['enable_listing'], 1 ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></span>
						</label>
						<div class="directorist-affiliate-input-affix">
							<label class="screen-reader-text" for="directorist-affiliate-listing-amount"><?php esc_html_e( 'Listing commission amount', 'directorist-affiliate' ); ?></label>
							<input id="directorist-affiliate-listing-amount" type="text" inputmode="decimal" name="listing_amount" value="<?php echo esc_attr( $settings['listing_amount'] ); ?>" />
							<span class="directorist-affiliate-affix"><?php esc_html_e( 'per listing', 'directorist-affiliate' ); ?></span>
						</div>
						<label class="screen-reader-text" for="directorist-affiliate-listing-trigger"><?php esc_html_e( 'Commission trigger', 'directorist-affiliate' ); ?></label>
						<select id="directorist-affiliate-listing-trigger" name="listing_trigger">
							<option value="submission" <?php selected( $settings['listing_trigger'], 'submission' ); ?>><?php esc_html_e( 'Credit on submission', 'directorist-affiliate' ); ?></option>
							<option value="publish" <?php selected( $settings['listing_trigger'], 'publish' ); ?>><?php esc_html_e( 'Credit on approval/publish', 'directorist-affiliate' ); ?></option>
						</select>
					</div>
				</div>

				<?php $directorist_affiliate_directories = Directorist_Affiliate_Directorist_Integration::directory_types(); ?>
				<?php if ( count( $directorist_affiliate_directories ) > 1 ) : ?>
					<div class="directorist-affiliate-field">
						<div class="directorist-affiliate-field-label">
							<label><?php esc_html_e( 'Pay for these directory types', 'directorist-affiliate' ); ?></label>
							<p class="description"><?php esc_html_e( 'Restrict listing commissions to particular directories. Leaving every box unticked pays for all of them, including any added later.', 'directorist-affiliate' ); ?></p>
						</div>
						<div class="directorist-affiliate-field-control">
							<?php $directorist_affiliate_chosen_dirs = array_map( 'absint', (array) $settings['listing_directory_types'] ); ?>
							<div class="directorist-affiliate-checkboxes">
								<?php foreach ( $directorist_affiliate_directories as $directorist_affiliate_dir_id => $directorist_affiliate_dir_name ) : ?>
									<label class="directorist-affiliate-checkbox">
										<input
											type="checkbox"
											name="listing_directory_types[]"
											value="<?php echo esc_attr( (string) $directorist_affiliate_dir_id ); ?>"
											<?php checked( ! $directorist_affiliate_chosen_dirs || in_array( (int) $directorist_affiliate_dir_id, $directorist_affiliate_chosen_dirs, true ) ); ?>
										/>
										<span><?php echo esc_html( $directorist_affiliate_dir_name ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div class="directorist-affiliate-card directorist-affiliate-field-card">
				<div class="directorist-affiliate-card-head">
					<h3><?php esc_html_e( 'Paid orders', 'directorist-affiliate' ); ?></h3>
					<p><?php esc_html_e( 'Commissions on completed paid orders. Refunded or cancelled orders reverse their commission automatically.', 'directorist-affiliate' ); ?></p>
				</div>

				<div class="directorist-affiliate-field is-toggle<?php echo $plan_available ? '' : ' is-unavailable'; ?>">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-enable-plan"><?php esc_html_e( 'Pricing plan purchases', 'directorist-affiliate' ); ?></label>
						<?php if ( $plan_available ) : ?>
							<p class="description"><?php esc_html_e( 'Pay when a referred user buys a pricing plan.', 'directorist-affiliate' ); ?></p>
						<?php else : ?>
							<p class="description"><span class="directorist-affiliate-badge is-rejected"><?php esc_html_e( 'Inactive', 'directorist-affiliate' ); ?></span> <?php esc_html_e( 'Requires the Directorist Pricing Plans extension.', 'directorist-affiliate' ); ?></p>
						<?php endif; ?>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch">
							<input id="directorist-affiliate-enable-plan" type="checkbox" name="enable_plan_commission" value="1" <?php checked( $settings['enable_plan_commission'], 1 ); ?> <?php disabled( ! $plan_available ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></span>
						</label>
						<div class="directorist-affiliate-rate-group">
							<label class="screen-reader-text" for="directorist-affiliate-plan-type"><?php esc_html_e( 'Plan commission type', 'directorist-affiliate' ); ?></label>
							<select id="directorist-affiliate-plan-type" name="plan_commission_type" <?php disabled( ! $plan_available ); ?>>
								<option value="percentage" <?php selected( $settings['plan_commission_type'], 'percentage' ); ?>><?php esc_html_e( 'Percentage (%)', 'directorist-affiliate' ); ?></option>
								<option value="fixed" <?php selected( $settings['plan_commission_type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'directorist-affiliate' ); ?></option>
							</select>
							<label class="screen-reader-text" for="directorist-affiliate-plan-value"><?php esc_html_e( 'Plan commission value', 'directorist-affiliate' ); ?></label>
							<input id="directorist-affiliate-plan-value" type="text" inputmode="decimal" name="plan_commission_value" value="<?php echo esc_attr( $settings['plan_commission_value'] ); ?>" <?php disabled( ! $plan_available ); ?> />
						</div>
					</div>
				</div>

				<div class="directorist-affiliate-field is-toggle<?php echo $featured_available ? '' : ' is-unavailable'; ?>">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-enable-featured"><?php esc_html_e( 'Featured listing purchases', 'directorist-affiliate' ); ?></label>
						<?php if ( $featured_available ) : ?>
							<p class="description"><?php esc_html_e( 'Pay when a referred user buys featured status.', 'directorist-affiliate' ); ?></p>
						<?php else : ?>
							<p class="description"><span class="directorist-affiliate-badge is-rejected"><?php esc_html_e( 'Inactive', 'directorist-affiliate' ); ?></span> <?php esc_html_e( 'Requires Directorist monetization with featured listings enabled.', 'directorist-affiliate' ); ?></p>
						<?php endif; ?>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch">
							<input id="directorist-affiliate-enable-featured" type="checkbox" name="enable_featured_commission" value="1" <?php checked( $settings['enable_featured_commission'], 1 ); ?> <?php disabled( ! $featured_available ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></span>
						</label>
						<div class="directorist-affiliate-rate-group">
							<label class="screen-reader-text" for="directorist-affiliate-featured-type"><?php esc_html_e( 'Featured commission type', 'directorist-affiliate' ); ?></label>
							<select id="directorist-affiliate-featured-type" name="featured_commission_type" <?php disabled( ! $featured_available ); ?>>
								<option value="percentage" <?php selected( $settings['featured_commission_type'], 'percentage' ); ?>><?php esc_html_e( 'Percentage (%)', 'directorist-affiliate' ); ?></option>
								<option value="fixed" <?php selected( $settings['featured_commission_type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'directorist-affiliate' ); ?></option>
							</select>
							<label class="screen-reader-text" for="directorist-affiliate-featured-value"><?php esc_html_e( 'Featured commission value', 'directorist-affiliate' ); ?></label>
							<input id="directorist-affiliate-featured-value" type="text" inputmode="decimal" name="featured_commission_value" value="<?php echo esc_attr( $settings['featured_commission_value'] ); ?>" <?php disabled( ! $featured_available ); ?> />
						</div>
					</div>
				</div>

				<div class="directorist-affiliate-field is-toggle">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-auto-approve"><?php esc_html_e( 'Auto-approve commissions', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'New referrals are created as Approved (payable immediately) instead of Pending review.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch">
							<input id="directorist-affiliate-auto-approve" type="checkbox" name="auto_approve_commissions" value="1" <?php checked( $settings['auto_approve_commissions'], 1 ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></span>
						</label>
					</div>
				</div>
			</div>
		</div>

		<div class="directorist-affiliate-settings-section" data-section="payout">
			<h2><?php esc_html_e( 'Payout', 'directorist-affiliate' ); ?></h2>

			<div class="directorist-affiliate-card directorist-affiliate-field-card">
				<div class="directorist-affiliate-field">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-minimum-payout"><?php esc_html_e( 'Minimum payout', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Bulk payouts skip affiliates whose selected total is below this amount. Use 0 for no minimum.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<input id="directorist-affiliate-minimum-payout" type="text" inputmode="decimal" name="minimum_payout" value="<?php echo esc_attr( $settings['minimum_payout'] ); ?>" />
					</div>
				</div>

				<div class="directorist-affiliate-field">
					<div class="directorist-affiliate-field-label">
						<label><?php esc_html_e( 'Available payout methods', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'What affiliates can choose from when they ask to be paid. Each method asks them for the details it needs. Leaving all unticked keeps every method available.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<?php
						$directorist_affiliate_all_methods = array(
							'paypal' => __( 'PayPal — asks for a PayPal email', 'directorist-affiliate' ),
							'bank'   => __( 'Bank transfer — asks for account details', 'directorist-affiliate' ),
							'cash'   => __( 'Cash — asks for a phone number', 'directorist-affiliate' ),
						);
						$directorist_affiliate_chosen = (array) $settings['payout_methods'];
						?>
						<div class="directorist-affiliate-checkboxes">
							<?php foreach ( $directorist_affiliate_all_methods as $directorist_affiliate_key => $directorist_affiliate_label ) : ?>
								<label class="directorist-affiliate-checkbox">
									<input
										type="checkbox"
										name="payout_methods[]"
										value="<?php echo esc_attr( $directorist_affiliate_key ); ?>"
										<?php checked( in_array( $directorist_affiliate_key, $directorist_affiliate_chosen, true ) ); ?>
									/>
									<span><?php echo esc_html( $directorist_affiliate_label ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<div class="directorist-affiliate-field">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-payout-instructions"><?php esc_html_e( 'Payout instructions', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Shown to affiliates on their dashboard — payment schedule, method, or who to contact.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<textarea id="directorist-affiliate-payout-instructions" rows="5" name="payout_instructions"><?php echo esc_textarea( $settings['payout_instructions'] ); ?></textarea>
					</div>
				</div>
			</div>
		</div>

		<div class="directorist-affiliate-settings-section" data-section="notifications">
			<h2><?php esc_html_e( 'Notifications', 'directorist-affiliate' ); ?></h2>

			<div class="directorist-affiliate-card directorist-affiliate-field-card">
				<div class="directorist-affiliate-field is-toggle">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-notify-admin"><?php esc_html_e( 'New application (to admin)', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Email the site admin when someone applies to the program.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch">
							<input id="directorist-affiliate-notify-admin" type="checkbox" name="notify_admin_application" value="1" <?php checked( $settings['notify_admin_application'], 1 ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></span>
						</label>
					</div>
				</div>

				<div class="directorist-affiliate-field is-toggle">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-notify-status"><?php esc_html_e( 'Application decision (to affiliate)', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Email the applicant when you approve or reject their application.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch">
							<input id="directorist-affiliate-notify-status" type="checkbox" name="notify_affiliate_status" value="1" <?php checked( $settings['notify_affiliate_status'], 1 ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></span>
						</label>
					</div>
				</div>

				<div class="directorist-affiliate-field is-toggle">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-notify-referral"><?php esc_html_e( 'New referral (to affiliate)', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Email the affiliate each time one of their referrals converts.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch">
							<input id="directorist-affiliate-notify-referral" type="checkbox" name="notify_affiliate_referral" value="1" <?php checked( $settings['notify_affiliate_referral'], 1 ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></span>
						</label>
					</div>
				</div>

				<div class="directorist-affiliate-field is-toggle">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-notify-payout-request"><?php esc_html_e( 'Payout requested (to you)', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Email you when an affiliate asks to be paid, so requests do not sit unnoticed.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch">
							<input id="directorist-affiliate-notify-payout-request" type="checkbox" name="notify_admin_payout_request" value="1" <?php checked( $settings['notify_admin_payout_request'], 1 ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></span>
						</label>
					</div>
				</div>

				<div class="directorist-affiliate-field is-toggle">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-notify-payout"><?php esc_html_e( 'Payout decision (to affiliate)', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Email the affiliate when you pay or decline their payout request.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch">
							<input id="directorist-affiliate-notify-payout" type="checkbox" name="notify_affiliate_payout" value="1" <?php checked( $settings['notify_affiliate_payout'], 1 ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></span>
						</label>
					</div>
				</div>
			</div>
		</div>

		<div class="directorist-affiliate-settings-section" data-section="advanced">
			<h2><?php esc_html_e( 'Advanced', 'directorist-affiliate' ); ?></h2>

			<div class="directorist-affiliate-card directorist-affiliate-field-card">
				<div class="directorist-affiliate-field is-toggle">
					<div class="directorist-affiliate-field-label">
						<label for="directorist-affiliate-delete-data"><?php esc_html_e( 'Delete data on uninstall', 'directorist-affiliate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Permanently remove all affiliate tables, settings, and related user meta when the plugin is deleted.', 'directorist-affiliate' ); ?></p>
					</div>
					<div class="directorist-affiliate-field-control">
						<label class="directorist-affiliate-switch is-danger">
							<input id="directorist-affiliate-delete-data" type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( $settings['delete_data_on_uninstall'], 1 ); ?> />
							<span class="directorist-affiliate-switch-track" aria-hidden="true"></span>
							<span class="directorist-affiliate-switch-text"><?php esc_html_e( 'Enabled', 'directorist-affiliate' ); ?></span>
						</label>
					</div>
				</div>
			</div>

			<div class="directorist-affiliate-card directorist-affiliate-field-card">
				<div class="directorist-affiliate-card-head">
					<h3><?php esc_html_e( 'Shortcodes', 'directorist-affiliate' ); ?></h3>
					<p><?php esc_html_e( 'Place these on any page to expose the affiliate program.', 'directorist-affiliate' ); ?></p>
				</div>
				<ul class="directorist-affiliate-shortcode-list">
					<li>
						<code>[directorist_affiliate_registration]</code>
						<span><?php esc_html_e( 'Application form for new affiliates.', 'directorist-affiliate' ); ?></span>
					</li>
					<li>
						<code>[directorist_affiliate_dashboard]</code>
						<span><?php esc_html_e( 'Affiliate dashboard: referral link, stats, and history.', 'directorist-affiliate' ); ?></span>
					</li>
				</ul>
			</div>
		</div>

		<div class="directorist-affiliate-save-bar">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'directorist-affiliate' ); ?></button>
		</div>
	</form>
</div>

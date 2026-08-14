<?php
/**
 * Affiliate dashboard.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

$da_status    = (string) $affiliate->status;
$da_approved  = 'approved' === $da_status;
$da_lifetime  = (float) $pending_commission + (float) $approved_commission + (float) $paid_commission;
$da_rate      = $visits > 0 ? ( (int) $converted_visits / (int) $visits ) * 100 : 0;
$da_share_msg = sprintf(
	/* translators: %s: site name. */
	__( 'I recommend %s — take a look:', 'directorist-affiliate' ),
	$site_name
);

// Progress toward the payout threshold, when the site sets one.
$da_progress = $minimum_payout > 0 ? min( 100, ( (float) $approved_commission / $minimum_payout ) * 100 ) : 0;
$da_shortfall = max( 0, $minimum_payout - (float) $approved_commission );

// Whether a payout can be requested right now.
$da_can_request = $da_approved
	&& empty( $open_request )
	&& (float) $approved_commission > 0
	&& ( $minimum_payout <= 0 || (float) $approved_commission >= $minimum_payout );

// Tabs are built here so one that has nothing to show is never rendered.
$da_tabs = array(
	'summary' => __( 'Summary', 'directorist-affiliate' ),
);

if ( $da_approved ) {
	$da_tabs['link'] = __( 'Your link', 'directorist-affiliate' );
}

$da_tabs['referrals'] = __( 'Referrals', 'directorist-affiliate' );
$da_tabs['payouts']   = __( 'Payouts', 'directorist-affiliate' );

// Settings last, and only when there is something to configure.
$da_has_settings = $da_approved && ! empty( $payout_methods );

if ( $da_has_settings ) {
	$da_tabs['settings'] = __( 'Settings', 'directorist-affiliate' );
}
?>
<div class="directorist-affiliate-wrap directorist-affiliate-dashboard">

	<?php if ( ! $da_approved ) : ?>
		<div class="da-banner da-banner--<?php echo esc_attr( 'pending' === $da_status ? 'pending' : 'stopped' ); ?>">
			<span class="da-banner-dot" aria-hidden="true"></span>
			<div>
				<strong>
					<?php
					if ( 'pending' === $da_status ) {
						esc_html_e( 'Your application is being reviewed', 'directorist-affiliate' );
					} elseif ( 'suspended' === $da_status ) {
						esc_html_e( 'Your account is on hold', 'directorist-affiliate' );
					} else {
						esc_html_e( 'Your application was not approved', 'directorist-affiliate' );
					}
					?>
				</strong>
				<p>
					<?php
					if ( 'pending' === $da_status ) {
						esc_html_e( 'Your referral link appears here as soon as you are approved. We will email you either way.', 'directorist-affiliate' );
					} elseif ( 'suspended' === $da_status ) {
						esc_html_e( 'New referrals are not being tracked right now. Anything you already earned is still shown below.', 'directorist-affiliate' );
					} else {
						esc_html_e( 'New referrals are not being tracked. Get in touch if you think this was a mistake.', 'directorist-affiliate' );
					}
					?>
				</p>
			</div>
		</div>
	<?php endif; ?>


	<div class="da-tabs" data-da-panels>
		<div class="da-tabnav" role="tablist" aria-label="<?php esc_attr_e( 'Affiliate dashboard sections', 'directorist-affiliate' ); ?>">
			<?php foreach ( $da_tabs as $da_tab_key => $da_tab_label ) : ?>
				<button type="button" class="da-tabnav-btn" role="tab" data-panel-target="<?php echo esc_attr( $da_tab_key ); ?>">
					<?php echo esc_html( $da_tab_label ); ?>
				</button>
			<?php endforeach; ?>
		</div>

		<div class="da-panel" data-panel="summary">
			<h2 class="da-panel-title"><?php esc_html_e( 'Summary', 'directorist-affiliate' ); ?></h2>
		<div class="da-stats">
			<div class="da-stat da-stat--lead">
				<span class="da-stat-label"><?php esc_html_e( 'Ready to be paid', 'directorist-affiliate' ); ?></span>
				<span class="da-stat-value"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $approved_commission ) ); ?></span>
				<?php if ( $minimum_payout > 0 ) : ?>
					<div class="da-progress" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: percentage toward the payout minimum. */ __( '%s%% of the payout minimum', 'directorist-affiliate' ), number_format_i18n( $da_progress, 0 ) ) ); ?>">
						<span style="width:<?php echo esc_attr( (string) round( $da_progress, 2 ) ); ?>%"></span>
					</div>
					<span class="da-stat-meta">
						<?php if ( $da_shortfall > 0 ) : ?>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: remaining amount before a payout can be made. */
									__( '%s to go before your next payout', 'directorist-affiliate' ),
									Directorist_Affiliate_Commission::format_money( $da_shortfall )
								)
							);
							?>
						<?php else : ?>
							<?php esc_html_e( 'You have reached the payout minimum', 'directorist-affiliate' ); ?>
						<?php endif; ?>
					</span>
				<?php else : ?>
					<span class="da-stat-meta"><?php esc_html_e( 'Approved and awaiting payment', 'directorist-affiliate' ); ?></span>
				<?php endif; ?>
			</div>

			<div class="da-stat">
				<span class="da-stat-label"><?php esc_html_e( 'Pending review', 'directorist-affiliate' ); ?></span>
				<span class="da-stat-value"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $pending_commission ) ); ?></span>
				<span class="da-stat-meta"><?php esc_html_e( 'Not approved yet', 'directorist-affiliate' ); ?></span>
			</div>

			<div class="da-stat">
				<span class="da-stat-label"><?php esc_html_e( 'Paid to date', 'directorist-affiliate' ); ?></span>
				<span class="da-stat-value"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $paid_commission ) ); ?></span>
				<span class="da-stat-meta">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: lifetime earnings. */
							__( '%s earned in total', 'directorist-affiliate' ),
							Directorist_Affiliate_Commission::format_money( $da_lifetime )
						)
					);
					?>
				</span>
			</div>

			<div class="da-stat">
				<span class="da-stat-label"><?php esc_html_e( 'Traffic', 'directorist-affiliate' ); ?></span>
				<span class="da-stat-value"><?php echo esc_html( number_format_i18n( (int) $visits ) ); ?></span>
				<span class="da-stat-meta">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: number of referrals, 2: conversion rate. */
							__( '%1$s referrals · %2$s%% converted', 'directorist-affiliate' ),
							number_format_i18n( (int) $total_referrals ),
							number_format_i18n( $da_rate, 1 )
						)
					);
					?>
				</span>
			</div>
		</div>
		</div>

		<?php if ( $da_approved ) : ?>
			<div class="da-panel" data-panel="link">
				<section class="da-share" aria-labelledby="da-share-title" data-da-builder>
					<div class="da-share-head">
						<h2 id="da-share-title"><?php esc_html_e( 'Your referral link', 'directorist-affiliate' ); ?></h2>
						<p>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %d: number of days the referral cookie lasts. */
									_n(
										'Anyone who arrives through this link stays credited to you for %d day.',
										'Anyone who arrives through this link stays credited to you for %d days.',
										(int) $cookie_duration,
										'directorist-affiliate'
									),
									(int) $cookie_duration
								)
							);
							?>
						</p>
					</div>

					<div class="da-builder-row">
						<div class="da-field">
							<label for="da-builder-type">
								<?php esc_html_e( 'Where should it point?', 'directorist-affiliate' ); ?>
								<span class="da-tip" tabindex="0" role="note" aria-label="<?php esc_attr_e( 'Choose the kind of content first. A search box appears next to it so you can find the exact item by name, and your link updates as you pick.', 'directorist-affiliate' ); ?>">
									<span aria-hidden="true">?</span>
								</span>
							</label>
							<select id="da-builder-type" data-da-builder-type>
								<?php foreach ( $link_types as $da_type_key => $da_type ) : ?>
									<option value="<?php echo esc_attr( $da_type_key ); ?>" data-hint="<?php echo esc_attr( $da_type['hint'] ); ?>" data-kind="<?php echo esc_attr( $da_type['kind'] ); ?>">
										<?php echo esc_html( $da_type['label'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<small class="da-hint" data-da-builder-hint><?php echo esc_html( $link_types ? reset( $link_types )['hint'] : '' ); ?></small>
						</div>

						<div class="da-field da-combo" data-da-builder-search hidden>
							<label for="da-builder-search"><?php esc_html_e( 'Find it by name', 'directorist-affiliate' ); ?></label>
							<input
								id="da-builder-search"
								type="text"
								autocomplete="off"
								role="combobox"
								aria-expanded="false"
								aria-autocomplete="list"
								aria-controls="da-builder-results"
								placeholder="<?php esc_attr_e( 'Start typing a title…', 'directorist-affiliate' ); ?>"
							/>
							<ul class="da-combo-list" id="da-builder-results" role="listbox" hidden></ul>
							<small class="da-hint" data-da-builder-status role="status" aria-live="polite"></small>
						</div>

						<div class="da-field" data-da-builder-custom hidden>
							<label for="da-builder-url"><?php esc_html_e( 'Paste the address', 'directorist-affiliate' ); ?></label>
							<input id="da-builder-url" type="url" autocomplete="off" placeholder="<?php echo esc_attr( home_url( '/some-page/' ) ); ?>" />
							<small class="da-hint" data-da-builder-custom-status role="status" aria-live="polite"><?php esc_html_e( 'Only addresses on this website can be tracked.', 'directorist-affiliate' ); ?></small>
						</div>
					</div>

					<div class="da-copyfield" data-da-builder-output>
						<label class="da-sr" for="directorist-affiliate-referral-url"><?php esc_html_e( 'Your referral link', 'directorist-affiliate' ); ?></label>
						<input id="directorist-affiliate-referral-url" type="text" readonly value="<?php echo esc_url( $referral_url ); ?>" onfocus="this.select();" />
						<button type="button" class="da-btn da-btn--copy directorist-affiliate-copy" data-target="directorist-affiliate-referral-url" data-copied-label="<?php esc_attr_e( 'Copied', 'directorist-affiliate' ); ?>">
							<?php esc_html_e( 'Copy', 'directorist-affiliate' ); ?>
						</button>
					</div>

					<div class="da-share-actions">
						<span class="da-share-label"><?php esc_html_e( 'Share via', 'directorist-affiliate' ); ?></span>
						<a class="da-chip" data-da-share-net="whatsapp" href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( $da_share_msg . ' ' . $referral_url ) ); ?>" target="_blank" rel="noopener noreferrer nofollow">WhatsApp</a>
						<a class="da-chip" data-da-share-net="x" href="<?php echo esc_url( 'https://x.com/intent/tweet?text=' . rawurlencode( $da_share_msg ) . '&url=' . rawurlencode( $referral_url ) ); ?>" target="_blank" rel="noopener noreferrer nofollow">X</a>
						<a class="da-chip" data-da-share-net="facebook" href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $referral_url ) ); ?>" target="_blank" rel="noopener noreferrer nofollow">Facebook</a>
						<a class="da-chip" data-da-share-net="email" href="<?php echo esc_url( 'mailto:?subject=' . rawurlencode( $da_share_msg ) . '&body=' . rawurlencode( $referral_url ) ); ?>"><?php esc_html_e( 'Email', 'directorist-affiliate' ); ?></a>
					</div>

					<noscript>
						<p class="da-muted"><?php esc_html_e( 'Building links to other pages needs JavaScript. The link above works as it is.', 'directorist-affiliate' ); ?></p>
					</noscript>
				</section>
			</div>
		<?php endif; ?>

		<div class="da-panel" data-panel="referrals">
			<h2 class="da-panel-title"><?php esc_html_e( 'Referrals', 'directorist-affiliate' ); ?></h2>
			<div class="da-card da-table-card">

					<?php if ( $referrals ) : ?>
						<div class="da-tablewrap">
							<table class="da-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Event', 'directorist-affiliate' ); ?></th>
										<th class="da-num"><?php esc_html_e( 'Commission', 'directorist-affiliate' ); ?></th>
										<th><?php esc_html_e( 'Status', 'directorist-affiliate' ); ?></th>
										<th><?php esc_html_e( 'Date', 'directorist-affiliate' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $referrals as $referral ) : ?>
										<tr>
											<td data-label="<?php esc_attr_e( 'Event', 'directorist-affiliate' ); ?>"><?php echo esc_html( $plugin->referral->type_label( (string) $referral->referral_type ) ); ?></td>
											<td class="da-num" data-label="<?php esc_attr_e( 'Commission', 'directorist-affiliate' ); ?>"><strong><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $referral->commission_amount ) ); ?></strong></td>
											<td data-label="<?php esc_attr_e( 'Status', 'directorist-affiliate' ); ?>"><span class="da-badge is-<?php echo esc_attr( sanitize_html_class( $referral->status ) ); ?>"><?php echo esc_html( $plugin->referral->status_label( (string) $referral->status ) ); ?></span></td>
											<td data-label="<?php esc_attr_e( 'Date', 'directorist-affiliate' ); ?>"><?php echo esc_html( mysql2date( get_option( 'date_format' ), $referral->date_created ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else : ?>
						<div class="da-empty">
							<p class="da-empty-title"><?php esc_html_e( 'No referrals yet', 'directorist-affiliate' ); ?></p>
							<p><?php echo $da_approved ? esc_html__( 'Share your link above. Anything your visitors do here shows up on this list.', 'directorist-affiliate' ) : esc_html__( 'Referrals appear here once your account is approved.', 'directorist-affiliate' ); ?></p>
						</div>
					<?php endif; ?>
			</div>
		</div>

		<div class="da-panel" data-panel="payouts">
			<h2 class="da-panel-title"><?php esc_html_e( 'Payouts', 'directorist-affiliate' ); ?></h2>


		<section class="da-card da-payout-info">
				<div class="da-payout-head">
					<h2><?php esc_html_e( 'How you get paid', 'directorist-affiliate' ); ?></h2>
					<?php if ( $da_approved ) : ?>
						<?php if ( $da_can_request ) : ?>
							<button type="button" class="da-btn" data-da-modal-open="directorist-affiliate-payout-modal">
								<?php esc_html_e( 'Request payout', 'directorist-affiliate' ); ?>
							</button>
						<?php else : ?>
							<button type="button" class="da-btn" disabled aria-describedby="da-payout-blocked">
								<?php esc_html_e( 'Request payout', 'directorist-affiliate' ); ?>
							</button>
						<?php endif; ?>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $open_request ) ) : ?>
					<div class="da-request-state" id="da-payout-blocked">
						<span class="da-badge is-requested"><?php esc_html_e( 'Requested', 'directorist-affiliate' ); ?></span>
						<p>
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: requested amount, 2: date requested. */
									__( 'You asked for %1$s on %2$s. We will email you once it has been processed.', 'directorist-affiliate' ),
									Directorist_Affiliate_Commission::format_money( (float) $open_request->amount ),
									mysql2date( get_option( 'date_format' ), $open_request->date_created )
								)
							);
							?>
						</p>
					</div>
				<?php elseif ( $da_approved && ! $da_can_request ) : ?>
					<p class="da-muted" id="da-payout-blocked">
						<?php if ( (float) $approved_commission <= 0 ) : ?>
							<?php esc_html_e( 'You can request a payout once you have approved commissions waiting.', 'directorist-affiliate' ); ?>
						<?php else : ?>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: minimum payout amount. */
									__( 'You can request a payout once your approved balance reaches %s.', 'directorist-affiliate' ),
									Directorist_Affiliate_Commission::format_money( $minimum_payout )
								)
							);
							?>
						<?php endif; ?>
					</p>
				<?php endif; ?>

				<dl class="da-deflist">
					<div>
						<dt><?php esc_html_e( 'Payout email', 'directorist-affiliate' ); ?></dt>
						<dd><?php echo esc_html( $affiliate->payout_email ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Your code', 'directorist-affiliate' ); ?></dt>
						<dd><code><?php echo esc_html( $affiliate->referral_code ); ?></code></dd>
					</div>
					<?php if ( $minimum_payout > 0 ) : ?>
						<div>
							<dt><?php esc_html_e( 'Minimum payout', 'directorist-affiliate' ); ?></dt>
							<dd><?php echo esc_html( Directorist_Affiliate_Commission::format_money( $minimum_payout ) ); ?></dd>
						</div>
					<?php endif; ?>
				</dl>
				<?php if ( ! empty( $payout_instructions ) ) : ?>
					<p class="da-muted"><?php echo nl2br( esc_html( $payout_instructions ) ); ?></p>
				<?php endif; ?>
			</section>

			<section class="da-card da-table-card" aria-labelledby="da-payout-history-title">
				<h3 id="da-payout-history-title"><?php esc_html_e( 'Payout history', 'directorist-affiliate' ); ?></h3>

						<?php if ( ! empty( $payouts ) ) : ?>
							<div class="da-tablewrap">
								<table class="da-table">
									<thead>
										<tr>
											<th class="da-num"><?php esc_html_e( 'Amount', 'directorist-affiliate' ); ?></th>
											<th><?php esc_html_e( 'Method', 'directorist-affiliate' ); ?></th>
											<th><?php esc_html_e( 'Date paid', 'directorist-affiliate' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $payouts as $payout ) : ?>
											<tr>
												<td class="da-num" data-label="<?php esc_attr_e( 'Amount', 'directorist-affiliate' ); ?>"><strong><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $payout->amount ) ); ?></strong></td>
												<td data-label="<?php esc_attr_e( 'Method', 'directorist-affiliate' ); ?>"><?php echo esc_html( ucfirst( (string) $payout->payment_method ) ); ?></td>
												<td data-label="<?php esc_attr_e( 'Date paid', 'directorist-affiliate' ); ?>"><?php echo esc_html( $payout->date_paid ? mysql2date( get_option( 'date_format' ), $payout->date_paid ) : '—' ); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php else : ?>
							<div class="da-empty">
								<p class="da-empty-title"><?php esc_html_e( 'No payouts yet', 'directorist-affiliate' ); ?></p>
								<p><?php esc_html_e( 'Once approved commissions are paid out, each payment is listed here.', 'directorist-affiliate' ); ?></p>
							</div>
						<?php endif; ?>
			</section>
		</div>
		<?php if ( $da_has_settings ) : ?>
			<div class="da-panel" data-panel="settings">
				<h2 class="da-panel-title"><?php esc_html_e( 'Settings', 'directorist-affiliate' ); ?></h2>
					<section class="da-card da-payout-settings" aria-labelledby="da-payout-settings-title">
						<div class="da-payout-head">
							<h2 id="da-payout-settings-title"><?php esc_html_e( 'Payout settings', 'directorist-affiliate' ); ?></h2>
							<?php if ( $payout_ready ) : ?>
								<span class="da-badge is-approved"><?php esc_html_e( 'Ready', 'directorist-affiliate' ); ?></span>
							<?php else : ?>
								<span class="da-badge is-pending"><?php esc_html_e( 'Not set up', 'directorist-affiliate' ); ?></span>
							<?php endif; ?>
						</div>

						<p class="da-muted">
							<?php if ( $payout_ready ) : ?>
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: the affiliate's saved payout method and details. */
										__( 'Payments go to: %s', 'directorist-affiliate' ),
										$payout_summary
									)
								);
								?>
							<?php else : ?>
								<?php esc_html_e( 'Tell us how to pay you. You can save it here once, or fill it in when you request a payout.', 'directorist-affiliate' ); ?>
							<?php endif; ?>
						</p>

						<form method="post" class="da-payout-form" data-da-ajax="directorist_affiliate_save_payout_method" data-da-success="reload">
							<?php wp_nonce_field( 'directorist_affiliate_payout_method', 'directorist_affiliate_nonce' ); ?>
							<?php
							Directorist_Affiliate_View::public_partial(
								'payout-method-fields.php',
								array(
									'payout_methods' => $payout_methods,
									'payout_method'  => $payout_method,
									'payout_details' => $payout_details,
									'id_prefix'      => 'da-payout-settings',
								)
							);
							?>
							<div class="da-form-foot">
								<button type="submit" class="da-btn"><?php esc_html_e( 'Save payout details', 'directorist-affiliate' ); ?></button>
								<p class="da-muted"><?php esc_html_e( 'Only the site owner can see these details.', 'directorist-affiliate' ); ?></p>
							</div>
						</form>
					</section>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $da_can_request ) : ?>
		<dialog id="directorist-affiliate-payout-modal" class="da-modal" aria-labelledby="da-payout-modal-title">
			<div class="da-modal-head">
				<h2 id="da-payout-modal-title"><?php esc_html_e( 'Request a payout', 'directorist-affiliate' ); ?></h2>
				<button type="button" class="da-modal-close" data-da-modal-close aria-label="<?php esc_attr_e( 'Close', 'directorist-affiliate' ); ?>">&times;</button>
			</div>

			<form method="post" class="da-form da-modal-form" data-da-ajax="directorist_affiliate_request_payout" data-da-success="reload">
				<?php wp_nonce_field( 'directorist_affiliate_request_payout', 'directorist_affiliate_nonce' ); ?>

				<div class="da-request-amount">
					<span class="da-stat-label"><?php esc_html_e( 'Amount to be paid', 'directorist-affiliate' ); ?></span>
					<span class="da-stat-value"><?php echo esc_html( Directorist_Affiliate_Commission::format_money( (float) $approved_commission ) ); ?></span>
					<span class="da-stat-meta"><?php esc_html_e( 'Every approved commission in your balance right now.', 'directorist-affiliate' ); ?></span>
				</div>

				<?php if ( $payout_ready ) : ?>
					<div class="da-request-method" data-da-saved-method>
						<div>
							<span class="da-stat-label"><?php esc_html_e( 'Paying to', 'directorist-affiliate' ); ?></span>
							<span class="da-request-method-value"><?php echo esc_html( $payout_summary ); ?></span>
						</div>
						<button type="button" class="da-btn da-btn--ghost" data-da-change-method><?php esc_html_e( 'Change', 'directorist-affiliate' ); ?></button>
					</div>
				<?php endif; ?>

				<div data-da-method-wrap <?php echo $payout_ready ? 'hidden' : ''; ?>>
					<?php
					Directorist_Affiliate_View::public_partial(
						'payout-method-fields.php',
						array(
							'payout_methods' => $payout_methods,
							'payout_method'  => $payout_method,
							'payout_details' => $payout_details,
							'id_prefix'      => 'da-request',
						)
					);
					?>
				</div>

				<div class="da-field">
					<label for="da-request-note"><?php esc_html_e( 'Note (optional)', 'directorist-affiliate' ); ?></label>
					<textarea id="da-request-note" name="note" rows="3" placeholder="<?php esc_attr_e( 'Anything the team should know about this payment…', 'directorist-affiliate' ); ?>"></textarea>
				</div>

				<div class="da-modal-actions">
					<button type="button" class="da-btn da-btn--ghost" data-da-modal-close><?php esc_html_e( 'Cancel', 'directorist-affiliate' ); ?></button>
					<button type="submit" class="da-btn"><?php esc_html_e( 'Send request', 'directorist-affiliate' ); ?></button>
				</div>
			</form>
		</dialog>
	<?php endif; ?>
</div>

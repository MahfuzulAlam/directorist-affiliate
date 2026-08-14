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
$da_rate      = $visits > 0 ? ( (int) $total_referrals / (int) $visits ) * 100 : 0;
$da_share_msg = sprintf(
	/* translators: %s: site name. */
	__( 'I recommend %s — take a look:', 'directorist-affiliate' ),
	$site_name
);

// Progress toward the payout threshold, when the site sets one.
$da_progress = $minimum_payout > 0 ? min( 100, ( (float) $approved_commission / $minimum_payout ) * 100 ) : 0;
$da_shortfall = max( 0, $minimum_payout - (float) $approved_commission );
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

	<?php if ( $da_approved ) : ?>
		<section class="da-share" aria-labelledby="da-share-title">
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

			<div class="da-copyfield">
				<label class="da-sr" for="directorist-affiliate-referral-url"><?php esc_html_e( 'Referral link', 'directorist-affiliate' ); ?></label>
				<input id="directorist-affiliate-referral-url" type="text" readonly value="<?php echo esc_url( $referral_url ); ?>" onfocus="this.select();" />
				<button type="button" class="da-btn da-btn--copy directorist-affiliate-copy" data-target="directorist-affiliate-referral-url" data-copied-label="<?php esc_attr_e( 'Copied', 'directorist-affiliate' ); ?>">
					<?php esc_html_e( 'Copy', 'directorist-affiliate' ); ?>
				</button>
			</div>

			<div class="da-share-actions">
				<span class="da-share-label"><?php esc_html_e( 'Share via', 'directorist-affiliate' ); ?></span>
				<a class="da-chip" href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( $da_share_msg . ' ' . $referral_url ) ); ?>" target="_blank" rel="noopener noreferrer nofollow">WhatsApp</a>
				<a class="da-chip" href="<?php echo esc_url( 'https://x.com/intent/tweet?text=' . rawurlencode( $da_share_msg ) . '&url=' . rawurlencode( $referral_url ) ); ?>" target="_blank" rel="noopener noreferrer nofollow">X</a>
				<a class="da-chip" href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $referral_url ) ); ?>" target="_blank" rel="noopener noreferrer nofollow">Facebook</a>
				<a class="da-chip" href="<?php echo esc_url( 'mailto:?subject=' . rawurlencode( $da_share_msg ) . '&body=' . rawurlencode( $referral_url ) ); ?>"><?php esc_html_e( 'Email', 'directorist-affiliate' ); ?></a>
				<button type="button" class="da-chip" data-da-share data-share-text="<?php echo esc_attr( $da_share_msg ); ?>" data-share-url="<?php echo esc_url( $referral_url ); ?>" hidden>
					<?php esc_html_e( 'More…', 'directorist-affiliate' ); ?>
				</button>
			</div>
		</section>
	<?php endif; ?>

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

	<?php if ( $da_approved ) : ?>
		<section class="da-card da-builder" aria-labelledby="da-builder-title" data-da-builder>
			<div class="da-builder-head">
				<h2 id="da-builder-title"><?php esc_html_e( 'Build a link to anywhere on the site', 'directorist-affiliate' ); ?></h2>
				<p class="da-muted"><?php esc_html_e( 'Pick what you want to promote and we will attach your referral code to it.', 'directorist-affiliate' ); ?></p>
			</div>

			<div class="da-builder-row">
				<div class="da-field">
					<label for="da-builder-type">
						<?php esc_html_e( 'What are you linking to?', 'directorist-affiliate' ); ?>
						<span class="da-tip" tabindex="0" role="note" aria-label="<?php esc_attr_e( 'Choose the kind of content first. A search box appears next to it so you can find the exact item by name.', 'directorist-affiliate' ); ?>">
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

				<div class="da-field da-combo" data-da-builder-search>
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

			<div class="da-copyfield" data-da-builder-output hidden>
				<label class="da-sr" for="directorist-affiliate-built-link"><?php esc_html_e( 'Your referral link', 'directorist-affiliate' ); ?></label>
				<input id="directorist-affiliate-built-link" type="text" readonly value="" onfocus="this.select();" />
				<button type="button" class="da-btn da-btn--copy directorist-affiliate-copy" data-target="directorist-affiliate-built-link" data-copied-label="<?php esc_attr_e( 'Copied', 'directorist-affiliate' ); ?>">
					<?php esc_html_e( 'Copy', 'directorist-affiliate' ); ?>
				</button>
			</div>

			<?php if ( ! empty( $link_targets ) ) : ?>
				<div class="da-quick">
					<span class="da-share-label"><?php esc_html_e( 'Quick links', 'directorist-affiliate' ); ?></span>
					<?php foreach ( $link_targets as $target_url => $target_label ) : ?>
						<button type="button" class="da-chip" data-da-quick-link="<?php echo esc_url( $target_url ); ?>"><?php echo esc_html( $target_label ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<noscript>
				<p class="da-muted"><?php esc_html_e( 'The link builder needs JavaScript. You can still add your referral code to any address by appending it manually.', 'directorist-affiliate' ); ?></p>
			</noscript>
		</section>
	<?php endif; ?>

	<section class="da-card da-activity" data-da-panels aria-labelledby="da-activity-title">
		<div class="da-activity-head">
			<h2 id="da-activity-title"><?php esc_html_e( 'Activity', 'directorist-affiliate' ); ?></h2>
			<div class="da-seg" role="tablist" aria-label="<?php esc_attr_e( 'Activity type', 'directorist-affiliate' ); ?>">
				<button type="button" class="da-seg-btn" role="tab" data-panel-target="referrals"><?php esc_html_e( 'Referrals', 'directorist-affiliate' ); ?></button>
				<button type="button" class="da-seg-btn" role="tab" data-panel-target="payouts"><?php esc_html_e( 'Payouts', 'directorist-affiliate' ); ?></button>
			</div>
		</div>

		<div class="da-panel" data-panel="referrals">
			<h3 class="da-panel-title"><?php esc_html_e( 'Referrals', 'directorist-affiliate' ); ?></h3>
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

		<div class="da-panel" data-panel="payouts">
			<h3 class="da-panel-title"><?php esc_html_e( 'Payouts', 'directorist-affiliate' ); ?></h3>
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
		</div>
	</section>

	<section class="da-card da-payout-info">
		<h2><?php esc_html_e( 'How you get paid', 'directorist-affiliate' ); ?></h2>
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
</div>

<?php
/**
 * Affiliate registration form.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="directorist-affiliate-wrap">
	<?php if ( ! empty( $message ) ) : ?>
		<?php
		$notice_class = 'directorist-affiliate-notice';

		if ( ! empty( $message_type ) ) {
			$notice_class .= ' directorist-affiliate-notice--' . sanitize_html_class( $message_type );
		}
		?>
		<div class="<?php echo esc_attr( $notice_class ); ?>"><?php echo esc_html( $message ); ?></div>
	<?php endif; ?>

	<form class="directorist-affiliate-form" method="post" data-da-ajax="directorist_affiliate_register" data-da-success="hide">
		<?php wp_nonce_field( 'directorist_affiliate_register', 'directorist_affiliate_nonce' ); ?>
		<input type="hidden" name="directorist_affiliate_register" value="1" />

		<div class="directorist-affiliate-form-head">
			<h3><?php esc_html_e( 'Join the affiliate program', 'directorist-affiliate' ); ?></h3>
			<p><?php esc_html_e( 'Tell us how you plan to promote the site. We review every application before activating your referral link.', 'directorist-affiliate' ); ?></p>
		</div>

		<p class="directorist-affiliate-hp" aria-hidden="true">
			<label for="directorist-affiliate-hp"><?php esc_html_e( 'Leave this field empty', 'directorist-affiliate' ); ?></label>
			<input id="directorist-affiliate-hp" type="text" name="da_hp" value="" tabindex="-1" autocomplete="off" />
		</p>

		<div class="directorist-affiliate-form-grid">
			<p>
				<label for="directorist-affiliate-name"><?php esc_html_e( 'Name', 'directorist-affiliate' ); ?> <span class="required" aria-hidden="true">*</span></label>
				<input id="directorist-affiliate-name" type="text" name="name" required autocomplete="name" value="<?php echo esc_attr( $user && $user->exists() ? $user->display_name : '' ); ?>" />
			</p>

			<p>
				<label for="directorist-affiliate-email"><?php esc_html_e( 'Email', 'directorist-affiliate' ); ?> <span class="required" aria-hidden="true">*</span></label>
				<input id="directorist-affiliate-email" type="email" name="email" required autocomplete="email" value="<?php echo esc_attr( $user && $user->exists() ? $user->user_email : '' ); ?>" />
			</p>

			<p>
				<label for="directorist-affiliate-website"><?php esc_html_e( 'Website', 'directorist-affiliate' ); ?></label>
				<input id="directorist-affiliate-website" type="url" name="website" value="" placeholder="https://" autocomplete="url" />
			</p>

			<p>
				<label for="directorist-affiliate-promotional-method"><?php esc_html_e( 'Promotional channel', 'directorist-affiliate' ); ?> <span class="required" aria-hidden="true">*</span></label>
				<input id="directorist-affiliate-promotional-method" type="text" name="promotional_method" required value="" placeholder="<?php esc_attr_e( 'Blog, YouTube, newsletter…', 'directorist-affiliate' ); ?>" />
			</p>

			<p class="directorist-affiliate-form-full">
				<label for="directorist-affiliate-payout-email"><?php esc_html_e( 'Payout email', 'directorist-affiliate' ); ?> <span class="required" aria-hidden="true">*</span></label>
				<input id="directorist-affiliate-payout-email" type="email" name="payout_email" required value="<?php echo esc_attr( $user && $user->exists() ? $user->user_email : '' ); ?>" />
				<small class="directorist-affiliate-hint"><?php esc_html_e( 'Where your commission payments should be sent.', 'directorist-affiliate' ); ?></small>
			</p>
		</div>

		<p>
			<label for="directorist-affiliate-application-note"><?php esc_html_e( 'Anything else we should know?', 'directorist-affiliate' ); ?></label>
			<textarea id="directorist-affiliate-application-note" name="application_note" rows="4" placeholder="<?php esc_attr_e( 'Audience size, niche, or how you found us…', 'directorist-affiliate' ); ?>"></textarea>
		</p>

		<p class="directorist-affiliate-form-actions">
			<button type="submit" class="directorist-affiliate-btn"><?php esc_html_e( 'Apply as affiliate', 'directorist-affiliate' ); ?></button>
		</p>
	</form>
</div>

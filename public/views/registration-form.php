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
		<div class="directorist-affiliate-notice"><?php echo esc_html( $message ); ?></div>
	<?php endif; ?>

	<form class="directorist-affiliate-form" method="post">
		<?php wp_nonce_field( 'directorist_affiliate_register', 'directorist_affiliate_nonce' ); ?>
		<input type="hidden" name="directorist_affiliate_register" value="1" />

		<p class="directorist-affiliate-hp" aria-hidden="true">
			<label for="directorist-affiliate-hp"><?php esc_html_e( 'Leave this field empty', 'directorist-affiliate' ); ?></label>
			<input id="directorist-affiliate-hp" type="text" name="da_hp" value="" tabindex="-1" autocomplete="off" />
		</p>

		<div class="directorist-affiliate-form-grid">
			<p>
				<label for="directorist-affiliate-name"><?php esc_html_e( 'Name', 'directorist-affiliate' ); ?> <span class="required">*</span></label>
				<input id="directorist-affiliate-name" type="text" name="name" required value="<?php echo esc_attr( $user && $user->exists() ? $user->display_name : '' ); ?>" />
			</p>

			<p>
				<label for="directorist-affiliate-email"><?php esc_html_e( 'Email', 'directorist-affiliate' ); ?> <span class="required">*</span></label>
				<input id="directorist-affiliate-email" type="email" name="email" required value="<?php echo esc_attr( $user && $user->exists() ? $user->user_email : '' ); ?>" />
			</p>

			<p>
				<label for="directorist-affiliate-website"><?php esc_html_e( 'Website', 'directorist-affiliate' ); ?></label>
				<input id="directorist-affiliate-website" type="url" name="website" value="" placeholder="https://" />
			</p>

			<p>
				<label for="directorist-affiliate-promotional-method"><?php esc_html_e( 'Promotional channel', 'directorist-affiliate' ); ?> <span class="required">*</span></label>
				<input id="directorist-affiliate-promotional-method" type="text" name="promotional_method" required value="" placeholder="<?php esc_attr_e( 'Blog, YouTube, newsletter…', 'directorist-affiliate' ); ?>" />
			</p>

			<p>
				<label for="directorist-affiliate-payout-email"><?php esc_html_e( 'Payout email', 'directorist-affiliate' ); ?> <span class="required">*</span></label>
				<input id="directorist-affiliate-payout-email" type="email" name="payout_email" required value="<?php echo esc_attr( $user && $user->exists() ? $user->user_email : '' ); ?>" />
			</p>
		</div>

		<p>
			<label for="directorist-affiliate-application-note"><?php esc_html_e( 'Message/application note', 'directorist-affiliate' ); ?></label>
			<textarea id="directorist-affiliate-application-note" name="application_note" rows="5"></textarea>
		</p>

		<p>
			<button type="submit" class="button directorist-btn directorist-btn-primary"><?php esc_html_e( 'Apply as affiliate', 'directorist-affiliate' ); ?></button>
		</p>
	</form>
</div>

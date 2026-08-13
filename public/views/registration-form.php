<?php
/**
 * Affiliate registration form.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

$da_user      = $user && $user->exists() ? $user : null;
$da_logged_in = null !== $da_user;
?>
<div class="directorist-affiliate-wrap directorist-affiliate-signup">

	<?php if ( ! empty( $terms ) ) : ?>
		<section class="da-terms" aria-labelledby="da-terms-title">
			<h2 id="da-terms-title"><?php esc_html_e( 'What you earn', 'directorist-affiliate' ); ?></h2>
			<ul class="da-terms-list">
				<?php foreach ( $terms as $da_term ) : ?>
					<li>
						<span class="da-terms-value"><?php echo esc_html( $da_term['value'] ); ?></span>
						<span class="da-terms-label"><?php echo esc_html( $da_term['label'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="da-terms-note">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of days the referral cookie lasts. */
						_n(
							'Visitors you send stay credited to you for %d day.',
							'Visitors you send stay credited to you for %d days.',
							(int) $cookie_duration,
							'directorist-affiliate'
						),
						(int) $cookie_duration
					)
				);
				?>
			</p>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $message ) ) : ?>
		<?php
		$notice_class = 'directorist-affiliate-notice';

		if ( ! empty( $message_type ) ) {
			$notice_class .= ' directorist-affiliate-notice--' . sanitize_html_class( $message_type );
		}
		?>
		<div class="<?php echo esc_attr( $notice_class ); ?>" role="<?php echo 'success' === $message_type ? 'status' : 'alert'; ?>"><?php echo esc_html( $message ); ?></div>
	<?php endif; ?>

	<form class="da-card da-form" method="post" data-da-ajax="directorist_affiliate_register" data-da-success="hide">
		<?php wp_nonce_field( 'directorist_affiliate_register', 'directorist_affiliate_nonce' ); ?>
		<input type="hidden" name="directorist_affiliate_register" value="1" />

		<header class="da-form-head">
			<h2><?php esc_html_e( 'Apply to the affiliate program', 'directorist-affiliate' ); ?></h2>
			<p><?php esc_html_e( 'Tell us who you are and how you plan to promote us. Every application is reviewed by a person.', 'directorist-affiliate' ); ?></p>
		</header>

		<p class="directorist-affiliate-hp" aria-hidden="true">
			<label for="directorist-affiliate-hp"><?php esc_html_e( 'Leave this field empty', 'directorist-affiliate' ); ?></label>
			<input id="directorist-affiliate-hp" type="text" name="da_hp" value="" tabindex="-1" autocomplete="off" />
		</p>

		<fieldset class="da-fieldset">
			<legend><?php esc_html_e( 'About you', 'directorist-affiliate' ); ?></legend>
			<div class="da-grid">
				<div class="da-field">
					<label for="directorist-affiliate-name"><?php esc_html_e( 'Name', 'directorist-affiliate' ); ?><span class="da-req" aria-hidden="true">*</span></label>
					<input id="directorist-affiliate-name" type="text" name="name" required autocomplete="name" value="<?php echo esc_attr( $da_logged_in ? $da_user->display_name : '' ); ?>" />
				</div>
				<div class="da-field">
					<label for="directorist-affiliate-email"><?php esc_html_e( 'Email', 'directorist-affiliate' ); ?><span class="da-req" aria-hidden="true">*</span></label>
					<input id="directorist-affiliate-email" type="email" name="email" required autocomplete="email" value="<?php echo esc_attr( $da_logged_in ? $da_user->user_email : '' ); ?>" />
					<?php if ( ! $da_logged_in ) : ?>
						<small class="da-hint"><?php esc_html_e( 'We will create an account for you with this address.', 'directorist-affiliate' ); ?></small>
					<?php endif; ?>
				</div>
			</div>
		</fieldset>

		<fieldset class="da-fieldset">
			<legend><?php esc_html_e( 'How you will promote us', 'directorist-affiliate' ); ?></legend>
			<div class="da-grid">
				<div class="da-field">
					<label for="directorist-affiliate-promotional-method"><?php esc_html_e( 'Main channel', 'directorist-affiliate' ); ?><span class="da-req" aria-hidden="true">*</span></label>
					<input id="directorist-affiliate-promotional-method" type="text" name="promotional_method" required value="" placeholder="<?php esc_attr_e( 'Blog, YouTube, newsletter…', 'directorist-affiliate' ); ?>" />
				</div>
				<div class="da-field">
					<label for="directorist-affiliate-website"><?php esc_html_e( 'Website', 'directorist-affiliate' ); ?></label>
					<input id="directorist-affiliate-website" type="url" name="website" value="" placeholder="https://" autocomplete="url" />
				</div>
			</div>
			<div class="da-field">
				<label for="directorist-affiliate-application-note"><?php esc_html_e( 'Anything else we should know?', 'directorist-affiliate' ); ?></label>
				<textarea id="directorist-affiliate-application-note" name="application_note" rows="4" placeholder="<?php esc_attr_e( 'Audience size, your niche, or how you found us…', 'directorist-affiliate' ); ?>"></textarea>
			</div>
		</fieldset>

		<fieldset class="da-fieldset">
			<legend><?php esc_html_e( 'Getting paid', 'directorist-affiliate' ); ?></legend>
			<div class="da-field">
				<label for="directorist-affiliate-payout-email"><?php esc_html_e( 'Payout email', 'directorist-affiliate' ); ?><span class="da-req" aria-hidden="true">*</span></label>
				<input id="directorist-affiliate-payout-email" type="email" name="payout_email" required value="<?php echo esc_attr( $da_logged_in ? $da_user->user_email : '' ); ?>" />
				<small class="da-hint"><?php esc_html_e( 'Where your commission payments should be sent. It can differ from your login email.', 'directorist-affiliate' ); ?></small>
			</div>
		</fieldset>

		<footer class="da-form-foot">
			<button type="submit" class="da-btn"><?php esc_html_e( 'Apply to join', 'directorist-affiliate' ); ?></button>
			<p class="da-muted"><?php esc_html_e( 'We will email you once your application has been reviewed.', 'directorist-affiliate' ); ?></p>
		</footer>
	</form>
</div>

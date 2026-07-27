<?php
/**
 * Email notifications.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sends simple notification emails.
 */
final class Directorist_Affiliate_Email {
	/**
	 * Notify admin about new application.
	 *
	 * @param object $affiliate Affiliate row.
	 *
	 * @return void
	 */
	public function new_application( $affiliate ): void {
		wp_mail(
			get_option( 'admin_email' ),
			__( 'New affiliate application', 'directorist-affiliate' ),
			sprintf(
				/* translators: 1: affiliate email, 2: admin URL. */
				__( "A new Directorist affiliate application was submitted by %1\$s.\n\nReview it here: %2\$s", 'directorist-affiliate' ),
				$affiliate->payout_email,
				Directorist_Affiliate_Admin::page_url( 'affiliates' )
			)
		);
	}

	/**
	 * Notify affiliate about application decision.
	 *
	 * @param object $affiliate Affiliate row.
	 * @param string $status Status.
	 *
	 * @return void
	 */
	public function application_status( $affiliate, string $status ): void {
		$email = $this->affiliate_email( $affiliate );

		if ( ! $email ) {
			return;
		}

		$subject = 'approved' === $status
			? __( 'Your affiliate application was approved', 'directorist-affiliate' )
			: __( 'Your affiliate application was rejected', 'directorist-affiliate' );

		$message = 'approved' === $status
			? __( 'Your Directorist affiliate application has been approved. You can now use your referral link from the affiliate dashboard.', 'directorist-affiliate' )
			: __( 'Your Directorist affiliate application has been rejected.', 'directorist-affiliate' );

		wp_mail( $email, $subject, $message );
	}

	/**
	 * Notify affiliate that a referral was created.
	 *
	 * @param object $affiliate Affiliate row.
	 * @param object $referral Referral row.
	 *
	 * @return void
	 */
	public function referral_created( $affiliate, $referral ): void {
		$email = $this->affiliate_email( $affiliate );

		if ( ! $email ) {
			return;
		}

		wp_mail(
			$email,
			__( 'New affiliate referral recorded', 'directorist-affiliate' ),
			sprintf(
				/* translators: 1: referral type, 2: commission amount. */
				__( 'A new "%1$s" referral was recorded with a commission amount of %2$s.', 'directorist-affiliate' ),
				Directorist_Affiliate_Plugin::instance()->referral->type_label( (string) $referral->referral_type ),
				number_format_i18n( (float) $referral->commission_amount, 2 )
			)
		);
	}

	/**
	 * Get affiliate email.
	 *
	 * @param object $affiliate Affiliate row.
	 *
	 * @return string
	 */
	private function affiliate_email( $affiliate ): string {
		if ( ! empty( $affiliate->user_id ) ) {
			$user = get_user_by( 'id', (int) $affiliate->user_id );

			if ( $user ) {
				return $user->user_email;
			}
		}

		return is_email( $affiliate->payout_email ) ? $affiliate->payout_email : '';
	}
}

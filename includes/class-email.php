<?php
/**
 * Email notifications.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sends simple notification emails, honoring the notification toggles.
 */
final class Directorist_Affiliate_Email {
	/**
	 * Settings service.
	 *
	 * @var Directorist_Affiliate_Settings
	 */
	private $settings;

	/**
	 * Template service.
	 *
	 * @var Directorist_Affiliate_Email_Templates
	 */
	private $templates;

	/**
	 * Constructor.
	 *
	 * @param Directorist_Affiliate_Settings        $settings Settings.
	 * @param Directorist_Affiliate_Email_Templates $templates Templates.
	 */
	public function __construct( Directorist_Affiliate_Settings $settings, Directorist_Affiliate_Email_Templates $templates ) {
		$this->settings  = $settings;
		$this->templates = $templates;
	}

	/**
	 * Render a template and send it, honoring its on/off toggle.
	 *
	 * @param string               $key Template key.
	 * @param string               $to Recipient address.
	 * @param array<string,string> $tokens Placeholder values.
	 *
	 * @return bool
	 */
	private function send( string $key, string $to, array $tokens ): bool {
		$all = $this->templates->all();

		if ( ! isset( $all[ $key ] ) || ! $to ) {
			return false;
		}

		if ( ! absint( $this->settings->get( $all[ $key ]['toggle'], 1 ) ) ) {
			return false;
		}

		$email = $this->templates->render( $key, $tokens );

		return (bool) wp_mail( $to, $email['subject'], $email['body'] );
	}

	/**
	 * Where an affiliate reads their dashboard, for use in a template.
	 *
	 * @return string
	 */
	private function dashboard_url(): string {
		$plugin = Directorist_Affiliate_Plugin::instance();

		return $plugin->shortcodes ? $plugin->shortcodes->dashboard_url() : home_url( '/' );
	}

	/**
	 * Notify admin about new application.
	 *
	 * @param object $affiliate Affiliate row.
	 *
	 * @return void
	 */
	public function new_application( $affiliate ): void {
		$this->send(
			'admin_application',
			(string) get_option( 'admin_email' ),
			array(
				'affiliate_name'  => Directorist_Affiliate_Plugin::instance()->affiliate->get_name( $affiliate ),
				'affiliate_email' => (string) $affiliate->payout_email,
				'admin_url'       => Directorist_Affiliate_Admin::page_url( 'affiliates' ),
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
		$this->send(
			'approved' === $status ? 'affiliate_approved' : 'affiliate_rejected',
			$this->affiliate_email( $affiliate ),
			array(
				'affiliate_name' => Directorist_Affiliate_Plugin::instance()->affiliate->get_name( $affiliate ),
				'dashboard_url'  => $this->dashboard_url(),
			)
		);
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
		$this->send(
			'affiliate_referral',
			$this->affiliate_email( $affiliate ),
			array(
				'affiliate_name' => Directorist_Affiliate_Plugin::instance()->affiliate->get_name( $affiliate ),
				'referral_type'  => Directorist_Affiliate_Plugin::instance()->referral->type_label( (string) $referral->referral_type ),
				'amount'         => Directorist_Affiliate_Commission::format_money( (float) $referral->commission_amount ),
				'dashboard_url'  => $this->dashboard_url(),
			)
		);
	}

	/**
	 * Tell the admin an affiliate has asked to be paid.
	 *
	 * @param object $affiliate Affiliate row.
	 * @param object $payout Payout row.
	 *
	 * @return void
	 */
	public function payout_requested( $affiliate, $payout ): void {
		$this->send(
			'admin_payout_request',
			(string) get_option( 'admin_email' ),
			array(
				'affiliate_name' => Directorist_Affiliate_Plugin::instance()->affiliate->get_name( $affiliate ),
				'amount'         => Directorist_Affiliate_Commission::format_money( (float) $payout->amount ),
				'payout_method'  => Directorist_Affiliate_Plugin::instance()->payout_methods->label( (string) $payout->payment_method ),
				'admin_url'      => Directorist_Affiliate_Admin::page_url( 'payouts', array( 'section' => 'requests' ) ),
			)
		);
	}

	/**
	 * Tell an affiliate what happened to their payout request.
	 *
	 * @param object $affiliate Affiliate row.
	 * @param object $payout Payout row.
	 * @param string $status New payout status.
	 *
	 * @return void
	 */
	public function payout_decision( $affiliate, $payout, string $status ): void {
		$this->send(
			'paid' === $status ? 'affiliate_payout_paid' : 'affiliate_payout_rejected',
			$this->affiliate_email( $affiliate ),
			array(
				'affiliate_name' => Directorist_Affiliate_Plugin::instance()->affiliate->get_name( $affiliate ),
				'amount'         => Directorist_Affiliate_Commission::format_money( (float) $payout->amount ),
				'payout_method'  => Directorist_Affiliate_Plugin::instance()->payout_methods->label( (string) $payout->payment_method ),
				'dashboard_url'  => $this->dashboard_url(),
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

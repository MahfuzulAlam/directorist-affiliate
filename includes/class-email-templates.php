<?php
/**
 * Editable email templates.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Holds the default wording for every notification and applies the admin's
 * overrides and placeholder substitution.
 *
 * Defaults stay in code so they are picked up by POT extraction and stay
 * translatable through Loco Translate or any .mo file. Overrides are stored
 * in settings and registered with WPML string translation, which is how
 * user-entered text becomes translatable at all.
 */
final class Directorist_Affiliate_Email_Templates {
	/**
	 * WPML/Polylang string-translation context.
	 */
	public const STRING_CONTEXT = 'Directorist - Affiliate';

	/**
	 * Settings service.
	 *
	 * @var Directorist_Affiliate_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Directorist_Affiliate_Settings $settings Settings.
	 */
	public function __construct( Directorist_Affiliate_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Every template: its audience, default wording and placeholders.
	 *
	 * @return array<string,array{label:string,toggle:string,subject:string,body:string,tokens:string[]}>
	 */
	public function all(): array {
		$templates = array(
			'admin_application'        => array(
				'label'   => __( 'New application (to admin)', 'directorist-affiliate' ),
				'toggle'  => 'notify_admin_application',
				'subject' => __( 'New affiliate application', 'directorist-affiliate' ),
				'body'    => __( "A new affiliate application was submitted by {affiliate_email}.\n\nReview it here: {admin_url}", 'directorist-affiliate' ),
				'tokens'  => array( 'site_name', 'affiliate_name', 'affiliate_email', 'admin_url' ),
			),
			'affiliate_approved'       => array(
				'label'   => __( 'Application approved (to affiliate)', 'directorist-affiliate' ),
				'toggle'  => 'notify_affiliate_status',
				'subject' => __( 'Your affiliate application was approved', 'directorist-affiliate' ),
				'body'    => __( "Good news — your affiliate application for {site_name} has been approved.\n\nYour referral link is waiting on your dashboard: {dashboard_url}", 'directorist-affiliate' ),
				'tokens'  => array( 'site_name', 'affiliate_name', 'dashboard_url' ),
			),
			'affiliate_rejected'       => array(
				'label'   => __( 'Application rejected (to affiliate)', 'directorist-affiliate' ),
				'toggle'  => 'notify_affiliate_status',
				'subject' => __( 'Your affiliate application was not approved', 'directorist-affiliate' ),
				'body'    => __( 'Thank you for applying to the {site_name} affiliate program. On this occasion your application was not approved.', 'directorist-affiliate' ),
				'tokens'  => array( 'site_name', 'affiliate_name' ),
			),
			'affiliate_referral'       => array(
				'label'   => __( 'New referral (to affiliate)', 'directorist-affiliate' ),
				'toggle'  => 'notify_affiliate_referral',
				'subject' => __( 'You earned a new referral', 'directorist-affiliate' ),
				'body'    => __( "A new \"{referral_type}\" referral was recorded, earning you {amount}.\n\nSee it on your dashboard: {dashboard_url}", 'directorist-affiliate' ),
				'tokens'  => array( 'site_name', 'affiliate_name', 'referral_type', 'amount', 'dashboard_url' ),
			),
			'admin_payout_request'     => array(
				'label'   => __( 'Payout requested (to admin)', 'directorist-affiliate' ),
				'toggle'  => 'notify_admin_payout_request',
				'subject' => __( 'New affiliate payout request', 'directorist-affiliate' ),
				'body'    => __( "{affiliate_name} has requested a payout of {amount}, to be paid by {payout_method}.\n\nReview it here: {admin_url}", 'directorist-affiliate' ),
				'tokens'  => array( 'site_name', 'affiliate_name', 'amount', 'payout_method', 'admin_url' ),
			),
			'affiliate_payout_paid'    => array(
				'label'   => __( 'Payout paid (to affiliate)', 'directorist-affiliate' ),
				'toggle'  => 'notify_affiliate_payout',
				'subject' => __( 'Your payout has been sent', 'directorist-affiliate' ),
				'body'    => __( "Your payout of {amount} has been marked as paid, via {payout_method}.\n\nThank you for promoting {site_name}.", 'directorist-affiliate' ),
				'tokens'  => array( 'site_name', 'affiliate_name', 'amount', 'payout_method', 'dashboard_url' ),
			),
			'affiliate_payout_rejected' => array(
				'label'   => __( 'Payout declined (to affiliate)', 'directorist-affiliate' ),
				'toggle'  => 'notify_affiliate_payout',
				'subject' => __( 'Your payout request was declined', 'directorist-affiliate' ),
				'body'    => __( 'Your payout request of {amount} was not approved. Those commissions stay in your balance, so you can request them again.', 'directorist-affiliate' ),
				'tokens'  => array( 'site_name', 'affiliate_name', 'amount', 'dashboard_url' ),
			),
		);

		/**
		 * Filters the notification templates.
		 *
		 * @param array<string,array<string,mixed>> $templates Template definitions.
		 */
		return (array) apply_filters( 'directorist_affiliate_email_templates', $templates );
	}

	/**
	 * Whether a template key is one this class knows.
	 *
	 * @param string $key Template key.
	 *
	 * @return bool
	 */
	public function exists( string $key ): bool {
		return array_key_exists( $key, $this->all() );
	}

	/**
	 * The admin's override for a template, if any.
	 *
	 * @param string $key Template key.
	 *
	 * @return array{subject:string,body:string}
	 */
	public function override( string $key ): array {
		$stored = (array) $this->settings->get( 'email_templates', array() );
		$one    = isset( $stored[ $key ] ) && is_array( $stored[ $key ] ) ? $stored[ $key ] : array();

		return array(
			'subject' => isset( $one['subject'] ) ? (string) $one['subject'] : '',
			'body'    => isset( $one['body'] ) ? (string) $one['body'] : '',
		);
	}

	/**
	 * Build the finished subject and body for one notification.
	 *
	 * @param string                $key Template key.
	 * @param array<string,string>  $tokens Replacement values, without braces.
	 *
	 * @return array{subject:string,body:string}
	 */
	public function render( string $key, array $tokens = array() ): array {
		$all = $this->all();

		if ( ! isset( $all[ $key ] ) ) {
			return array(
				'subject' => '',
				'body'    => '',
			);
		}

		$override = $this->override( $key );
		$subject  = '' !== $override['subject'] ? $this->translate( $key . '_subject', $override['subject'] ) : $all[ $key ]['subject'];
		$body     = '' !== $override['body'] ? $this->translate( $key . '_body', $override['body'] ) : $all[ $key ]['body'];

		$tokens = wp_parse_args( $tokens, $this->default_tokens() );
		$search = array();

		foreach ( array_keys( $tokens ) as $token ) {
			$search[] = '{' . $token . '}';
		}

		$subject = str_replace( $search, array_values( $tokens ), $subject );
		$body    = str_replace( $search, array_values( $tokens ), $body );

		/**
		 * Filters a rendered notification just before it is sent.
		 *
		 * @param array{subject:string,body:string} $email Rendered email.
		 * @param string                            $key Template key.
		 * @param array<string,string>              $tokens Tokens used.
		 */
		return (array) apply_filters(
			'directorist_affiliate_email_content',
			array(
				'subject' => $subject,
				'body'    => $body,
			),
			$key,
			$tokens
		);
	}

	/**
	 * Tokens available to every template.
	 *
	 * @return array<string,string>
	 */
	public function default_tokens(): array {
		return array(
			'site_name'       => wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ),
			'site_url'        => home_url( '/' ),
			'affiliate_name'  => '',
			'affiliate_email' => '',
			'amount'          => '',
			'referral_type'   => '',
			'payout_method'   => '',
			'dashboard_url'   => '',
			'admin_url'       => '',
		);
	}

	/**
	 * Register the stored overrides with WPML/Polylang string translation.
	 *
	 * Without this, admin-entered wording is untranslatable: it lives in the
	 * database, so it never reaches the POT file.
	 *
	 * @return void
	 */
	public function register_strings(): void {
		if ( ! has_action( 'wpml_register_single_string' ) ) {
			return;
		}

		foreach ( array_keys( $this->all() ) as $key ) {
			$override = $this->override( $key );

			foreach ( array( 'subject', 'body' ) as $part ) {
				if ( '' === $override[ $part ] ) {
					continue;
				}

				do_action( 'wpml_register_single_string', self::STRING_CONTEXT, $key . '_' . $part, $override[ $part ] );
			}
		}
	}

	/**
	 * Translate one stored string through WPML, when available.
	 *
	 * @param string $name String name.
	 * @param string $value Original value.
	 *
	 * @return string
	 */
	private function translate( string $name, string $value ): string {
		if ( ! has_filter( 'wpml_translate_single_string' ) ) {
			return $value;
		}

		return (string) apply_filters( 'wpml_translate_single_string', $value, self::STRING_CONTEXT, $name );
	}

	/**
	 * Sanitize submitted template overrides.
	 *
	 * Wording identical to the default is stored as empty, so a template the
	 * admin never really changed keeps following future default improvements
	 * and stays translatable through the POT rather than through WPML.
	 *
	 * @param mixed $raw Raw value.
	 *
	 * @return array<string,array{subject:string,body:string}>
	 */
	public function sanitize( $raw ): array {
		$clean = array();
		$all   = $this->all();

		foreach ( (array) $raw as $key => $parts ) {
			$key = sanitize_key( $key );

			if ( ! isset( $all[ $key ] ) || ! is_array( $parts ) ) {
				continue;
			}

			$subject = isset( $parts['subject'] ) ? sanitize_text_field( wp_unslash( $parts['subject'] ) ) : '';
			$body    = isset( $parts['body'] ) ? sanitize_textarea_field( wp_unslash( $parts['body'] ) ) : '';

			if ( $subject === $all[ $key ]['subject'] ) {
				$subject = '';
			}

			if ( $body === $all[ $key ]['body'] ) {
				$body = '';
			}

			if ( '' === $subject && '' === $body ) {
				continue;
			}

			$clean[ $key ] = array(
				'subject' => $subject,
				'body'    => $body,
			);
		}

		return $clean;
	}
}

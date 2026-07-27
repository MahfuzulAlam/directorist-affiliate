<?php
/**
 * Visit tracking service.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles referral cookies and visit records.
 */
final class Directorist_Affiliate_Tracking {
	private const AFFILIATE_COOKIE = 'directorist_affiliate_ref';
	private const VISIT_COOKIE     = 'directorist_affiliate_visit';

	/**
	 * Settings service.
	 *
	 * @var Directorist_Affiliate_Settings
	 */
	private $settings;

	/**
	 * Affiliate repository.
	 *
	 * @var Directorist_Affiliate_Affiliate
	 */
	private $affiliate;

	/**
	 * Constructor.
	 *
	 * @param Directorist_Affiliate_Settings  $settings Settings.
	 * @param Directorist_Affiliate_Affiliate $affiliate Affiliate repository.
	 */
	public function __construct( Directorist_Affiliate_Settings $settings, Directorist_Affiliate_Affiliate $affiliate ) {
		$this->settings  = $settings;
		$this->affiliate = $affiliate;
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public function table(): string {
		global $wpdb;

		return $wpdb->prefix . 'directorist_affiliate_visits';
	}

	/**
	 * Register tracking hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'capture_visit' ), 1 );
	}

	/**
	 * Capture referral link visits.
	 *
	 * @return void
	 */
	public function capture_visit(): void {
		if ( is_admin() || ! $this->settings->is_enabled() ) {
			return;
		}

		$param = (string) $this->settings->get( 'ref_param', 'ref' );

		if ( empty( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$code      = sanitize_text_field( wp_unslash( $_GET[ $param ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$affiliate = $this->affiliate->get_approved_by_code( $code );

		if ( ! $affiliate ) {
			return;
		}

		if ( is_user_logged_in() && ! empty( $affiliate->user_id ) && get_current_user_id() === (int) $affiliate->user_id ) {
			return;
		}

		// First-click attribution: an existing valid credit is never overwritten.
		if ( 'first_click' === $this->settings->get( 'attribution_model', 'first_click' ) ) {
			$existing_id = $this->get_cookie_affiliate_id();

			if ( $existing_id && $existing_id !== (int) $affiliate->id ) {
				$existing = $this->affiliate->get( $existing_id );

				if ( $existing && 'approved' === $existing->status ) {
					return;
				}
			}
		}

		$visit_id = $this->create_visit( $affiliate );
		$expires  = time() + ( DAY_IN_SECONDS * max( 1, absint( $this->settings->get( 'cookie_duration', 30 ) ) ) );
		$secure   = is_ssl();

		setcookie( self::AFFILIATE_COOKIE, (string) $affiliate->id, $expires, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, true );
		setcookie( self::VISIT_COOKIE, (string) $visit_id, $expires, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, true );

		$_COOKIE[ self::AFFILIATE_COOKIE ] = (string) $affiliate->id;
		$_COOKIE[ self::VISIT_COOKIE ]     = (string) $visit_id;
	}

	/**
	 * Create visit record.
	 *
	 * @param object $affiliate Affiliate row.
	 *
	 * @return int
	 */
	public function create_visit( $affiliate ): int {
		global $wpdb;

		$inserted = $wpdb->insert(
			$this->table(),
			array(
				'affiliate_id'  => (int) $affiliate->id,
				'referral_code' => $affiliate->referral_code,
				'landing_url'   => esc_url_raw( $this->current_url() ),
				'referrer_url'  => ! empty( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
				'ip_address'    => $this->get_ip_address(),
				'user_agent'    => ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
				'converted'     => 0,
				'date_created'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		return $inserted ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Get current cookie affiliate ID.
	 *
	 * @return int
	 */
	public function get_cookie_affiliate_id(): int {
		return ! empty( $_COOKIE[ self::AFFILIATE_COOKIE ] ) ? absint( wp_unslash( $_COOKIE[ self::AFFILIATE_COOKIE ] ) ) : 0;
	}

	/**
	 * Get current cookie visit ID.
	 *
	 * @return int
	 */
	public function get_cookie_visit_id(): int {
		return ! empty( $_COOKIE[ self::VISIT_COOKIE ] ) ? absint( wp_unslash( $_COOKIE[ self::VISIT_COOKIE ] ) ) : 0;
	}

	/**
	 * Mark visit as converted.
	 *
	 * @param int $visit_id Visit ID.
	 * @param int $user_id User ID.
	 * @param int $listing_id Listing ID.
	 *
	 * @return void
	 */
	public function mark_converted( int $visit_id, int $user_id = 0, int $listing_id = 0 ): void {
		global $wpdb;

		if ( ! $visit_id ) {
			return;
		}

		$wpdb->update(
			$this->table(),
			array(
				'converted'        => 1,
				'referred_user_id' => $user_id ? $user_id : null,
				'listing_id'       => $listing_id ? $listing_id : null,
			),
			array( 'id' => $visit_id ),
			array( '%d', '%d', '%d' ),
			array( '%d' )
		);
	}

	/**
	 * List visits.
	 *
	 * @param int $limit Limit.
	 *
	 * @return object[]
	 */
	public function list( int $limit = 50 ): array {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table()} ORDER BY date_created DESC LIMIT %d", absint( $limit ) )
		);
	}

	/**
	 * Count visits.
	 *
	 * @param int $affiliate_id Optional affiliate ID.
	 *
	 * @return int
	 */
	public function count( int $affiliate_id = 0 ): int {
		global $wpdb;

		if ( $affiliate_id ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$this->table()} WHERE affiliate_id = %d", $affiliate_id )
			);
		}

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table()}" );
	}

	/**
	 * Build current URL.
	 *
	 * @return string
	 */
	private function current_url(): string {
		$scheme = is_ssl() ? 'https://' : 'http://';
		$host   = ! empty( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : wp_parse_url( home_url(), PHP_URL_HOST );
		$uri    = ! empty( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

		return $scheme . $host . $uri;
	}

	/**
	 * Get visitor IP.
	 *
	 * @return string
	 */
	private function get_ip_address(): string {
		$ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( $ip && absint( $this->settings->get( 'anonymize_ip', 0 ) ) ) {
			$ip = wp_privacy_anonymize_ip( $ip );
		}

		return substr( $ip, 0, 100 );
	}
}

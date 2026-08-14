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
	 * Cookie payload format marker.
	 *
	 * Both cookies carry "v1.<id>-<timestamp>.<signature>". Bumping this
	 * invalidates every cookie in the wild, so only change it when the
	 * payload layout itself changes.
	 */
	private const COOKIE_VERSION = 'v1';

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
		if ( is_admin() || headers_sent() || ! $this->settings->is_enabled() ) {
			return;
		}

		$param = (string) $this->settings->get( 'ref_param', 'ref' );

		if ( empty( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// Crawlers and speculative prefetches are requests no human made.
		if ( $this->is_bot() || $this->is_prefetch() ) {
			return;
		}

		$code = sanitize_text_field( wp_unslash( $_GET[ $param ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		/**
		 * Filters whether this request may be tracked at all.
		 *
		 * Return false from a consent manager to suppress the tracking
		 * cookies until the visitor has agreed to them.
		 *
		 * @param bool   $should_track Whether to track. Default true.
		 * @param string $code Referral code from the URL.
		 */
		if ( ! apply_filters( 'directorist_affiliate_should_track', true, $code ) ) {
			return;
		}

		$affiliate = $this->affiliate->get_approved_by_code( $code );

		if ( ! $affiliate ) {
			return;
		}

		if ( is_user_logged_in() && ! empty( $affiliate->user_id ) && get_current_user_id() === (int) $affiliate->user_id ) {
			return;
		}

		$affiliate_id = (int) $affiliate->id;
		$now          = time();
		$credit       = $this->read_cookie( self::AFFILIATE_COOKIE );
		$holds_credit = $credit && $credit['id'] === $affiliate_id;

		// First-click attribution: an existing valid credit is never overwritten.
		if ( $credit && ! $holds_credit && 'first_click' === $this->settings->get( 'attribution_model', 'first_click' ) ) {
			$existing = $this->affiliate->get( $credit['id'] );

			if ( $existing && 'approved' === $existing->status ) {
				return;
			}
		}

		// The window is anchored to the first click and does not slide, so
		// "first click keeps the credit for N days" means exactly N days.
		$first_seen = $holds_credit ? $credit['time'] : $now;
		$expires    = $first_seen + ( DAY_IN_SECONDS * max( 1, absint( $this->settings->get( 'cookie_duration', 30 ) ) ) );

		if ( $expires <= $now ) {
			$first_seen = $now;
			$expires    = $now + ( DAY_IN_SECONDS * max( 1, absint( $this->settings->get( 'cookie_duration', 30 ) ) ) );
		}

		// One visit row per affiliate per visitor per dedupe window: a reload
		// or a second click from the same person is not a new click.
		$visit  = $this->read_cookie( self::VISIT_COOKIE );
		$recent = $holds_credit && $visit && ( $now - $visit['time'] ) < $this->visit_dedupe_window();

		if ( $recent ) {
			$visit_id   = $visit['id'];
			$counted_at = $visit['time'];
		} else {
			$visit_id   = $this->create_visit( $affiliate );
			$counted_at = $now;
		}

		$this->write_cookie( self::AFFILIATE_COOKIE, $affiliate_id, $first_seen, $expires );
		$this->write_cookie( self::VISIT_COOKIE, $visit_id, $counted_at, $expires );
	}

	/**
	 * How long before the same visitor counts as a new visit for an affiliate.
	 *
	 * @return int Seconds.
	 */
	private function visit_dedupe_window(): int {
		/**
		 * Filters the visit deduplication window.
		 *
		 * @param int $seconds Window length. Default one day.
		 */
		return max( 0, absint( apply_filters( 'directorist_affiliate_visit_dedupe_window', DAY_IN_SECONDS ) ) );
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
	 * @return int Zero when absent or tampered with.
	 */
	public function get_cookie_affiliate_id(): int {
		$cookie = $this->read_cookie( self::AFFILIATE_COOKIE );

		return $cookie ? $cookie['id'] : 0;
	}

	/**
	 * Get current cookie visit ID.
	 *
	 * @return int Zero when absent or tampered with.
	 */
	public function get_cookie_visit_id(): int {
		$cookie = $this->read_cookie( self::VISIT_COOKIE );

		return $cookie ? $cookie['id'] : 0;
	}

	/**
	 * Sign a cookie payload with the site's auth salt.
	 *
	 * @param string $payload Payload to sign.
	 *
	 * @return string
	 */
	private function sign( string $payload ): string {
		return substr( hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) ), 0, 32 );
	}

	/**
	 * Read and verify one of the tracking cookies.
	 *
	 * @param string $name Cookie name.
	 *
	 * @return array{id:int,time:int}|null Null when absent, malformed, or the
	 *                                     signature does not verify.
	 */
	private function read_cookie( string $name ): ?array {
		$raw = isset( $_COOKIE[ $name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) ) : '';

		if ( '' === $raw ) {
			return null;
		}

		// Unsigned cookies written before 1.3.0 held a bare ID. They are
		// honored so live referral windows survive the upgrade, and get
		// replaced with a signed cookie on the visitor's next tracked hit.
		// This branch can be dropped once the longest cookie duration in use
		// has elapsed since upgrading.
		if ( ctype_digit( $raw ) ) {
			return array(
				'id'   => (int) $raw,
				'time' => time(),
			);
		}

		$parts = explode( '.', $raw );

		if ( 3 !== count( $parts ) || self::COOKIE_VERSION !== $parts[0] ) {
			return null;
		}

		if ( ! hash_equals( $this->sign( $parts[1] ), $parts[2] ) ) {
			return null;
		}

		$values = explode( '-', $parts[1] );

		if ( 2 !== count( $values ) ) {
			return null;
		}

		return array(
			'id'   => absint( $values[0] ),
			'time' => absint( $values[1] ),
		);
	}

	/**
	 * Write a signed tracking cookie.
	 *
	 * @param string $name Cookie name.
	 * @param int    $id Affiliate or visit ID.
	 * @param int    $timestamp Anchor timestamp carried in the payload.
	 * @param int    $expires Expiry timestamp.
	 *
	 * @return void
	 */
	private function write_cookie( string $name, int $id, int $timestamp, int $expires ): void {
		$payload = absint( $id ) . '-' . absint( $timestamp );
		$value   = self::COOKIE_VERSION . '.' . $payload . '.' . $this->sign( $payload );

		setcookie(
			$name,
			$value,
			array(
				'expires'  => $expires,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				// Lax still arrives on the top-level click that starts a
				// referral, while keeping the cookie off cross-site requests.
				'samesite' => 'Lax',
			)
		);

		$_COOKIE[ $name ] = $value;
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
	 * @param array<string,mixed> $args Query args (affiliate_id, converted, date_from, date_to, limit, offset).
	 *
	 * @return object[]
	 */
	public function list( array $args = array() ): array {
		global $wpdb;

		list( $where, $params ) = $this->build_where( $args );

		$params[] = isset( $args['limit'] ) ? absint( $args['limit'] ) : 50;
		$params[] = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE {$where} ORDER BY date_created DESC LIMIT %d OFFSET %d", $params )
		);
	}

	/**
	 * Count visits matching the same filters as list().
	 *
	 * @param array<string,mixed> $args Query args (affiliate_id, converted, date_from, date_to).
	 *
	 * @return int
	 */
	public function count( array $args = array() ): int {
		global $wpdb;

		list( $where, $params ) = $this->build_where( $args );

		$sql = "SELECT COUNT(*) FROM {$this->table()} WHERE {$where}";

		if ( $params ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Visit totals for every affiliate, in one grouped query.
	 *
	 * Mirrors Referral::stats_by_affiliate(). Screens and exports that show a
	 * per-affiliate visit count must use this rather than calling count() per
	 * row, which would be one query per affiliate.
	 *
	 * @return array<int,object>
	 */
	public function stats_by_affiliate(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT affiliate_id,
				COUNT(*) AS total_visits,
				COALESCE(SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END), 0) AS converted_visits
			FROM {$this->table()}
			GROUP BY affiliate_id"
		);

		$stats = array();

		foreach ( $rows as $row ) {
			$stats[ (int) $row->affiliate_id ] = $row;
		}

		return $stats;
	}

	/**
	 * Build the shared WHERE clause for list()/count().
	 *
	 * `converted` accepts a bool, or '1'/'0' strings from a request; anything
	 * else (including '') leaves the filter off.
	 *
	 * @param array<string,mixed> $args Query args.
	 *
	 * @return array{0:string,1:array<int,mixed>} WHERE fragment and its params.
	 */
	private function build_where( array $args ): array {
		$where  = '1=1';
		$params = array();

		if ( ! empty( $args['affiliate_id'] ) ) {
			$where   .= ' AND affiliate_id = %d';
			$params[] = absint( $args['affiliate_id'] );
		}

		if ( isset( $args['converted'] ) && '' !== $args['converted'] && null !== $args['converted'] ) {
			$where   .= ' AND converted = %d';
			$params[] = ( $args['converted'] && '0' !== $args['converted'] ) ? 1 : 0;
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where   .= ' AND date_created >= %s';
			$params[] = (string) $args['date_from'];
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where   .= ' AND date_created <= %s';
			$params[] = (string) $args['date_to'];
		}

		return array( $where, $params );
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
	 * Whether the current request looks like a crawler.
	 *
	 * Keeps obvious bot traffic out of the visits table so click counts
	 * reflect real visitors. Deliberately conservative: unknown agents pass.
	 *
	 * @return bool
	 */
	private function is_bot(): bool {
		$user_agent = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		if ( '' === $user_agent ) {
			return true;
		}

		return (bool) preg_match( '/bot|crawl|spider|slurp|preview|headless|scrape|curl|wget|python-requests|facebookexternalhit/i', $user_agent );
	}

	/**
	 * Whether the browser is speculatively fetching, not showing, this page.
	 *
	 * Chrome and Safari prefetch links the visitor merely hovered; those
	 * requests carry a real user agent and would otherwise be logged as
	 * clicks nobody made.
	 *
	 * @return bool
	 */
	private function is_prefetch(): bool {
		$headers = array( 'HTTP_SEC_PURPOSE', 'HTTP_PURPOSE', 'HTTP_X_PURPOSE', 'HTTP_X_MOZ' );

		foreach ( $headers as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$value = strtolower( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );

			if ( false !== strpos( $value, 'prefetch' ) || false !== strpos( $value, 'prerender' ) || false !== strpos( $value, 'preview' ) ) {
				return true;
			}
		}

		return false;
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

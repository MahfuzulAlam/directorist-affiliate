<?php
/**
 * Date range filtering.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolves named date-range presets (and custom ranges) into the MySQL
 * datetime bounds used by the list queries.
 *
 * All boundaries are computed in the site's timezone and are inclusive:
 * a range always spans 00:00:00 of the first day to 23:59:59 of the last.
 */
final class Directorist_Affiliate_Date_Range {
	/**
	 * Query var holding the selected preset.
	 */
	public const PARAM_PRESET = 'range';

	/**
	 * Query var holding the custom start date (Y-m-d).
	 */
	public const PARAM_FROM = 'from';

	/**
	 * Query var holding the custom end date (Y-m-d).
	 */
	public const PARAM_TO = 'to';

	/**
	 * Preset key used when the admin picks explicit dates.
	 */
	public const CUSTOM = 'custom';

	/**
	 * Available presets as key => translated label.
	 *
	 * @return array<string,string>
	 */
	public static function presets(): array {
		return array(
			''            => __( 'All time', 'directorist-affiliate' ),
			'today'       => __( 'Today', 'directorist-affiliate' ),
			'yesterday'   => __( 'Yesterday', 'directorist-affiliate' ),
			'this_week'   => __( 'This week', 'directorist-affiliate' ),
			'last_week'   => __( 'Last week', 'directorist-affiliate' ),
			'this_month'  => __( 'This month', 'directorist-affiliate' ),
			'last_month'  => __( 'Last month', 'directorist-affiliate' ),
			'last_7'      => __( 'Last 7 days', 'directorist-affiliate' ),
			'last_30'     => __( 'Last 30 days', 'directorist-affiliate' ),
			'this_year'   => __( 'This year', 'directorist-affiliate' ),
			self::CUSTOM  => __( 'Custom range…', 'directorist-affiliate' ),
		);
	}

	/**
	 * Whether a preset key is one this class understands.
	 *
	 * @param string $preset Preset key.
	 *
	 * @return bool
	 */
	public static function is_valid_preset( string $preset ): bool {
		return array_key_exists( $preset, self::presets() );
	}

	/**
	 * Read the range filter out of a request array.
	 *
	 * Returns the raw (sanitized) selection, ready to echo back into the form
	 * and to carry through pagination links.
	 *
	 * @param array<string,mixed> $request Request data (e.g. $_GET).
	 *
	 * @return array{preset:string,from:string,to:string}
	 */
	public static function from_request( array $request ): array {
		$preset = isset( $request[ self::PARAM_PRESET ] ) ? sanitize_key( wp_unslash( $request[ self::PARAM_PRESET ] ) ) : '';

		if ( ! self::is_valid_preset( $preset ) ) {
			$preset = '';
		}

		return array(
			'preset' => $preset,
			'from'   => self::sanitize_date( $request[ self::PARAM_FROM ] ?? '' ),
			'to'     => self::sanitize_date( $request[ self::PARAM_TO ] ?? '' ),
		);
	}

	/**
	 * Resolve a selection into inclusive MySQL datetime bounds.
	 *
	 * @param string $preset Preset key.
	 * @param string $from Custom start date (Y-m-d).
	 * @param string $to Custom end date (Y-m-d).
	 *
	 * @return array{0:string,1:string} Start and end datetimes; empty strings mean unbounded.
	 */
	public static function resolve( string $preset, string $from = '', string $to = '' ): array {
		if ( self::CUSTOM === $preset ) {
			$from = self::sanitize_date( $from );
			$to   = self::sanitize_date( $to );

			// A half-open custom range is still useful; swap reversed dates.
			if ( $from && $to && $from > $to ) {
				list( $from, $to ) = array( $to, $from );
			}

			return array(
				$from ? $from . ' 00:00:00' : '',
				$to ? $to . ' 23:59:59' : '',
			);
		}

		if ( ! $preset || ! self::is_valid_preset( $preset ) ) {
			return array( '', '' );
		}

		$today      = self::today();
		$start_day  = (int) get_option( 'start_of_week', 1 );
		$day_number = (int) gmdate( 'w', strtotime( $today ) );
		$week_back  = ( $day_number - $start_day + 7 ) % 7;

		switch ( $preset ) {
			case 'today':
				return self::span( $today, $today );

			case 'yesterday':
				$yesterday = self::shift( $today, '-1 day' );
				return self::span( $yesterday, $yesterday );

			case 'this_week':
				return self::span( self::shift( $today, '-' . $week_back . ' days' ), $today );

			case 'last_week':
				$last_start = self::shift( $today, '-' . ( $week_back + 7 ) . ' days' );
				return self::span( $last_start, self::shift( $last_start, '+6 days' ) );

			case 'this_month':
				return self::span( gmdate( 'Y-m-01', strtotime( $today ) ), $today );

			case 'last_month':
				$last_start = gmdate( 'Y-m-01', strtotime( $today . ' first day of last month' ) );
				return self::span( $last_start, gmdate( 'Y-m-t', strtotime( $last_start ) ) );

			case 'last_7':
				return self::span( self::shift( $today, '-6 days' ), $today );

			case 'last_30':
				return self::span( self::shift( $today, '-29 days' ), $today );

			case 'this_year':
				return self::span( gmdate( 'Y-01-01', strtotime( $today ) ), $today );
		}

		return array( '', '' );
	}

	/**
	 * Human-readable description of the active range, for the filter summary.
	 *
	 * @param string $preset Preset key.
	 * @param string $from Custom start date.
	 * @param string $to Custom end date.
	 *
	 * @return string Empty string when no range is active.
	 */
	public static function label( string $preset, string $from = '', string $to = '' ): string {
		if ( ! $preset ) {
			return '';
		}

		if ( self::CUSTOM !== $preset ) {
			return self::presets()[ $preset ] ?? '';
		}

		list( $start, $end ) = self::resolve( $preset, $from, $to );

		if ( ! $start && ! $end ) {
			return '';
		}

		$format = (string) get_option( 'date_format', 'Y-m-d' );

		if ( $start && $end ) {
			return sprintf(
				/* translators: 1: range start date, 2: range end date. */
				__( '%1$s – %2$s', 'directorist-affiliate' ),
				mysql2date( $format, $start ),
				mysql2date( $format, $end )
			);
		}

		if ( $start ) {
			return sprintf(
				/* translators: %s: range start date. */
				__( 'From %s', 'directorist-affiliate' ),
				mysql2date( $format, $start )
			);
		}

		return sprintf(
			/* translators: %s: range end date. */
			__( 'Until %s', 'directorist-affiliate' ),
			mysql2date( $format, $end )
		);
	}

	/**
	 * Validate a Y-m-d date string.
	 *
	 * @param mixed $date Raw date.
	 *
	 * @return string Empty string when the value is not a real calendar date.
	 */
	public static function sanitize_date( $date ): string {
		$date = is_string( $date ) ? trim( wp_unslash( $date ) ) : '';

		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts ) ) {
			return '';
		}

		return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ? $date : '';
	}

	/**
	 * Today's date in the site's timezone.
	 *
	 * @return string Y-m-d
	 */
	private static function today(): string {
		return current_time( 'Y-m-d' );
	}

	/**
	 * Shift a Y-m-d date by a relative modifier.
	 *
	 * @param string $date Y-m-d date.
	 * @param string $modifier strtotime modifier, e.g. '-1 day'.
	 *
	 * @return string Y-m-d
	 */
	private static function shift( string $date, string $modifier ): string {
		return gmdate( 'Y-m-d', strtotime( $date . ' ' . $modifier ) );
	}

	/**
	 * Build inclusive datetime bounds for two dates.
	 *
	 * @param string $start Y-m-d start date.
	 * @param string $end Y-m-d end date.
	 *
	 * @return array{0:string,1:string}
	 */
	private static function span( string $start, string $end ): array {
		return array( $start . ' 00:00:00', $end . ' 23:59:59' );
	}
}

<?php
/**
 * Affiliate repository.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores and retrieves affiliate applications.
 */
final class Directorist_Affiliate_Affiliate {
	/**
	 * Allowed affiliate statuses.
	 *
	 * @return string[]
	 */
	public function statuses(): array {
		return array( 'pending', 'approved', 'rejected', 'suspended' );
	}

	/**
	 * Translated label for an affiliate status.
	 *
	 * @param string $status Affiliate status.
	 *
	 * @return string
	 */
	public function status_label( string $status ): string {
		$labels = array(
			'pending'   => __( 'Pending', 'directorist-affiliate' ),
			'approved'  => __( 'Approved', 'directorist-affiliate' ),
			'rejected'  => __( 'Rejected', 'directorist-affiliate' ),
			'suspended' => __( 'Suspended', 'directorist-affiliate' ),
		);

		return $labels[ $status ] ?? ucfirst( $status );
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public function table(): string {
		global $wpdb;

		return $wpdb->prefix . 'directorist_affiliates';
	}

	/**
	 * Create an affiliate application.
	 *
	 * @param array<string,mixed> $data Affiliate data.
	 *
	 * @return int
	 */
	public function create( array $data ): int {
		global $wpdb;

		$user_id       = ! empty( $data['user_id'] ) ? absint( $data['user_id'] ) : null;
		$referral_code = $this->generate_referral_code( $user_id );
		$now           = current_time( 'mysql' );
		$status        = isset( $data['status'] ) && in_array( $data['status'], $this->statuses(), true ) ? $data['status'] : 'pending';

		$inserted = $wpdb->insert(
			$this->table(),
			array(
				'user_id'            => $user_id,
				'status'             => $status,
				'referral_code'      => $referral_code,
				'payout_email'       => sanitize_email( $data['payout_email'] ?? '' ),
				'website'            => esc_url_raw( $data['website'] ?? '' ),
				'promotional_method' => sanitize_text_field( $data['promotional_method'] ?? '' ),
				'application_note'   => sanitize_textarea_field( $data['application_note'] ?? '' ),
				'date_created'       => $now,
				'date_updated'       => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return 0;
		}

		$affiliate_id = (int) $wpdb->insert_id;

		/**
		 * Fires after an affiliate record is created.
		 *
		 * @param int    $affiliate_id Affiliate ID.
		 * @param string $status Initial status.
		 */
		do_action( 'directorist_affiliate_created', $affiliate_id, $status );

		return $affiliate_id;
	}

	/**
	 * Create a WordPress user for an affiliate application.
	 *
	 * Generates a unique username from the email local part, assigns a random
	 * password, and sends the core new-user notification.
	 *
	 * @param string $name Display name.
	 * @param string $email Email address.
	 *
	 * @return int|WP_Error New user ID or error.
	 */
	public function register_user( string $name, string $email ) {
		$username = sanitize_user( current( explode( '@', $email ) ), true );
		$username = $username ? $username : 'affiliate';
		$base     = $username;
		$i        = 1;

		while ( username_exists( $username ) ) {
			$username = $base . $i;
			$i++;
		}

		$user_id = wp_create_user( $username, wp_generate_password( 16 ), $email );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => $name,
			)
		);

		wp_new_user_notification( $user_id, null, 'user' );

		return (int) $user_id;
	}

	/**
	 * Update affiliate fields.
	 *
	 * @param int                 $affiliate_id Affiliate ID.
	 * @param array<string,mixed> $data Data.
	 *
	 * @return bool
	 */
	public function update( int $affiliate_id, array $data ): bool {
		global $wpdb;

		$allowed = array(
			'user_id'            => '%d',
			'status'             => '%s',
			'payout_email'       => '%s',
			'website'            => '%s',
			'promotional_method' => '%s',
			'application_note'   => '%s',
		);

		$values  = array();
		$formats = array();

		foreach ( $allowed as $key => $format ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			$value = $data[ $key ];

			if ( 'status' === $key && ! in_array( $value, $this->statuses(), true ) ) {
				continue;
			}

			if ( 'payout_email' === $key ) {
				$value = sanitize_email( $value );
			} elseif ( 'website' === $key ) {
				$value = esc_url_raw( $value );
			} elseif ( 'application_note' === $key ) {
				$value = sanitize_textarea_field( $value );
			} elseif ( 'user_id' === $key ) {
				$value = absint( $value );
			} else {
				$value = sanitize_text_field( $value );
			}

			$values[ $key ] = $value;
			$formats[]      = $format;
		}

		if ( empty( $values ) ) {
			return false;
		}

		$values['date_updated'] = current_time( 'mysql' );
		$formats[]             = '%s';

		return false !== $wpdb->update(
			$this->table(),
			$values,
			array( 'id' => absint( $affiliate_id ) ),
			$formats,
			array( '%d' )
		);
	}

	/**
	 * Update affiliate status.
	 *
	 * @param int    $affiliate_id Affiliate ID.
	 * @param string $status Status.
	 *
	 * @return bool
	 */
	public function update_status( int $affiliate_id, string $status ): bool {
		if ( ! in_array( $status, $this->statuses(), true ) ) {
			return false;
		}

		$updated = $this->update( $affiliate_id, array( 'status' => $status ) );

		if ( $updated ) {
			/**
			 * Fires after an affiliate status changes.
			 *
			 * @param int    $affiliate_id Affiliate ID.
			 * @param string $status New status.
			 */
			do_action( 'directorist_affiliate_status_changed', $affiliate_id, $status );
		}

		return $updated;
	}

	/**
	 * Get affiliate by ID.
	 *
	 * @param int $affiliate_id Affiliate ID.
	 *
	 * @return object|null
	 */
	public function get( int $affiliate_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id = %d", $affiliate_id )
		);
	}

	/**
	 * Get affiliate by user ID.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return object|null
	 */
	public function get_by_user_id( int $user_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE user_id = %d ORDER BY id DESC LIMIT 1", $user_id )
		);
	}

	/**
	 * Get approved affiliate by referral code.
	 *
	 * @param string $code Referral code.
	 *
	 * @return object|null
	 */
	public function get_approved_by_code( string $code ) {
		global $wpdb;

		$code = sanitize_text_field( $code );

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE referral_code = %s AND status = 'approved' LIMIT 1", $code )
		);
	}

	/**
	 * List affiliates.
	 *
	 * @param array<string,mixed> $args Query args (status, search, limit, offset).
	 *
	 * @return object[]
	 */
	public function list( array $args = array() ): array {
		global $wpdb;

		$limit  = isset( $args['limit'] ) ? absint( $args['limit'] ) : 50;
		$offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;

		list( $where, $params ) = $this->build_where( $args );

		$params[] = $limit;
		$params[] = $offset;

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE {$where} ORDER BY date_created DESC LIMIT %d OFFSET %d", $params )
		);
	}

	/**
	 * Count affiliates, honoring the same filters as list().
	 *
	 * Accepts either an args array or, for the common status-only case, the
	 * status slug on its own.
	 *
	 * @param array<string,mixed>|string $args Query args, or a status slug.
	 *
	 * @return int
	 */
	public function count( $args = array() ): int {
		global $wpdb;

		if ( ! is_array( $args ) ) {
			$args = array( 'status' => (string) $args );
		}

		list( $where, $params ) = $this->build_where( $args );

		$sql = "SELECT COUNT(*) FROM {$this->table()} WHERE {$where}";

		if ( $params ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Get several affiliates as an ID-indexed map (one query).
	 *
	 * @param int[] $ids Affiliate IDs.
	 *
	 * @return array<int,object>
	 */
	public function get_many( array $ids ): array {
		global $wpdb;

		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

		if ( empty( $ids ) ) {
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
		$rows         = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id IN ( {$placeholders} )", $ids ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$map = array();

		foreach ( $rows as $row ) {
			$map[ (int) $row->id ] = $row;
		}

		return $map;
	}

	/**
	 * All affiliates as ID => display name, for filter dropdowns.
	 *
	 * @param int $limit Maximum affiliates to return.
	 *
	 * @return array<int,string>
	 */
	public function options( int $limit = 500 ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table()} ORDER BY date_created DESC LIMIT %d", absint( $limit ) )
		);

		$options = array();

		foreach ( $rows as $row ) {
			$options[ (int) $row->id ] = $this->get_name( $row );
		}

		natcasesort( $options );

		return $options;
	}

	/**
	 * Build the shared WHERE clause for list()/count().
	 *
	 * Search matches referral code, payout email, and website in the affiliate
	 * table, plus WP users found by login/email/display name.
	 *
	 * @param array<string,mixed> $args Query args.
	 *
	 * @return array{0:string,1:array<int,mixed>} WHERE fragment and its params.
	 */
	private function build_where( array $args ): array {
		global $wpdb;

		$where  = '1=1';
		$params = array();
		$status = isset( $args['status'] ) ? sanitize_key( (string) $args['status'] ) : '';
		$search = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';

		if ( $status && in_array( $status, $this->statuses(), true ) ) {
			$where   .= ' AND status = %s';
			$params[] = $status;
		}

		if ( '' !== $search ) {
			$like       = '%' . $wpdb->esc_like( $search ) . '%';
			$conditions = array( 'referral_code LIKE %s', 'payout_email LIKE %s', 'website LIKE %s' );
			$params[]   = $like;
			$params[]   = $like;
			$params[]   = $like;

			$user_ids = get_users(
				array(
					'search'         => '*' . $search . '*',
					'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'display_name' ),
					'fields'         => 'ID',
					'number'         => 100,
				)
			);

			if ( $user_ids ) {
				$user_ids     = array_map( 'absint', $user_ids );
				$conditions[] = 'user_id IN ( ' . implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) ) . ' )';
				$params       = array_merge( $params, $user_ids );
			}

			$where .= ' AND ( ' . implode( ' OR ', $conditions ) . ' )';
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
	 * Build affiliate display name.
	 *
	 * @param object $affiliate Affiliate row.
	 *
	 * @return string
	 */
	public function get_name( $affiliate ): string {
		$user = ! empty( $affiliate->user_id ) ? get_user_by( 'id', (int) $affiliate->user_id ) : false;

		if ( $user ) {
			return $user->display_name ?: $user->user_login;
		}

		return $affiliate->payout_email;
	}

	/**
	 * Build affiliate email.
	 *
	 * @param object $affiliate Affiliate row.
	 *
	 * @return string
	 */
	public function get_email( $affiliate ): string {
		$user = ! empty( $affiliate->user_id ) ? get_user_by( 'id', (int) $affiliate->user_id ) : false;

		return $user ? $user->user_email : $affiliate->payout_email;
	}

	/**
	 * Generate unique referral code.
	 *
	 * @param int|null $user_id User ID.
	 *
	 * @return string
	 */
	private function generate_referral_code( ?int $user_id ): string {
		global $wpdb;

		$base = $user_id ? (string) $user_id : strtolower( wp_generate_password( 8, false, false ) );
		$code = $base;
		$i    = 1;

		while ( (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table()} WHERE referral_code = %s", $code ) ) > 0 ) {
			$code = $base . '-' . $i;
			$i++;
		}

		return $code;
	}
}

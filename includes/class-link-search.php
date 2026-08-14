<?php
/**
 * Link builder content search.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Finds published content an affiliate can build a referral link to, and
 * validates hand-typed URLs.
 *
 * Searches match the title (or term name) only — an affiliate is looking for
 * something they can already name, so matching body copy just adds noise.
 */
final class Directorist_Affiliate_Link_Search {
	/**
	 * Maximum results returned for one search.
	 */
	public const LIMIT = 10;

	/**
	 * Shortest term worth querying for.
	 */
	public const MIN_TERM_LENGTH = 2;

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
	 * Content types the builder offers, keyed by request value.
	 *
	 * Directorist-specific types are omitted when Directorist is not providing
	 * them, so the dropdown never offers a search that cannot return anything.
	 *
	 * @return array<string,array{label:string,hint:string,kind:string,source:string}>
	 */
	public function types(): array {
		$types = array(
			// Needs no input: the default, so the dashboard always opens with
			// a usable referral link already in the box.
			'home'   => array(
				'label'  => __( 'Home page', 'directorist-affiliate' ),
				'hint'   => __( 'Your main referral link. Sends people to the front page of the site.', 'directorist-affiliate' ),
				'kind'   => 'none',
				'source' => '',
			),
			'page'   => array(
				'label'  => __( 'Page', 'directorist-affiliate' ),
				'hint'   => __( 'Any published page on this site, such as a landing or pricing page.', 'directorist-affiliate' ),
				'kind'   => 'post_type',
				'source' => 'page',
			),
			'post'   => array(
				'label'  => __( 'Post', 'directorist-affiliate' ),
				'hint'   => __( 'A published blog post — useful when you have written about the site.', 'directorist-affiliate' ),
				'kind'   => 'post_type',
				'source' => 'post',
			),
		);

		if ( defined( 'ATBDP_POST_TYPE' ) ) {
			$types['listing'] = array(
				'label'  => __( 'Listing', 'directorist-affiliate' ),
				'hint'   => __( 'A single published listing. Send people straight to the business you are recommending.', 'directorist-affiliate' ),
				'kind'   => 'post_type',
				'source' => ATBDP_POST_TYPE,
			);
		}

		if ( defined( 'ATBDP_CATEGORY' ) && taxonomy_exists( ATBDP_CATEGORY ) ) {
			$types['category'] = array(
				'label'  => __( 'Category', 'directorist-affiliate' ),
				'hint'   => __( 'A listing category archive, for example every restaurant in the directory.', 'directorist-affiliate' ),
				'kind'   => 'taxonomy',
				'source' => ATBDP_CATEGORY,
			);
		}

		if ( defined( 'ATBDP_LOCATION' ) && taxonomy_exists( ATBDP_LOCATION ) ) {
			$types['location'] = array(
				'label'  => __( 'Location', 'directorist-affiliate' ),
				'hint'   => __( 'A location archive, for example every listing in one city.', 'directorist-affiliate' ),
				'kind'   => 'taxonomy',
				'source' => ATBDP_LOCATION,
			);
		}

		$types['custom'] = array(
			'label'  => __( 'Custom link', 'directorist-affiliate' ),
			'hint'   => __( 'Paste any address on this website. Links to other websites cannot be tracked, so they are not accepted.', 'directorist-affiliate' ),
			'kind'   => 'url',
			'source' => '',
		);

		/**
		 * Filters the content types offered by the dashboard link builder.
		 *
		 * @param array<string,array<string,string>> $types Types keyed by request value.
		 */
		return (array) apply_filters( 'directorist_affiliate_link_types', $types );
	}

	/**
	 * Whether a requested type is one this service handles.
	 *
	 * @param string $type Type key.
	 *
	 * @return bool
	 */
	public function is_valid_type( string $type ): bool {
		return array_key_exists( $type, $this->types() );
	}

	/**
	 * Search published content of one type by title.
	 *
	 * @param string $type Type key.
	 * @param string $term Search term.
	 * @param string $code Affiliate referral code.
	 *
	 * @return array<int,array{id:int,title:string,url:string,link:string}>
	 */
	public function search( string $type, string $term, string $code ): array {
		$term  = trim( $term );
		$types = $this->types();

		if ( ! isset( $types[ $type ] ) || mb_strlen( $term ) < self::MIN_TERM_LENGTH ) {
			return array();
		}

		$config = $types[ $type ];

		if ( 'post_type' === $config['kind'] ) {
			return $this->search_posts( (string) $config['source'], $term, $code );
		}

		if ( 'taxonomy' === $config['kind'] ) {
			return $this->search_terms( (string) $config['source'], $term, $code );
		}

		return array();
	}

	/**
	 * Search a post type by title.
	 *
	 * @param string $post_type Post type.
	 * @param string $term Search term.
	 * @param string $code Affiliate referral code.
	 *
	 * @return array<int,array{id:int,title:string,url:string,link:string}>
	 */
	private function search_posts( string $post_type, string $term, string $code ): array {
		if ( ! post_type_exists( $post_type ) ) {
			return array();
		}

		add_filter( 'posts_search', array( $this, 'restrict_search_to_title' ), 10, 2 );

		$query = new WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'has_password'           => false,
				's'                      => $term,
				'posts_per_page'         => self::LIMIT,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		remove_filter( 'posts_search', array( $this, 'restrict_search_to_title' ), 10 );

		$results = array();

		foreach ( $query->posts as $post ) {
			$url = (string) get_permalink( $post );

			if ( ! $url ) {
				continue;
			}

			$results[] = array(
				'id'    => (int) $post->ID,
				'title' => $this->title( get_the_title( $post ) ),
				'url'   => $url,
				'link'  => $this->referral_url( $code, $url ),
			);
		}

		return $results;
	}

	/**
	 * Search a taxonomy by term name.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $term Search term.
	 * @param string $code Affiliate referral code.
	 *
	 * @return array<int,array{id:int,title:string,url:string,link:string}>
	 */
	private function search_terms( string $taxonomy, string $term, string $code ): array {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'name__like' => $term,
				'number'     => self::LIMIT,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$results = array();

		foreach ( $terms as $found ) {
			$url = get_term_link( $found );

			if ( is_wp_error( $url ) || ! $url ) {
				continue;
			}

			$results[] = array(
				'id'    => (int) $found->term_id,
				'title' => $this->title( $found->name ),
				'url'   => (string) $url,
				'link'  => $this->referral_url( $code, (string) $url ),
			);
		}

		return $results;
	}

	/**
	 * Restrict a WP_Query search to post titles.
	 *
	 * Registered only for the duration of one query in search_posts().
	 *
	 * @param string   $search Search SQL.
	 * @param WP_Query $query Query.
	 *
	 * @return string
	 */
	public function restrict_search_to_title( $search, $query ): string {
		global $wpdb;

		$term = (string) $query->get( 's' );

		if ( '' === $term ) {
			return (string) $search;
		}

		return $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s ", '%' . $wpdb->esc_like( $term ) . '%' );
	}

	/**
	 * Validate a hand-typed URL and turn it into a referral link.
	 *
	 * Only addresses on this site are accepted: a referral cookie cannot be
	 * set on someone else's domain, so an off-site link would never track.
	 *
	 * @param string $url Raw URL.
	 * @param string $code Affiliate referral code.
	 *
	 * @return array{valid:bool,url:string,link:string,message:string}
	 */
	public function build_custom_link( string $url, string $code ): array {
		$fail = function ( string $message ): array {
			return array(
				'valid'   => false,
				'url'     => '',
				'link'    => '',
				'message' => $message,
			);
		};

		$url = trim( $url );

		if ( '' === $url ) {
			return $fail( __( 'Enter a link from this website.', 'directorist-affiliate' ) );
		}

		// A bare path or a host without a scheme is a reasonable thing to type.
		if ( 0 === strpos( $url, '/' ) ) {
			$url = home_url( $url );
		} elseif ( ! preg_match( '#^https?://#i', $url ) ) {
			$url = 'https://' . ltrim( $url, '/' );
		}

		$url   = esc_url_raw( $url );
		$parts = $url ? wp_parse_url( $url ) : false;
		$home  = wp_parse_url( home_url( '/' ) );

		if ( ! $parts || empty( $parts['host'] ) || empty( $home['host'] ) ) {
			return $fail( __( 'That does not look like a valid web address.', 'directorist-affiliate' ) );
		}

		if ( $this->normalize_host( $parts['host'] ) !== $this->normalize_host( (string) $home['host'] ) ) {
			return $fail( __( 'Only links on this website can be tracked. Check the address and try again.', 'directorist-affiliate' ) );
		}

		$path = strtolower( $parts['path'] ?? '' );

		if ( false !== strpos( $path, '/wp-admin' ) || false !== strpos( $path, 'wp-login.php' ) ) {
			return $fail( __( 'That is an admin address, so it will not work as a referral link.', 'directorist-affiliate' ) );
		}

		return array(
			'valid'   => true,
			'url'     => $url,
			'link'    => $this->referral_url( $code, $url ),
			'message' => '',
		);
	}

	/**
	 * Append the affiliate's referral code to a URL.
	 *
	 * @param string $code Affiliate referral code.
	 * @param string $base Base URL; defaults to the site home.
	 *
	 * @return string
	 */
	public function referral_url( string $code, string $base = '' ): string {
		return add_query_arg(
			rawurlencode( (string) $this->settings->get( 'ref_param', 'ref' ) ),
			rawurlencode( $code ),
			$base ? $base : home_url( '/' )
		);
	}

	/**
	 * Host without a leading www., lowercased, for comparison.
	 *
	 * @param string $host Host name.
	 *
	 * @return string
	 */
	private function normalize_host( string $host ): string {
		return strtolower( preg_replace( '/^www\./i', '', $host ) );
	}

	/**
	 * Readable title for a result, with a fallback for untitled content.
	 *
	 * @param string $title Raw title.
	 *
	 * @return string
	 */
	private function title( string $title ): string {
		$title = wp_strip_all_tags( $title );
		$title = html_entity_decode( $title, ENT_QUOTES, get_bloginfo( 'charset' ) );

		return '' !== trim( $title ) ? $title : __( '(no title)', 'directorist-affiliate' );
	}
}

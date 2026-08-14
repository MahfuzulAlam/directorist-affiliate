<?php
/**
 * Directorist integration.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Connects affiliate referrals to Directorist events.
 */
final class Directorist_Affiliate_Directorist_Integration {
	/**
	 * Plugin service container.
	 *
	 * @var Directorist_Affiliate_Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Directorist_Affiliate_Plugin $plugin Plugin instance.
	 */
	public function __construct( Directorist_Affiliate_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'user_register', array( $this, 'track_user_registration' ), 20 );
		add_action( 'atbdp_user_registration_completed', array( $this, 'track_user_registration' ), 20 );

		// Directorist writes _user_type AFTER firing its registration hook, and
		// email verification happens on a later request entirely. Both write
		// user meta, so re-evaluating on those writes is what makes the
		// user-type and credit-on-verification gates work. Referral creation
		// is deduplicated per user, so re-running is harmless.
		add_action( 'added_user_meta', array( $this, 'reevaluate_on_meta' ), 10, 3 );
		add_action( 'updated_user_meta', array( $this, 'reevaluate_on_meta' ), 10, 3 );
		add_action( 'deleted_user_meta', array( $this, 'reevaluate_on_meta' ), 10, 3 );
		add_action( 'atbdp_after_created_listing', array( $this, 'track_listing_submission' ), 20 );
		add_action( 'transition_post_status', array( $this, 'track_listing_publish' ), 20, 3 );
		add_filter( 'directorist_dashboard_tabs', array( $this, 'add_dashboard_tab' ) );
	}

	/**
	 * Re-check a pending registration commission when the meta it depends on changes.
	 *
	 * @param int|int[] $meta_id Meta ID(s) — unused, signature varies by hook.
	 * @param int       $user_id User ID.
	 * @param string    $meta_key Meta key.
	 *
	 * @return void
	 */
	public function reevaluate_on_meta( $meta_id, $user_id, $meta_key ): void {
		if ( ! in_array( $meta_key, array( '_user_type', 'directorist_user_email_unverified' ), true ) ) {
			return;
		}

		$this->track_user_registration( (int) $user_id );
	}

	/**
	 * Whether this user's Directorist type is one the admin pays for.
	 *
	 * Directorist stores `author`, `general` or `guest`; an unset value during
	 * registration is treated as not-yet-known and blocks crediting, because
	 * the meta write that follows will trigger a re-check.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	private function user_type_allowed( int $user_id ): bool {
		$allowed = (array) $this->plugin->settings->get( 'registration_user_types', array( 'author', 'general' ) );
		$type    = (string) get_user_meta( $user_id, '_user_type', true );

		// Both types allowed: no need to wait for the meta to appear.
		if ( count( array_intersect( array( 'author', 'general' ), $allowed ) ) === 2 ) {
			return true;
		}

		return in_array( $type, $allowed, true );
	}

	/**
	 * Whether the commission may be credited yet, given the credit-on setting.
	 *
	 * With "verification" selected, the commission waits until Directorist
	 * removes its unverified flag. If verification is switched off site-wide
	 * that flag never appears, so the setting falls back to crediting on
	 * registration rather than never paying at all.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	private function registration_credit_due( int $user_id ): bool {
		if ( 'verification' !== $this->plugin->settings->get( 'registration_credit_on', 'registration' ) ) {
			return true;
		}

		if ( function_exists( 'directorist_is_email_verification_enabled' ) && ! directorist_is_email_verification_enabled() ) {
			return true;
		}

		return ! get_user_meta( $user_id, 'directorist_user_email_unverified', true );
	}

	/**
	 * Create referral when a referred visitor registers.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public function track_user_registration( int $user_id ): void {
		$affiliate_id = $this->get_affiliate_id_for_user( $user_id );

		if ( ! $affiliate_id ) {
			return;
		}

		$affiliate = $this->plugin->affiliate->get( $affiliate_id );

		if ( ! $affiliate || 'approved' !== $affiliate->status ) {
			return;
		}

		if ( ! empty( $affiliate->user_id ) && (int) $affiliate->user_id === $user_id ) {
			return;
		}

		// Always persist the referred-user ↔ affiliate mapping, even when the
		// registration commission event is disabled: later conversions (listing,
		// plan, featured) and admin-side order updates attribute through it.
		update_user_meta( $user_id, '_directorist_affiliate_id', $affiliate_id );

		$visit_id = $this->plugin->tracking->get_cookie_visit_id();

		if ( $visit_id ) {
			update_user_meta( $user_id, '_directorist_affiliate_visit_id', $visit_id );
		}

		$amount = $this->plugin->commission->registration_amount();

		if ( null === $amount ) {
			return;
		}

		// Only the user types the admin pays for, and only once the chosen
		// credit point is reached.
		if ( ! $this->user_type_allowed( $user_id ) || ! $this->registration_credit_due( $user_id ) ) {
			return;
		}

		$referral_id = $this->plugin->referral->create(
			array(
				'affiliate_id'      => $affiliate_id,
				'referral_type'     => 'user_registration',
				'referred_user_id'  => $user_id,
				'commission_amount' => $amount,
				'status'            => $this->plugin->commission->default_referral_status(),
				'notes'             => __( 'Created from referral cookie during user registration.', 'directorist-affiliate' ),
			)
		);

		if ( ! $referral_id ) {
			return;
		}

		$this->plugin->tracking->mark_converted( $this->plugin->tracking->get_cookie_visit_id(), $user_id );

		$referral = $this->plugin->referral->get( $referral_id );

		if ( $referral ) {
			$this->plugin->email->referral_created( $affiliate, $referral );
		}
	}

	/**
	 * Create listing referral when trigger is submission.
	 *
	 * @param int $listing_id Listing ID.
	 *
	 * @return void
	 */
	public function track_listing_submission( int $listing_id ): void {
		$this->create_listing_referral( $listing_id, 'submission' );
	}

	/**
	 * Create listing referral when trigger is publish.
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post Post.
	 *
	 * @return void
	 */
	public function track_listing_publish( string $new_status, string $old_status, WP_Post $post ): void {
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		if ( defined( 'ATBDP_POST_TYPE' ) && ATBDP_POST_TYPE !== $post->post_type ) {
			return;
		}

		$this->create_listing_referral( (int) $post->ID, 'publish' );
	}

	/**
	 * Add affiliate tab to Directorist user dashboard.
	 *
	 * @param array<string,array<string,mixed>> $tabs Dashboard tabs.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function add_dashboard_tab( array $tabs ): array {
		$tabs['dashboard_directorist_affiliate'] = array(
			'title'   => __( 'Affiliate', 'directorist-affiliate' ),
			'content' => $this->plugin->shortcodes->dashboard_shortcode(),
			'icon'    => 'las la-handshake',
		);

		return $tabs;
	}

	/**
	 * Create listing referral.
	 *
	 * @param int    $listing_id Listing ID.
	 * @param string $trigger Trigger.
	 *
	 * @return void
	 */
	private function create_listing_referral( int $listing_id, string $trigger ): void {
		if ( defined( 'ATBDP_POST_TYPE' ) && ATBDP_POST_TYPE !== get_post_type( $listing_id ) ) {
			return;
		}

		if ( ! $this->directory_type_allowed( $listing_id ) ) {
			return;
		}

		$amount = $this->plugin->commission->listing_amount( $trigger );

		if ( null === $amount ) {
			return;
		}

		$user_id      = (int) get_post_field( 'post_author', $listing_id );
		$affiliate_id = (int) get_user_meta( $user_id, '_directorist_affiliate_id', true );

		// The tracking cookie belongs to the current browser session. Only fall
		// back to it when that session is the listing author's own — a moderator
		// publishing the listing must never attribute through their own cookie.
		if ( ! $affiliate_id && get_current_user_id() === $user_id ) {
			$affiliate_id = $this->plugin->tracking->get_cookie_affiliate_id();
		}

		if ( ! $affiliate_id ) {
			return;
		}

		$affiliate = $this->plugin->affiliate->get( $affiliate_id );

		if ( ! $affiliate || 'approved' !== $affiliate->status ) {
			return;
		}

		if ( ! empty( $affiliate->user_id ) && (int) $affiliate->user_id === $user_id ) {
			return;
		}

		// Keep first-click semantics: persist the mapping only when none exists yet.
		add_user_meta( $user_id, '_directorist_affiliate_id', $affiliate_id, true );

		$cookie_visit_id = $this->plugin->tracking->get_cookie_visit_id();

		if ( $cookie_visit_id ) {
			add_user_meta( $user_id, '_directorist_affiliate_visit_id', $cookie_visit_id, true );
		}

		$referral_id = $this->plugin->referral->create(
			array(
				'affiliate_id'      => $affiliate_id,
				'referral_type'     => 'listing_submission',
				'referred_user_id'  => $user_id,
				'listing_id'        => $listing_id,
				'commission_amount' => $amount,
				'status'            => $this->plugin->commission->default_referral_status(),
				'notes'             => sprintf(
					/* translators: %s: commission trigger. */
					__( 'Created from Directorist listing %s trigger.', 'directorist-affiliate' ),
					$trigger
				),
			)
		);

		if ( ! $referral_id ) {
			return;
		}

		$visit_id = (int) get_user_meta( $user_id, '_directorist_affiliate_visit_id', true );
		$visit_id = $visit_id ? $visit_id : $this->plugin->tracking->get_cookie_visit_id();

		$this->plugin->tracking->mark_converted( $visit_id, $user_id, $listing_id );

		$referral = $this->plugin->referral->get( $referral_id );

		if ( $referral ) {
			$this->plugin->email->referral_created( $affiliate, $referral );
		}
	}

	/**
	 * Whether this listing sits in a directory type the admin pays for.
	 *
	 * An empty setting means every directory type qualifies, which is also
	 * what a single-directory site gets without configuring anything.
	 *
	 * @param int $listing_id Listing ID.
	 *
	 * @return bool
	 */
	private function directory_type_allowed( int $listing_id ): bool {
		$allowed = array_filter( array_map( 'absint', (array) $this->plugin->settings->get( 'listing_directory_types', array() ) ) );

		if ( ! $allowed ) {
			return true;
		}

		$directory = function_exists( 'directorist_get_listing_directory' )
			? (int) directorist_get_listing_directory( $listing_id )
			: (int) get_post_meta( $listing_id, '_directory_type', true );

		return in_array( $directory, $allowed, true );
	}

	/**
	 * Every directory type on the site, for the settings screen.
	 *
	 * @return array<int,string> Term ID => name.
	 */
	public static function directory_types(): array {
		$taxonomy = defined( 'ATBDP_TYPE' ) ? ATBDP_TYPE : 'atbdp_listing_types';

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$types = array();

		foreach ( $terms as $term ) {
			$types[ (int) $term->term_id ] = $term->name;
		}

		return $types;
	}

	/**
	 * Resolve affiliate ID from cookie, excluding self-referrals.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int
	 */
	private function get_affiliate_id_for_user( int $user_id ): int {
		// The mapping saved at registration is authoritative: crediting can
		// happen on a later request (an email-verification click, or an admin
		// marking the user verified) where the original cookie is absent or
		// belongs to somebody else entirely.
		$affiliate_id = absint( get_user_meta( $user_id, '_directorist_affiliate_id', true ) );

		if ( ! $affiliate_id && ( ! is_user_logged_in() || get_current_user_id() === $user_id ) ) {
			$affiliate_id = $this->plugin->tracking->get_cookie_affiliate_id();
		}

		if ( ! $affiliate_id ) {
			return 0;
		}

		$affiliate = $this->plugin->affiliate->get( $affiliate_id );

		if ( ! $affiliate || ! empty( $affiliate->user_id ) && (int) $affiliate->user_id === $user_id ) {
			return 0;
		}

		return $affiliate_id;
	}
}

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
		add_action( 'atbdp_after_created_listing', array( $this, 'track_listing_submission' ), 20 );
		add_action( 'transition_post_status', array( $this, 'track_listing_publish' ), 20, 3 );
		add_filter( 'directorist_dashboard_tabs', array( $this, 'add_dashboard_tab' ) );
	}

	/**
	 * Create referral when a referred visitor registers.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public function track_user_registration( int $user_id ): void {
		$amount = $this->plugin->commission->registration_amount();

		if ( null === $amount ) {
			return;
		}

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

		$referral_id = $this->plugin->referral->create(
			array(
				'affiliate_id'      => $affiliate_id,
				'referral_type'     => 'user_registration',
				'referred_user_id'  => $user_id,
				'commission_amount' => $amount,
				'status'            => 'pending',
				'notes'             => __( 'Created from referral cookie during user registration.', 'directorist-affiliate' ),
			)
		);

		if ( ! $referral_id ) {
			return;
		}

		update_user_meta( $user_id, '_directorist_affiliate_id', $affiliate_id );
		update_user_meta( $user_id, '_directorist_affiliate_visit_id', $this->plugin->tracking->get_cookie_visit_id() );
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

		$amount = $this->plugin->commission->listing_amount( $trigger );

		if ( null === $amount ) {
			return;
		}

		$user_id      = (int) get_post_field( 'post_author', $listing_id );
		$affiliate_id = (int) get_user_meta( $user_id, '_directorist_affiliate_id', true );

		if ( ! $affiliate_id ) {
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

		$referral_id = $this->plugin->referral->create(
			array(
				'affiliate_id'      => $affiliate_id,
				'referral_type'     => 'listing_submission',
				'referred_user_id'  => $user_id,
				'listing_id'        => $listing_id,
				'commission_amount' => $amount,
				'status'            => 'pending',
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
	 * Resolve affiliate ID from cookie, excluding self-referrals.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int
	 */
	private function get_affiliate_id_for_user( int $user_id ): int {
		$affiliate_id = $this->plugin->tracking->get_cookie_affiliate_id();

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

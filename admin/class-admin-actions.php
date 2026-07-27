<?php
/**
 * Admin action handlers.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Processes admin form submissions, moderation links, and exports.
 *
 * Screen rendering lives in Directorist_Affiliate_Admin.
 */
final class Directorist_Affiliate_Admin_Actions {
	/**
	 * Plugin instance.
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
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
	}

	/**
	 * Handle admin actions.
	 *
	 * @return void
	 */
	public function handle_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->handle_settings_save();
		$this->handle_add_affiliate();
		$this->handle_affiliate_action();
		$this->handle_referral_action();
		$this->handle_payout_post();
		$this->handle_export();
	}

	/**
	 * Handle manual affiliate creation from admin.
	 *
	 * @return void
	 */
	private function handle_add_affiliate(): void {
		if ( empty( $_POST['directorist_affiliate_add_affiliate'] ) ) {
			return;
		}

		check_admin_referer( 'directorist_affiliate_add_affiliate' );

		$name               = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email              = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$website            = isset( $_POST['website'] ) ? esc_url_raw( wp_unslash( $_POST['website'] ) ) : '';
		$promotional_method = isset( $_POST['promotional_method'] ) ? sanitize_text_field( wp_unslash( $_POST['promotional_method'] ) ) : '';
		$payout_email       = isset( $_POST['payout_email'] ) ? sanitize_email( wp_unslash( $_POST['payout_email'] ) ) : '';
		$application_note   = isset( $_POST['application_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['application_note'] ) ) : '';
		$status             = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'approved';

		if ( ! $name || ! is_email( $email ) || ! is_email( $payout_email ) || ! in_array( $status, $this->plugin->affiliate->statuses(), true ) ) {
			$this->redirect( 'directorist-affiliate-affiliates', array( 'directorist_affiliate_notice' => 'invalid_affiliate' ) );
		}

		$user = get_user_by( 'email', $email );

		if ( $user ) {
			$user_id = (int) $user->ID;
		} else {
			$user_id = $this->plugin->affiliate->register_user( $name, $email );

			if ( is_wp_error( $user_id ) ) {
				$this->redirect( 'directorist-affiliate-affiliates', array( 'directorist_affiliate_notice' => 'user_create_failed' ) );
			}
		}

		if ( $this->plugin->affiliate->get_by_user_id( $user_id ) ) {
			$this->redirect( 'directorist-affiliate-affiliates', array( 'directorist_affiliate_notice' => 'affiliate_exists' ) );
		}

		$affiliate_id = $this->plugin->affiliate->create(
			array(
				'user_id'            => $user_id,
				'status'             => $status,
				'payout_email'       => $payout_email,
				'website'            => $website,
				'promotional_method' => $promotional_method,
				'application_note'   => $application_note,
			)
		);

		if ( ! $affiliate_id ) {
			$this->redirect( 'directorist-affiliate-affiliates', array( 'directorist_affiliate_notice' => 'affiliate_create_failed' ) );
		}

		$affiliate = $this->plugin->affiliate->get( $affiliate_id );

		if ( $affiliate && in_array( $status, array( 'approved', 'rejected' ), true ) ) {
			$this->plugin->email->application_status( $affiliate, $status );
		}

		$this->redirect( 'directorist-affiliate-affiliates', array( 'directorist_affiliate_notice' => 'affiliate_created' ) );
	}

	/**
	 * Handle settings save.
	 *
	 * @return void
	 */
	private function handle_settings_save(): void {
		if ( empty( $_POST['directorist_affiliate_save_settings'] ) ) {
			return;
		}

		check_admin_referer( 'directorist_affiliate_save_settings' );
		$this->plugin->settings->save( $_POST );

		$this->redirect( 'directorist-affiliate-settings', array( 'updated' => '1' ) );
	}

	/**
	 * Handle affiliate moderation.
	 *
	 * @return void
	 */
	private function handle_affiliate_action(): void {
		if ( empty( $_GET['directorist_affiliate_action'] ) || empty( $_GET['affiliate_id'] ) ) {
			return;
		}

		$action       = sanitize_key( wp_unslash( $_GET['directorist_affiliate_action'] ) );
		$affiliate_id = absint( $_GET['affiliate_id'] );

		if ( ! in_array( $action, array( 'approve', 'reject', 'suspend' ), true ) ) {
			return;
		}

		check_admin_referer( 'directorist_affiliate_action_' . $affiliate_id );

		$status = 'approve' === $action ? 'approved' : ( 'reject' === $action ? 'rejected' : 'suspended' );

		if ( $this->plugin->affiliate->update_status( $affiliate_id, $status ) ) {
			$affiliate = $this->plugin->affiliate->get( $affiliate_id );

			if ( $affiliate && in_array( $status, array( 'approved', 'rejected' ), true ) ) {
				$this->plugin->email->application_status( $affiliate, $status );
			}
		}

		$this->redirect( 'directorist-affiliate-affiliates', array( 'directorist_affiliate_notice' => 'affiliate_updated' ) );
	}

	/**
	 * Handle referral actions.
	 *
	 * @return void
	 */
	private function handle_referral_action(): void {
		if ( empty( $_GET['directorist_referral_action'] ) || empty( $_GET['referral_id'] ) ) {
			return;
		}

		$action      = sanitize_key( wp_unslash( $_GET['directorist_referral_action'] ) );
		$referral_id = absint( $_GET['referral_id'] );

		if ( ! in_array( $action, array( 'approve', 'reject', 'paid' ), true ) ) {
			return;
		}

		check_admin_referer( 'directorist_referral_action_' . $referral_id );

		$notice = 'referral_updated';

		if ( 'paid' === $action ) {
			$referral = $this->plugin->referral->get( $referral_id );

			// Only approved referrals may be paid, so every payment leaves a payout record.
			if ( $referral && 'approved' === $referral->status ) {
				$affiliate = $this->plugin->affiliate->get( (int) $referral->affiliate_id );

				$this->plugin->payout->mark_paid(
					(int) $referral->affiliate_id,
					array( $referral_id ),
					$affiliate ? $affiliate->payout_email : '',
					__( 'Manual payout marked from referral list.', 'directorist-affiliate' )
				);
			} else {
				$notice = 'referral_not_approved';
			}
		} else {
			$status = 'approve' === $action ? 'approved' : 'rejected';
			$this->plugin->referral->update_status( $referral_id, $status );
		}

		$this->redirect( 'directorist-affiliate-referrals', array( 'directorist_affiliate_notice' => $notice ) );
	}

	/**
	 * Handle manual payout form.
	 *
	 * @return void
	 */
	private function handle_payout_post(): void {
		if ( empty( $_POST['directorist_affiliate_mark_paid'] ) ) {
			return;
		}

		check_admin_referer( 'directorist_affiliate_mark_paid' );

		$referral_ids = isset( $_POST['referral_ids'] ) && is_array( $_POST['referral_ids'] )
			? array_map( 'absint', wp_unslash( $_POST['referral_ids'] ) )
			: array();

		$grouped = array();
		$totals  = array();

		foreach ( $referral_ids as $referral_id ) {
			$referral = $this->plugin->referral->get( $referral_id );

			if ( ! $referral || 'approved' !== $referral->status ) {
				continue;
			}

			$affiliate_id = (int) $referral->affiliate_id;

			$grouped[ $affiliate_id ][] = $referral_id;
			$totals[ $affiliate_id ]    = ( $totals[ $affiliate_id ] ?? 0.0 ) + (float) $referral->commission_amount;
		}

		$minimum = (float) $this->plugin->settings->get( 'minimum_payout', '0.00' );
		$paid    = 0;
		$skipped = 0;

		foreach ( $grouped as $affiliate_id => $ids ) {
			if ( $minimum > 0 && $totals[ $affiliate_id ] < $minimum ) {
				$skipped++;
				continue;
			}

			$affiliate = $this->plugin->affiliate->get( (int) $affiliate_id );
			$payout_id = $this->plugin->payout->mark_paid(
				(int) $affiliate_id,
				$ids,
				$affiliate ? $affiliate->payout_email : '',
				__( 'Manual payout marked from admin.', 'directorist-affiliate' )
			);

			if ( $payout_id ) {
				$paid++;
			}
		}

		$this->redirect(
			'directorist-affiliate-payouts',
			array(
				'directorist_affiliate_paid'    => $paid,
				'directorist_affiliate_skipped' => $skipped,
			)
		);
	}

	/**
	 * Handle payout CSV export.
	 *
	 * @return void
	 */
	private function handle_export(): void {
		$export = isset( $_GET['directorist_affiliate_export'] ) ? sanitize_key( wp_unslash( $_GET['directorist_affiliate_export'] ) ) : '';

		if ( 'payouts' !== $export ) {
			return;
		}

		check_admin_referer( 'directorist_affiliate_export_payouts' );

		$referrals = $this->plugin->referral->list(
			array(
				'status' => 'approved',
				'limit'  => 1000,
			)
		);

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=directorist-affiliate-payouts.csv' );

		$output = fopen( 'php://output', 'w' );

		if ( false !== $output ) {
			fputcsv( $output, array( 'affiliate_id', 'payout_email', 'referral_id', 'amount', 'date_created' ) );

			foreach ( $referrals as $referral ) {
				$affiliate = $this->plugin->affiliate->get( (int) $referral->affiliate_id );
				fputcsv(
					$output,
					array(
						$referral->affiliate_id,
						$affiliate ? $affiliate->payout_email : '',
						$referral->id,
						$referral->commission_amount,
						$referral->date_created,
					)
				);
			}
		}

		exit;
	}

	/**
	 * Redirect to a plugin admin page with query args and stop execution.
	 *
	 * @param string               $page Admin page slug.
	 * @param array<string,string> $args Extra query args.
	 *
	 * @return void
	 */
	private function redirect( string $page, array $args = array() ): void {
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php?page=' . $page ) ) );
		exit;
	}
}

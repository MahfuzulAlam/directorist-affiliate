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
		// admin-ajax.php also fires admin_init; these no-JS fallbacks must not
		// intercept AJAX submissions (their redirect would corrupt the JSON response).
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->handle_settings_save();
		$this->handle_add_affiliate();
		$this->handle_affiliate_action();
		$this->handle_referral_action();
		$this->handle_referral_bulk();
		$this->handle_payout_post();
		$this->handle_export();
	}

	/**
	 * Handle manual affiliate creation from admin (no-JS fallback).
	 *
	 * @return void
	 */
	private function handle_add_affiliate(): void {
		if ( empty( $_POST['directorist_affiliate_add_affiliate'] ) ) {
			return;
		}

		check_admin_referer( 'directorist_affiliate_add_affiliate' );

		$result = $this->plugin->registration->process_admin( $_POST );

		$this->redirect( 'affiliates', array( 'directorist_affiliate_notice' => $result['notice'] ) );
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

		$this->redirect( 'settings', array( 'updated' => '1' ) );
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

		$this->redirect( 'affiliates', array( 'directorist_affiliate_notice' => 'affiliate_updated' ) );
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

		$this->redirect( 'referrals', array( 'directorist_affiliate_notice' => $notice ) );
	}

	/**
	 * Handle bulk referral moderation from the referrals tab.
	 *
	 * @return void
	 */
	private function handle_referral_bulk(): void {
		if ( empty( $_POST['directorist_affiliate_referral_bulk'] ) ) {
			return;
		}

		check_admin_referer( 'directorist_affiliate_referral_bulk' );

		$action       = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$referral_ids = isset( $_POST['referral_ids'] ) && is_array( $_POST['referral_ids'] )
			? array_filter( array_map( 'absint', wp_unslash( $_POST['referral_ids'] ) ) )
			: array();

		if ( empty( $referral_ids ) || ! in_array( $action, array( 'approve', 'reject', 'paid' ), true ) ) {
			$this->redirect( 'referrals', array( 'directorist_affiliate_notice' => 'referral_bulk_empty' ) );
		}

		if ( 'paid' === $action ) {
			$result = $this->plugin->payout->mark_paid_bulk(
				$referral_ids,
				(float) $this->plugin->settings->get( 'minimum_payout', '0.00' )
			);

			$this->redirect(
				'referrals',
				array(
					'directorist_affiliate_notice' => $result['paid'] ? 'referral_bulk_paid' : 'referral_not_approved',
				)
			);
		}

		$status = 'approve' === $action ? 'approved' : 'rejected';

		foreach ( $referral_ids as $referral_id ) {
			$this->plugin->referral->update_status( $referral_id, $status );
		}

		$this->redirect( 'referrals', array( 'directorist_affiliate_notice' => 'referral_bulk_updated' ) );
	}

	/**
	 * Handle manual payout form (no-JS fallback).
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

		$result = $this->plugin->payout->mark_paid_bulk(
			$referral_ids,
			(float) $this->plugin->settings->get( 'minimum_payout', '0.00' )
		);

		$this->redirect(
			'payouts',
			array(
				'directorist_affiliate_paid'    => $result['paid'],
				'directorist_affiliate_skipped' => $result['skipped'],
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

		$affiliates_map = $this->plugin->affiliate->get_many( wp_list_pluck( $referrals, 'affiliate_id' ) );
		$output         = fopen( 'php://output', 'w' );

		if ( false !== $output ) {
			fputcsv( $output, array( 'affiliate_id', 'payout_email', 'referral_id', 'amount', 'date_created' ) );

			foreach ( $referrals as $referral ) {
				$affiliate = $affiliates_map[ (int) $referral->affiliate_id ] ?? null;
				fputcsv(
					$output,
					array(
						$referral->affiliate_id,
						$this->escape_csv_field( $affiliate ? $affiliate->payout_email : '' ),
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
	 * Neutralize spreadsheet formula injection in a CSV cell.
	 *
	 * Cells starting with =, +, -, @, tab, or CR are prefixed with an
	 * apostrophe so spreadsheet apps treat them as text, never formulas.
	 *
	 * @param string $value Cell value.
	 *
	 * @return string
	 */
	private function escape_csv_field( string $value ): string {
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $value;
		}

		return $value;
	}

	/**
	 * Redirect to a tab of the Affiliate admin page and stop execution.
	 *
	 * @param string               $tab Tab slug.
	 * @param array<string,string> $args Extra query args.
	 *
	 * @return void
	 */
	private function redirect( string $tab, array $args = array() ): void {
		wp_safe_redirect( Directorist_Affiliate_Admin::page_url( $tab, $args ) );
		exit;
	}
}

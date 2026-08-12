<?php
/**
 * Payouts sub-tab navigation.
 *
 * Expects:
 *   $sections array<string,string>  Section slug => label.
 *   $section  string                Active section slug.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;
?>
<nav class="directorist-affiliate-subtab-nav is-visible" aria-label="<?php esc_attr_e( 'Payout sections', 'directorist-affiliate' ); ?>">
	<?php foreach ( $sections as $da_slug => $da_label ) : ?>
		<a
			class="directorist-affiliate-subtab-link<?php echo $da_slug === $section ? ' is-active' : ''; ?>"
			href="<?php echo esc_url( Directorist_Affiliate_Admin::page_url( 'payouts', array( 'section' => $da_slug ) ) ); ?>"
			<?php echo $da_slug === $section ? ' aria-current="page"' : ''; ?>
		>
			<?php echo esc_html( $da_label ); ?>
		</a>
	<?php endforeach; ?>
</nav>

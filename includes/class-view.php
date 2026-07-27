<?php
/**
 * View renderer.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads view templates with a scoped variable context.
 *
 * Templates are responsible for escaping their own output.
 */
final class Directorist_Affiliate_View {
	/**
	 * Include a view, printing its output directly.
	 *
	 * @param string              $view Plugin-relative view path, e.g. 'admin/views/dashboard.php'.
	 * @param array<string,mixed> $context Variables exposed to the template.
	 *
	 * @return void
	 */
	public static function output( string $view, array $context = array() ): void {
		$file = DIRECTORIST_AFFILIATE_DIR . $view;

		if ( ! file_exists( $file ) ) {
			return;
		}

		extract( $context, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		include $file;
	}

	/**
	 * Render a view and return its output as a string.
	 *
	 * @param string              $view Plugin-relative view path, e.g. 'public/views/registration-form.php'.
	 * @param array<string,mixed> $context Variables exposed to the template.
	 *
	 * @return string
	 */
	public static function render( string $view, array $context = array() ): string {
		ob_start();
		self::output( $view, $context );

		return (string) ob_get_clean();
	}
}

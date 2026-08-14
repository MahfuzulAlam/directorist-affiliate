<?php
/**
 * Shared list filter bar.
 *
 * A plain GET form, so filters survive pagination and are bookmarkable.
 * Expects:
 *   $tab      string  Tab slug the form submits back to.
 *   $filters  array   Raw selections from Directorist_Affiliate_Admin::request_filters().
 *   $fields   array   Extra controls: each item is
 *                     array{type:'select'|'search', name:string, label:string, options?:array, value:string, placeholder?:string}
 *   $count_label     string  Pre-translated result summary, e.g. "12 referrals".
 *                            Built by the caller so _n() sees literal strings.
 *   $affiliate_list  array   Optional affiliate ID => name for the affiliate filter.
 *   $section         string  Optional sub-tab to preserve.
 *   $lead_button     array   Optional primary action rendered before the filters:
 *                            array{modal:string, label:string}.
 *   $export          string  Optional dataset key ('affiliates', 'referrals',
 *                            'visits', 'payouts'). Renders an Export CSV link
 *                            carrying exactly the filters shown here.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

$da_range_presets = Directorist_Affiliate_Date_Range::presets();
$da_is_custom     = Directorist_Affiliate_Date_Range::CUSTOM === $filters['range'];
$da_has_filters   = ! empty( $filters['affiliate_id'] ) || ! empty( $filters['range'] );

foreach ( $fields as $da_field ) {
	if ( ! empty( $da_field['value'] ) ) {
		$da_has_filters = true;
		break;
	}
}

// The export link carries the same query vars this form would submit, so the
// download always matches the rows on screen rather than the whole table.
$da_export_carry = array();

if ( ! empty( $filters['affiliate_id'] ) ) {
	$da_export_carry['affiliate'] = (string) $filters['affiliate_id'];
}

foreach ( array(
	Directorist_Affiliate_Date_Range::PARAM_PRESET => 'range',
	Directorist_Affiliate_Date_Range::PARAM_FROM   => 'from',
	Directorist_Affiliate_Date_Range::PARAM_TO     => 'to',
) as $da_param => $da_key ) {
	if ( ! empty( $filters[ $da_key ] ) ) {
		$da_export_carry[ $da_param ] = (string) $filters[ $da_key ];
	}
}

foreach ( $fields as $da_field ) {
	if ( ! empty( $da_field['value'] ) ) {
		$da_export_carry[ $da_field['name'] ] = (string) $da_field['value'];
	}
}

if ( ! empty( $section ) ) {
	$da_export_carry['section'] = $section;
}
?>
<form method="get" class="directorist-affiliate-filter-bar" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
	<input type="hidden" name="post_type" value="<?php echo esc_attr( defined( 'ATBDP_POST_TYPE' ) ? ATBDP_POST_TYPE : 'at_biz_dir' ); ?>" />
	<input type="hidden" name="page" value="<?php echo esc_attr( Directorist_Affiliate_Admin::PAGE_SLUG ); ?>" />
	<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>" />
	<?php if ( ! empty( $section ) ) : ?>
		<input type="hidden" name="section" value="<?php echo esc_attr( $section ); ?>" />
	<?php endif; ?>

	<?php if ( ! empty( $lead_button ) ) : ?>
		<button type="button" class="directorist-affiliate-add-btn" data-da-modal-open="<?php echo esc_attr( $lead_button['modal'] ); ?>">
			<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
			<?php echo esc_html( $lead_button['label'] ); ?>
		</button>
	<?php endif; ?>

	<?php if ( ! empty( $affiliate_list ) ) : ?>
		<label class="screen-reader-text" for="da-filter-affiliate"><?php esc_html_e( 'Filter by affiliate', 'directorist-affiliate' ); ?></label>
		<select id="da-filter-affiliate" name="affiliate">
			<option value=""><?php esc_html_e( 'All affiliates', 'directorist-affiliate' ); ?></option>
			<?php foreach ( $affiliate_list as $da_affiliate_id => $da_affiliate_name ) : ?>
				<option value="<?php echo esc_attr( (string) $da_affiliate_id ); ?>" <?php selected( (int) $filters['affiliate_id'], (int) $da_affiliate_id ); ?>>
					<?php echo esc_html( $da_affiliate_name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	<?php endif; ?>

	<?php foreach ( $fields as $da_field ) : ?>
		<?php $da_field_id = 'da-filter-' . sanitize_html_class( $da_field['name'] ); ?>
		<label class="screen-reader-text" for="<?php echo esc_attr( $da_field_id ); ?>"><?php echo esc_html( $da_field['label'] ); ?></label>
		<?php if ( 'search' === $da_field['type'] ) : ?>
			<input
				id="<?php echo esc_attr( $da_field_id ); ?>"
				type="search"
				name="<?php echo esc_attr( $da_field['name'] ); ?>"
				value="<?php echo esc_attr( $da_field['value'] ); ?>"
				placeholder="<?php echo esc_attr( $da_field['placeholder'] ?? $da_field['label'] ); ?>"
			/>
		<?php else : ?>
			<select id="<?php echo esc_attr( $da_field_id ); ?>" name="<?php echo esc_attr( $da_field['name'] ); ?>">
				<?php foreach ( $da_field['options'] as $da_value => $da_label ) : ?>
					<option value="<?php echo esc_attr( (string) $da_value ); ?>" <?php selected( (string) $da_field['value'], (string) $da_value ); ?>>
						<?php echo esc_html( $da_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>
	<?php endforeach; ?>

	<div class="directorist-affiliate-daterange<?php echo $da_is_custom ? ' is-custom' : ''; ?>" data-da-daterange>
		<label class="screen-reader-text" for="da-filter-range"><?php esc_html_e( 'Filter by date', 'directorist-affiliate' ); ?></label>
		<select id="da-filter-range" name="<?php echo esc_attr( Directorist_Affiliate_Date_Range::PARAM_PRESET ); ?>" data-da-daterange-preset>
			<?php foreach ( $da_range_presets as $da_preset => $da_preset_label ) : ?>
				<option value="<?php echo esc_attr( $da_preset ); ?>" <?php selected( $filters['range'], $da_preset ); ?>>
					<?php echo esc_html( $da_preset_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<div class="directorist-affiliate-daterange-custom" data-da-daterange-custom<?php echo $da_is_custom ? '' : ' hidden'; ?>>
			<label class="screen-reader-text" for="da-filter-from"><?php esc_html_e( 'Start date', 'directorist-affiliate' ); ?></label>
			<input id="da-filter-from" type="date" name="<?php echo esc_attr( Directorist_Affiliate_Date_Range::PARAM_FROM ); ?>" value="<?php echo esc_attr( $filters['from'] ); ?>" max="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" />
			<span class="directorist-affiliate-daterange-sep" aria-hidden="true">→</span>
			<label class="screen-reader-text" for="da-filter-to"><?php esc_html_e( 'End date', 'directorist-affiliate' ); ?></label>
			<input id="da-filter-to" type="date" name="<?php echo esc_attr( Directorist_Affiliate_Date_Range::PARAM_TO ); ?>" value="<?php echo esc_attr( $filters['to'] ); ?>" max="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" />
		</div>
	</div>

	<button type="submit" class="button"><?php esc_html_e( 'Filter', 'directorist-affiliate' ); ?></button>

	<?php if ( $da_has_filters ) : ?>
		<a class="directorist-affiliate-filter-reset" href="<?php echo esc_url( Directorist_Affiliate_Admin::page_url( $tab, ! empty( $section ) ? array( 'section' => $section ) : array() ) ); ?>">
			<?php esc_html_e( 'Reset', 'directorist-affiliate' ); ?>
		</a>
	<?php endif; ?>

	<?php if ( ! empty( $export ) ) : ?>
		<a class="button directorist-affiliate-icon-btn" href="<?php echo esc_url( Directorist_Affiliate_Admin_Export::url( $export, $da_export_carry, $tab ) ); ?>">
			<span class="dashicons dashicons-download" aria-hidden="true"></span>
			<?php esc_html_e( 'Export CSV', 'directorist-affiliate' ); ?>
		</a>
	<?php endif; ?>

	<?php $da_range_label = Directorist_Affiliate_Date_Range::label( $filters['range'], $filters['from'], $filters['to'] ); ?>
	<span class="directorist-affiliate-filter-count">
		<?php echo esc_html( $count_label ); ?>
		<?php if ( $da_range_label ) : ?>
			<span class="directorist-affiliate-filter-range"><?php echo esc_html( $da_range_label ); ?></span>
		<?php endif; ?>
	</span>
</form>

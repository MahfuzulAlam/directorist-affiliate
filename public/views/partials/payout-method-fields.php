<?php
/**
 * Payout method picker and its per-method fields.
 *
 * Shared by the dashboard's payout settings form and the request modal, so
 * both stay in step with the method definitions.
 *
 * Expects:
 *   $payout_methods array  Enabled methods from Payout_Methods::enabled().
 *   $payout_method  string Currently selected method key.
 *   $payout_details array  Saved details.
 *   $id_prefix      string Unique prefix, since both forms sit on one page.
 *
 * @package DirectoristAffiliate
 */

defined( 'ABSPATH' ) || exit;

$da_selected = $payout_method && isset( $payout_methods[ $payout_method ] )
	? $payout_method
	: (string) array_key_first( $payout_methods );
?>
<div class="da-methods" data-da-methods>
	<div class="da-field">
		<label for="<?php echo esc_attr( $id_prefix ); ?>-method"><?php esc_html_e( 'How would you like to be paid?', 'directorist-affiliate' ); ?><span class="da-req" aria-hidden="true">*</span></label>
		<select id="<?php echo esc_attr( $id_prefix ); ?>-method" name="payout_method" data-da-method-select>
			<?php foreach ( $payout_methods as $da_key => $da_method ) : ?>
				<option value="<?php echo esc_attr( $da_key ); ?>" data-hint="<?php echo esc_attr( $da_method['hint'] ); ?>" <?php selected( $da_selected, $da_key ); ?>>
					<?php echo esc_html( $da_method['label'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<small class="da-hint" data-da-method-hint><?php echo esc_html( $payout_methods[ $da_selected ]['hint'] ?? '' ); ?></small>
	</div>

	<?php foreach ( $payout_methods as $da_key => $da_method ) : ?>
		<div class="da-method-fields" data-da-method-fields="<?php echo esc_attr( $da_key ); ?>" <?php echo $da_key === $da_selected ? '' : 'hidden'; ?>>
			<div class="da-grid">
				<?php foreach ( $da_method['fields'] as $da_field_key => $da_field ) : ?>
					<?php $da_field_id = $id_prefix . '-' . sanitize_html_class( $da_key . '-' . $da_field_key ); ?>
					<div class="da-field">
						<label for="<?php echo esc_attr( $da_field_id ); ?>">
							<?php echo esc_html( $da_field['label'] ); ?>
							<?php if ( ! empty( $da_field['required'] ) ) : ?>
								<span class="da-req" aria-hidden="true">*</span>
							<?php endif; ?>
						</label>
						<input
							id="<?php echo esc_attr( $da_field_id ); ?>"
							type="<?php echo esc_attr( 'email' === $da_field['type'] ? 'email' : ( 'tel' === $da_field['type'] ? 'tel' : 'text' ) ); ?>"
							name="payout_details[<?php echo esc_attr( $da_field_key ); ?>]"
							value="<?php echo esc_attr( $da_key === $payout_method && isset( $payout_details[ $da_field_key ] ) ? $payout_details[ $da_field_key ] : '' ); ?>"
							autocomplete="off"
							data-da-method-input="<?php echo esc_attr( $da_key ); ?>"
						/>
						<?php if ( ! empty( $da_field['hint'] ) ) : ?>
							<small class="da-hint"><?php echo esc_html( $da_field['hint'] ); ?></small>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>

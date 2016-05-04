<?php

class SiteOrigin_Widget_ContactForm_Field_Checkboxes extends SiteOrigin_Widget_ContactForm_Field_Base {

	public function render_field( $options ) {

		if ( ! empty( $options['field']['options'] ) ) {
			if ( empty( $options['value'] ) || ! is_array( $options['value'] ) ) {
				$options['value'] = array();
			}

			?><ul><?php
			foreach ( $options['field']['options'] as $i => $option ) {
				?><li>
				<label>
				<input type="checkbox" value="<?php echo esc_attr( $option['value'] ) ?>" name="<?php echo esc_attr( $options['field_name'] ) ?>[]" id="<?php echo esc_attr( $options['field_id'] ) . '-' . $i ?>"<?php echo checked( in_array( $option['value'], $options['value'] ), true, false ) ?>/>
				<?php echo esc_html( $option['value'] ); ?>
				</li>
			<?php } ?>
			</ul><?php
		}
	}
}

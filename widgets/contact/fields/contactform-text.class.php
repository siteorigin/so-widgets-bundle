<?php

class SiteOrigin_Widget_Field_ContactForm_Text extends SiteOrigin_Widget_Field_ContactForm_Base {

	public function render_field( $options ) {

		?>
		<input type="<?php echo $options['field']['type'] ?>" name="<?php echo esc_attr( $options['field_name'] ) ?>" id="<?php echo esc_attr( $options['field_id'] ) ?>" value="<?php echo esc_attr( $options['value'] ) ?>" class="sow-text-field"<?php echo ( $options['show_placeholder'] ? 'placeholder="' . esc_attr( $options['field']['label'] ) . '"' : '' ) ?>/>
		<?php
	}
}

<?php

/**
 * Class SiteOrigin_Widget_Field_Checkbox
 */
class SiteOrigin_Widget_Field_Checkbox extends SiteOrigin_Widget_Field_Base {

	protected function render_field( $value, $instance ) {
		?>
		<label for="<?php echo esc_attr( $this->element_id ) ?>" class="so-checkbox-label">
			<input type="checkbox" name="<?php echo esc_attr( $this->element_name ) ?>" id="<?php echo esc_attr( $this->element_id ) ?>"
			       class="siteorigin-widget-input" <?php checked( !empty( $value ) ) ?> />
			<?php echo esc_html( $this->label ) ?>
		</label>
		<?php
	}

	protected function render_field_label( $value, $instance ) {
		// Empty override. This field renders it's own label in the render_field() function.
	}

	protected function sanitize_field_input( $value, $instance ) {
		return ! empty( $value );
	}

}

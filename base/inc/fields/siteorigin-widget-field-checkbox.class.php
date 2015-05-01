<?php

/**
 * Class SiteOrigin_Widget_Field_Checkbox
 */
class SiteOrigin_Widget_Field_Checkbox extends SiteOrigin_Widget_Field {

	protected function render_field( $value ) {
		?>
		<label for="<?php echo $this->element_id ?>">
			<input type="checkbox" name="<?php echo $this->element_name ?>" id="<?php echo $this->element_id ?>"
			       class="siteorigin-widget-input" <?php checked( !empty( $value ) ) ?> />
			<?php echo $this->label ?>
		</label>
		<?php
	}

	protected function sanitize_field_input( $value ) {
		return ! empty( $value );
	}

}
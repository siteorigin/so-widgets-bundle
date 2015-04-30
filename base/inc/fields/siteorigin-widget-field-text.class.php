<?php

/**
 * Class SiteOrigin_Widget_Field_Text
 */
class SiteOrigin_Widget_Field_Text extends SiteOrigin_Widget_Field_Text_Input_Base {

	protected function render_field( $value ) {
		?><input type="text" name="<?php echo $this->element_name ?>" id="<?php echo $this->element_id ?>"
		         value="<?php echo esc_attr( $value ) ?>" class="widefat siteorigin-widget-input"
		<?php if ( ! empty( $this->placeholder ) ) echo 'placeholder="' . $this->placeholder . '"' ?>
		<?php if( ! empty( $this->readonly ) ) echo 'readonly' ?> /><?php
	}
}
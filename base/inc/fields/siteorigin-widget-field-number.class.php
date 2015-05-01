<?php

/**
 * Class SiteOrigin_Widget_Field_Number
 */
class SiteOrigin_Widget_Field_Number extends SiteOrigin_Widget_Field_Text_Input_Base {

	protected function render_field( $value ) {
		$this->input_classes[] = 'siteorigin-widget-input-number';
		$this->render_text_input( $value );
	}

	protected function sanitize_field_input( $value ) {
		return (float) $value;
	}
}
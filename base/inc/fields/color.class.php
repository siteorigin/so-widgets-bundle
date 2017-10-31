<?php

/**
 * Class SiteOrigin_Widget_Field_Color
 */
class SiteOrigin_Widget_Field_Color extends SiteOrigin_Widget_Field_Text_Input_Base {

	protected function get_input_classes() {
		$input_classes = parent::get_input_classes();
		$input_classes[] = 'siteorigin-widget-input-color';
		return $input_classes;
	}

	protected function get_input_data_attributes() {
		$data_attributes = parent::get_input_data_attributes();
		if ( ! empty( $this->default ) ) {
			$data_attributes['default-color'] = $this->default;
		}
		return $data_attributes;
	}

	protected function sanitize_field_input( $value, $instance ) {
		$sanitized_value = $value;
		if( ! preg_match('|^#|', $sanitized_value) ) {
			$sanitized_value = '#' . $sanitized_value;
		}
		if ( ! preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $sanitized_value ) ){
			// 3 or 6 hex digits, or the empty string.
			$sanitized_value = false;
		}
		return $sanitized_value;
	}
}
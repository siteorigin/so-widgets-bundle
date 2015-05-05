<?php

/**
 * Class SiteOrigin_Widget_Field_Color
 */
class SiteOrigin_Widget_Field_Color extends SiteOrigin_Widget_Field_Text_Input_Base {
	public function __construct( $base_name, $element_id, $element_name, $field_options ) {
		parent::__construct( $base_name, $element_id, $element_name, $field_options );

		$this->input_classes[] = 'siteorigin-widget-input-color';
	}

	protected function sanitize_field_input( $value ) {
		$sanitized_value = $value;
		if ( !preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $sanitized_value ) ){
			// 3 or 6 hex digits, or the empty string.
			$sanitized_value = false;
		}
		return $sanitized_value;
	}
}
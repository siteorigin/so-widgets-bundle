<?php

/**
 * Class SiteOrigin_Widget_Field_Number
 */
class SiteOrigin_Widget_Field_Number extends SiteOrigin_Widget_Field_Text_Input_Base {
	public function __construct( $base_name, $element_id, $element_name, $options ) {
		parent::__construct( $base_name, $element_id, $element_name, $options );

		$this->input_classes[] = 'siteorigin-widget-input-number';
	}

	protected function sanitize_field_input( $value ) {
		return (float) $value;
	}
}
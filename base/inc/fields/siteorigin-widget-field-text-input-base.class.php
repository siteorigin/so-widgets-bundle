<?php

/**
 * Class SiteOrigin_Widget_Field_Text
 */
abstract class SiteOrigin_Widget_Field_Text_Input_Base extends SiteOrigin_Widget_Field {

	protected $placeholder;

	protected $readonly;

	public function __construct( $field_name, $element_id, $element_name, $field_options) {
		parent::__construct( $field_name, $element_id, $element_name, $field_options );

		if( isset( $field_options['placeholder'] ) ) $this->placeholder = $field_options['placeholder'];
		if( isset( $field_options['readonly'] ) ) $this->readonly = $field_options['readonly'];
	}

	protected function sanitize_field_input( $value ) {
		$sanitized_value = wp_kses_post( $value );
		$sanitized_value = balanceTags( $sanitized_value , true );
		return $sanitized_value;
	}
}
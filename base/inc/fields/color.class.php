<?php

/**
 * Class SiteOrigin_Widget_Field_Color
 */
class SiteOrigin_Widget_Field_Color extends SiteOrigin_Widget_Field_Text_Input_Base {

	/**
	 * An optional array containing the color hexes to be used as the palette. 
	 * If set to false, no color palettes will be output.
	 *
	 * @access protected
	 * @var array|bool
	 */
	protected $palettes;

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

		// Allow developers to add custom colors using a filter, and field options.
		$this->palettes = array_merge(
			apply_filters( 'siteorigin_widget_color_palette', array() ),
			! empty( $this->palettes ) ? $this->palettes : array()
		);

		if ( ! empty( $this->palettes ) ) {
			if ( ! empty( $this->palettes ) && is_array( $this->palettes ) ) {
				$valid_palette = array();
				$valid_palette = array_filter( $this->palettes, 'sanitize_hex_color' );
				if ( ! empty( $valid_palette ) ) {
					$data_attributes['palettes'] = wp_json_encode( $valid_palette );
				}
			} else {
				$data_attributes['palettes'] = $this->palettes;
			}
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

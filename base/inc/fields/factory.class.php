<?php

/**
 * Class SiteOrigin_Widget_Field_Factory
 */
class SiteOrigin_Widget_Field_Factory {
	public static function single(){
		static $single;

		if( empty( $single ) ) {
			$single = new self();
		}

		return $single;
	}

	public function create_field( $field_name, $field_options, SiteOrigin_Widget $for_widget, $for_repeater = array(), $is_template = false ) {
		$element_id = $for_widget->so_get_field_id( $field_name, $for_repeater, $is_template );
		$element_name = $for_widget->so_get_field_name( $field_name, $for_repeater );
		if ( empty( $field_options['type'] ) ) {
			$field_options['type'] = 'text';
			$field_options['label'] = __( 'This field does not have a type. Please specify a type for it to be rendered correctly.', 'so-widgets-bundle' );
		}
		$field_class = $this->get_field_class_name( $field_options['type'] );

		// If we still don't have a class use the 'SiteOrigin_Widget_Field_Error' class to indicate this to the user.
		if( ! class_exists( $field_class ) ) {
			return new SiteOrigin_Widget_Field_Error('', '', '',
				array(
					'type' => 'error',
					'message' => 'The class \'' . $field_class . '\' could not be found. Please make sure you specified the correct field type and that the class exists.'
				)
			);
		}

		return new $field_class( $field_name, $element_id, $element_name, $field_options, $for_widget, $for_repeater );
	}

	private function get_field_class_name( $field_type ) {
		$field_class_type = implode( '_', array_map( 'ucfirst', explode( '-', $field_type ) ) );
		$class_prefixes = $this->get_class_prefixes();
		$class_found = false;
		$field_class = '';
		foreach( $class_prefixes as $class_prefix ) {
			$field_class = $class_prefix . $field_class_type;
			if ( class_exists( $field_class ) ) {
				$class_found = true;
				break;
			}
		}
		// If we can't find the custom class, attempt fall back to the 'SiteOrigin_Widget_Field_' prefix.
		if ( ! $class_found ) {
			$field_class = 'SiteOrigin_Widget_Field_' . $field_class_type;
		}
		return $field_class;
	}

	private function get_class_prefixes() {
		return apply_filters( 'siteorigin_widgets_field_class_prefixes', array( 'SiteOrigin_Widget_Field_' ) );
	}
}

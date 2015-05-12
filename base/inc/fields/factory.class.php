<?php

/**
 * Class SiteOrigin_Widget_Field_Factory
 */
class SiteOrigin_Widget_Field_Factory {

	public static function create_field( $field_name, $field_options, SiteOrigin_Widget $for_widget, $for_repeater = array(), $is_template = false ) {
		$element_id = $for_widget->so_get_field_id( $field_name, $for_repeater, $is_template );
		$element_name = $for_widget->so_get_field_name( $field_name, $for_repeater );
		$field_class = SiteOrigin_Widget_Field_Factory::get_field_class_name( $field_options['type'] );

		if( SiteOrigin_Widget_Field_Factory::is_container_type( $field_options['type'] ) ) {
			return new $field_class( $field_name, $element_id, $element_name, $field_options, $for_widget, $for_repeater );
		}
		else {
			return new $field_class( $field_name, $element_id, $element_name, $field_options );
		}
	}

	private static function get_field_class_name( $field_type ) {
		$field_class_type = implode( '_', array_map( 'ucfirst', explode( '-', $field_type ) ) );
		$class_prefixes = SiteOrigin_Widget_Field_Factory::get_class_prefixes();
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

	private static function get_class_prefixes() {
		return apply_filters( 'siteorigin_widgets_field_class_prefixes', array( 'SiteOrigin_Widget_Field_' ) );
	}

	private static function get_container_types() {
		return apply_filters( 'siteorigin_widgets_field_container_types', array( 'section', 'widget', 'repeater', 'media' ) );
	}

	private static function is_container_type( $type ) {
		$container_types = SiteOrigin_Widget_Field_Factory::get_container_types();
		return in_array( $type, $container_types );
	}
}
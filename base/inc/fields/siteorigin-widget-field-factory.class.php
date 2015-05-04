<?php

/**
 * Class SiteOrigin_Widget_Field_Factory
 */
class SiteOrigin_Widget_Field_Factory {

	public static function create_field( $field_name, $field_options, SiteOrigin_Widget $for_widget, $for_repeater = array(), $is_template = false ) {
		$element_id = $for_widget->so_get_field_id( $field_name, $for_repeater, $is_template );
		$element_name = $for_widget->so_get_field_name( $field_name, $for_repeater );
		$field_class = 'SiteOrigin_Widget_Field_' . $field_options['type'];
		switch( $field_options['type'] ) {
			case 'repeater':
			case 'media':
				return new $field_class( $field_name, $element_id, $element_name, $field_options, $for_widget, $for_repeater );
				break;
			default:
				return new $field_class( $field_name, $element_id, $element_name, $field_options );
		}
	}
}
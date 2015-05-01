<?php

/**
 * Class SiteOrigin_Widget_Field_Factory
 */
class SiteOrigin_Widget_Field_Factory {

	public static function create_field( $field_name, $field_options, SiteOrigin_Widget $for_widget, $for_repeater = array(), $is_template = false ) {
		$element_id = $for_widget->so_get_field_id( $field_name, $for_repeater, $is_template );
		$element_name = $for_widget->so_get_field_name( $field_name, $for_repeater );
		switch ( $field_options['type'] ) {
			case SiteOrigin_Widget_Field::TYPE_TEXT:
				return new SiteOrigin_Widget_Field_Text( $field_name, $element_id, $element_name, $field_options );
				break;
			case SiteOrigin_Widget_Field::TYPE_LINK:
				return new SiteOrigin_Widget_Field_Link( $field_name, $element_id, $element_name, $field_options );
				break;
			case SiteOrigin_Widget_Field::TYPE_COLOR:
				return new SiteOrigin_Widget_Field_Color( $field_name, $element_id, $element_name, $field_options );
				break;
			case SiteOrigin_Widget_Field::TYPE_NUMBER:
				return new SiteOrigin_Widget_Field_Number( $field_name, $element_id, $element_name, $field_options );
				break;
			case SiteOrigin_Widget_Field::TYPE_REPEATER:
				return new SiteOrigin_Widget_Field_Repeater( $field_name, $element_id, $element_name, $field_options, $for_widget, $for_repeater );
				break;
		}
		return null;
	}
}
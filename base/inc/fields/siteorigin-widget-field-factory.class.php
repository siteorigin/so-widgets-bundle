<?php

/**
 * Class SiteOrigin_Widget_Field_Factory
 */
class SiteOrigin_Widget_Field_Factory {

	public static function create_field( $field_name, $element_id, $element_name, $field_options ) {
		switch ( $field_options['type'] ) {
			case SiteOrigin_Widget_Field::TYPE_TEXT:
				return new SiteOrigin_Widget_Field_Text( $field_name, $element_id, $element_name, $field_options );
				break;
		}
	}
}
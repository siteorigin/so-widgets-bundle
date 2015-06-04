<?php

function get_field_render_output( $field, $value = '' ) {
	ob_start();
	/* @var $field SiteOrigin_Widget_Field_Base */
	$field->render( $value );
	return ob_get_clean();
}
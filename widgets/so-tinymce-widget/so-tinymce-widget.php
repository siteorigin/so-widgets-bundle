<?php

/*
Widget Name: TinyMCE Widget
Description: A widget which allows editing of content using the TinyMCE editor.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

class SiteOrigin_Widget_TinyMCE_Widget extends SiteOrigin_Widget {

	function __construct() {

		parent::__construct(
			'sow-tinymce',
			__('SiteOrigin TinyMCE Widget', 'siteorigin-widgets'),
			array(
				'description' => __('A TinyMCE Widget.', 'siteorigin-widgets'),
			),
			array(),
			array(
				'text' => array(
					'type' => 'tinymce',
					'label' => __( 'TinyMCE input', 'siteorigin-widgets' ),
					'editor_height' => 200
				),
			),
			plugin_dir_path(__FILE__)
		);
	}

	public function get_template_variables( $instance, $args ) {
		return array(
			'text' => $instance['text']
		);
	}


	function get_template_name($instance) {
		return 'tinymce';
	}

	function get_style_name($instance) {
		return '';
	}
}

siteorigin_widget_register( 'tinymce', __FILE__ );
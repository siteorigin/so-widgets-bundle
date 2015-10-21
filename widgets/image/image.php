<?php
/*
Widget Name: Image
Description: A very simple image widget.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

class SiteOrigin_Widget_Image_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-image',
			__('SiteOrigin Image', 'so-widgets-bundle'),
			array(
				'description' => __('A simple image widget with massive power.', 'so-widgets-bundle'),
				'help' => 'https://siteorigin.com/widgets-bundle/image-widget-documentation/'
			),
			array(

			),
			array(
				'image' => array(
					'type' => 'media',
					'label' => __('Image file', 'so-widgets-bundle'),
					'library' => 'image',
					'fallback' => true,
				),

				'size' => array(
					'type' => 'select',
					'label' => __('Image size', 'so-widgets-bundle'),
					'options' => array(
						'full' => __('Full', 'so-widgets-bundle'),
						'large' => __('Large', 'so-widgets-bundle'),
						'medium' => __('Medium', 'so-widgets-bundle'),
						'thumb' => __('Thumbnail', 'so-widgets-bundle'),
					),
				),

				'title' => array(
					'type' => 'text',
					'label' => __('Title text', 'so-widgets-bundle'),
				),

				'alt' => array(
					'type' => 'text',
					'label' => __('Alt text', 'so-widgets-bundle'),
				),

				'url' => array(
					'type' => 'link',
					'label' => __('Destination URL', 'so-widgets-bundle'),
				),
				'new_window' => array(
					'type' => 'checkbox',
					'default' => false,
					'label' => __('Open in new window', 'so-widgets-bundle'),
				),

				'bound' => array(
					'type' => 'checkbox',
					'default' => true,
					'label' => __('Bound', 'so-widgets-bundle'),
					'description' => __("Make sure the image doesn't extend beyond its container.", 'so-widgets-bundle'),
				),
				'full_width' => array(
					'type' => 'checkbox',
					'default' => false,
					'label' => __('Full Width', 'so-widgets-bundle'),
					'description' => __("Resize image to fit its container.", 'so-widgets-bundle'),
				),

			),
			plugin_dir_path(__FILE__).'../'
		);
	}

	function modify_form($form){
		global $_wp_additional_image_sizes;
		if( !empty($_wp_additional_image_sizes) ) {
			foreach($_wp_additional_image_sizes as $i => $s) {
				$form['size']['options'][$i] = $i;
			}
		}

		return $form;
	}

	function get_style_hash($instance) {
		return substr( md5( serialize( $this->get_less_variables( $instance ) ) ), 0, 12 );
	}

	function get_template_name($instance) {
		return 'base';
	}

	function get_style_name($instance) {
		return false;
	}

	function get_less_variables($instance){
		return array();
	}
}

siteorigin_widget_register('sow-image', __FILE__, 'SiteOrigin_Widget_Image_Widget');
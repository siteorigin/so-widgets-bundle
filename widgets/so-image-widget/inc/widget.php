<?php

class SiteOrigin_Widget_Image_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-image',
			__('SiteOrigin Image', 'siteorigin-widgets'),
			array(
				'description' => __('A simple image widget with massive power.', 'siteorigin-widgets'),
				'help' => 'http://siteorigin.com/widgets-bundle/image-widget-documentation/'
			),
			array(

			),
			array(
				'image' => array(
					'type' => 'media',
					'label' => __('Image File', 'siteorigin-widgets'),
				),

				'size' => array(
					'type' => 'select',
					'label' => __('Image Size', 'siteorigin-widgets'),
					'options' => array(
						'full' => __('Full', 'siteorigin-widgets'),
						'large' => __('Large', 'siteorigin-widgets'),
						'medium' => __('Medium', 'siteorigin-widgets'),
						'thumb' => __('Thumbnail', 'siteorigin-widgets'),
					),
				),

				'title' => array(
					'type' => 'text',
					'label' => __('Title Text', 'siteorigin-widgets'),
				),

				'alt' => array(
					'type' => 'text',
					'label' => __('Alt Text', 'siteorigin-widgets'),
				),

				'url' => array(
					'type' => 'text',
					'label' => __('Destination URL', 'siteorigin-widgets'),
				),
				'new_window' => array(
					'type' => 'checkbox',
					'default' => false,
					'label' => __('Open in New Window', 'siteorigin-widgets'),
				),

				'bound' => array(
					'type' => 'checkbox',
					'default' => true,
					'label' => __('Bound', 'siteorigin-widgets'),
					'description' => __("Make sure the image doesn't extend beyond its container.", 'siteorigin-widgets'),
				),

			),
			plugin_dir_path(__FILE__).'../'
		);
	}

	function modify_form($form){
		global $_wp_additional_image_sizes;
		foreach($_wp_additional_image_sizes as $i => $s) {
			$form['size']['options'][$i] = $i;
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
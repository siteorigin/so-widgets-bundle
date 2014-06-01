<?php

class SiteOrigin_Widget_Image_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-image',
			__('SiteOrigin Image', 'sow-image'),
			array(
				'description' => __('A simple image widget with massive power.', 'sow-image'),
				'help' => 'http://siteorigin.com/image-widget-documentation/'
			),
			array(

			),
			array(
				'image' => array(
					'type' => 'media',
					'label' => __('Image File', 'sow-image'),
				),

				'size' => array(
					'type' => 'select',
					'label' => __('Image Size', 'sow-image'),
					'options' => array(
						'full' => __('Full', 'sow-image'),
						'large' => __('Large', 'sow-image'),
						'medium' => __('Medium', 'sow-image'),
						'thumb' => __('Thumbnail', 'sow-image'),
					),
				),

				'title' => array(
					'type' => 'text',
					'label' => __('Title Text', 'sow-image'),
				),

				'alt' => array(
					'type' => 'text',
					'label' => __('Alt Text', 'sow-image'),
				),

				'url' => array(
					'type' => 'text',
					'label' => __('Destination URL', 'sow-image'),
				),
				'new_window' => array(
					'type' => 'checkbox',
					'default' => false,
					'label' => __('Open in New Window', 'sow-image'),
				),

				'bound' => array(
					'type' => 'checkbox',
					'default' => true,
					'label' => __('Bound', 'sow-image'),
					'description' => __("Make sure the image doesn't extend beyond its container.", 'sow-image'),
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
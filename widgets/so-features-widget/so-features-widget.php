<?php
/*
Widget Name: Features widget
Description: Displays a block of features with icons.
Author: Greg Priday
Author URI: http://siteorigin.com
*/

class SiteOrigin_Widget_Features_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-features',
			__( 'SiteOrigin Features', 'siteorigin-widgets' ),
			array(
				'description' => __( 'Displays a list of features.', 'siteorigin-widgets' ),
				'help'        => 'http://siteorigin.com/widgets-bundle/features-widget-documentation/'
			),
			array(),
			array(
				'features' => array(
					'type' => 'repeater',
					'label' => __('Features', 'siteorigin-widgets'),
					'item_name' => __('Feature', 'siteorigin-widgets'),
					'item_label' => array(
						'selector' => "[id*='features-title']",
						'update_event' => 'change',
						'value_method' => 'val'
					),
					'fields' => array(

						// The container shape

						'container_color' => array(
							'type' => 'color',
							'label' => __('Container color', 'siteorigin-widgets'),
							'default' => '#404040',
						),

						// The Icon

						'icon' => array(
							'type' => 'icon',
							'label' => __('Icon', 'siteorigin-widgets'),
						),

						'icon_color' => array(
							'type' => 'color',
							'label' => __('Icon color', 'siteorigin-widgets'),
							'default' => '#FFFFFF',
						),

						'icon_image' => array(
							'type' => 'media',
							'library' => 'image',
							'label' => __('Icon image', 'siteorigin-widgets'),
							'description' => __('Use your own icon image.', 'siteorigin-widgets'),
						),

						// The text under the icon

						'title' => array(
							'type' => 'text',
							'label' => __('Title text', 'siteorigin-widgets'),
						),

						'text' => array(
							'type' => 'text',
							'label' => __('Text', 'siteorigin-widgets')
						),

						'more_text' => array(
							'type' => 'text',
							'label' => __('More link text', 'siteorigin-widgets'),
						),

						'more_url' => array(
							'type' => 'link',
							'label' => __('More link URL', 'siteorigin-widgets'),
						),
					),
				),

				'container_shape' => array(
					'type' => 'select',
					'label' => __('Container shape', 'siteorigin-widgets'),
					'options' => array(
					),
				),

				'container_size' => array(
					'type' => 'number',
					'label' => __('Container size', 'siteorigin-widgets'),
					'default' => 84,
				),

				'icon_size' => array(
					'type' => 'number',
					'label' => __('Icon size', 'siteorigin-widgets'),
					'default' => 24,
				),

				'per_row' => array(
					'type' => 'number',
					'label' => __('Features per row', 'siteorigin-widgets'),
					'default' => 3,
				),

				'responsive' => array(
					'type' => 'checkbox',
					'label' => __('Responsive layout', 'siteorigin-widgets'),
					'default' => true,
				),

				'title_link' => array(
					'type' => 'checkbox',
					'label' => __('Link feature title to more URL', 'siteorigin-widgets'),
					'default' => false,
				),

				'icon_link' => array(
					'type' => 'checkbox',
					'label' => __('Link icon to more URL', 'siteorigin-widgets'),
					'default' => false,
				),

				'new_window' => array(
					'type' => 'checkbox',
					'label' => __('Open more URL in a new window', 'siteorigin-widgets'),
					'default' => false,
				),

			),
			plugin_dir_path(__FILE__).'../'
		);
	}

	function initialize() {
		$this->register_frontend_styles(
			array(
				array(
					'siteorigin-widgets',
					siteorigin_widget_get_plugin_dir_url( 'features' ) . 'css/style.css',
					array(),
					SOW_BUNDLE_VERSION
				)
			)
		);
	}

	function get_style_name($instance){
		return false;
	}

	function get_template_name($instance){
		return 'base';
	}

	function modify_form( $form ){
		$form['container_shape']['options'] = include dirname( __FILE__ ) . '/inc/containers.php';
		return $form;
	}
}

siteorigin_widget_register('features', __FILE__);
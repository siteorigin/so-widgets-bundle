<?php
/*
Widget Name: Features
Description: Displays a block of features with icons.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

class SiteOrigin_Widget_Features_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-features',
			__( 'SiteOrigin Features', 'so-widgets-bundle' ),
			array(
				'description' => __( 'Displays a list of features.', 'so-widgets-bundle' ),
				'help'        => 'https://siteorigin.com/widgets-bundle/features-widget-documentation/'
			),
			array(),
			array(
				'features' => array(
					'type' => 'repeater',
					'label' => __('Features', 'so-widgets-bundle'),
					'item_name' => __('Feature', 'so-widgets-bundle'),
					'item_label' => array(
						'selector' => "[id*='features-title']",
						'update_event' => 'change',
						'value_method' => 'val'
					),
					'fields' => array(

						// The container shape

						'container_color' => array(
							'type' => 'color',
							'label' => __('Container color', 'so-widgets-bundle'),
							'default' => '#404040',
						),

						// The Icon

						'icon' => array(
							'type' => 'icon',
							'label' => __('Icon', 'so-widgets-bundle'),
						),

						'icon_color' => array(
							'type' => 'color',
							'label' => __('Icon color', 'so-widgets-bundle'),
							'default' => '#FFFFFF',
						),

						'icon_image' => array(
							'type' => 'media',
							'library' => 'image',
							'label' => __('Icon image', 'so-widgets-bundle'),
							'description' => __('Use your own icon image.', 'so-widgets-bundle'),
						),

						// The text under the icon

						'title' => array(
							'type' => 'text',
							'label' => __('Title text', 'so-widgets-bundle'),
						),

						'text' => array(
							'type' => 'text',
							'label' => __('Text', 'so-widgets-bundle')
						),

						'more_text' => array(
							'type' => 'text',
							'label' => __('More link text', 'so-widgets-bundle'),
						),

						'more_url' => array(
							'type' => 'link',
							'label' => __('More link URL', 'so-widgets-bundle'),
						),
					),
				),

				'container_shape' => array(
					'type' => 'select',
					'label' => __('Container shape', 'so-widgets-bundle'),
					'default' => 'round',
					'options' => array(
					),
				),

				'container_size' => array(
					'type' => 'number',
					'label' => __('Container size', 'so-widgets-bundle'),
					'default' => 84,
				),

				'icon_size' => array(
					'type' => 'number',
					'label' => __('Icon size', 'so-widgets-bundle'),
					'default' => 24,
				),

				'per_row' => array(
					'type' => 'number',
					'label' => __('Features per row', 'so-widgets-bundle'),
					'default' => 3,
				),

				'responsive' => array(
					'type' => 'checkbox',
					'label' => __('Responsive layout', 'so-widgets-bundle'),
					'default' => true,
				),

				'title_link' => array(
					'type' => 'checkbox',
					'label' => __('Link feature title to more URL', 'so-widgets-bundle'),
					'default' => false,
				),

				'icon_link' => array(
					'type' => 'checkbox',
					'label' => __('Link icon to more URL', 'so-widgets-bundle'),
					'default' => false,
				),

				'new_window' => array(
					'type' => 'checkbox',
					'label' => __('Open more URL in a new window', 'so-widgets-bundle'),
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
					plugin_dir_url(__FILE__) . 'css/style.css',
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

siteorigin_widget_register('sow-features', __FILE__, 'SiteOrigin_Widget_Features_Widget');
<?php
/*
Widget Name: Features Widget
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
					'fields' => array(

						// The container shape

						'container_color' => array(
							'type' => 'color',
							'label' => __('Container Color', 'siteorigin-widgets'),
							'default' => '#404040',
						),

						// The Icon

						'icon' => array(
							'type' => 'icon',
							'label' => __('Icon', 'siteorigin-widgets'),
						),

						'icon_color' => array(
							'type' => 'color',
							'label' => __('Icon Color', 'siteorigin-widgets'),
							'default' => '#FFFFFF',
						),

						'icon_image' => array(
							'type' => 'media',
							'library' => 'image',
							'label' => __('Icon Image', 'siteorigin-widgets'),
							'description' => __('Use your own icon image.', 'siteorigin-widgets'),
						),

						// The text under the icon

						'title' => array(
							'type' => 'text',
							'label' => __('Title Text', 'siteorigin-widgets'),
						),

						'text' => array(
							'type' => 'text',
							'label' => __('Text', 'siteorigin-widgets'),
						),

						'more_text' => array(
							'type' => 'text',
							'label' => __('More Link Text', 'siteorigin-widgets'),
						),

						'more_url' => array(
							'type' => 'text',
							'label' => __('More Link URL', 'siteorigin-widgets'),
							'sanitize' => 'url',
						),
					),
				),

				'container_shape' => array(
					'type' => 'select',
					'label' => __('Container Shape', 'siteorigin-widgets'),
					'options' => array(
					),
				),

				'container_size' => array(
					'type' => 'number',
					'label' => __('Container Size', 'siteorigin-widgets'),
					'default' => 84,
				),

				'icon_size' => array(
					'type' => 'number',
					'label' => __('Icon Size', 'siteorigin-widgets'),
					'default' => 24,
				),

				'per_row' => array(
					'type' => 'number',
					'label' => __('Features Per Row', 'siteorigin-widgets'),
					'default' => 3,
				),

				'responsive' => array(
					'type' => 'checkbox',
					'label' => __('Responsive Layout', 'siteorigin-widgets'),
					'default' => true,
				),

				'title_link' => array(
					'type' => 'checkbox',
					'label' => __('Link Feature Title to More URL', 'siteorigin-widgets'),
					'default' => false,
				),

				'icon_link' => array(
					'type' => 'checkbox',
					'label' => __('Link Icon to More URL', 'siteorigin-widgets'),
					'default' => false,
				),

				'new_window' => array(
					'type' => 'checkbox',
					'label' => __('Open More URL in New Window', 'siteorigin-widgets'),
					'default' => false,
				),

			),
			plugin_dir_path(__FILE__).'../'
		);
	}

	function get_style_name($instance){
		return false;
	}

	function get_template_name($instance){
		return 'base';
	}

	function enqueue_frontend_scripts(){
		wp_enqueue_style('siteorigin-widgets', siteorigin_widget_get_plugin_dir_url('features').'css/style.css', array(), SOW_BUNDLE_VERSION );
	}

	function modify_form( $form ){
		$form['container_shape']['options'] = include dirname(__FILE__).'/inc/containers.php';
		return $form;
	}
}

siteorigin_widget_register('features', __FILE__);
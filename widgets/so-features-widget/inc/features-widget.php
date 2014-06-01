<?php

class SiteOrigin_Widget_Features_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-features',
			__( 'SiteOrigin Features', 'sow-features' ),
			array(
				'description' => __( 'Displays a list of features.', 'sow-features' ),
				'help'        => 'http://siteorigin.com/features-widget-documentation/'
			),
			array(),
			array(
				'features' => array(
					'type' => 'repeater',
					'label' => __('Features', 'sow-features'),
					'item_name' => __('Feature', 'sow-features'),
					'fields' => array(

						// The container shape

						'container_color' => array(
							'type' => 'color',
							'label' => __('Container Color', 'sow-feature'),
							'default' => '#404040',
						),

						// The Icon

						'icon' => array(
							'type' => 'icon',
							'label' => __('Icon', 'sow-feature'),
						),

						'icon_color' => array(
							'type' => 'color',
							'label' => __('Icon Color', 'sow-feature'),
							'default' => '#FFFFFF',
						),

						'icon_image' => array(
							'type' => 'media',
							'library' => 'image',
							'label' => __('Icon Image', 'sow-feature'),
							'description' => __('Use your own icon image.', 'sow-feature'),
						),

						// The text under the icon

						'title' => array(
							'type' => 'text',
							'label' => __('Title Text', 'sow-feature'),
						),

						'text' => array(
							'type' => 'text',
							'label' => __('Text', 'sow-feature'),
						),

						'more_text' => array(
							'type' => 'text',
							'label' => __('More Link Text', 'sow-feature'),
						),

						'more_url' => array(
							'type' => 'text',
							'label' => __('More Link URL', 'sow-feature'),
							'sanitize' => 'url',
						),
					),
				),

				'container_shape' => array(
					'type' => 'select',
					'label' => __('Container Shape', 'sow-feature'),
					'options' => array(
					),
				),

				'container_size' => array(
					'type' => 'number',
					'label' => __('Container Size', 'sow-feature'),
					'default' => 84,
				),

				'icon_size' => array(
					'type' => 'number',
					'label' => __('Icon Size', 'sow-feature'),
					'default' => 24,
				),

				'per_row' => array(
					'type' => 'number',
					'label' => __('Features Per Row', 'sow-feature'),
					'default' => 3,
				),

				'responsive' => array(
					'type' => 'checkbox',
					'label' => __('Responsive Layout', 'sow-feature'),
					'default' => true,
				),

				'title_link' => array(
					'type' => 'checkbox',
					'label' => __('Link Feature Title to More URL', 'sow-feature'),
					'default' => false,
				),

				'new_window' => array(
					'type' => 'checkbox',
					'label' => __('Open More URL in New Window', 'sow-feature'),
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
		wp_enqueue_style('sow-features', siteorigin_widget_get_plugin_dir_url('features').'css/style.css', array(), SOW_BUNDLE_VERSION );
	}

	function modify_form( $form ){
		$form['container_shape']['options'] = include dirname(__FILE__).'/containers.php';
		return $form;
	}
}
<?php

class SiteOrigin_Widget_Cta_widget extends SiteOrigin_Widget {

	function __construct() {

		parent::__construct(
			'sow-cta',
			__('SiteOrigin Call To Action', 'sow-cta'),
			array(
				'description' => __('A simple call to action widget with massive power.', 'sow-cta'),
				'help' => 'http://siteorigin.com/'
			),
			array(

			),
			array(

				'title' => array(
					'type' => 'text',
					'label' => __('Title', 'sow-cta'),
				),

				'sub_title' => array(
					'type' => 'text',
					'label' => __('Subtitle', 'sow-cta'),
				),

				'design' => array(
					'type' => 'section',
					'label' => __('Design', 'sow-cta'),
					'fields' => array(

						'background_color' => array(
							'type' => 'color',
							'label' => __('Background Color', 'sow-cta'),
						),

					)
				),

				'button' => array(
					'type' => 'widget',
					'class' => 'SiteOrigin_Widget_Button_Widget',
					'label' => __('Button', 'sow-cta'),
				),

			),
			plugin_dir_path(__FILE__).'../'
		);
	}

	/**
	 * Initialize the CTA widget
	 */
	function initialize(){
		if( !class_exists('SiteOrigin_Widget_Button_Widget') ) {
			// The bundled version of the button widget is version 1.0.2
			include plugin_dir_path( __FILE__ ) . '../../so-button-widget/inc/widget.php';
			siteorigin_widget_register_self( 'button', realpath( plugin_dir_path( __FILE__ ) . '../../so-button-widget/so-button-widget.php' ) );
		}
	}

	function get_template_name($instance) {
		return 'base';
	}

	function get_style_name($instance) {
		return 'basic';
	}

	function enqueue_frontend_scripts(){
		wp_enqueue_style( 'sow-cta-main', siteorigin_widget_get_plugin_dir_url('cta').'css/style.css', array(), SOW_BUNDLE_VERSION );
	}

}
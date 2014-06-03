<?php

class SiteOrigin_Widget_Cta_widget extends SiteOrigin_Widget {

	function __construct() {

		parent::__construct(
			'sow-cta',
			__('SiteOrigin Call To Action', 'sow-cta'),
			array(
				'description' => __('A simple call to action widget with massive power.', 'sow-cta'),
				'help' => 'http://siteorigin.com/widgets-bundle/'
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

						'border_color' => array(
							'type' => 'color',
							'label' => __('Border Color', 'sow-cta'),
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

	function get_less_variables($instance) {
		return array(
			'border_color' => $instance['design']['border_color'],
			'background_color' => $instance['design']['background_color'],
		);
	}

	function enqueue_frontend_scripts(){
		wp_enqueue_style( 'sow-cta-main', siteorigin_widget_get_plugin_dir_url('cta').'css/style.css', array(), SOW_BUNDLE_VERSION );
		wp_enqueue_script( 'sow-cta-main', siteorigin_widget_get_plugin_dir_url('cta').'js/cta.js', array('jquery'), SOW_BUNDLE_VERSION );
	}

}
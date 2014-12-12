<?php
/*
Widget Name: Call To Action Widget
Description: A simple call to action widget. You can do what ever you want with a call to action widget.
Author: Greg Priday
Author URI: http://siteorigin.com
*/

class SiteOrigin_Widget_Cta_widget extends SiteOrigin_Widget {

	function __construct() {

		parent::__construct(
			'sow-cta',
			__('SiteOrigin Call To Action', 'siteorigin-widgets'),
			array(
				'description' => __('A simple call to action widget with massive power.', 'siteorigin-widgets'),
				'help' => 'http://siteorigin.com/widgets-bundle/'
			),
			array(

			),
			array(

				'title' => array(
					'type' => 'text',
					'label' => __('Title', 'siteorigin-widgets'),
				),

				'sub_title' => array(
					'type' => 'text',
					'label' => __('Subtitle', 'siteorigin-widgets'),
				),

				'design' => array(
					'type' => 'section',
					'label' => __('Design', 'siteorigin-widgets'),
					'fields' => array(

						'background_color' => array(
							'type' => 'color',
							'label' => __('Background Color', 'siteorigin-widgets'),
						),

						'border_color' => array(
							'type' => 'color',
							'label' => __('Border Color', 'siteorigin-widgets'),
						),

					)
				),

				'button' => array(
					'type' => 'widget',
					'class' => 'SiteOrigin_Widget_Button_Widget',
					'label' => __('Button', 'siteorigin-widgets'),
				),

			),
			plugin_dir_path(__FILE__)
		);
	}

	/**
	 * Initialize the CTA widget
	 */
	function initialize(){
		if( !class_exists('SiteOrigin_Widget_Button_Widget') ) {
			include plugin_dir_path( __FILE__ ) . '../so-button-widget/so-button-widget.php';
			siteorigin_widget_register( 'button', realpath( plugin_dir_path( __FILE__ ) . '../so-button-widget/so-button-widget.php' ) );
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
		$js_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_style( 'sow-cta-main', siteorigin_widget_get_plugin_dir_url('cta').'css/style.css', array(), SOW_BUNDLE_VERSION );
		wp_enqueue_script( 'sow-cta-main', siteorigin_widget_get_plugin_dir_url('cta').'js/cta' . $js_suffix . '.js', array('jquery'), SOW_BUNDLE_VERSION );
	}

}

siteorigin_widget_register('cta', __FILE__);
<?php
/*
Widget Name: Call-to-action widget
Description: A simple call-to-action widget. You can do what ever you want with a call-to-action widget.
Author: Greg Priday
Author URI: http://siteorigin.com
*/

class SiteOrigin_Widget_Cta_widget extends SiteOrigin_Widget {

	function __construct() {

		parent::__construct(
			'sow-cta',
			__('SiteOrigin Call-to-action', 'siteorigin-widgets'),
			array(
				'description' => __('A simple call-to-action widget with massive power.', 'siteorigin-widgets'),
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
					'label' => __('Subtitle', 'siteorigin-widgets')
				),

				'design' => array(
					'type' => 'section',
					'label' => __('Design', 'siteorigin-widgets'),
					'fields' => array(
						'background_color' => array(
							'type' => 'color',
							'label' => __('Background color', 'siteorigin-widgets'),
						),
						'border_color' => array(
							'type' => 'color',
							'label' => __('Border color', 'siteorigin-widgets'),
						),
						'button_align' => array(
							'type' => 'select',
							'label' => __( 'Button align', 'siteorigin-widgets' ),
							'default' => 'right',
							'options' => array(
								'left' => __( 'Left', 'siteorigin-widgets'),
								'right' => __( 'Right', 'siteorigin-widgets'),
							)
						)
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
		$this->register_frontend_styles(
			array(
				array(
					'sow-cta-main',
					siteorigin_widget_get_plugin_dir_url( 'cta' ) . 'css/style.css',
					array(),
					SOW_BUNDLE_VERSION
				)
			)
		);
		$this->register_frontend_scripts(
			array(
				array(
					'sow-cta-main',
					siteorigin_widget_get_plugin_dir_url( 'cta' ) . 'js/cta' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' ),
					SOW_BUNDLE_VERSION
				)
			)
		);
	}

	function get_template_name($instance) {
		return 'base';
	}

	function get_style_name($instance) {
		return 'basic';
	}

	function get_less_variables($instance) {
		if( empty( $instance ) ) return array();

		return array(
			'border_color' => $instance['design']['border_color'],
			'background_color' => $instance['design']['background_color'],
			'button_align' => $instance['design']['button_align'],
		);
	}

	function modify_child_widget_form($child_widget_form, $child_widget) {
		unset( $child_widget_form['design']['fields']['align'] );
		return $child_widget_form;
	}

}

siteorigin_widget_register('cta', __FILE__);
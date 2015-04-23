<?php
/*
Widget Name: Button widget
Description: A powerful yet simple button widget for your sidebars or Page Builder pages.
Author: Greg Priday
Author URI: http://siteorigin.com
*/

class SiteOrigin_Widget_Button_Widget extends SiteOrigin_Widget {
	function __construct() {

		parent::__construct(
			'sow-button',
			__('SiteOrigin Button', 'siteorigin-widgets'),
			array(
				'description' => __('A customizable button widget.', 'siteorigin-widgets'),
				'help' => 'http://siteorigin.com/widgets-bundle/button-widget-documentation/'
			),
			array(

			),
			array(
				'text' => array(
					'type' => 'text',
					'label' => __('Button text', 'siteorigin-widgets'),
				),

				'url' => array(
					'type' => 'link',
					'label' => __('Destination URL', 'siteorigin-widgets'),
				),

				'new_window' => array(
					'type' => 'checkbox',
					'default' => false,
					'label' => __('Open in a new window', 'siteorigin-widgets'),
				),

				'button_icon' => array(
					'type' => 'section',
					'label' => __('Icon', 'siteorigin-widgets'),
					'fields' => array(
						'icon_selected' => array(
							'type' => 'icon',
							'label' => __('Icon', 'siteorigin-widgets'),
						),

						'icon_color' => array(
							'type' => 'color',
							'label' => __('Icon color', 'siteorigin-widgets'),
						),

						'icon' => array(
							'type' => 'media',
							'label' => __('Image icon', 'siteorigin-widgets'),
							'description' => __('Replaces the icon with your own image icon.'),
						),
					),
				),

				'design' => array(
					'type' => 'section',
					'label' => __('Design and layout', 'siteorigin-widgets'),
					'hide' => true,
					'fields' => array(
						'align' => array(
							'type' => 'select',
							'label' => __('Align', 'siteorigin-widgets'),
							'default' => 'center',
							'options' => array(
								'left' => __('Left', 'siteorigin-widgets'),
								'right' => __('Right', 'siteorigin-widgets'),
								'center' => __('Center', 'siteorigin-widgets'),
								'justify' => __('Justify', 'siteorigin-widgets'),
							),
						),

						'theme' => array(
							'type' => 'select',
							'label' => __('Button theme', 'siteorigin-widgets'),
							'default' => 'atom',
							'options' => array(
								'atom' => __('Atom', 'siteorigin-widgets'),
								'flat' => __('Flat', 'siteorigin-widgets'),
								'wire' => __('Wire', 'siteorigin-widgets'),
							),
						),


						'button_color' => array(
							'type' => 'color',
							'label' => __('Button color', 'siteorigin-widgets'),
						),

						'text_color' => array(
							'type' => 'color',
							'label' => __('Text color', 'siteorigin-widgets'),
						),

						'hover' => array(
							'type' => 'checkbox',
							'default' => true,
							'label' => __('Use hover effects', 'siteorigin-widgets'),
						),

						'font_size' => array(
							'type' => 'select',
							'label' => __('Font size', 'siteorigin-widgets'),
							'options' => array(
								'1' => __('Normal', 'siteorigin-widgets'),
								'1.15' => __('Medium', 'siteorigin-widgets'),
								'1.3' => __('Large', 'siteorigin-widgets'),
								'1.45' => __('Extra large', 'siteorigin-widgets'),
							),
						),

						'rounding' => array(
							'type' => 'select',
							'label' => __('Rounding', 'siteorigin-widgets'),
							'default' => '0.25',
							'options' => array(
								'0' => __('None', 'siteorigin-widgets'),
								'0.25' => __('Slightly rounded', 'siteorigin-widgets'),
								'0.5' => __('Very rounded', 'siteorigin-widgets'),
								'1.5' => __('Completely rounded', 'siteorigin-widgets'),
							),
						),

						'padding' => array(
							'type' => 'select',
							'label' => __('Padding', 'siteorigin-widgets'),
							'default' => '1',
							'options' => array(
								'0.5' => __('Low', 'siteorigin-widgets'),
								'1' => __('Medium', 'siteorigin-widgets'),
								'1.4' => __('High', 'siteorigin-widgets'),
								'1.8' => __('Very high', 'siteorigin-widgets'),
							),
						),

					),
				),

				'attributes' => array(
					'type' => 'section',
					'label' => __('Other attributes and SEO', 'siteorigin-widgets'),
					'hide' => true,
					'fields' => array(
						'id' => array(
							'type' => 'text',
							'label' => __('Button ID', 'siteorigin-widgets'),
							'description' => __('An ID attribute allows you to target this button in Javascript.', 'siteorigin-widgets'),
						),

						'title' => array(
							'type' => 'text',
							'label' => __('Title attribute', 'siteorigin-widgets'),
							'description' => __('Adds a title attribute to the button link.', 'siteorigin-widgets'),
						),

						'onclick' => array(
							'type' => 'text',
							'label' => __('Onclick', 'siteorigin-widgets'),
							'description' => __('Run this Javascript when the button is clicked. Ideal for tracking.', 'siteorigin-widgets'),
						),
					)
				),
			),
			plugin_dir_path(__FILE__)
		);

	}

	function initialize() {
		$this->register_frontend_styles(
			array(
				array(
					'sow-button-base',
					siteorigin_widget_get_plugin_dir_url( 'button' ) . 'css/style.css',
					array(),
					SOW_BUNDLE_VERSION
				),
			)
		);
	}

	function get_template_name($instance) {
		return 'base';
	}

	function get_style_name($instance) {
		if(empty($instance['design']['theme'])) return 'atom';
		return $instance['design']['theme'];
	}

	/**
	 * Get the variables that we'll be injecting into the less stylesheet.
	 *
	 * @param $instance
	 *
	 * @return array
	 */
	function get_less_variables($instance){
		if( empty( $instance ) || empty( $instance['design'] ) ) return array();

		return array(
			'button_color' => $instance['design']['button_color'],
			'text_color' => $instance['design']['text_color'],

			'font_size' => $instance['design']['font_size'] . 'em',
			'rounding' => $instance['design']['rounding'] . 'em',
			'padding' => $instance['design']['padding'] . 'em',
			'has_text' => empty( $instance['text'] ) ? 'false' : 'true',
		);
	}

	/**
	 * Make sure the instance is the most up to date version.
	 *
	 * @param $instance
	 *
	 * @return mixed
	 */
	function modify_instance($instance){

		if( empty($instance['button_icon']) ) {
			$instance['button_icon'] = array();

			if(isset($instance['icon_selected'])) $instance['button_icon']['icon_selected'] = $instance['icon_selected'];
			if(isset($instance['icon_color'])) $instance['button_icon']['icon_color'] = $instance['icon_color'];
			if(isset($instance['icon'])) $instance['button_icon']['icon'] = $instance['icon'];

			unset($instance['icon_selected']);
			unset($instance['icon_color']);
			unset($instance['icon']);
		}

		if( empty($instance['design']) ) {
			$instance['design'] = array();

			if(isset($instance['align'])) $instance['design']['align'] = $instance['align'];
			if(isset($instance['theme'])) $instance['design']['theme'] = $instance['theme'];
			if(isset($instance['button_color'])) $instance['design']['button_color'] = $instance['button_color'];
			if(isset($instance['text_color'])) $instance['design']['text_color'] = $instance['text_color'];
			if(isset($instance['hover'])) $instance['design']['hover'] = $instance['hover'];
			if(isset($instance['font_size'])) $instance['design']['font_size'] = $instance['font_size'];
			if(isset($instance['rounding'])) $instance['design']['rounding'] = $instance['rounding'];
			if(isset($instance['padding'])) $instance['design']['padding'] = $instance['padding'];

			unset($instance['align']);
			unset($instance['theme']);
			unset($instance['button_color']);
			unset($instance['text_color']);
			unset($instance['hover']);
			unset($instance['font_size']);
			unset($instance['rounding']);
			unset($instance['padding']);
		}

		if( empty($instance['attributes']) ) {
			$instance['attributes'] = array();
			if(isset($instance['id'])) $instance['attributes']['id'] = $instance['id'];
			unset($instance['id']);
		}

		return $instance;
	}
}

siteorigin_widget_register('button', __FILE__);
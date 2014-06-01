<?php

class SiteOrigin_Widget_Button_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-button',
			__('SiteOrigin Button', 'sow-button'),
			array(
				'description' => __('A customizable button widget.', 'sow-button'),
				'help' => 'http://siteorigin.com/button-widget-documentation/'
			),
			array(

			),
			array(
				'text' => array(
					'type' => 'text',
					'label' => __('Button Text', 'sow-button'),
				),

				'url' => array(
					'type' => 'text',
					'sanitize' => 'url',
					'label' => __('Destination URL', 'sow-button'),
				),

				'new_window' => array(
					'type' => 'checkbox',
					'default' => false,
					'label' => __('Open in New Window', 'sow-button'),
				),

				'button_icon' => array(
					'type' => 'section',
					'label' => __('Icon', 'sow-button'),
					'fields' => array(
						'icon_selected' => array(
							'type' => 'icon',
							'label' => __('Icon', 'sow-button'),
						),

						'icon_color' => array(
							'type' => 'color',
							'label' => __('Icon Color', 'sow-button'),
						),

						'icon' => array(
							'type' => 'media',
							'label' => __('Image Icon', 'sow-button'),
							'description' => __('Replaces the icon with your own image icon.'),
						),
					),
				),

				'design' => array(
					'type' => 'section',
					'label' => __('Design and Layout', 'sow-button'),
					'hide' => true,
					'fields' => array(
						'align' => array(
							'type' => 'select',
							'label' => __('Align', 'sow-button'),
							'options' => array(
								'left' => __('Left', 'sow-button'),
								'right' => __('Right', 'sow-button'),
								'center' => __('Center', 'sow-button'),
								'justify' => __('Justify', 'sow-button'),
							),
						),

						'theme' => array(
							'type' => 'select',
							'label' => __('Button Theme', 'sow-button'),
							'default' => 'atom',
							'options' => array(
								'atom' => __('Atom', 'sow-button'),
								'flat' => __('Flat', 'sow-button'),
								'wire' => __('Wire', 'sow-button'),
							),
						),


						'button_color' => array(
							'type' => 'color',
							'label' => __('Button Color', 'sow-button'),
						),

						'text_color' => array(
							'type' => 'color',
							'label' => __('Text Color', 'sow-button'),
						),

						'hover' => array(
							'type' => 'checkbox',
							'default' => true,
							'label' => __('Use Hover Effects', 'sow-button'),
						),

						'font_size' => array(
							'type' => 'select',
							'label' => __('Font Size', 'sow-button'),
							'options' => array(
								'1' => __('Normal', 'sow-button'),
								'1.15' => __('Medium', 'sow-button'),
								'1.3' => __('Large', 'sow-button'),
								'1.45' => __('Extra Large', 'sow-button'),
							),
						),

						'rounding' => array(
							'type' => 'select',
							'label' => __('Rounding', 'sow-button'),
							'default' => '0.25',
							'options' => array(
								'0' => __('None', 'sow-button'),
								'0.25' => __('Slight Rounding', 'sow-button'),
								'0.5' => __('Very Rounded', 'sow-button'),
								'1.5' => __('Completely Rounded', 'sow-button'),
							),
						),

						'padding' => array(
							'type' => 'select',
							'label' => __('Padding', 'sow-button'),
							'default' => '1',
							'options' => array(
								'0.5' => __('Low', 'sow-button'),
								'1' => __('Medium', 'sow-button'),
								'1.4' => __('High', 'sow-button'),
								'1.8' => __('Very High', 'sow-button'),
							),
						),

					),
				),

				'attributes' => array(
					'type' => 'section',
					'label' => __('Other Attributes and SEO', 'sow-button'),
					'hide' => true,
					'fields' => array(
						'id' => array(
							'type' => 'text',
							'label' => __('Button ID', 'sow-button'),
							'description' => __('An ID attribute allows you to target this button in Javascript.', 'sow-button'),
						),

						'title' => array(
							'type' => 'text',
							'label' => __('Title Attribute', 'sow-button'),
							'description' => __('Adds a title attribute to the button link.', 'sow-button'),
						),

						'onclick' => array(
							'type' => 'text',
							'label' => __('Onclick', 'sow-button'),
							'description' => __('Run this Javascript when the button is clicked. Ideal for tracking.', 'sow-button'),
						),
					)
				),
			),
			plugin_dir_path(__FILE__).'../'
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
		return array(
			'button_color' => $instance['design']['button_color'],
			'text_color' => $instance['design']['text_color'],

			'font_size' => $instance['design']['font_size'] . 'em',
			'rounding' => $instance['design']['rounding'] . 'em',
			'padding' => $instance['design']['padding'] . 'em',
		);
	}

	/**
	 * Enqueue the basic button CSS.
	 */
	function enqueue_frontend_scripts(){
		wp_enqueue_style('sow-button-base', siteorigin_widget_get_plugin_dir_url('button').'css/style.css', array(), SOW_BUNDLE_VERSION );
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

function sow_button_register_widget(){
	register_widget('SiteOrigin_Widget_Button_Widget');
}
add_action('widgets_init', 'sow_button_register_widget');
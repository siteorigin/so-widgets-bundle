<?php
/*
Widget Name: Accordion
Description: A powerful yet simple accordion widget for your sidebars or Page Builder pages.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

class SiteOrigin_Widget_Accordion_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-accordion',
			__('SiteOrigin Accordion', 'so-widgets-bundle'),
			array(
				'description' => __('A responsive accordion widget.', 'so-widgets-bundle'),
				'panels_title' => false,
			),
			array(

			),
			false,
			plugin_dir_path(__FILE__)
		);
	}

	/**
	 * Register all the frontend scripts and styles for the base slider.
	 */
	function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'sow-accordion-js',
					plugin_dir_url(__FILE__) . 'js/accordion.js',
					array('jquery'),
					SOW_BUNDLE_VERSION
				)
			)
		);
		$this->register_frontend_styles(
			array(
				array(
					'sow-accordion-css',
					plugin_dir_url(__FILE__) . 'styles/accordion.css',
					array(),
					SOW_BUNDLE_VERSION
				)
			)
		);

	}

	function get_template_name($instance) {
		return 'accordion';
	}

	function initialize_form() {
		return array(
			'frames' => array(
				'type' => 'repeater',
				'label' => __('Accordion Items', 'so-widgets-bundle'),
				'item_name' => __('Item', 'so-widgets-bundle'),
				'item_label' => array(
					'selector' => "[id*='frames-url']",
					'update_event' => 'change',
					'value_method' => 'val'
				),
				'fields' => array(
					'title' => array(
						'type' => 'text',
						'label' => __('Title', 'so-widgets-bundle')
					),
					'text' => array(
						'type' => 'tinymce',
						'label' => __('Text', 'so-widgets-bundle'),
					),
				),
			),
			'controls' => array(
				'type' => 'section',
				'label' => __('Controls', 'so-widgets-bundle'),
				'fields' => $this->control_form_fields()
			)
		);
	}

	/**
	 * The control array required for the slider
	 *
	 * @return array
	 */
	function control_form_fields(){
		return array(
			'open_first_element' => array(
				'type' => 'checkbox',
				'label' => __('Open first element', 'so-widgets-bundle'),
				'description' => __('Should the first element be open?', 'so-widgets-bundle'),
				'default' => true,
			),
		);
	}

	/**
	 * The less variables to control the design of the slider
	 *
	 * @param $instance
	 *
	 * @return array
	 */
	function get_less_variables($instance) {
		$less = array();

		return $less;
	}

	/**
	 * Change the instance to the new one we're using for sliders
	 *
	 * @param $instance
	 *
	 * @return mixed|void
	 */
	function modify_instance( $instance ){


		return $instance;
	}
}

siteorigin_widget_register('sow-accordion', __FILE__, 'SiteOrigin_Widget_Accordion_Widget');

<?php
/*
Widget Name: Hero widget
Description: A big hero image with a few settings to make it your own.
Author: Greg Priday
Author URI: http://siteorigin.com
*/

class SiteOrigin_Widget_Hero_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-hero',
			__('SiteOrigin Hero', 'siteorigin-widgets'),
			array(
				'description' => __('A big hero image with a few settings to make it your own.', 'siteorigin-widgets'),
				'help' => 'http://siteorigin.com/widgets-bundle/slider-widget-documentation/',
				'panels_title' => false,
			),
			array( ),
			array(
				'frames' => array(
					'type' => 'repeater',
					'label' => __('Hero frames', 'siteorigin-widgets'),
					'item_name' => __('Frame', 'siteorigin-widgets'),
					'item_label' => array(
						'selector' => "[id*='frames-title']",
						'update_event' => 'change',
						'value_method' => 'val'
					),

					'fields' => array(

						'title' => array(
							'type' => 'text',
							'label' => __( 'Title', 'siteorigin-widgets' ),
						),

						'subtitle' => array(
							'type' => 'text',
							'label' => __( 'Sub title', 'siteorigin-widgets' ),
						),

						'content' => array(
							'type' => 'tinymce',
							'label' => __( 'Content', 'siteorigin-widgets' ),
						),

						'background_image' => array(
							'type' => 'media',
							'label' => __( 'Background image', 'siteorigin-widgets' ),
							'library' => 'image',
						),

						'buttons' => array(
							'type' => 'repeater',
							'label' => __('Buttons', 'siteorigin-widgets'),
							'item_name' => __('Button', 'siteorigin-widgets'),
							'item_label' => array(
								'selector' => "[id*='buttons-button-text']",
								'update_event' => 'change',
								'value_method' => 'val'
							),
							'fields' => array(
								'button' => array(
									'type' => 'widget',
									'class' => 'SiteOrigin_Widget_Button_Widget',
									'label' => __('Button', 'siteorigin-widgets'),
									'first_level' => true,
								)
							)
						)

					)
				)
			)
		);
	}
}

siteorigin_widget_register('hero', __FILE__);
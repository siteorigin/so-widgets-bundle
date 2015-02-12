<?php
/*
Widget Name: Testimonial widget
Description: Share your product testimonials in a variety of different ways.
Author: SiteOrigin
Author URI: http://siteorigin.com
*/

class SiteOrigin_Widget_Testimonial_Widget extends SiteOrigin_Widget  {
	function __construct() {
		parent::__construct(
			'sow-testimonial',
			__('SiteOrigin Testimonial', 'siteorigin-widgets'),
			array(
				'description' => __('Share your product testimonials in a variety of different ways.', 'siteorigin-widgets'),
				'help' => 'http://siteorigin.com/widgets-bundle/testimonial-widget-documentation/'
			),
			array(

			),
			array(
				'template' => array(
					'type' => 'select',
					'label' => __( 'Template', 'siteorigin-widgets' ),
					'options' => array(
						'simple-left' => __( 'Simple Image Left', 'siteorigin-widgets' ),
						'simple-top' => __( 'Simple Image Top', 'siteorigin-widgets' ),
					)
				),
				'name' => array(
					'type' => 'text',
					'label' => __('Name', 'siteorigin-widgets'),
				),

				'location' => array(
					'type' => 'text',
					'label' => __('Location', 'siteorigin-widgets'),
				),

				'image' => array(
					'type' => 'media',
					'label' => __('Image', 'siteorigin-widgets'),
				),

				'text' => array(
					'type' => 'textarea',
					'label' => __('Text', 'siteorigin-widgets'),
				),

				'url' => array(
					'type' => 'text',
					'label' => __('URL', 'siteorigin-widgets'),
				),

				'new_window' => array(
					'type' => 'checkbox',
					'label' => __('Open In New Window', 'siteorigin-widgets'),
				),
			)
		);
	}

	function get_style_name( $instance ) {
		return ! empty( $instance['template'] ) ? $instance['template'] : 'simple-left';
	}

	function get_template_name( $instance ) {
		return ! empty( $instance['template'] ) ? $instance['template'] : 'simple-left';
	}
}

siteorigin_widget_register('testimonial', __FILE__);
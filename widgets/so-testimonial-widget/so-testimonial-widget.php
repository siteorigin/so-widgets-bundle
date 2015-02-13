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
						'default' => __( 'Default', 'siteorigin-widgets' ),
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
				'design' => array(
					'type' => 'section',
					'label' => __( 'Design and layout', 'siteorigin-widgets' ),
					'hide' => true,
					'description' => __( 'Style options for the default template.', 'siteorigin-widgets' ),
					'fields' => array(
						'show_border' => array(
							'type' => 'checkbox',
							'label' => __( 'Show border', 'siteorigin-widgets' ),
							'default' => true
						),
						'border_radius' => array(
							'type' => 'select',
							'label' => __( 'Border corners', 'siteorigin-widgets' ),
							'default' => '0',
							'options' => array(
								'0' => __( 'Not rounded', 'siteorigin-widgets' ),
								'4' => __( 'Slightly rounded', 'siteorigin-widgets' ),
								'8' => __( 'Rounded', 'siteorigin-widgets' ),
								'16' => __( 'Very rounded', 'siteorigin-widgets' ),
							)
						),
						'background_color' => array(
							'type'    => 'color',
							'label'   => __( 'Background color', 'siteorigin-widgets' ),
							'default' => '#FCFCFC'
						),
						'box_shadow' => array(
							'type' => 'checkbox',
							'label' => __( 'Show box shadow', 'siteorigin-widgets' ),
							'default' => true,
						),
						'image_border_radius' => array(
							'type' => 'select',
							'label' => __( 'Image corners', 'siteorigin-widgets' ),
							'default' => '4',
							'options' => array(
								'0' => __( 'Not rounded', 'siteorigin-widgets' ),
								'4' => __( 'Slightly rounded', 'siteorigin-widgets' ),
								'8' => __( 'Rounded', 'siteorigin-widgets' ),
								'16' => __( 'Very rounded', 'siteorigin-widgets' ),
							)
						),
						'image_alignment' => array(
							'type' => 'select',
							'label' => __( 'Image alignment', 'siteorigin-widgets' ),
							'default' => 'left',
							'options' => array(
								'left' => __( 'Left', 'siteorigin-widgets' ),
								'right' => __( 'Right', 'siteorigin-widgets' ),
								'top' => __( 'Top', 'siteorigin-widgets' ),
							)
						),
						'image_height' => array(
							'type' => 'number',
							'label' => __( 'Image height', 'siteorigin-widgets' ),
							'default' => 60,
						),
					)
				),
			)
		);
	}

	function get_style_name( $instance ) {
		if ( empty( $instance['template'] ) || $instance['template'] == 'default' ) {
			return 'default';
		}
		else {
			return $instance['template'];
		}
	}

	function get_template_name( $instance ) {
		return ! empty( $instance['template'] ) ? $instance['template'] : 'default';
	}

	function get_template_variables( $instance, $args ) {
		if ( ! empty( $instance['image'] ) ) {
			$image_src = wp_get_attachment_image_src( $instance['image'] );
		}

		return array(
			'image_url' => ! empty( $image_src ) ? $image_src[0] : '',
			'testimonial' => $instance['text'],
			'has_url' => ! empty( $instance['url'] ),
			'url' => $instance['url'],
			'location' => $instance['location'],
			'new_window' => $instance['new_window'],
		);
	}

	function get_less_variables( $instance ) {
		$border_style = '1px solid #D0D0D0';
		if ( empty( $instance['design']['show_border'] ) ) {
			$border_style = 'none';
		}
		$box_shadow = '0 1px 2px rgba(0,0,0,0.1)';
		if ( empty($instance['design']['box_shadow'] ) ) {
			$box_shadow = 'none';
		}
		return array(
			'borders' => $border_style,
			'border_radius' => $instance['design']['border_radius'] . 'px',
			'background_color' => $instance['design']['background_color'],
			'box_shadow' => $box_shadow,
			'image_border_radius' => $instance['design']['image_border_radius'] . 'px',
			'image_alignment' => $instance['design']['image_alignment'],
			'image_height' => $instance['design']['image_height'] . 'px',
		);
	}
}

siteorigin_widget_register('testimonial', __FILE__);
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
						'style_one' => __( 'Style One', 'siteorigin-widgets' ),
						'style_two' => __( 'Style Two', 'siteorigin-widgets' ),
						'style_three' => __( 'Style Three', 'siteorigin-widgets' ),
					)
				),
				'testimonials_per_page' => array(
					'type' => 'slider',
					'label' => __( 'Testimonials per page', 'siteorigin-widgets' ),
					'min' => 1,
					'max' => 3,
					'integer' => true,
					'default' => 3
				),
				'transition_style' => array(
					'type' => 'select',
					'label' => __( 'Transition style', 'siteorigin-widgets' ),
					'options' => array(
						'fade' => __( 'Fade', 'siteorigin-widgets' ),
						'slide' => __( 'Carousel', 'siteorigin-widgets' ),
						'thumbnails' => __( 'Thumbnails', 'siteorigin-widgets' )
					)
				),
				'testimonials' => array(
					'type' => 'repeater',
					'label' => __( 'Testimonials', 'siteorigin-widgets' ),
					'item_name'  => __( 'Testimonial', 'siteorigin-widgets' ),
					'item_label' => array(
						'selector'     => "[id*='testimonials-name']",
						'update_event' => 'change',
						'value_method' => 'val'
					),
					'fields' => array(
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
				)
			)
		);
	}

	function enqueue_frontend_scripts( $instance ) {
		$js_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'flex-slider' , plugin_dir_url(SOW_BUNDLE_BASE_FILE). 'base/js/libs/jquery.flexslider' . $js_suffix . '.js' , array( 'jquery' ), '2.3.0' );
		wp_enqueue_script( 'sow-testimonial-widget', siteorigin_widget_get_plugin_dir_url( 'testimonial' ) . 'js/so-testimonial-widget' . $js_suffix . '.js', array( 'jquery', 'flex-slider', 'underscore', 'backbone' ), '');
		wp_localize_script( 'sow-testimonial-widget', 'sowTestimonialWidget', array(
			'testimonialTemplate' => file_get_contents( siteorigin_widget_get_plugin_dir_url( 'testimonial' ) . 'tpl/' . $instance['template'] . '.html' ),
			'testimonialsPerPage' => $instance['testimonials_per_page'],
			'transitionStyle' => $instance['transition_style'],
			'testimonials' => array_map( array( $this, 'toJSONTestimonial' ), $instance['testimonials'] )
		) );
	}

	function toJSONTestimonial( $testimonial ) {
		if ( isset( $testimonial['image'] ) && ! empty( $testimonial['image'] ) ) {
			$image_src = wp_get_attachment_image_src( $testimonial['image'] );
			if ( ! empty( $image_src ) ) {
				$testimonial['image'] = $image_src[0];
			}
		}
		return $this->underscores_to_camel_case( $testimonial );
	}

	function get_style_name( $instance ) {
		return $instance['template'];
	}

	function get_template_name( $instance ) {
		return 'testimonials';
	}

	function get_template_variables( $instance, $args ) {
		return array();
	}
}

siteorigin_widget_register('testimonial', __FILE__);
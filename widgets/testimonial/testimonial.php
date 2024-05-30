<?php
/*
Widget Name: Testimonials
Description: Feature testimonials from satisfied customers with tailored layouts, images, text, colors, and mobile compatibility.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/testimonials-widget/
*/

class SiteOrigin_Widgets_Testimonials_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'sow-testimonials',
			__( 'SiteOrigin Testimonials', 'so-widgets-bundle' ),
			array(
				'description' => __( 'Feature testimonials from satisfied customers with tailored layouts, images, text, colors, and mobile compatibility.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/testimonial-widget-documentation/',
			),
			array(
			),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	public function initialize() {
		$this->register_frontend_styles( array(
			array(
				'sow-testimonial',
				plugin_dir_url( __FILE__ ) . 'css/style.css',
			),
		) );
	}

	public function get_widget_form() {
		return array(
			'title' => array(
				'type' => 'text',
				'label' => __( 'Title', 'so-widgets-bundle' ),
			),
			'testimonials' => array(
				'type' => 'repeater',
				'label' => __( 'Testimonials', 'so-widgets-bundle' ),
				'item_name'  => __( 'Testimonial', 'so-widgets-bundle' ),
				'item_label' => array(
					'selector'     => "[id*='testimonials-name']",
					'update_event' => 'change',
					'value_method' => 'val',
				),
				'fields' => array(
					'name' => array(
						'type' => 'text',
						'label' => __( 'Name', 'so-widgets-bundle' ),
						'description' => __( 'The author of the testimonial', 'so-widgets-bundle' ),
					),

					'link_name' => array(
						'type' => 'checkbox',
						'label' => __( 'Link name', 'so-widgets-bundle' ),
					),

					'location' => array(
						'type' => 'text',
						'label' => __( 'Location', 'so-widgets-bundle' ),
						'description' => __( 'Their location or company name', 'so-widgets-bundle' ),
					),

					'image' => array(
						'type' => 'media',
						'label' => __( 'Image', 'so-widgets-bundle' ),
						'fallback' => true,
					),

					'link_image' => array(
						'type' => 'checkbox',
						'label' => __( 'Link image', 'so-widgets-bundle' ),
					),

					'text' => array(
						'type' => 'tinymce',
						'label' => __( 'Text', 'so-widgets-bundle' ),
						'description' => __( 'What your customer had to say', 'so-widgets-bundle' ),
					),

					'url' => array(
						'type' => 'text',
						'label' => __( 'URL', 'so-widgets-bundle' ),
					),

					'new_window' => array(
						'type' => 'checkbox',
						'label' => __( 'Open in a new window', 'so-widgets-bundle' ),
					),
				),
			),

			'settings' => array(
				'type' => 'section',
				'label' => __( 'Settings', 'so-widgets-bundle' ),
				'fields' => array(
					'per_line' => array(
						'type' => 'slider',
						'label' => __( 'Testimonials per row', 'so-widgets-bundle' ),
						'min' => 1,
						'max' => 5,
						'integer' => true,
						'default' => 3,
					),

					'responsive' => array(
						'type' => 'section',
						'label' => __( 'Responsive', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'tablet' => array(
								'type' => 'section',
								'label' => __( 'Tablet', 'so-widgets-bundle' ),
								'fields' => array(
									'per_line' => array(
										'type' => 'slider',
										'label' => __( 'Testimonials per row', 'so-widgets-bundle' ),
										'min' => 1,
										'max' => 5,
										'integer' => true,
										'default' => 2,
									),
									'image_size' => array(
										'type' => 'slider',
										'label' => __( 'Image size', 'so-widgets-bundle' ),
										'integer' => true,
										'default' => 50,
										'max' => 150,
										'min' => 20,
									),
									'width' => array(
										'type' => 'text',
										'label' => __( 'Resolution', 'so-widgets-bundle' ),
										'description' => __( 'The resolution to treat as a tablet resolution.', 'so-widgets-bundle' ),
										'default' => 800,
										'sanitize' => 'number',
									),
								),
							),
							'mobile' => array(
								'type' => 'section',
								'label' => __( 'Mobile Phone', 'so-widgets-bundle' ),
								'fields' => array(
									'per_line' => array(
										'type' => 'slider',
										'label' => __( 'Testimonials per row', 'so-widgets-bundle' ),
										'min' => 1,
										'max' => 5,
										'integer' => true,
										'default' => 1,
									),
									'image_size' => array(
										'type' => 'slider',
										'label' => __( 'Image size', 'so-widgets-bundle' ),
										'integer' => true,
										'default' => 50,
										'max' => 150,
										'min' => 20,
									),
									'width' => array(
										'type' => 'text',
										'label' => __( 'Resolution', 'so-widgets-bundle' ),
										'description' => __( 'The resolution to treat as a mobile resolution.', 'so-widgets-bundle' ),
										'default' => 480,
										'sanitize' => 'number',
									),
								),
							),
						),
					),
				),
			),

			'design' => array(
				'type' => 'section',
				'label' => __( 'Design', 'so-widgets-bundle' ),
				'fields' => array(
					'image' => array(
						'type' => 'section',
						'label' => __( 'Image', 'so-widgets-bundle' ),
						'fields' => array(
							'image_shape' => array(
								'type' => 'select',
								'label' => __( 'Image shape', 'so-widgets-bundle' ),
								'options' => array(
									'square' => __( 'Square', 'so-widgets-bundle' ),
									'round' => __( 'Round', 'so-widgets-bundle' ),
								),
								'default' => 'square',
							),

							'image_size' => array(
								'type' => 'slider',
								'label' => __( 'Image size', 'so-widgets-bundle' ),
								'integer' => true,
								'default' => 50,
								'max' => 150,
								'min' => 20,
							),
						),
					),

					'colors' => array(
						'type' => 'section',
						'label' => __( 'Colors', 'so-widgets-bundle' ),
						'fields' => array(
							'testimonial_background' => array(
								'type' => 'color',
								'label' => __( 'Widget background', 'so-widgets-bundle' ),
							),
							'text_background' => array(
								'type' => 'color',
								'label' => __( 'Text background', 'so-widgets-bundle' ),
								'default' => '#f0f0f0',
							),
							'text_color' => array(
								'type' => 'color',
								'label' => __( 'Text color', 'so-widgets-bundle' ),
								'default' => '#444444',
							),
						),
					),

					'padding' => array(
						'type' => 'slider',
						'label' => __( 'Padding', 'so-widgets-bundle' ),
						'integer' => true,
						'default' => 10,
						'max' => 100,
						'min' => 0,
					),

					'border_radius' => array(
						'type' => 'slider',
						'label' => __( 'Text background radius', 'so-widgets-bundle' ),
						'integer' => true,
						'default' => 4,
						'max' => 100,
						'min' => 0,
					),

					'user_position' => array(
						'type' => 'select',
						'label' => __( 'User position', 'so-widgets-bundle' ),
						'options' => array(
							'left' => __( 'Left', 'so-widgets-bundle' ),
							'right' => __( 'Right', 'so-widgets-bundle' ),
							'middle' => __( 'Middle', 'so-widgets-bundle' ),
						),
						'default' => 'left',
					),

					'layout' => array(
						'type' => 'select',
						'label' => __( 'Testimonial layout', 'so-widgets-bundle' ),
						'options' => array(
							'side' => __( 'Side by side', 'so-widgets-bundle' ),
							'text_above' => __( 'Text above user', 'so-widgets-bundle' ),
							'text_below' => __( 'Text below user', 'so-widgets-bundle' ),
						),
						'default' => 'side',
					),

					'equalize_testimonial_height' => array(
						'type' => 'checkbox',
						'label' => __( 'Equalize testimonial height', 'so-widgets-bundle' ),
					),
				),
			),
		);
	}

	public function get_less_variables( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		return array(
			'image_size' => (int) $instance['design']['image']['image_size'] . 'px',
			'testimonial_size' => round( 100 / $instance['settings']['per_line'], 4 ) . '%',
			'testimonial_padding' => (int) $instance['design']['padding'] . 'px',
			'testimonial_background' => $instance['design']['colors']['testimonial_background'],
			'equalize_testimonial_height' => ! empty( $instance['design']['equalize_testimonial_height'] ) ? 'true' : 'false',

			// The text block.
			'text_border_radius' => (int) $instance['design']['border_radius'] . 'px',
			'text_background' => $instance['design']['colors']['text_background'],
			'text_color' => $instance['design']['colors']['text_color'],

			// All the responsive sizes.
			'tablet_testimonial_size' => round( 100 / $instance['settings']['responsive']['tablet']['per_line'], 4 ) . '%',
			'tablet_image_size' => (int) $instance['settings']['responsive']['tablet']['image_size'] . 'px',
			'tablet_width' => (int) $instance['settings']['responsive']['tablet']['width'] . 'px',
			'mobile_testimonial_size' => round( 100 / $instance['settings']['responsive']['mobile']['per_line'], 4 ) . '%',
			'mobile_image_size' => (int) $instance['settings']['responsive']['mobile']['image_size'] . 'px',
			'mobile_width' => (int) $instance['settings']['responsive']['mobile']['width'] . 'px',
		);
	}

	public function get_template_variables( $instance, $args ) {
		return array(
			'testimonials' => ! empty( $instance['testimonials'] ) ? $instance['testimonials'] : array(),
			'settings' => $instance['settings'],
			'design' => $instance['design'],
		);
	}

	public function testimonial_user_image( $image_id, $design, $image_fallback = false ) {
		$src = siteorigin_widgets_get_attachment_image_src(
			$image_id,
			$design['image']['image_size'],
			! empty( $image_fallback ) ? $image_fallback : false
		);

		if ( ! empty( $src ) ) {
			if ( $design['image']['image_shape'] == 'square' ) {
				return '<img src="' . esc_url( $src[0] ) . '" class="sow-image-shape-' . $design['image']['image_shape'] . '">';
			} else {
				return '<div class="sow-round-image-frame" style="background-image: url( ' . esc_url( $src[0] ) . ' );"></div>';
			}
		}
	}

	public function testimonial_wrapper_class( $design ) {
		$classes = array();
		$classes[] = 'sow-user-' . sanitize_html_class( $design['user_position'] );
		$classes[] = 'sow-layout-' . sanitize_html_class( $design['layout'] );

		return str_replace( '_', '-', implode( ' ', $classes ) );
	}

	public function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return array(
			sprintf(
				__( 'Get more testimonial font customization options with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/testimonial" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
			sprintf(
				__( 'Use Google Fonts right inside the Testimonials Widget with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/web-font-selector" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
		);
	}
}

siteorigin_widget_register( 'sow-testimonials', __FILE__, 'SiteOrigin_Widgets_Testimonials_Widget' );

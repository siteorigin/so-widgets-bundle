<?php
/*
Widget Name: Testimonials
Description: Display some testimonials.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

class SiteOrigin_Widgets_Testimonials_Widget extends SiteOrigin_Widget {

	function __construct() {
		parent::__construct(
			'sow-testimonials',
			__('SiteOrigin Testimonials', 'so-widgets-bundle'),
			array(
				'description' => __('Share your product/service testimonials in a variety of different ways.', 'so-widgets-bundle'),
				'help' => 'https://siteorigin.com/widgets-bundle/testimonial-widget-documentation/'
			),
			array(

			),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	function initialize(){
		$this->register_frontend_styles( array(
			array(
				'sow-testimonial',
				plugin_dir_url(__FILE__) . 'css/style.css'
			)
		) );
	}

	function get_widget_form(){
		return array(
			'title' => array(
				'type' => 'text',
				'label' => __('Title', 'so-widgets-bundle'),
			),
			'testimonials' => array(
				'type' => 'repeater',
				'label' => __( 'Testimonials', 'so-widgets-bundle' ),
				'item_name'  => __( 'Testimonial', 'so-widgets-bundle' ),
				'item_label' => array(
					'selector'     => "[id*='testimonials-name']",
					'update_event' => 'change',
					'value_method' => 'val'
				),
				'fields' => array(
					'name' => array(
						'type' => 'text',
						'label' => __('Name', 'so-widgets-bundle'),
						'description' => __('The author of the testimonial', 'so-widgets-bundle'),
					),

					'link_name' => array(
						'type' => 'checkbox',
						'label' => __('Link name', 'so-widgets-bundle'),
					),

					'location' => array(
						'type' => 'text',
						'label' => __('Location', 'so-widgets-bundle'),
						'description' => __('Their location or company name', 'so-widgets-bundle'),
					),

					'image' => array(
						'type' => 'media',
						'label' => __('Image', 'so-widgets-bundle'),
					),

					'link_image' => array(
						'type' => 'checkbox',
						'label' => __('Link image', 'so-widgets-bundle'),
					),

					'text' => array(
						'type' => 'tinymce',
						'label' => __('Text', 'so-widgets-bundle'),
						'description' => __('What your customer had to say', 'so-widgets-bundle'),
					),

					'url' => array(
						'type' => 'text',
						'label' => __('URL', 'so-widgets-bundle'),
					),

					'new_window' => array(
						'type' => 'checkbox',
						'label' => __('Open In New Window', 'so-widgets-bundle'),
					),
				)
			),

			'settings' => array(
				'type' => 'section',
				'label' => __('Settings', 'so-widgets-bundle'),
				'fields' => array(

					'per_line' => array(
						'type' => 'slider',
						'label' => __( 'Testimonials per row', 'so-widgets-bundle' ),
						'min' => 1,
						'max' => 5,
						'integer' => true,
						'default' => 3
					),

					'responsive' => array(
						'type' => 'section',
						'label' => __('Responsive', 'so-widgets-bundle'),
						'hide' => true,
						'fields' => array(
							'tablet' => array(
								'type' => 'section',
								'label' => __('Tablet', 'so-widgets-bundle'),
								'fields' => array(
									'per_line' => array(
										'type' => 'slider',
										'label' => __( 'Testimonials per row', 'so-widgets-bundle' ),
										'min' => 1,
										'max' => 5,
										'integer' => true,
										'default' => 2
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
										'default' => 800,
										'sanitize' => 'intval',
									)
								)
							),
							'mobile' => array(
								'type' => 'section',
								'label' => __('Mobile Phone', 'so-widgets-bundle'),
								'fields' => array(
									'per_line' => array(
										'type' => 'slider',
										'label' => __( 'Testimonials per row', 'so-widgets-bundle' ),
										'min' => 1,
										'max' => 5,
										'integer' => true,
										'default' => 1
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
										'default' => 480,
										'sanitize' => 'intval',
									)
								)
							)

						)
					),
				)
			),

			'design' => array(
				'type' => 'section',
				'label' => __('Design', 'so-widgets-bundle'),
				'fields' => array(

					'image' => array(
						'type' => 'section',
						'label' => __('Image', 'so-widgets-bundle'),
						'fields' => array(
							'image_shape' => array(
								'type' => 'select',
								'label' => __('Testimonial image shape', 'so-widgets-bundle'),
								'options' => array(
									'square' => __('Square', 'so-widgets-bundle'),
									'round' => __('Round', 'so-widgets-bundle'),
								),
								'default' => 'square',
							),

							'image_size' => array(
								'type' => 'slider',
								'label' => __('Image size', 'so-widgets-bundle'),
								'integer' => true,
								'default' => 50,
								'max' => 150,
								'min' => 20,
							),
						),
					),

					'colors' => array(
						'type' => 'section',
						'label' => __('Colors', 'so-widgets-bundle'),
						'fields' => array(
							'testimonial_background' => array(
								'type' => 'color',
								'label' => __('Widget Background', 'so-widgets-bundle'),
							),
							'text_background' => array(
								'type' => 'color',
								'label' => __('Text Background', 'so-widgets-bundle'),
								'default' => '#f0f0f0',
							),
							'text_color' => array(
								'type' => 'color',
								'label' => __('Text Color', 'so-widgets-bundle'),
								'default' => '#444444',
							),
						),
					),

					'padding' => array(
						'type' => 'slider',
						'label' => __('Padding', 'so-widgets-bundle'),
						'integer' => true,
						'default' => 10,
						'max' => 100,
						'min' => 0,
					),

					'border_radius' => array(
						'type' => 'slider',
						'label' => __( 'Testimonial Radius', 'so-widgets-bundle' ),
						'integer' => true,
						'default' => 4,
						'max' => 100,
						'min' => 0,
					),

					'user_position' => array(
						'type' => 'select',
						'label' => __('User position', 'so-widgets-bundle'),
						'options' => array(
							'left' => __('Left', 'so-widgets-bundle'),
							'right' => __('Right', 'so-widgets-bundle'),
							'middle' => __('Middle', 'so-widgets-bundle'),
						),
						'default' => 'left',
					),

					'layout' => array(
						'type' => 'select',
						'label' => __('Testimonial layout', 'so-widgets-bundle'),
						'options' => array(
							'side' => __('Side by side', 'so-widgets-bundle'),
							'text_above' => __('Text above user', 'so-widgets-bundle'),
							'text_below' => __('Text below user', 'so-widgets-bundle'),
						),
						'default' => 'side',
					),
				),
			),
		);
	}

	function caret_svg(){
		static $done = false;
		if( $done ) return;

		?>
		<svg style="position: absolute; width: 0; height: 0;" width="0" height="0" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
			<defs>
				<symbol id="icon-caret-down" viewBox="0 0 585 1024">
					<title>caret-down</title>
					<path class="path1" d="M585.143 402.286q0 14.857-10.857 25.714l-256 256q-10.857 10.857-25.714 10.857t-25.714-10.857l-256-256q-10.857-10.857-10.857-25.714t10.857-25.714 25.714-10.857h512q14.857 0 25.714 10.857t10.857 25.714z"></path>
				</symbol>
				<symbol id="icon-caret-up" viewBox="0 0 585 1024">
					<title>caret-up</title>
					<path class="path1" d="M585.143 694.857q0 14.857-10.857 25.714t-25.714 10.857h-512q-14.857 0-25.714-10.857t-10.857-25.714 10.857-25.714l256-256q10.857-10.857 25.714-10.857t25.714 10.857l256 256q10.857 10.857 10.857 25.714z"></path>
				</symbol>
				<symbol id="icon-caret-left" viewBox="0 0 366 1024">
					<title>caret-left</title>
					<path class="path1" d="M365.714 256v512q0 14.857-10.857 25.714t-25.714 10.857-25.714-10.857l-256-256q-10.857-10.857-10.857-25.714t10.857-25.714l256-256q10.857-10.857 25.714-10.857t25.714 10.857 10.857 25.714z"></path>
				</symbol>
				<symbol id="icon-caret-right" viewBox="0 0 366 1024">
					<title>caret-right</title>
					<path class="path1" d="M329.143 512q0 14.857-10.857 25.714l-256 256q-10.857 10.857-25.714 10.857t-25.714-10.857-10.857-25.714v-512q0-14.857 10.857-25.714t25.714-10.857 25.714 10.857l256 256q10.857 10.857 10.857 25.714z"></path>
				</symbol>
			</defs>
		</svg>
		<?php
		$done = true;
	}

	function get_less_variables( $instance ){
		return array (
			'image_size' => intval($instance['design']['image']['image_size']) . 'px',
			'testimonial_size' => round(100/$instance['settings']['per_line'], 4) . '%',
			'testimonial_padding' => intval($instance['design']['padding']) . 'px',
			'testimonial_background' => $instance['design']['colors']['testimonial_background'],

			// The text block
			'text_border_radius' => intval($instance['design']['border_radius']) . 'px',
			'text_background' => $instance['design']['colors']['text_background'],
			'text_color' => $instance['design']['colors']['text_color'],

			// All the responsive sizes
			'tablet_testimonial_size' => round(100/$instance['settings']['responsive']['tablet']['per_line'], 4) . '%',
			'tablet_image_size' => intval( $instance['settings']['responsive']['tablet']['image_size'] ) . 'px',
			'tablet_width' => intval($instance['settings']['responsive']['tablet']['width']) . 'px',
			'mobile_testimonial_size' => round(100/$instance['settings']['responsive']['mobile']['per_line'], 4) . '%',
			'mobile_image_size' => intval( $instance['settings']['responsive']['mobile']['image_size'] ) . 'px',
			'mobile_width' => intval($instance['settings']['responsive']['mobile']['width']) . 'px',
		);
	}

	function get_template_variables( $instance, $args ){
		return array(
			'testimonials' => !empty($instance['testimonials']) ? $instance['testimonials'] : array(),
			'settings' => $instance['settings'],
			'design' => $instance['design'],
		);
	}

	function testimonial_user_image( $image_id, $design ){
		if ( ! empty( $image_id ) ) {
			if( $design['image']['image_shape'] == 'square') {
				return wp_get_attachment_image( $image_id, array( $design['image']['image_size'], $design['image']['image_size'] ), false, array(
					'class' => 'sow-image-shape-' . $design['image']['image_shape'],
				) );
			}
			else {
				$src = wp_get_attachment_image_src( $image_id, array( $design['image']['image_size'], $design['image']['image_size'] ) );
				return '<div class="sow-round-image-frame" style="background-image: url(' . esc_url( $src[0] ) . ');"></div>';
			}
		}
	}

	function testimonial_pointer( $design ){

	}

	function testimonial_wrapper_class($design){
		$classes = array();
		$classes[] = 'sow-user-' . sanitize_html_class( $design['user_position'] );
		$classes[] = 'sow-layout-' . sanitize_html_class( $design['layout'] );
		return str_replace( '_', '-', implode( ' ', $classes ) );
	}

}

siteorigin_widget_register( 'sow-testimonials', __FILE__, 'SiteOrigin_Widgets_Testimonials_Widget' );

<?php
/*
Widget Name: Post Carousel
Description: Gives you a widget to display your posts as a carousel.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/post-carousel-widget/
*/

/**
 * Add the carousel image sizes
 */
function sow_carousel_register_image_sizes(){
	add_image_size('sow-carousel-default', 272, 182, true);
}
add_action('init', 'sow_carousel_register_image_sizes');

function sow_carousel_get_next_posts_page() {
	if ( empty( $_REQUEST['_widgets_nonce'] ) || !wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) return;

	$template_vars = array();
	if ( ! empty( $_GET['instance_hash'] ) ) {
		$instance_hash = $_GET['instance_hash'];
		global $wp_widget_factory;
        /** @var SiteOrigin_Widget $widget */
		$widget = ! empty ( $wp_widget_factory->widgets['SiteOrigin_Widget_PostCarousel_Widget'] ) ?
            $wp_widget_factory->widgets['SiteOrigin_Widget_PostCarousel_Widget'] : null;
		if ( ! empty( $widget ) ) {
            $instance = $widget->get_stored_instance($instance_hash);
            $instance['paged'] = $_GET['paged'];
            $template_vars = $widget->get_template_variables($instance, array());
        }
	}
	ob_start();
	extract( $template_vars );
	include 'tpl/carousel-post-loop.php';
	$result = array( 'html' => ob_get_clean() );
	header('content-type: application/json');
	echo json_encode( $result );

	exit();
}
add_action( 'wp_ajax_sow_carousel_load', 'sow_carousel_get_next_posts_page' );
add_action( 'wp_ajax_nopriv_sow_carousel_load', 'sow_carousel_get_next_posts_page' );

class SiteOrigin_Widget_PostCarousel_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-post-carousel',
			__('SiteOrigin Post Carousel', 'so-widgets-bundle'),
			array(
				'description' => __('Gives you a widget to display your posts as a carousel.', 'so-widgets-bundle'),
				'instance_storage' => true,
				'help' => 'https://siteorigin.com/widgets-bundle/post-carousel-widget/'
			),
			array(

			),
			false ,
			plugin_dir_path(__FILE__)
		);
	}

	function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'slick',
					plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/lib/slick' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' ),
					'1.8.1'
				),
				array(
					'sow-carousel-basic',
					plugin_dir_url(__FILE__) . 'js/carousel' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery', 'slick' ),
					SOW_BUNDLE_VERSION,
					true
				)
			)
		);

		$this->register_frontend_styles(
			array(
				array(
					'sow-carousel-basic',
					plugin_dir_url(__FILE__) . 'css/style.css',
					array(),
					SOW_BUNDLE_VERSION
				),
				array(
					'slick',
					plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'css/lib/slick.css',
					array(),
					'1.8.1'
				)
			)
		);
	}

	private function get_breakpoints() {
		return apply_filters(
			'siteorigin_widgets_post_carousel_breakpoints',
			array(
				'tablet_landscape' => 1366,
				'tablet_portrait'  => 1025,
				'mobile'           => 480,
			)
		);
	}

	function get_widget_form(){
		$breakpoints = $this->get_breakpoints();

		return array(
			'title' => array(
				'type' => 'text',
				'label' => __('Title', 'so-widgets-bundle'),
			),

			'default_thumbnail' => array(
				'type'     => 'media',
				'library'  => 'image',
				'label'    => __( 'Default Thumbnail', 'so-widgets-bundle' ),
				'choose'   => __( 'Choose Thumbnail', 'so-widgets-bundle' ),
				'update'   => __( 'Set Thumbnail', 'so-widgets-bundle' ),
				'fallback' => true,
			),

			'image_size' => array(
				'type' => 'image-size',
				'label' => __('Featured Image size', 'so-widgets-bundle'),
				'default' => 'sow-carousel-default',
			),

			'link_target' => array(
				'type' => 'select',
				'label' => __( 'Link target', 'so-widgets-bundle' ),
				'description' => __( 'Choose where to open each carousel item.', 'so-widgets-bundle' ),
				'options' => array(
					'same'    => __( 'Same window ', 'so-widgets-bundle' ),
					'new'    => __( 'New window ', 'so-widgets-bundle' ),
				),
			),
			'loop_posts' => array(
				'type' => 'checkbox',
				'label' => __( 'Loop posts', 'so-widgets-bundle' ),
				'description' => __( 'Automatically return to the first post after the last post.', 'so-widgets-bundle' ),
				'default' => true,
			),

			'posts' => array(
				'type' => 'posts',
				'label' => __('Posts query', 'so-widgets-bundle'),
				'hide' => true,
				'fields' => array(
					'posts_per_page' => array(
						'label' => __( 'Posts per load', 'so-widgets-bundle' ),
						'description' => __( 'Set the number of posts preloaded in the background when clicking next. The default is 10.', 'so-widgets-bundle' ),
					),
				),
			),

			'design' => array(
				'type' => 'section',
				'label' => __( 'Design', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'thumbnail_overlay_hover_color' => array(
						'type' => 'color',
						'label' => __( 'Thumbnail overlay hover color', 'so-widgets-bundle' ),
						'default' => '#3279BB',
					),
					'navigation_color' => array(
						'type' => 'color',
						'label' => __( 'Navigation arrow color', 'so-widgets-bundle' ),
						'default' => '#fff',
					),
					'navigation_color_hover' => array(
						'type' => 'color',
						'label' => __( 'Navigation arrow hover color', 'so-widgets-bundle' ),
					),
					'navigation_background' => array(
						'type' => 'color',
						'label' => __( 'Navigation background', 'so-widgets-bundle' ),
						'default' => '#333',
					),
					'navigation_hover_background' => array(
						'type' => 'color',
						'label' => __( 'Navigation hover background', 'so-widgets-bundle' ),
						'default' => '#444',
					),
				),
			),
			'responsive' => array(
				'type' => 'section',
				'label' => __( 'Responsive', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'desktop' => array(
						'type' => 'section',
						'label' => __( 'Desktop', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'slides_to_scroll' => array(
								'type' => 'number',
								'label' => __( 'Slides to scroll', 'so-widgets-bundle' ),
								'description' => __( 'Set the number of slides to scroll per navigation click or swipe on desktop.', 'so-widgets-bundle' ),
								'default' => 1,
							),
						),
					),
					'tablet' => array(
						'type' => 'section',
						'label' => __( 'Tablet', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'landscape' => array(
								'type' => 'section',
								'label' => __( 'Landscape', 'so-widgets-bundle' ),
								'hide' => true,
								'fields' => array(
									'breakpoint' => array(
										'type' => 'number',
										'label' => __( 'Breakpoint', 'so-widgets-bundle' ),
										'default' => $breakpoints['tablet_landscape'],
									),
									'slides_to_scroll' => array(
										'type' => 'number',
										'label' => __( 'Slides to scroll', 'so-widgets-bundle' ),
										'description' => __( 'Set the number of slides to scroll per navigation click or swipe on tablet devices.', 'so-widgets-bundle' ),
										'default' => 2,
									),
								),
							),
							'portrait' => array(
								'type' => 'section',
								'label' => __( 'Portrait', 'so-widgets-bundle' ),
								'hide' => true,
								'fields' => array(
									'breakpoint' => array(
										'type' => 'number',
										'label' => __( 'Breakpoint', 'so-widgets-bundle' ),
										'default' => $breakpoints['tablet_portrait'],
									),
									'slides_to_scroll' => array(
										'type' => 'number',
										'label' => __( 'Slides to scroll', 'so-widgets-bundle' ),
										'description' => __( 'Set the number of slides to scroll per navigation click or swipe on tablet devices.', 'so-widgets-bundle' ),
										'default' => 2,
									),
								),
							),
						),
					),
					'mobile' => array(
						'type' => 'section',
						'label' => __( 'Mobile', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'breakpoint' => array(
								'type' => 'number',
								'label' => __( 'Breakpoint', 'so-widgets-bundle' ),
								'default' => $breakpoints['mobile'],
							),
							'slides_to_scroll' => array(
								'type' => 'number',
								'label' => __( 'Slides to scroll', 'so-widgets-bundle' ),
								'description' => __( ' Set the number of slides to scroll per navigation click or swipe on mobile devices.', 'so-widgets-bundle' ),
								'default' => 1,
							),
						),
					),
				),
			),
		);
	}

	function get_less_variables( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		$size = siteorigin_widgets_get_image_size( $instance['image_size'] );

		$thumb_width = '';
		$thumb_height = '';
		$thumb_hover_width = '';
		$thumb_hover_height = '';
		if ( ! ( empty( $size['width'] ) || empty( $size['height'] ) ) ) {
			$thumb_width = $size['width'] - $size['width'] * 0.1;
			$thumb_height = $size['height'] - $size['height'] * 0.1;
			$thumb_hover_width = $size['width'];
			$thumb_hover_height = $size['height'];
		}

		return array(
			'thumbnail_width' => $thumb_width . 'px',
			'thumbnail_height'=> $thumb_height . 'px',
			'thumbnail_hover_width' => $thumb_hover_width . 'px',
			'thumbnail_hover_height'=> $thumb_hover_height . 'px',
			'thumbnail_overlay_hover_color' => ! empty ( $instance['design']['thumbnail_overlay_hover_color'] ) ? $instance['design']['thumbnail_overlay_hover_color'] : '',
			'navigation_color' => ! empty ( $instance['design']['navigation_color'] ) ? $instance['design']['navigation_color'] : '',
			'navigation_color_hover' => ! empty ( $instance['design']['navigation_color_hover'] ) ? $instance['design']['navigation_color_hover'] : '',
			'navigation_background' => ! empty ( $instance['design']['navigation_background'] ) ? $instance['design']['navigation_background'] : '',
			'navigation_hover_background' => ! empty ( $instance['design']['navigation_hover_background'] ) ? $instance['design']['navigation_hover_background'] : '',
		);
	}

	public function get_template_variables( $instance, $args ) {
		if ( ! empty( $instance['default_thumbnail'] ) ) {
			$default_thumbnail = wp_get_attachment_image_src( $instance['default_thumbnail'], 'sow-carousel-default' );
		}

		$query = siteorigin_widget_post_selector_process_query( wp_parse_args(
			$instance['posts'],
			array(
				'paged' => empty( $instance['paged'] ) ? 1 : $instance['paged'],
			)
		) );
		$posts = new WP_Query( $query );

		$breakpoints = $this->get_breakpoints();
		$responsive_settings = array(
			'desktop_slides' => ! empty ( $instance['responsive']['desktop']['slides_to_scroll'] ) ? $instance['responsive']['desktop']['slides_to_scroll'] : 1,
			'tablet_portrait_slides' => ! empty ( $instance['responsive']['tablet']['portrait']['slides_to_scroll'] ) ? $instance['responsive']['tablet']['portrait']['slides_to_scroll'] : 2,
			'tablet_portrait_breakpoint' => ! empty ( $instance['responsive']['tablet']['portrait']['breakpoint'] ) ? $instance['responsive']['tablet']['portrait']['breakpoint'] : $breakpoints['tablet_portrait'],
			'tablet_landscape_slides' => ! empty ( $instance['responsive']['tablet']['landscape']['slides_to_scroll'] ) ? $instance['responsive']['tablet']['landscape']['slides_to_scroll'] : 2,
			'tablet_landscape_breakpoint' => ! empty ( $instance['responsive']['tablet']['landscape']['breakpoint'] ) ? $instance['responsive']['tablet']['landscape']['breakpoint'] : $breakpoints['tablet_landscape'],
			'mobile_breakpoint' => ! empty ( $instance['responsive']['mobile']['breakpoint'] ) ? $instance['responsive']['mobile']['breakpoint'] : $breakpoints['mobile'],
			'mobile_slides' => ! empty ( $instance['responsive']['mobile']['slides_to_scroll'] ) ? $instance['responsive']['mobile']['slides_to_scroll'] : 1,
		);

		return array(
			'title' => $instance['title'],
			'posts' => $posts,
			'default_thumbnail' => ! empty( $default_thumbnail ) ? $default_thumbnail[0] : '',
			'loop_posts' => ! empty( $instance['loop_posts'] ),
			'link_target' => ! empty( $instance['link_target'] ) ? $instance['link_target'] : 'same',
			'responsive_settings' => $responsive_settings,
		);
	}

	function get_template_name($instance){
		return 'base';
	}
}

siteorigin_widget_register('sow-post-carousel', __FILE__, 'SiteOrigin_Widget_PostCarousel_Widget');

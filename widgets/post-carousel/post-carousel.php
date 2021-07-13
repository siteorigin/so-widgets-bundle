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

/**
 * This function allows for users to limit the total number of posts.
 */
function sow_carousel_handle_post_limit( $posts, $paged = 0 ) {
	$post_limit = apply_filters( 'siteorigin_widgets_post_carousel_post_limit', false );

	if ( is_numeric( $post_limit ) && $posts->found_posts > $post_limit ) {
		$posts_per_page = $posts->query['posts_per_page'];
		$current = $posts_per_page * ( $paged - 1 );

		if ( $current < 0 ) {
			$current = $posts_per_page;
		}

		set_query_var( 'sow-total_posts', $post_limit - 1 );
		if ( $current >= $post_limit ) {
			// Check if we've exceeded the expected pagination.
			if ( $current + 1 > $post_limit + $posts_per_page ) {
				$posts->posts = null;
			} else {
				// Work out how many posts we need to return
				$posts->post_count = $post_limit % $posts_per_page;
				$posts->posts = array_slice( $posts->posts, $current % $posts_per_page, $posts->post_count );
			}
		}
	} else {
		set_query_var( 'sow-total_posts', $posts->found_posts );
	}

	return $posts;
}

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
			$instance = $widget->get_stored_instance( $instance_hash );
			$instance['paged'] = (int) $_GET['paged'];
			$template_vars = $widget->get_template_variables( $instance, array() );
			if ( ! empty( $template_vars ) ) {
				$settings = $template_vars['settings'];
			}

			$settings['posts'] = sow_carousel_handle_post_limit(
				$settings['posts'],
				$instance['paged']
			);
		}
	}

	// Don't output anything if there are no posts to return;
	if ( ! empty( $settings['posts']->posts ) ) {
		ob_start();
		include 'tpl/item.php';
		$result = array( 'html' => ob_get_clean() );
		header( 'content-type: application/json' );
		echo json_encode( $result );
	}

	exit();
}
add_action( 'wp_ajax_sow_carousel_load', 'sow_carousel_get_next_posts_page' );
add_action( 'wp_ajax_nopriv_sow_carousel_load', 'sow_carousel_get_next_posts_page' );

if ( ! class_exists( 'SiteOrigin_Widget_Base_Carousel' ) ) {
	include_once plugin_dir_path(SOW_BUNDLE_BASE_FILE) . '/base/inc/widgets/base-carousel.class.php';
}

class SiteOrigin_Widget_PostCarousel_Widget extends SiteOrigin_Widget_Base_Carousel {
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
		// Let the carousel base class do its initialization.
		parent::initialize();

		$this->register_frontend_scripts(
			array(
				array(
					'sow-post-carousel',
					plugin_dir_url( __FILE__ ) . 'js/script' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery', 'slick' ),
					SOW_BUNDLE_VERSION,
					true,
				),
			)
		);

		$this->register_frontend_styles(
			array(
				array(
					'sow-carousel-basic',
					plugin_dir_url( __FILE__ ) . 'css/style.css',
				),
			)
		);
	}

	function override_carousel_settings() {
		return array(
			'breakpoints' => apply_filters(
				'siteorigin_widgets_post_carousel_breakpoints',
				array(
					'tablet_landscape' => 1366,
					'tablet_portrait'  => 1025,
					'mobile'           => 480,
				)
			),
			'slides_to_scroll' => array(
				'desktop' => 1,
				'tablet_landscape' => 2,
				'tablet_portrait' => 2,
				'mobile' => 1,
			),
		);
	}

	function get_widget_form() {
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
					'thumbnail_overlay_hover_opacity' => array(
						'type' => 'slider',
						'label' => __( 'Thumbnail overlay hover opacity', 'so-widgets-bundle' ),
						'default' => '0.5',
						'min' => 0,
						'max' => 1,
						'step' => 0.1,
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
			'responsive' => $this->responsive_form_fields(),
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
			'thumbnail_overlay_hover_opacity' => ! empty ( $instance['design']['thumbnail_overlay_hover_opacity'] ) ? $instance['design']['thumbnail_overlay_hover_opacity'] : 0.5,
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

		return array(
			'settings' => array(
				'args' => $args,
				'title' => $instance['title'],
				'posts' => sow_carousel_handle_post_limit( $posts ),
				'default_thumbnail' => ! empty( $default_thumbnail ) ? $default_thumbnail[0] : '',
				'image_size' => $instance['image_size'],
				'link_target' => ! empty( $instance['link_target'] ) ? $instance['link_target'] : 'same',
				'item_template' => plugin_dir_path( __FILE__ ) . 'tpl/item.php',
				'navigation' => 'title',
				'attributes' => array(
					'widget' => 'post',
					'fetching' => 'false',
					'page' => 1,
					'ajax-url' => sow_esc_url( wp_nonce_url( admin_url('admin-ajax.php'), 'widgets_action', '_widgets_nonce' ) ),

					// Base carousel specific settings.
					'item_count' => get_query_var( 'sow-total_posts' ),
					'carousel_settings' => json_encode(
						array(
							'loop' => ! empty( $instance['loop_posts'] ),
						)
					),
					'responsive' => $this->responsive_template_variables( $instance['responsive'] ),
					'variable_width' => 'true',
				),
			),
		);
	}

	function get_template_name($instance){
		return 'base';
	}
}

siteorigin_widget_register('sow-post-carousel', __FILE__, 'SiteOrigin_Widget_PostCarousel_Widget');

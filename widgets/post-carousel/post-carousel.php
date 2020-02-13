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
				'description' => __('Display your posts as a carousel.', 'so-widgets-bundle'),
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
					'touch-swipe',
					plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/jquery.touchSwipe' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' ),
					'1.6.6'
				),
				array(
					'sow-carousel-basic',
					plugin_dir_url(__FILE__) . 'js/carousel' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery', 'touch-swipe' ),
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
				)
			)
		);
	}

	function get_widget_form(){
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
		);
	}

	public function get_template_variables( $instance, $args ) {
		if ( ! empty( $instance['default_thumbnail'] ) ) {
			$default_thumbnail = wp_get_attachment_image_src( $instance['default_thumbnail'], 'sow-carousel-default' );
		}

		$query = wp_parse_args(
			siteorigin_widget_post_selector_process_query( $instance['posts'] ),
			array(
				'paged' => empty( $instance['paged'] ) ? 1 : $instance['paged']
			)
		);
		$posts = new WP_Query( $query );

		return array(
			'title' => $instance['title'],
			'posts' => $posts,
			'default_thumbnail' => ! empty( $default_thumbnail ) ? $default_thumbnail[0] : '',
			'loop_posts' => ! empty( $instance['loop_posts'] ),
		);
	}

	function get_template_name($instance){
		return 'base';
	}
}

siteorigin_widget_register('sow-post-carousel', __FILE__, 'SiteOrigin_Widget_PostCarousel_Widget');

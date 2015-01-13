<?php
/*
Widget Name: Post carousel widget
Description: Gives you a widget to display your posts as a carousel.
Author: Greg Priday
Author URI: http://siteorigin.com
*/

/**
 * Add the carousel image sizes
 */
function sow_carousel_register_image_sizes(){
	add_image_size('sow-carousel-default', 272, 182, true);
}
add_action('init', 'sow_carousel_register_image_sizes');

class SiteOrigin_Widget_PostCarousel_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-post-carousel',
			__('SiteOrigin Post Carousel', 'siteorigin-widgets'),
			array(
				'description' => __('Display your posts as a carousel.', 'siteorigin-widgets'),
				'help' => 'http://siteorigin.com/widgets-bundle/'
			),
			array(

			),
			array(
				'title' => array(
					'type' => 'text',
					'label' => __('Title', 'siteorigin-widgets'),
				),

				'posts' => array(
					'type' => 'posts',
					'label' => __('Posts query', 'siteorigin-widgets'),
				),
			),
			plugin_dir_path(__FILE__).'../'
		);
	}

	function enqueue_frontend_scripts(){
		$js_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style('sow-carousel-basic', siteorigin_widget_get_plugin_dir_url('post-carousel') . 'css/style.css', array(), SOW_BUNDLE_VERSION);
		wp_enqueue_script('sow-carousel-basic', siteorigin_widget_get_plugin_dir_url('post-carousel') . 'js/carousel' . $js_suffix . '.js', array('jquery'), SOW_BUNDLE_VERSION);
	}

	function enqueue_admin_scripts() {
		$js_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'sow-carousel-basic', siteorigin_widget_get_plugin_dir_url( 'post-carousel' ) . 'js/carousel-admin' . $js_suffix . '.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
	}

	function get_template_name($instance){
		return 'base';
	}

	function get_style_name($instance){
		return false;
	}
}

siteorigin_widget_register('post-carousel', __FILE__);
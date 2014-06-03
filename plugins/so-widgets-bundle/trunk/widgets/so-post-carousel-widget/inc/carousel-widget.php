<?php

class SiteOrigin_Widget_PostCarousel_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-post-carousel',
			__('SiteOrigin Post Carousel', 'sow-carousel'),
			array(
				'description' => __('Display your posts as a carousel.', 'sow-carousel'),
				'help' => 'http://siteorigin.com/widgets-bundle/'
			),
			array(

			),
			array(
				'title' => array(
					'type' => 'text',
					'label' => __('Title', 'sow-carousel'),
				),

				'posts' => array(
					'type' => 'posts',
					'label' => __('Posts Query', 'sow-carousel'),
				),
			),
			plugin_dir_path(__FILE__).'../'
		);
	}

	function enqueue_frontend_scripts(){
		wp_enqueue_style('sow-carousel-basic', siteorigin_widget_get_plugin_dir_url('post-carousel') . 'css/style.css', array(), SOW_BUNDLE_VERSION);
		wp_enqueue_script('sow-carousel-basic', siteorigin_widget_get_plugin_dir_url('post-carousel') . 'js/carousel.js', array('jquery'), SOW_BUNDLE_VERSION);
	}

	function get_template_name($instance){
		return 'base';
	}

	function get_style_name($instance){
		return false;
	}
}
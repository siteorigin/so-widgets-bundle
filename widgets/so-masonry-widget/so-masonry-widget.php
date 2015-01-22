<?php

/*
Widget Name: Masonry widget
Description: A stunning, responsive masonry layout.
Author: SiteOrigin
Author URI: http://siteorigin.com
*/

class SiteOrigin_Widget_Masonry_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-masonry',
			__( 'SiteOrigin Masonry Layout', 'siteorigin-widgets' ),
			array(
				'description' => __('A stunning, responsive masonry layout.', 'siteorigin-widgets'),
				'help' => 'http://siteorigin.com/widgets-bundle/masonry-widget-documentation/'
			),
			array(
			),
			array(
				'title' => array(
					'type' => 'text',
					'label' => __( 'Title', 'siteorigin-widgets' ),
				),
				'posts_query' => array(
					'type' => 'posts',
					'label' => __( 'Posts query', 'siteorigin-widgets' )
				),
				'responsive' => array(
					'type' => 'checkbox',
					'label' => __( 'Responsive layout', 'siteorigin-widgets' ),
					'default' =>  true
				)
			),
			plugin_dir_path(__FILE__).'../'
		);
	}

	function get_style_name( $instance ) {
		return 'masonry';
	}

	function get_template_name( $instance ) {
		return 'masonry';
	}

	function get_template_variables( $instance , $args ) {
		$query = siteorigin_widget_post_selector_process_query( $instance['posts_query'] );
		return array(
			'responsive' => !empty( $instance['responsive'] ),
			'posts' => new WP_Query( $query )
		);
	}

	function enqueue_frontend_scripts(){
		$js_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'siteorigin-masonry' , plugin_dir_url(__FILE__) . '/js/jquery.masonry'.$js_suffix.'.js', array('jquery'), '2.1.07' );
		wp_enqueue_script( 'siteorigin-masonry-main' , plugin_dir_url(__FILE__) . '/js/main'.$js_suffix.'.js', array('jquery'), SOW_BUNDLE_VERSION );
		wp_localize_script( 'siteorigin-masonry-main', 'soMasonrySettings', array(
			'loader' => plugin_dir_url(__FILE__).'images/ajax-loader.gif'
		) );
	}
}

siteorigin_widget_register( 'masonry', __FILE__ );
include_once( 'inc/masonry_post_settings.php' );
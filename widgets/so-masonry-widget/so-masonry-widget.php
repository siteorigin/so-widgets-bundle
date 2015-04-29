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

	function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'siteorigin-masonry',
					plugin_dir_url( __FILE__ ) . '/js/jquery.masonry' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' ),
					'2.1.07'
				),
				array(
					'siteorigin-masonry-main',
					plugin_dir_url( __FILE__ ) . '/js/masonry' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' ),
					SOW_BUNDLE_VERSION
				)
			)
		);
		global $sow_meta_box_manager;
		$sow_meta_box_manager->append_to_form(
			$this->id_base,
			array(
				'brick_size' => array(
					'type' => 'select',
					'label' => __( 'Brick size', 'siteorigin-widgets' ),
					'default' => '11',
					'options' => array(
						'11' => __( '1 by 1', 'siteorigin-widgets' ),
						'12' => __( '1 by 2', 'siteorigin-widgets' ),
						'21' => __( '2 by 1', 'siteorigin-widgets' ),
						'22' => __( '2 by 2', 'siteorigin-widgets' ),
					)
				)
			)
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

	function get_brick_size( $post_id ){
		global $sow_meta_box_manager;
		$brick_size = $sow_meta_box_manager->get_widget_post_meta(
			$post_id,
			$this->id_base,
			'brick_size'
		);
		return !empty( $brick_size ) ? $brick_size : '11';
	}

	function enqueue_admin_scripts() {
		wp_enqueue_script( 'siteorigin-masonry', siteorigin_widget_get_plugin_dir_url( 'masonry' ) . 'js/masonry-admin' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
	}
}

siteorigin_widget_register( 'masonry', __FILE__ );

include_once plugin_dir_path( __FILE__ ) . 'inc/masonry_post_settings.php';
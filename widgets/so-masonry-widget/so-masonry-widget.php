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
				'posts' => array(
					'type' => 'repeater',
					'label' => __( 'Selected posts', 'siteorigin-widgets' ),
					'item_name' => __( 'Post', 'siteorigin-widgets' ),
					'item_label' => array(
						'selector'     => "[id*='posts-post_title']",
						'update_event' => 'change',
						'value_method' => 'val'
					),
					'readonly' => true,
					'scroll_count' => 10,
					'fields' => array(
						'post_title' => array(
							'type' => 'text',
							'label' => __( 'Post title', 'siteorigin-widgets' ),
							'readonly' => true
						),
						'post_id' => array(
							'type' => 'number',
							'label' => __( 'Post ID', 'siteorigin-widgets' ),
							'readonly' => true
						),
						//maybe just have an image field...?
//						'post_thumbnail' => array(
//							'type' => 'media',
//							'label' => __( 'Post thumbnail', 'siteorigin-widgets' ),
//							'editable' => false
//						),
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

	function get_brick_size($post_id, $instance){
		foreach ( $instance['posts'] as $post ) {
			if ( $post['post_id'] == $post_id ) {
				return $post['brick_size'];
			}
		}
		return '11';
	}

	function enqueue_frontend_scripts( $instance ){
		$js_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'siteorigin-masonry' , plugin_dir_url(__FILE__) . '/js/jquery.masonry'.$js_suffix.'.js', array('jquery'), '2.1.07' );
		wp_enqueue_script( 'siteorigin-masonry-main' , plugin_dir_url(__FILE__) . '/js/masonry'.$js_suffix.'.js', array('jquery'), SOW_BUNDLE_VERSION );
		wp_localize_script( 'siteorigin-masonry-main', 'soMasonrySettings', array(
			'loader' => plugin_dir_url(__FILE__).'images/ajax-loader.gif'
		) );
	}

	function enqueue_admin_scripts() {
		$js_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'siteorigin-masonry', siteorigin_widget_get_plugin_dir_url( 'masonry' ) . 'js/masonry-admin' . $js_suffix . '.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
	}
}

siteorigin_widget_register( 'masonry', __FILE__ );
include_once plugin_dir_path( __FILE__ ) . 'inc/masonry_post_settings.php';
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
				'post_type' => array(
					'type' => 'radio',
					'label' => __( 'Post type', 'siteorigin-widgets' ),
					'default' => 'post',
					'options' => array(
						'post' => __( 'Post', 'siteorigin-widgets' ),
						'page' => __( 'Page', 'siteorigin-widgets' )
					)
				),
				'posts_per_page' => array(
					'type' => 'number',
					'label' => __( 'Posts per page', 'siteorigin-widgets' )
				),
				'order_by' => array(
					'type' => 'select',
					'label' => 'Order by',
					'default' => 'none',
					'options' => array(
						'none' => __( 'None', 'siteorigin-widgets' ),
						'ID' => __( 'Post ID', 'siteorigin-widgets' ),
						'author' => __( 'Author', 'siteorigin-widgets' ),
						'name' => __( 'Name', 'siteorigin-widgets' ),
						'date' => __( 'Date', 'siteorigin-widgets' ),
						'modified' => __( 'Modified', 'siteorigin-widgets' ),
						'parent' => __( 'Parent', 'siteorigin-widgets' ),
						'rand' => __( 'Random', 'siteorigin-widgets' ),
						'comment_count' => __( 'Comment count', 'siteorigin-widgets' ),
						'menu_order' => __( 'Menu order', 'siteorigin-widgets' ),
					)
				),
				'order' => array(
					'type' => 'radio',
					'label' => __( 'Order', 'siteorigin-widgets' ),
					'default' => 'DESC',
					'options' => array(
						'DESC' => __( 'Descending', 'siteorigin-widgets' ),
						'ASC' => __( 'Ascending', 'siteorigin-widgets' ),
					)
				),
				'sticky_posts' => array(
					'type' => 'radio',
					'label' => __( 'Sticky posts', 'siteorigin-widgets' ),
					'default' => 'default',
					'options' => array(
						'default' => __( 'Default', 'siteorigin-widgets' ),
						'ignore' => __( 'Ignore sticky', 'siteorigin-widgets' ),
						'exclude' => __( 'Exclude sticky', 'siteorigin-widgets' ),
						'only' => __( 'Only sticky', 'siteorigin-widgets' ),
					)
				),
				'post_category' => array(
					'type' => 'select',
					'label' => __( 'Post category', 'siteorigin-widgets' ),
					'options' => array(
						'default' => __( 'Default', 'siteorigin-widgets' ),
						//append get_categories() in modify_form
					)
				),
				'additional' => array(
					'type' => 'text',
					'label' => __( 'Additional', 'siteorigin-widgets' ),
					'description' => __( 'Additional query arguments. See <a href="http://codex.wordpress.org/Function_Reference/query_posts" target="_blank">query posts</a>.')
				),
				'responsive' => array(
					'type' => 'checkbox',
					'label' => __( 'Responsive layout', 'siteorigin-widgets' ),
					'default' => true
				),
			)
		);
	}

	function get_style_name( $instance ) {
		return '';
	}

	function get_template_name( $instance ) {
		return 'base';
	}
}

siteorigin_widget_register( 'masonry', __FILE__ );
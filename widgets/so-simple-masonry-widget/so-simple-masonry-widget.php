<?php
/*
Widget Name: Simple Masonry Layout
Description: A masonry layout for images or posts.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

class SiteOrigin_Widget_Simple_Masonry_Widget extends SiteOrigin_Widget {
	function __construct() {

		parent::__construct(
			'sow-simple-masonry',
			__('SiteOrigin Simple Masonry', 'so-widgets-bundle'),
			array(
				'description' => __('A simple masonry layout widget.', 'so-widgets-bundle'),
//				'help' => 'https://siteorigin.com/widgets-bundle/simple-masonry-widget-documentation/'
			),
			array(),
			array(
				'widget_title' => array(
					'type' => 'text',
					'label' => __('Widget Title', 'so-widgets-bundle'),
				),
				'items' => array(
					'type' => 'repeater',
					'label' => __( 'Images', 'so-widgets-bundle' ),
					'item_label' => array(
						'selector'     => "[id*='title']"
					),
					'fields' => array(
						'image' => array(
							'type' => 'media',
							'label' => __( 'Image', 'so-widgets-bundle')
						),
						'column_span' => array(
							'type' => 'number',
							'label' => __( 'Column span', 'so-widgets-bundle' ),
							'description' => __( 'Number of columns this item should span. (Limited to number of columns selected in Layout section below.)', 'so-widgets-bundle' ),
							'default' => 1
						),
						'row_span' => array(
							'type' => 'number',
							'label' => __( 'Row span', 'so-widgets-bundle' ),
							'description' => __( 'Number of rows this item should span. (Limited to number of columns selected in Layout section below.)', 'so-widgets-bundle' ),
							'default' => 1
						),
						'title' => array(
							'type' => 'text',
							'label' => __('Title', 'so-widgets-bundle'),
						),
						'url' => array(
							'type' => 'link',
							'label' => __('Destination URL', 'so-widgets-bundle'),
						),
						'new_window' => array(
							'type' => 'checkbox',
							'default' => false,
							'label' => __('Open in a new window', 'so-widgets-bundle'),
						),
					)
				),
				'desktop_layout' => array(
					'type' => 'section',
					'label' => __( 'Layout', 'so-widgets-bundle' ),
					'fields' => array(
						'columns' => array(
							'type' => 'slider',
							'label' => __( 'Number of columns', 'so-widgets-bundle' ),
							'min' => 1,
							'max' => 10,
							'default' => 4
						),
						'row_height' => array(
							'type' => 'number',
							'label' => __( 'Row height', 'so-widgets-bundle' ),
							'description' => __( 'Leave blank to match calculated column width.', 'so-widgets-bundle' ),
							'default' => 0
						),
						'gutter' => array(
							'type' => 'number',
							'label' => __( 'Gutter', 'so-widgets-bundle'),
							'description' => __( 'Space between masonry items.', 'so-widgets-bundle' ),
							'default' => 0
						)
//						'randomize' => array(
//							'type' => 'checkbox',
//							'label' => __( 'Randomize item sizes', 'so-widgets-bundle' ),
//							'description' => __( 'Causes column and row spans to be ignored.', 'so-widgets-bundle' )
//						),
					)
				)

			),
			plugin_dir_path(__FILE__)
		);

	}

	function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'dessandro-packery',
					siteorigin_widget_get_plugin_dir_url( 'sow-simple-masonry' ) . 'js/packery.pkgd' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' ),
					SOW_BUNDLE_VERSION
				),
				array(
					'sow-simple-masonry',
					siteorigin_widget_get_plugin_dir_url( 'sow-simple-masonry' ) . 'js/simple-masonry' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery', 'dessandro-packery' ),
					SOW_BUNDLE_VERSION . '1'
				),
			)
		);
	}

	function get_template_name($instance) {
		return 'simple-masonry';
	}

	function get_style_name($instance) {
		return 'simple-masonry';
	}

	public function get_template_variables( $instance, $args ) {
		return array(
			'packery_settings' => array(
				'num_columns' => $instance['desktop_layout']['columns'],
				'row_height' => empty( $instance['desktop_layout']['row_height'] ) ? 0 : intval( $instance['desktop_layout']['row_height'] ),
				'gutter' => empty( $instance['desktop_layout']['gutter'] ) ? 0 : intval( $instance['desktop_layout']['gutter'] ),
			)
		);
	}
}

siteorigin_widget_register('sow-simple-masonry', __FILE__, 'SiteOrigin_Widget_Simple_Masonry_Widget');
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
			__('SiteOrigin Simple Masonry', 'siteorigin-widgets'),
			array(
				'description' => __('A simple masonry layout widget.', 'siteorigin-widgets'),
//				'help' => 'https://siteorigin.com/widgets-bundle/simple-masonry-widget-documentation/'
			),
			array(

			),
			array(

				'widget_title' => array(
					'type' => 'text',
					'label' => __('Widget Title', 'siteorigin-widgets'),
				),

				'items' => array(
					'type' => 'repeater',
					'label' => __( 'Images', 'siteorigin-widgets' ),
					'item_label' => array(
						'selector'     => "[id*='title']"
					),
					'fields' => array(
						'image' => array(
							'type' => 'media',
							'label' => __( 'Image', 'siteorigin-widgets')
						),
						'column_span' => array(
							'type' => 'number',
							'label' => __( 'Column span', 'siteorigin-widgets' ),
							'description' => __( 'Number of columns this item should span. (Limited to number of columns selected in Layout section below.)', 'siteorigin-widgets' ),
							'default' => 1
						),
						'row_span' => array(
							'type' => 'number',
							'label' => __( 'Row span', 'siteorigin-widgets' ),
							'description' => __( 'Number of rows this item should span. (Limited to number of columns selected in Layout section below.)', 'siteorigin-widgets' ),
							'default' => 1
						),
						'title' => array(
							'type' => 'text',
							'label' => __('Title', 'siteorigin-widgets'),
						),
						'url' => array(
							'type' => 'link',
							'label' => __('Destination URL', 'siteorigin-widgets'),
						),
						'new_window' => array(
							'type' => 'checkbox',
							'default' => false,
							'label' => __('Open in a new window', 'siteorigin-widgets'),
						),
					)
				),

				'layout' => array(
					'type' => 'section',
					'label' => __( 'Layout', 'siteorigin-widgets' ),
					'fields' => array(
						'columns' => array(
							'type' => 'slider',
							'label' => __( 'Number of columns', 'siteorigin-widgets' ),
							'min' => 1,
							'max' => 10,
							'default' => 4
						),
						'row_height' => array(
							'type' => 'number',
							'label' => __( 'Row height', 'siteorigin-widgets' ),
							'description' => __( 'Leave blank to match calculated column width.', 'siteorigin-widgets' ),
							'default' => 0
						)
//						'randomize' => array(
//							'type' => 'checkbox',
//							'label' => __( 'Randomize item sizes', 'siteorigin-widgets' ),
//							'description' => __( 'Causes column and row spans to be ignored.', 'siteorigin-widgets' )
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

	/**
	 * Get the variables that we'll be injecting into the less stylesheet.
	 *
	 * @param $instance
	 *
	 * @return array
	 */
	function get_less_variables($instance){
		$cols = empty( $instance['layout']['columns'] ) ? 4 : $instance['layout']['columns'];
		return array(
			'num_columns' => $cols
		);
	}
}

siteorigin_widget_register('sow-simple-masonry', __FILE__, 'SiteOrigin_Widget_Simple_Masonry_Widget');
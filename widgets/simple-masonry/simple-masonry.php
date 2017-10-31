<?php
/*
Widget Name: Simple Masonry Layout
Description: A masonry layout for images. Images can link to your posts.
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
			false,
			plugin_dir_path(__FILE__)
		);

	}

	function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'sow-simple-masonry',
					siteorigin_widget_get_plugin_dir_url( 'sow-simple-masonry' ) . 'js/simple-masonry' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery', 'dessandro-imagesLoaded', 'dessandro-packery' ),
					SOW_BUNDLE_VERSION
				),
			)
		);
	}

	function get_widget_form(){
		return array(
			'widget_title' => array(
				'type' => 'text',
				'label' => __('Title', 'so-widgets-bundle'),
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
						'type' => 'slider',
						'label' => __( 'Column span', 'so-widgets-bundle' ),
						'description' => __( 'Number of columns this item should span. (Limited to number of columns selected in Layout section below.)', 'so-widgets-bundle' ),
						'min' => 1,
						'max' => 10,
						'default' => 1
					),
					'row_span' => array(
						'type' => 'slider',
						'label' => __( 'Row span', 'so-widgets-bundle' ),
						'description' => __( 'Number of rows this item should span. (Limited to number of columns selected in Layout section below.)', 'so-widgets-bundle' ),
						'min' => 1,
						'max' => 10,
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
				'label' => __( 'Desktop Layout', 'so-widgets-bundle' ),
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
				)
			),
			'tablet_layout' => array(
				'type' => 'section',
				'label' => __( 'Tablet Layout', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'break_point' => array(
						'type' => 'number',
						'lanel' => __( 'Break point', 'so-widgets-bundle' ),
						'default' => 768
					),
					'columns' => array(
						'type' => 'slider',
						'label' => __( 'Number of columns', 'so-widgets-bundle' ),
						'min' => 1,
						'max' => 10,
						'default' => 2
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
				)
			),
			'mobile_layout' => array(
				'type' => 'section',
				'label' => __( 'Mobile Layout', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'break_point' => array(
						'type' => 'number',
						'lanel' => __( 'Break point', 'so-widgets-bundle' ),
						'default' => 480
					),
					'columns' => array(
						'type' => 'slider',
						'label' => __( 'Number of columns', 'so-widgets-bundle' ),
						'min' => 1,
						'max' => 10,
						'default' => 1
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
				)
			)
		);
	}

	public function get_template_variables( $instance, $args ) {
		$items = isset( $instance['items'] ) ? $instance['items'] : array();
		
		foreach ( $items as &$item ) {
			$link_atts = empty( $item['link_attributes'] ) ? array() : $item['link_attributes'];
			if ( ! empty( $item['new_window'] ) ) {
				$link_atts['target'] = '_blank';
			}
			$item['link_attributes'] = $link_atts;
		}
		
		return array(
			'args' => $args,
			'items' => $items,
			'layouts' => array(
				'desktop' => siteorigin_widgets_underscores_to_camel_case(
					array(
						'num_columns' => $instance['desktop_layout']['columns'],
						'row_height' => empty( $instance['desktop_layout']['row_height'] ) ? 0 : intval( $instance['desktop_layout']['row_height'] ),
						'gutter' => empty( $instance['desktop_layout']['gutter'] ) ? 0 : intval( $instance['desktop_layout']['gutter'] ),
					)
				),
				'tablet' => siteorigin_widgets_underscores_to_camel_case(
					array(
						'break_point' => $instance['tablet_layout']['break_point'],
						'num_columns' => $instance['tablet_layout']['columns'],
						'row_height' => empty( $instance['tablet_layout']['row_height'] ) ? 0 : intval( $instance['tablet_layout']['row_height'] ),
						'gutter' => empty( $instance['tablet_layout']['gutter'] ) ? 0 : intval( $instance['tablet_layout']['gutter'] ),
					)
				),
				'mobile' => siteorigin_widgets_underscores_to_camel_case(
					array(
						'break_point' => $instance['mobile_layout']['break_point'],
						'num_columns' => $instance['mobile_layout']['columns'],
						'row_height' => empty( $instance['mobile_layout']['row_height'] ) ? 0 : intval( $instance['mobile_layout']['row_height'] ),
						'gutter' => empty( $instance['mobile_layout']['gutter'] ) ? 0 : intval( $instance['mobile_layout']['gutter'] ),
					)
				),
			)
		);
	}

	function get_form_teaser(){
		if( class_exists( 'SiteOrigin_Premium' ) ) return false;

		return sprintf(
			__( 'Add a Lightbox to your masonry images with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
			'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/lightbox" target="_blank">',
			'</a>'
		);
	}
}

siteorigin_widget_register('sow-simple-masonry', __FILE__, 'SiteOrigin_Widget_Simple_Masonry_Widget');

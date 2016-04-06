<?php
/*
Widget Name: Image Grid
Description: Display a grid of images. Also useful for displaying client logos.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

class SiteOrigin_Widgets_ImageGrid_Widget extends SiteOrigin_Widget {

	function __construct(){

		parent::__construct(
			'sow-image-grid',
			__('SiteOrigin Image Grid', 'so-widgets-bundle'),
			array(
				'description' => __('Display a grid of images.', 'so-widgets-bundle'),
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	/**
	 * Initialize the image grid, mainly to add scripts and styles.
	 */
	function initialize(){
		$this->register_frontend_styles( array(
			array(
				'sow-image-grid',
				plugin_dir_url(__FILE__) . 'css/image-grid.css',
			)
		) );

		$this->register_frontend_scripts( array(
			array(
				'sow-image-grid',
				plugin_dir_url(__FILE__) . 'js/image-grid' . SOW_BUNDLE_JS_SUFFIX . '.js',
				array( 'jquery' )
			)
		) );
	}

	function initialize_form(){
		$intermediate = get_intermediate_image_sizes();
		$sizes = array();
		foreach( $intermediate as $name ) {
			$sizes[$name] = ucwords(preg_replace('/[-_]/', ' ', $name));
		}
		$sizes = array_merge( array( 'full' => __('Full', 'so-widgets-bundle') ), $sizes );

		return array(

			'images' => array(
				'type' => 'repeater',
				'label' => __('Images', 'so-widgets-bundle'),
				'item_name'  => __( 'Image', 'so-widgets-bundle' ),
				'item_label' => array(
					'selector'     => "[name*='title']",
					'update_event' => 'change',
					'value_method' => 'val'
				),
				'fields' => array(
					'image' => array(
						'type' => 'media',
						'label' => __('Image', 'so-widgets-bundle')
					),
					'title' => array(
						'type' => 'text',
						'label' => __('Image title', 'so-widgets-bundle')
					),
					'url' => array(
						'type' => 'link',
						'label' => __('URL', 'so-widgets-bundle')
					),
				)
			),

			'display' => array(
				'type' => 'section',
				'label' => __('Display', 'so-widgets-bundle'),
				'fields' => array(
					'attachment_size' => array(
						'label' => __('Image size', 'so-widgets-bundle'),
						'type' => 'select',
						'options' => $sizes,
						'default' => 'full',
					),

					'max_height' => array(
						'label' => __('Maximum image height', 'so-widgets-bundle'),
						'type' => 'number',
					),

					'max_width' => array(
						'label' => __('Maximum image width', 'so-widgets-bundle'),
						'type' => 'number',
					),

					'spacing' => array(
						'label' => __('Spacing', 'so-widgets-bundle'),
						'description' => __('Amount of spacing between images.', 'so-widgets-bundle'),
						'type' => 'number',
						'default' => 10,
					),
				)
			)
		);
	}

	/**
	 * Get the less variables for the image grid
	 *
	 * @param $instance
	 *
	 * @return mixed
	 */
	function get_less_variables( $instance ) {
		$less = array();
		if( !empty( $instance['display']['spacing'] ) ) {
			$less['spacing'] = intval($instance['display']['spacing']) . 'px';
		}

		return $less;
	}
}

siteorigin_widget_register( 'sow-image-grid', __FILE__, 'SiteOrigin_Widgets_ImageGrid_Widget' );

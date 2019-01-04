<?php
/*
Widget Name: Image Grid
Description: Display a grid of images. Also useful for displaying client logos.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/image-grid/
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
				plugin_dir_url( __FILE__ ) . 'css/image-grid.css',
			)
		) );

		$this->register_frontend_scripts( array(
			array(
				'sow-image-grid',
				plugin_dir_url( __FILE__ ) . 'js/image-grid' . SOW_BUNDLE_JS_SUFFIX . '.js',
				array( 'jquery', 'dessandro-imagesLoaded' ),
				SOW_BUNDLE_VERSION,
				true,
			)
		) );
	}

	function get_widget_form(){

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
					'new_window' => array(
						'type' => 'checkbox',
						'default' => false,
						'label' => __( 'Open in new window', 'so-widgets-bundle' ),
					),
				)
			),

			'display' => array(
				'type' => 'section',
				'label' => __('Display', 'so-widgets-bundle'),
				'fields' => array(
					'attachment_size' => array(
						'label' => __('Image size', 'so-widgets-bundle'),
						'type' => 'image-size',
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
	
	function get_template_variables( $instance, $args ) {
		$images = isset( $instance['images'] ) ? $instance['images'] : array();
		
		foreach ( $images as &$image ) {
			$link_atts = empty( $image['link_attributes'] ) ? array() : $image['link_attributes'];
			if ( ! empty( $image['new_window'] ) ) {
				$link_atts['target'] = '_blank';
				$link_atts['rel'] = 'noopener noreferrer';
			}
			$image['link_attributes'] = $link_atts;
		}
		
		return array(
			'images' => $images,
			'max_height' => $instance['display']['max_height'],
			'max_width' => $instance['display']['max_width'],
			'attachment_size' => $instance['display']['attachment_size'],
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
		if( isset( $instance['display']['spacing'] ) ) {
			$less['spacing'] = intval($instance['display']['spacing']) . 'px';
		}

		return $less;
	}

	function get_form_teaser(){
		if( class_exists( 'SiteOrigin_Premium' ) ) return false;

		return sprintf(
			__( 'Add a Lightbox to your images with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
			'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/lightbox" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);
	}
}

siteorigin_widget_register( 'sow-image-grid', __FILE__, 'SiteOrigin_Widgets_ImageGrid_Widget' );

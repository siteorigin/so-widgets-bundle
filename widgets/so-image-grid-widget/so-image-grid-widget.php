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
			__('SiteOrigin Image Grid', 'siteorigin-widgets'),
			array(
				'description' => __('Display a grid of images.', 'siteorigin-widgets'),
			),
			array(),
			array(

				'images' => array(
					'type' => 'repeater',
					'label' => __('Images', 'siteorigin-widgets'),
					'item_name'  => __( 'Image', 'siteorigin-widgets' ),
					'item_label' => array(
						'selector'     => "[name*='title']",
						'update_event' => 'change',
						'value_method' => 'val'
					),
					'fields' => array(
						'image' => array(
							'type' => 'media',
							'label' => __('Image', 'siteorigin-widgets')
						),
						'title' => array(
							'type' => 'text',
							'label' => __('Image title', 'siteorigin-widgets')
						),
						'url' => array(
							'type' => 'text',
							'sanitize' => 'url',
							'label' => __('URL', 'siteorigin-widgets')
						),
					)
				),

				'display' => array(
					'type' => 'section',
					'label' => __('Display', 'siteorigin-widgets'),
					'fields' => array(
						'attachment_size' => array(
							'label' => __('Image size', 'siteorigin-widgets'),
							'type' => 'select',
							'options' => array(),
							'default' => 'full',
						),

						'max_height' => array(
							'label' => __('Maximum image height', 'siteorigin-widgets'),
							'type' => 'number',
						),

						'max_width' => array(
							'label' => __('Maximum image width', 'siteorigin-widgets'),
							'type' => 'number',
						),

						'spacing' => array(
							'label' => __('Spacing', 'siteorigin-widgets'),
							'description' => __('Amount of spacing between images.', 'siteorigin-widgets'),
							'type' => 'number',
							'default' => 10,
						),
					)
				)

			)
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

	/**
	 * Modify the form widget
	 *
	 * @param $form
	 *
	 * @return mixed
	 */
	function modify_form( $form ){
		$intermediate = get_intermediate_image_sizes();
		foreach( $intermediate as $id => $name ) {
			$intermediate[$id] = ucwords(str_replace('-', ' ', $name));
		}
		$sizes = array_merge( array( 'full' => __('Full', 'siteorigin-widgets') ), $intermediate );
		$form['display']['fields']['attachment_size']['options'] = $sizes;
		return $form;
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
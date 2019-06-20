<?php
/*
Widget Name: Image Grid
Description: Display a grid of images. Also useful for displaying client logos.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/image-grid/
*/

class SiteOrigin_Widgets_ImageGrid_Widget extends SiteOrigin_Widget {
	
	/**
	 * @var int This is used to indicate that the widget's LESS styles have changed and the CSS needs to be recompiled.
	 */
	protected $version = 2;

	function __construct() {

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
	function initialize() {
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

	function get_widget_form() {

		return array(

			'images' => array(
				'type' => 'repeater',
				'label' => __( 'Images', 'so-widgets-bundle' ),
				'item_name'  => __( 'Image', 'so-widgets-bundle' ),
				'item_label' => array(
					'selector'     => "[name*='title']",
					'update_event' => 'change',
					'value_method' => 'val'
				),
				'fields' => array(
					'image' => array(
						'type' => 'media',
						'label' => __( 'Image', 'so-widgets-bundle' ),
						'library' => 'image',
						'fallback' => true,
					),
					'title' => array(
						'type' => 'text',
						'label' => __( 'Image title', 'so-widgets-bundle' )
					),
					'alt' => array(
						'type' => 'text',
						'label' => __( 'Alt text', 'so-widgets-bundle' ),
					),
					'url' => array(
						'type' => 'link',
						'label' => __( 'URL', 'so-widgets-bundle' )
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
				'label' => __( 'Display', 'so-widgets-bundle' ),
				'fields' => array(
					'attachment_size' => array(
						'label' => __( 'Image size', 'so-widgets-bundle' ),
						'type' => 'image-size',
						'default' => 'full',
					),

					'max_height' => array(
						'label' => __( 'Maximum image height', 'so-widgets-bundle' ),
						'type' => 'number',
					),

					'max_width' => array(
						'label' => __( 'Maximum image width', 'so-widgets-bundle' ),
						'type' => 'number',
					),

					'spacing' => array(
						'label' => __( 'Spacing', 'so-widgets-bundle' ),
						'description' => __( 'Amount of spacing between images.', 'so-widgets-bundle' ),
						'type' => 'measurement',
						'default' => '10px',
					),
				)
			)
		);
	}
	
	function get_template_variables( $instance, $args ) {
		$images = isset( $instance['images'] ) ? $instance['images'] : array();
		
		foreach ( $images as $id => &$image ) {
			if ( empty( $image['image'] ) && empty( $image['image_fallback'] ) ) {
				unset( $images[$id] );
				continue;
			}

			$link_atts = empty( $image['link_attributes'] ) ? array() : $image['link_attributes'];
			if ( ! empty( $image['new_window'] ) ) {
				$link_atts['target'] = '_blank';
				$link_atts['rel'] = 'noopener noreferrer';
			}
			$image['link_attributes'] = $link_atts;

			$title = $this->get_image_title($image);

			if ( empty( $image['image'] ) && ! empty( $image['image_fallback'] ) ) {
				$alt = ! empty ( $image['alt'] ) ? $image['alt'] .'"' : '';
				$image['image_html'] = '<img src="'. esc_url( $image['image_fallback'] ) .'" alt="'. esc_attr( $alt ) .'" title="'. esc_attr( $title ) .'">';
			} else {
				$image['image_html'] = wp_get_attachment_image( $image['image'], $instance['display']['attachment_size'], false, array(
					'title' => $title,
					'alt'   => $image['alt'],
				) );
			}
		}
		
		return array(
			'images' => $images,
			'max_height' => $instance['display']['max_height'],
			'max_width' => $instance['display']['max_width'],
		);
	}

	/**
	 * Try to figure out an image's title for display.
	 *
	 * @param $image
	 *
	 * @return string The title of the image.
	 */
	private function get_image_title($image) {
		if ( ! empty( $image['title'] ) ) {
			$title = $image['title'];
		} else {
			// We do not want to use the default image titles as they're based on the file name without the extension
			$file_name = pathinfo( get_post_meta( $image['image'], '_wp_attached_file', true ), PATHINFO_FILENAME );
			$title = get_the_title( $image['image'] );
			if ( $title == $file_name ) {
				$title = '';
			}
		}

		return $title;
	}
	
	function modify_instance( $instance ) {
		if ( ! empty( $instance['display'] ) ) {
			// Revert changes to `max_width` and `max_height` back to `number` fields.
			if ( ! empty( $instance['display']['max_height'] ) ) {
				$instance['display']['max_height'] = intval( $instance['display']['max_height'] );
			}

			if ( ! empty( $instance['display']['max_width'] ) ) {
				$instance['display']['max_width'] = intval( $instance['display']['max_width'] );
			}

			// Input for `spacing` changed from `number` to `measurement` field.
			if ( is_numeric( $instance['display']['spacing'] ) ) {
				$instance['display']['spacing'] = $instance['display']['spacing'] .'px';
			}
		}
		
		return $instance;
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
		if ( ! empty( $instance['display']['spacing'] ) ) {
			$less['spacing'] = $instance['display']['spacing'];
		}

		return $less;
	}

	function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return sprintf(
			__( 'Add a Lightbox to your images with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
			'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/lightbox" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);
	}
}

siteorigin_widget_register( 'sow-image-grid', __FILE__, 'SiteOrigin_Widgets_ImageGrid_Widget' );

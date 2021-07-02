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
				'description' => __('Display a grid of images. Also useful for displaying client logos.', 'so-widgets-bundle'),
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
					'selectorArray' => array(
						array(
							'selector' => "[id*='title']",
							'valueMethod' => 'val',
						),
						array(
							'selector' => '.media-field-wrapper .current .title',
							'valueMethod' => 'html'
						),
					),
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

					'padding' => array(
						'label' => __( 'Image padding', 'so-widgets-bundle' ),
						'type' => 'multi-measurement',
						'autofill' => true,
						'default' => '5px 5px 5px 5px',
						'measurements' => array(
							'top' => array(
							'label' => __( 'Top', 'so-widgets-bundle' ),
							),
							'right' => array(
								'label' => __( 'Right', 'so-widgets-bundle' ),
							),
							'bottom' => array(
								'label' => __( 'Bottom', 'so-widgets-bundle' ),
							),
							'left' => array(
								'label' => __( 'Left', 'so-widgets-bundle' ),
							),
						),
					),

					'alignment_vertical' => array(
						'type' => 'select',
						'label' => __( 'Image vertical alignment', 'so-widgets-bundle' ),
						'description' => __( 'Applied if image heights differ.', 'so-widgets-bundle' ),
						'default' => 'end',
						'options' => array(
							'flex-start' => __( 'Top', 'so-widgets-bundle' ),
							'center' => __( 'Center', 'so-widgets-bundle' ),
							'flex-end' => __( 'Bottom', 'so-widgets-bundle' ),
						),
					),

					'alignment_horizontal' => array(
						'type' => 'select',
						'label' => __( 'Grid horizontal alignment', 'so-widgets-bundle' ),
						'default' => 'center',
						'options' => array(
							'flex-start' => __( 'Left', 'so-widgets-bundle' ),
							'center' => __( 'Center', 'so-widgets-bundle' ),
							'flex-end' => __( 'Right', 'so-widgets-bundle' ),
						),
					),
				)
			)
		);
	}
	
	function get_template_variables( $instance, $args ) {
		$images = isset( $instance['images'] ) ? $instance['images'] : array();
		$lazy = function_exists( 'wp_lazy_loading_enabled' ) && wp_lazy_loading_enabled( 'img', 'sow-image-grid' );

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
				$image['image_html'] = '<img src="' . esc_url( $image['image_fallback'] ) . '" alt="' . esc_attr( $alt ) . '" title="' . esc_attr( $title ) . '" class="sow-image-grid-image_html" ' . ( $lazy ? 'loading="lazy"' : '' ) . '>';
			} else {
				$image['image_html'] = wp_get_attachment_image( $image['image'], $instance['display']['attachment_size'], false, array(
					'title' => $title,
					'alt'   => $image['alt'],
					'class' => 'sow-image-grid-image_html',
					'loading' => $lazy ? 'lazy' : '',
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
	private function get_image_title( $image ) {
		if ( ! empty( $image['title'] ) ) {
			$title = $image['title'];
		} else if ( apply_filters( 'siteorigin_widgets_auto_title', true, 'sow-image-grid' ) ) {
			$title = wp_get_attachment_caption( $image['image'] );
			if ( empty( $title ) ) {
				// We do not want to use the default image titles as they're based on the file name without the extension
				$file_name = pathinfo( get_post_meta( $image['image'], '_wp_attached_file', true ), PATHINFO_FILENAME );
				$title = get_the_title( $image['image'] );
				if ( $title == $file_name ) {
					$title = '';
				}
			}
		} else {
			$title = '';
		}

		return $title;
	}
	
	function modify_instance( $instance ) {
		if ( ! empty( $instance['display'] ) ) {
			// Revert changes to `max_width` and `max_height` back to `number` fields.
			if ( ! empty( $instance['display']['max_height'] ) ) {
				$instance['display']['max_height'] = (int) $instance['display']['max_height'];
			}

			if ( ! empty( $instance['display']['max_width'] ) ) {
				$instance['display']['max_width'] = (int) $instance['display']['max_width'];
			}

			// Migrate the Spacing setting to the Padding setting.
			if ( isset( $instance['display']['spacing'] ) ) {
				// The Spacing setting was initially a `number` field.
				if ( is_numeric( $instance['display']['spacing'] ) ) {
					$spacing = $instance['display']['spacing'] . 'px';
				} else if ( isset( $instance['display']['spacing_unit'] ) ) {
					// Prior to the rename, it was a `measurement` field.
					$spacing = $instance['display']['spacing'];
				}

				if ( isset( $spacing ) ) {
					$instance['display']['padding'] = "0px $spacing $spacing $spacing";
				}
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
		return array(
			'padding' => ! empty( $instance['display']['padding'] ) ? $instance['display']['padding'] : '5px 5px 5px 5px',
			'alignment_horizontal' => ! empty( $instance['display']['alignment_horizontal'] ) ? $instance['display']['alignment_horizontal'] : 'center',
			'alignment_vertical' => ! empty( $instance['display']['alignment_vertical'] ) ? $instance['display']['alignment_vertical'] : 'baseline',

		);
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

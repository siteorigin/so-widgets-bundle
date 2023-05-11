<?php
/*
Widget Name: Simple Masonry Layout
Description: A masonry layout for images. Images can link to your posts.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/simple-masonry-widget/
*/

class SiteOrigin_Widget_Simple_Masonry_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'sow-simple-masonry',
			__( 'SiteOrigin Simple Masonry', 'so-widgets-bundle' ),
			array(
				'description' => __( 'A masonry layout for images. Images can link to your posts.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/simple-masonry-widget/',
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	public function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'sow-simple-masonry',
					siteorigin_widget_get_plugin_dir_url( 'sow-simple-masonry' ) . 'js/simple-masonry' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery', 'dessandro-imagesLoaded', 'dessandro-packery' ),
					SOW_BUNDLE_VERSION,
				),
			)
		);
	}

	public function get_widget_form() {
		return array(
			'widget_title' => array(
				'type' => 'text',
				'label' => __( 'Title', 'so-widgets-bundle' ),
			),

			'items' => array(
				'type' => 'repeater',
				'label' => __( 'Images', 'so-widgets-bundle' ),
				'item_label' => array(
					'selectorArray' => array(
						array(
							'selector' => "[id*='title']",
							'valueMethod' => 'val',
						),
						array(
							'selector' => '.media-field-wrapper .current .title',
							'valueMethod' => 'html',
						),
					),
				),
				'fields' => array(
					'image' => array(
						'type' => 'media',
						'label' => __( 'Image', 'so-widgets-bundle' ),
						'fallback' => true,
					),
					'column_span' => array(
						'type' => 'slider',
						'label' => __( 'Column span', 'so-widgets-bundle' ),
						'description' => __( 'Number of columns this item should span. (Limited to number of columns selected in Layout section below.)', 'so-widgets-bundle' ),
						'min' => 1,
						'max' => 10,
						'default' => 1,
					),
					'row_span' => array(
						'type' => 'slider',
						'label' => __( 'Row span', 'so-widgets-bundle' ),
						'description' => __( 'Number of rows this item should span. (Limited to number of columns selected in Layout section below.)', 'so-widgets-bundle' ),
						'min' => 1,
						'max' => 10,
						'default' => 1,
					),
					'title' => array(
						'type' => 'text',
						'label' => __( 'Title', 'so-widgets-bundle' ),
					),
					'url' => array(
						'type' => 'link',
						'label' => __( 'Destination URL', 'so-widgets-bundle' ),
					),
					'new_window' => array(
						'type' => 'checkbox',
						'default' => false,
						'label' => __( 'Open in a new window', 'so-widgets-bundle' ),
					),
				),
			),

			'preloader' => array(
				'type' => 'section',
				'label' => __( 'Preloader', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'enabled' => array(
						'type' => 'checkbox',
						'label' => __( 'Enable preloader', 'so-widgets-bundle' ),
					),
					'color' => array(
						'type' => 'color',
						'label' => __( 'Preloader icon color', 'so-widgets-bundle' ),
						'default' => '#232323',
					),
					'height' => array(
						'type' => 'measurement',
						'label' => __( 'Preloader height', 'so-widgets-bundle' ),
						'default' => '250px',
						'description' => __( 'The size of the preloader prior to the Masonry images showing.', 'so-widgets-bundle' ),
					),
				),
			),

			'title' => array(
				'type' => 'section',
				'label' => __( 'Image Title', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'display' => array(
						'type' => 'checkbox',
						'label' => __( 'Display Image Title', 'so-widgets-bundle' ),
						'state_emitter' => array(
							'callback' => 'conditional',
							'args' => array(
								'title_display[show]: val',
								'title_display[hide]: ! val',
							),
						),
					),

					'position' => array(
						'type' => 'select',
						'label' => __( 'Title Position', 'so-widgets-bundle' ),
						'default' => 'below',
						'options' => array(
							'above' => __( 'Above Image', 'so-widgets-bundle' ),
							'below' => __( 'Below Image', 'so-widgets-bundle' ),
						),
						'state_handler' => array(
							'title_display[show]' => array( 'show' ),
							'title_display[hide]' => array( 'hide' ),
						),
					),

					'alignment' => array(
						'type' => 'select',
						'label' => __( 'Title Alignment', 'so-widgets-bundle' ),
						'default' => 'center',
						'options' => array(
							'left' => __( 'Left', 'so-widgets-bundle' ),
							'center' => __( 'Center', 'so-widgets-bundle' ),
							'right' => __( 'Right', 'so-widgets-bundle' ),
						),
						'state_handler' => array(
							'title_display[show]' => array( 'show' ),
							'title_display[hide]' => array( 'hide' ),
						),
					),

					'font' => array(
						'type' => 'font',
						'label' => __( 'Title Font', 'so-widgets-bundle' ),
						'state_handler' => array(
							'title_display[show]' => array( 'show' ),
							'title_display[hide]' => array( 'hide' ),
						),
					),

					'font_size' => array(
						'type' => 'measurement',
						'label' => __( 'Title Font Size', 'so-widgets-bundle' ),
						'default' => '0.9rem',
						'state_handler' => array(
							'title_display[show]' => array( 'show' ),
							'title_display[hide]' => array( 'hide' ),
						),
					),

					'color' => array(
						'type' => 'color',
						'label' => __( 'Title Color', 'so-widgets-bundle' ),
						'state_handler' => array(
							'title_display[show]' => array( 'show' ),
							'title_display[hide]' => array( 'hide' ),
						),
					),

					'padding' => array(
						'type' => 'color',
						'label' => __( 'Title Padding', 'so-widgets-bundle' ),
						'type' => 'multi-measurement',
						'autofill' => true,
						'default' => '5px 0px 10px 0px',
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
						'state_handler' => array(
							'title_display[show]' => array( 'show' ),
							'title_display[hide]' => array( 'hide' ),
						),
					),
				),
			),

			'layout' => array(
				'type' => 'section',
				'label' => __( 'Layout', 'so-widgets-bundle' ),
				'fields' => array(
					'origin_left' => array(
						'type' => 'select',
						'label' => __( 'Origin', 'so-widgets-bundle' ),
						'description' => __( 'Controls the horizontal flow of the layout. Items can either start positioned on the left or right.', 'so-widgets-bundle' ),
						'default' => 'true',
						'options' => array(
							'true' => __( 'Left', 'so-widgets-bundle' ),
							'false' => __( 'Right', 'so-widgets-bundle' ),
						),
					),

					'desktop' => array(
						'type' => 'section',
						'label' => __( 'Desktop', 'so-widgets-bundle' ),
						'fields' => array(
							'columns' => array(
								'type' => 'slider',
								'label' => __( 'Number of columns', 'so-widgets-bundle' ),
								'min' => 1,
								'max' => 10,
								'default' => 4,
							),
							'row_height' => array(
								'type' => 'number',
								'label' => __( 'Row height', 'so-widgets-bundle' ),
								'description' => __( 'Leave blank to match calculated column width.', 'so-widgets-bundle' ),
							),
							'gutter' => array(
								'type' => 'number',
								'label' => __( 'Gutter', 'so-widgets-bundle' ),
								'description' => __( 'Space between masonry items.', 'so-widgets-bundle' ),
							),
						),
					),

					'tablet' => array(
						'type' => 'section',
						'label' => __( 'Tablet', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'break_point' => array(
								'type' => 'number',
								'lanel' => __( 'Breakpoint', 'so-widgets-bundle' ),
								'description' => __( 'Device width, in pixels, at which to collapse into a tablet view.', 'so-widgets-bundle' ),
								'default' => 768,
							),
							'columns' => array(
								'type' => 'slider',
								'label' => __( 'Number of columns', 'so-widgets-bundle' ),
								'min' => 1,
								'max' => 10,
								'default' => 2,
							),
							'row_height' => array(
								'type' => 'number',
								'label' => __( 'Row height', 'so-widgets-bundle' ),
								'description' => __( 'Leave blank to match calculated column width.', 'so-widgets-bundle' ),
							),
							'gutter' => array(
								'type' => 'number',
								'label' => __( 'Gutter', 'so-widgets-bundle' ),
								'description' => __( 'Space between masonry items.', 'so-widgets-bundle' ),
							),
						),
					),

					'mobile' => array(
						'type' => 'section',
						'label' => __( 'Mobile', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'break_point' => array(
								'type' => 'number',
								'lanel' => __( 'Breakpoint', 'so-widgets-bundle' ),
								'description' => __( 'Device width, in pixels, at which to collapse into a mobile view.', 'so-widgets-bundle' ),
								'default' => 480,
							),
							'columns' => array(
								'type' => 'slider',
								'label' => __( 'Number of columns', 'so-widgets-bundle' ),
								'min' => 1,
								'max' => 10,
								'default' => 1,
							),
							'row_height' => array(
								'type' => 'number',
								'label' => __( 'Row height', 'so-widgets-bundle' ),
								'description' => __( 'Leave blank to match calculated column width.', 'so-widgets-bundle' ),
							),
							'gutter' => array(
								'type' => 'number',
								'label' => __( 'Gutter', 'so-widgets-bundle' ),
								'description' => __( 'Space between masonry items.', 'so-widgets-bundle' ),
							),
						),
					),
				),
			),
		);
	}

	public function get_template_variables( $instance, $args ) {
		$items = isset( $instance['items'] ) ? $instance['items'] : array();

		foreach ( $items as &$item ) {
			$link_atts = empty( $item['link_attributes'] ) ? array() : $item['link_attributes'];

			if ( ! empty( $item['new_window'] ) ) {
				$link_atts['target'] = '_blank';
				$link_atts['rel'] = 'noopener noreferrer';
			}
			$item['link_attributes'] = $link_atts;
			$item['title'] = $this->get_image_title( $item );
		}

		return array(
			'args' => $args,
			'items' => $items,
			'preloader_enabled' => ! empty( $instance['preloader']['enabled'] ) ? true : false,
			'layout_origin_left' => ! empty( $instance['layout']['origin_left'] ) ? $instance['layout']['origin_left'] : 'true',
			'layouts' => array(
				'desktop' => siteorigin_widgets_underscores_to_camel_case(
					array(
						'num_columns' => empty( $instance['layout']['desktop']['columns'] ) ? 3 : $instance['layout']['desktop']['columns'],
						'row_height' => empty( $instance['layout']['desktop']['row_height'] ) ? 0 : (int) $instance['layout']['desktop']['row_height'],
						'gutter' => empty( $instance['layout']['desktop']['gutter'] ) ? 0 : (int) $instance['layout']['desktop']['gutter'],
					)
				),
				'tablet' => siteorigin_widgets_underscores_to_camel_case(
					array(
						'break_point' => empty( $instance['layout']['tablet']['columns'] ) ? '768px' : $instance['layout']['tablet']['break_point'],
						'num_columns' => empty( $instance['layout']['tablet']['columns'] ) ? 2 : $instance['layout']['tablet']['columns'],
						'row_height' => empty( $instance['layout']['tablet']['row_height'] ) ? 0 : (int) $instance['layout']['tablet']['row_height'],
						'gutter' => empty( $instance['layout']['tablet']['gutter'] ) ? 0 : (int) $instance['layout']['tablet']['gutter'],
					)
				),
				'mobile' => siteorigin_widgets_underscores_to_camel_case(
					array(
						'break_point' => empty( $instance['layout']['mobile']['columns'] ) ? '480px' : $instance['layout']['mobile']['break_point'],
						'num_columns' => empty( $instance['layout']['mobile']['columns'] ) ? 1 : $instance['layout']['mobile']['columns'],
						'row_height' => empty( $instance['layout']['mobile']['row_height'] ) ? 0 : (int) $instance['layout']['mobile']['row_height'],
						'gutter' => empty( $instance['layout']['mobile']['gutter'] ) ? 0 : (int) $instance['layout']['mobile']['gutter'],
					)
				),
			),
		);
	}

	/**
	 * Try to figure out an image's title for display.
	 *
	 * @return string The title of the image.
	 */
	private function get_image_title( $image ) {
		if ( ! empty( $image['title'] ) ) {
			$title = $image['title'];
		} elseif ( apply_filters( 'siteorigin_widgets_auto_title', true, 'sow-simple-masonry' ) ) {
			$title = wp_get_attachment_caption( $image['image'] );

			if ( empty( $title ) ) {
				// We do not want to use the default image titles as they're based on the file name without the extension
				$file_name = pathinfo( get_post_meta( $image['image'], '_wp_attached_file', true ), PATHINFO_FILENAME );
				$title = get_the_title( $image['image'] );

				if ( $title == $file_name ) {
					return;
				}
			}
		} else {
			$title = '';
		}

		return $title;
	}

	public function get_less_variables( $instance ) {
		$less = array();

		if ( ! empty( $instance['preloader'] ) && ! empty( $instance['preloader']['enabled'] ) ) {
			$less['preloader_enabled'] = 'true';
			$less['preloader_height'] = $instance['preloader']['height'];
			$less['preloader_color'] = $instance['preloader']['color'];
		}

		if ( ! empty( $instance['title'] ) && ! empty( $instance['title']['display'] ) ) {
			$less['title_alignment'] = ! empty( $instance['title']['display'] ) ? $instance['title']['alignment'] : '';
			$title_font = siteorigin_widget_get_font( $instance['title']['font'] );
			$less['title_font'] = $title_font['family'];

			if ( ! empty( $title_font['weight'] ) ) {
				$less['title_font_weight'] = $title_font['weight_raw'];
				$less['title_font_style'] = $title_font['style'];
			}
			$less['title_font_size'] = ! empty( $instance['title']['font_size'] ) ? $instance['title']['font_size'] : '';
			$less['title_color'] = ! empty( $instance['title']['color'] ) ? $instance['title']['color'] : '';
			$less['title_padding'] = ! empty( $instance['title']['padding'] ) ? $instance['title']['padding'] : '';
		}

		return $less;
	}

	public function modify_instance( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		// If this Simple Masonry was created before the title settings were added, disable it by default.
		if ( ! empty( $instance['display'] ) || ! isset( $instance['title']['display'] ) ) {
			$instance['title']['title_display'] = false;
		}

		// Migrate Legacy device layout settings to layout seciton.
		if ( ! empty( $instance['desktop_layout'] ) ) {
			$instance['layout'] = array();
			$instance['layout']['desktop'] = array();
			$instance['layout']['tablet'] = array();
			$instance['layout']['mobile'] = array();
			$instance['layout']['origin_left'] = ! empty( $instance['layout_origin_left'] ) ? $instance['layout_origin_left'] : 'true';

			$migrate_layout_sections = array(
				'desktop' => array(
					'columns',
					'row_height',
					'gutter',
				),
				'tablet' => array(
					'break_point',
					'columns',
					'row_height',
					'gutter',
				),
				'mobile' => array(
					'break_point',
					'columns',
					'row_height',
					'gutter',
				),
			);

			foreach ( $migrate_layout_sections as $setting => $sub_section ) {
				foreach ( $sub_section as $layout_setting ) {
					if ( isset( $instance[ $setting . '_layout' ][ $layout_setting ] ) ) {
						$instance['layout'][ $setting ][ $layout_setting ] = $instance[ $setting . '_layout' ][ $layout_setting ];
					}
				}
				unset( $instance[ $setting . '_layout' ] );
			}
		}

		return $instance;
	}

	public function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return array(
			sprintf(
				__( 'Add a Lightbox to your images with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/lightbox" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
			sprintf(
				__( 'Add a beautiful and customizable text overlay with animations to your images with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/image-overlay" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
			sprintf(
				__( 'Add multiple Simple Masonry frames in one go with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/multiple-media" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
			sprintf(
				__( 'Add an image title tooltip with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/tooltip" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
		);
	}
}

siteorigin_widget_register( 'sow-simple-masonry', __FILE__, 'SiteOrigin_Widget_Simple_Masonry_Widget' );

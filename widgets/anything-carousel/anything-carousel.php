<?php
/*
Widget Name: Anything Carousel
Description: Display images, text, or any other content in a carousel.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/anything-carousel-widget/
*/

if ( ! class_exists( 'SiteOrigin_Widget_Base_Carousel' ) ) {
	include_once plugin_dir_path( SOW_BUNDLE_BASE_FILE ) . '/base/inc/widgets/base-carousel.class.php';
}

class SiteOrigin_Widget_Anything_Carousel_Widget extends SiteOrigin_Widget_Base_Carousel {
	function __construct() {
		parent::__construct(
			'sow-anything-carousel',
			__( 'SiteOrigin Anything Carousel', 'so-widgets-bundle' ),
			array(
				'description' => __( 'Display images, text, or any other content in a carousel.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/anything-carousel-widget/'
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	function initialize() {
		// Let the carousel base class do its initialization.
		parent::initialize();

		$this->register_frontend_styles(
			array(
				array(
					'sow-anything-carousel',
					plugin_dir_url( __FILE__ ) . 'css/style.css',
				),
			)
		);
	}

	function override_carousel_settings() {
		return array(
			'slides_to_scroll_text' => array(
				'label' => __( 'Slides to show ', 'so-widgets-bundle' ),
				'description' => __( 'The number of slides to show on %s.', 'so-widgets-bundle' ),
			),
		);
	}

	function get_widget_form() {
		$useable_units = array(
			'px',
			'%',
		);

		return array(
			'title' => array(
				'type' => 'text',
				'label' => __( 'Title', 'so-widgets-bundle' ),
			),

			'items' => array(
				'type' => 'repeater',
				'label' => __( 'Items', 'so-widgets-bundle' ),
				'item_name' => __( 'Item', 'so-widgets-bundle' ),
				'item_label' => array(
					'selector' => "[id*='title']",
					'update_event' => 'change',
					'value_method' => 'val'
				),

				'fields' => array(
					'title' => array(
						'type' => 'text',
						'label' => __( 'Title', 'so-widgets-bundle' ),
					),
					'content_text' => array(
						'type' => 'tinymce',
						'label' => __( 'Content', 'so-widgets-bundle' ),
					),
				),
			),
			'carousel_settings' => $this->carousel_settings_form_fields(),
			'design' => array(
				'type' => 'section',
				'label' => __( 'Design', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'item_title' => array(
						'type' => 'section',
						'label' => __( 'Item title', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'tag' => array(
								'type' => 'select',
								'label' => __( 'HTML Tag', 'so-widgets-bundle' ),
								'default' => 'h4',
								'options' => array(
									'h1' => __( 'H1', 'so-widgets-bundle' ),
									'h2' => __( 'H2', 'so-widgets-bundle' ),
									'h3' => __( 'H3', 'so-widgets-bundle' ),
									'h4' => __( 'H4', 'so-widgets-bundle' ),
									'h5' => __( 'H5', 'so-widgets-bundle' ),
									'h6' => __( 'H6', 'so-widgets-bundle' ),
									'p' => __( 'Paragraph', 'so-widgets-bundle' ),
								),
							),
							'font' => array(
								'type' => 'font',
								'label' => __( 'Font', 'so-widgets-bundle' ),
							),
							'size' => array(
								'type' => 'measurement',
								'label' => __( 'Size', 'so-widgets-bundle' ),
							),
							'color' => array(
								'type' => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
							),
							'bottom_margin' => array(
								'type' => 'measurement',
								'label' => __( 'Bottom margin', 'so-widgets-bundle' ),
								'default' => '24px',
							),
						),
					),
					'item' => array(
						'type' => 'section',
						'label' => __( 'Item', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'font' => array(
								'type' => 'font',
								'label' => __( 'Font', 'so-widgets-bundle' ),
							),
							'size' => array(
								'type' => 'measurement',
								'label' => __( 'Font size', 'so-widgets-bundle' ),
							),
							'color' => array(
								'type' => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
							),
							'margin' => array(
								'type' => 'multi-measurement',
								'label' => __( 'Margin', 'so-widgets-bundle' ),
								'autofill' => true,
								'default' => '0 12px 64px 12px',
								'measurements' => array(
									'top' => array(
										'label' => __( 'Top', 'so-widgets-bundle' ),
										'units' => $useable_units,
									),
									'right' => array(
										'label' => __( 'Right', 'so-widgets-bundle' ),
										'units' => $useable_units,
									),
									'bottom' => array(
										'label' => __( 'Bottom', 'so-widgets-bundle' ),
										'units' => $useable_units,
									),
									'left' => array(
										'label' => __( 'Left', 'so-widgets-bundle' ),
										'units' => $useable_units,
									),
								),
							),
						),
					),
					'navigation' => array(
						'type' => 'section',
						'label' => __( 'Navigation', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'arrow_color' => array(
								'type' => 'color',
								'label' => __( 'Arrows color', 'so-widgets-bundle' ),
								'default' => '#626262',
							),
							'arrow_color_hover' => array(
								'type' => 'color',
								'label' => __( 'Arrows hover color', 'so-widgets-bundle' ),
								'default' => '#000',
							),
							'arrow_margin' => array(
								'type' => 'measurement',
								'label' => __( 'Arrows margin', 'so-widgets-bundle' ),
								'description' => __( 'The space between the navigation arrows and items.', 'so-widgets-bundle' ),
							),
							'dots_color' => array(
								'type' => 'color',
								'label' => __( 'Dots color', 'so-widgets-bundle' ),
								'default' => '#bebebe',
							),
							'dots_color_hover' => array(
								'type' => 'color',
								'label' => __( 'Dots selected and hover color', 'so-widgets-bundle' ),
								'default' => '#f14e4e',
							),

						),
					),
				),
			),
			'responsive' => $this->responsive_form_fields(),
		);
	}

	function get_style_name( $instance ) {
		return empty( $instance['design']['theme'] ) ? 'base' : $instance['design']['theme'];
	}

	function get_less_variables( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		$less_vars = array(
			'item_title_tag' => $instance['design']['item_title']['tag'],
			'item_title_font_size' => $instance['design']['item_title']['size'],
			'item_title_color' => $instance['design']['item_title']['color'],
			'bottom_margin' => $instance['design']['item_title']['bottom_margin'],

			'item_size' => $instance['design']['item']['size'],
			'item_color' => $instance['design']['item']['color'],
			'item_margin' => $instance['design']['item']['margin'],

			'navigation_arrow_color' => $instance['design']['navigation']['arrow_color'],
			'navigation_arrow_color_hover' => $instance['design']['navigation']['arrow_color_hover'],
			'navigation_arrow_margin' => $instance['design']['navigation']['arrow_margin'],
			'navigation_dots_color' => $instance['design']['navigation']['dots_color'],
			'navigation_dots_color_hover' => $instance['design']['navigation']['dots_color_hover'],
		);

		$item_title_font = siteorigin_widget_get_font( $instance['design']['item_title']['font'] );
		$less_vars['item_title_font'] = $item_title_font['family'];
		if ( ! empty( $item_title_font['weight'] ) ) {
			$less_vars['item_title_font_style'] = $item_title_font['style'];
			$less_vars['item_title_font_weight'] = $item_title_font['weight_raw'];
		}

		$item_font = siteorigin_widget_get_font( $instance['design']['item']['font'] );
		$less_vars['item_font'] = $item_font['family'];
		if ( ! empty( $item_font['weight'] ) ) {
			$less_vars['item_font_style'] = $item_font['style'];
			$less_vars['item_font_weight'] = $item_font['weight_raw'];
		}

		return $less_vars;
	}

	public function get_template_variables( $instance, $args ) {
		return array(
			'settings' => array(
				'title' => $instance['title'],
				'item_template' => plugin_dir_path( __FILE__ ) . 'tpl/item.php',
				'navigation' => 'side',
				'item_title_tag' => $instance['design']['item_title']['tag'],
				'items' => ! empty( $instance['items'] ) ? $instance['items'] : array(),
				'attributes' => array(
					'widget' => 'anything',
					'item_count' => ! empty( $instance['items'] ) ? count( $instance['items'] ) : 0,
					'loop' => ! empty( $instance['loop_posts'] ),
					'carousel_settings' => $this->carousel_settings_template_variables( $instance['carousel_settings'] ),
					'responsive' => $this->responsive_template_variables( $instance['responsive'] ),
				),
			),
		);
	}

	function render_item_content( $item, $instance ) {
		echo apply_filters( 'siteorigin_widgets_anything_carousel_render_item_content', $item['content_text'], $item, $instance );
	}

	function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return sprintf(
			__( 'Add widgets and layouts to your carousel items with %sSiteOrigin Premium%s.', 'so-widgets-bundle' ),
			'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/carousel" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);
	}
}

siteorigin_widget_register( 'sow-anything-carousel', __FILE__, 'SiteOrigin_Widget_Anything_Carousel_Widget' );

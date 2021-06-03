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

	function get_slides_to_scroll_text() {
		return array(
			'label' => __( 'Slides to show ', 'so-widgets-bundle' ),
			'description' => __( 'The number of slides to show on %s', 'so-widgets-bundle' ),
		);
	}

	function get_widget_form() {
		$breakpoints = $this->get_breakpoints();

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
					'navigation_color' => array(
						'type' => 'color',
						'label' => __( 'Navigation color', 'so-widgets-bundle' ),
						'default' => '#000',
					),

					'item_margin' => array(
						'type' => 'multi-measurement',
						'label' => __( 'Item margin', 'so-widgets-bundle' ),
						'autofill' => true,
						'default' => '0px 10px 0px 10px',
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

					'item_padding' => array(
						'type' => 'multi-measurement',
						'label' => __( 'Item padding', 'so-widgets-bundle' ),
						'default' => '10px 5px 10px 5px',
						'autofill' => true,
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

				)
			),
			'responsive' => $this->responsive_form_fields(),
		);
	}

	function get_less_variables( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		return array(
			'navigation_color' => $instance['design']['navigation_color'],
			'item_margin' => $instance['design']['item_margin'],
			'item_padding' => $instance['design']['item_padding'],
		);
	}

	public function get_template_variables( $instance, $args ) {
		return array(
			'settings' => array(
				'title' => $instance['title'],
				'item_template' => plugin_dir_path( __FILE__ ) . 'tpl/item.php',
				'navigation' => 'side',
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
}

siteorigin_widget_register( 'sow-anything-carousel', __FILE__, 'SiteOrigin_Widget_Anything_Carousel_Widget' );

<?php
/*
Widget Name: Button
Description: A powerful yet simple button widget for your sidebars or Page Builder pages.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/button-widget-documentation/
*/

class SiteOrigin_Widget_Button_Widget extends SiteOrigin_Widget {
	function __construct() {

		parent::__construct(
			'sow-button',
			__('SiteOrigin Button', 'so-widgets-bundle'),
			array(
				'description' => __('A customizable button widget.', 'so-widgets-bundle'),
				'help' => 'https://siteorigin.com/widgets-bundle/button-widget-documentation/'
			),
			array(

			),
			false,
			plugin_dir_path(__FILE__)
		);

	}

	function initialize() {
		$this->register_frontend_styles(
			array(
				array(
					'sow-button-base',
					plugin_dir_url(__FILE__) . 'css/style.css',
					array(),
					SOW_BUNDLE_VERSION
				),
			)
		);
	}

	function get_widget_form() {
		return array(
			'text' => array(
				'type' => 'text',
				'label' => __('Button text', 'so-widgets-bundle'),
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

			'button_icon' => array(
				'type' => 'section',
				'label' => __('Icon', 'so-widgets-bundle'),
				'fields' => array(
					'icon_selected' => array(
						'type' => 'icon',
						'label' => __('Icon', 'so-widgets-bundle'),
					),

					'icon_color' => array(
						'type' => 'color',
						'label' => __('Icon color', 'so-widgets-bundle'),
					),

					'icon' => array(
						'type' => 'media',
						'label' => __('Image icon', 'so-widgets-bundle'),
						'description' => __('Replaces the icon with your own image icon.', 'so-widgets-bundle'),
					),

					'icon_placement' => array(
						'type' => 'select',
						'label' => __( 'Icon Placement', 'so-widgets-bundle' ),
						'default' => 'left',
						'options' => array(
							'top'    => __( 'Top', 'so-widgets-bundle' ),
							'right'  => __( 'Right', 'so-widgets-bundle' ),
							'bottom' => __( 'Bottom', 'so-widgets-bundle' ),
							'left'   => __( 'Left', 'so-widgets-bundle' ),
						),
					),
				),
			),

			'design' => array(
				'type' => 'section',
				'label' => __('Design and layout', 'so-widgets-bundle'),
				'hide' => true,
				'fields' => array(

					'width' => array(
						'type' => 'measurement',
						'label' => __( 'Width', 'so-widgets-bundle' ),
						'description' => __( 'Leave blank to let the button resize according to content.', 'so-widgets-bundle' )
					),

					'align' => array(
						'type' => 'select',
						'label' => __('Align', 'so-widgets-bundle'),
						'default' => 'center',
						'options' => array(
							'left' => __('Left', 'so-widgets-bundle'),
							'right' => __('Right', 'so-widgets-bundle'),
							'center' => __('Center', 'so-widgets-bundle'),
							'justify' => __('Justify', 'so-widgets-bundle'),
						),
					),

					'theme' => array(
						'type' => 'select',
						'label' => __('Button theme', 'so-widgets-bundle'),
						'default' => 'atom',
						'options' => array(
							'atom' => __('Atom', 'so-widgets-bundle'),
							'flat' => __('Flat', 'so-widgets-bundle'),
							'wire' => __('Wire', 'so-widgets-bundle'),
						),
					),


					'button_color' => array(
						'type' => 'color',
						'label' => __('Button color', 'so-widgets-bundle'),
					),

					'text_color' => array(
						'type' => 'color',
						'label' => __('Text color', 'so-widgets-bundle'),
					),

					'hover' => array(
						'type' => 'checkbox',
						'default' => true,
						'label' => __('Use hover effects', 'so-widgets-bundle'),
					),

					'font' => array(
						'type' => 'font',
						'label' => __( 'Font', 'so-widgets-bundle' ),
						'default' => 'default'
					),

					'font_size' => array(
						'type' => 'select',
						'label' => __('Font size', 'so-widgets-bundle'),
						'options' => array(
							'1' => __('Normal', 'so-widgets-bundle'),
							'1.15' => __('Medium', 'so-widgets-bundle'),
							'1.3' => __('Large', 'so-widgets-bundle'),
							'1.45' => __('Extra large', 'so-widgets-bundle'),
						),
					),

					'rounding' => array(
						'type' => 'select',
						'label' => __('Rounding', 'so-widgets-bundle'),
						'default' => '0.25',
						'options' => array(
							'0' => __('None', 'so-widgets-bundle'),
							'0.25' => __('Slightly rounded', 'so-widgets-bundle'),
							'0.5' => __('Very rounded', 'so-widgets-bundle'),
							'1.5' => __('Completely rounded', 'so-widgets-bundle'),
						),
					),

					'padding' => array(
						'type' => 'select',
						'label' => __('Padding', 'so-widgets-bundle'),
						'default' => '1',
						'options' => array(
							'0.5' => __('Low', 'so-widgets-bundle'),
							'1' => __('Medium', 'so-widgets-bundle'),
							'1.4' => __('High', 'so-widgets-bundle'),
							'1.8' => __('Very high', 'so-widgets-bundle'),
						),
					),

				),
			),

			'attributes' => array(
				'type' => 'section',
				'label' => __('Other attributes and SEO', 'so-widgets-bundle'),
				'hide' => true,
				'fields' => array(
					'id' => array(
						'type' => 'text',
						'label' => __('Button ID', 'so-widgets-bundle'),
						'description' => __('An ID attribute allows you to target this button in Javascript.', 'so-widgets-bundle'),
					),

					'classes' => array(
						'type' => 'text',
						'label' => __('Button Classes', 'so-widgets-bundle'),
						'description' => __('Additional CSS classes added to the button link.', 'so-widgets-bundle'),
					),

					'title' => array(
						'type' => 'text',
						'label' => __('Title attribute', 'so-widgets-bundle'),
						'description' => __('Adds a title attribute to the button link.', 'so-widgets-bundle'),
					),

					'onclick' => array(
						'type' => 'text',
						'label' => __('Onclick', 'so-widgets-bundle'),
						'description' => __('Run this Javascript when the button is clicked. Ideal for tracking.', 'so-widgets-bundle'),
					),

					'rel' => array(
						'type' => 'text',
						'label' => __('Rel attribute', 'so-widgets-bundle'),
						'description' => __('Adds a rel attribute to the button link.', 'so-widgets-bundle'),
					),
				)
			),
		);
	}

	function get_style_name($instance) {
		if(empty($instance['design']['theme'])) return 'atom';
		return $instance['design']['theme'];
	}

	/**
	 * Get the variables for the button widget.
	 *
	 * @param $instance
	 * @param $args
	 *
	 * @return array
	 */
	function get_template_variables( $instance, $args ) {
		$button_attributes = array();

		$attributes = $instance['attributes'];

		$classes = ! empty( $attributes['classes'] ) ? $attributes['classes'] : '';
		if ( ! empty( $classes ) ) {
			$classes .= ' ';
		}
		$classes .= 'ow-icon-placement-'. $instance['button_icon']['icon_placement'];
		if ( ! empty( $instance['design']['hover'] ) ) {
			$classes .= ' ow-button-hover';
		}

		$button_attributes['class'] = implode( ' ',
			array_map( 'sanitize_html_class',
				explode( ' ', $classes )
			)
		);

		if ( ! empty( $instance['new_window'] ) ) {
			$button_attributes['target'] = '_blank';
			$button_attributes['rel'] = 'noopener noreferrer';
		}

		if ( ! empty( $attributes['id'] ) ) {
			$button_attributes['id'] = $attributes['id'];
		}
		if ( ! empty( $attributes['title'] ) ) {
			$button_attributes['title'] = $attributes['title'];
		}
		if ( ! empty( $attributes['rel'] ) ) {
			if ( isset ( $button_attributes['rel'] ) ) {
				$button_attributes['rel'] .= " $attributes[rel]";
			} else {
				$button_attributes['rel'] = $attributes['rel'];
			}
		}

		$icon_image_url = '';
		if( ! empty( $instance['button_icon']['icon'] ) ) {
			$attachment = wp_get_attachment_image_src( $instance['button_icon']['icon'] );

			if ( ! empty( $attachment ) ) {
				$icon_image_url = $attachment[0];
			}
		}

		return array(
			'button_attributes' => $button_attributes,
			'href' => !empty( $instance['url'] ) ? $instance['url'] : '#',
			'onclick' => ! empty( $attributes['onclick'] ) ? $attributes['onclick'] : '',
			'align' => $instance['design']['align'],
			'icon_image_url' => $icon_image_url,
			'icon' => $instance['button_icon']['icon_selected'],
			'icon_color' => $instance['button_icon']['icon_color'],
			'text' => $instance['text'],
		);
	}

	/**
	 * Get the variables that we'll be injecting into the less stylesheet.
	 *
	 * @param $instance
	 *
	 * @return array
	 */
	function get_less_variables($instance){
		if( empty( $instance ) || empty( $instance['design'] ) ) return array();

		$less_vars = array(
			'button_width' => isset( $instance['design']['width'] ) ? $instance['design']['width'] : '',
			'button_color' => isset($instance['design']['button_color']) ? $instance['design']['button_color'] : '',
			'text_color' =>   isset($instance['design']['text_color']) ? $instance['design']['text_color'] : '',

			'font_size' => isset($instance['design']['font_size']) ? $instance['design']['font_size'] . 'em' : '',
			'rounding' => isset($instance['design']['rounding']) ? $instance['design']['rounding'] . 'em' : '',
			'padding' => isset($instance['design']['padding']) ? $instance['design']['padding'] . 'em' : '',
			'has_text' => empty( $instance['text'] ) ? 'false' : 'true',
		);

		if ( ! empty( $instance['design']['font'] ) ) {
			$font = siteorigin_widget_get_font( $instance['design']['font'] );
			$less_vars['button_font'] = $font['family'];
			if ( ! empty( $font['weight'] ) ) {
				$less_vars['button_font_weight'] = $font['weight'];
			}
		}
		return $less_vars;
	}

	function get_google_font_fields( $instance ) {
		return array(
			$instance['design']['font'],
		);
	}
	/**
	 * Make sure the instance is the most up to date version.
	 *
	 * @param $instance
	 *
	 * @return mixed
	 */
	function modify_instance( $instance ) {
		$migrate_props = array(
			'button_icon' => array(
				'icon_selected',
				'icon_color',
				'icon',
			),
			'design' => array(
				'align',
				'theme',
				'button_color',
				'text_color',
				'hover',
				'font_size',
				'rounding',
				'padding',
			),
			'attributes' => array(
				'id'
			),
		);
		
		foreach ( $migrate_props as $prop => $sub_props ) {
			if ( empty( $instance[ $prop ] ) ) {
				$instance[ $prop ] = array();
				foreach ( $sub_props as $sub_prop ) {
					if ( isset( $instance[ $sub_prop ] ) ) {
						$instance[ $prop ][ $sub_prop ] = $instance[ $sub_prop ];
						unset( $instance[ $sub_prop ] );
					}
				}
			}
		}

		return $instance;
	}
}

siteorigin_widget_register('sow-button', __FILE__, 'SiteOrigin_Widget_Button_Widget');

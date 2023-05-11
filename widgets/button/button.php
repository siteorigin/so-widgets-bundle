<?php
/*
Widget Name: Button
Description: A powerful yet simple button widget for your sidebars or Page Builder pages.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/button-widget-documentation/
*/

class SiteOrigin_Widget_Button_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'sow-button',
			__( 'SiteOrigin Button', 'so-widgets-bundle' ),
			array(
				'description' => __( 'A powerful yet simple button widget for your sidebars or Page Builder pages.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/button-widget-documentation/',
			),
			array(
			),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	public function get_settings_form() {
		return array(
			'responsive_breakpoint' => array(
				'type'        => 'measurement',
				'label'       => __( 'Responsive Breakpoint', 'so-widgets-bundle' ),
				'default'     => '780px',
				'description' => __( 'This setting controls when the Mobile Align setting will be used. The default value is 780px.', 'so-widgets-bundle' ),
			),
		);
	}

	public function initialize() {
		$this->register_frontend_styles(
			array(
				array(
					'sow-button-base',
					plugin_dir_url( __FILE__ ) . 'css/style.css',
					array(),
					SOW_BUNDLE_VERSION,
				),
			)
		);
	}

	public function get_widget_form() {
		return array(
			'text' => array(
				'type' => 'text',
				'label' => __( 'Button Text', 'so-widgets-bundle' ),
			),

			'url' => array(
				'type' => 'link',
				'label' => __( 'Destination URL', 'so-widgets-bundle' ),
				'allow_shortcode' => true,
			),

			'new_window' => array(
				'type' => 'checkbox',
				'default' => false,
				'label' => __( 'Open in a new window', 'so-widgets-bundle' ),
			),

			'download' => array(
				'type' => 'checkbox',
				'default' => false,
				'label' => __( 'Download', 'so-widgets-bundle' ),
				'description' => __( 'The Destination URL will be downloaded when a user clicks on the button.', 'so-widgets-bundle' ),
			),

			'button_icon' => array(
				'type' => 'section',
				'label' => __( 'Icon', 'so-widgets-bundle' ),
				'fields' => array(
					'icon_selected' => array(
						'type' => 'icon',
						'label' => __( 'Icon', 'so-widgets-bundle' ),
					),

					'icon_color' => array(
						'type' => 'color',
						'label' => __( 'Icon Color', 'so-widgets-bundle' ),
					),

					'icon' => array(
						'type' => 'media',
						'label' => __( 'Image Icon', 'so-widgets-bundle' ),
						'description' => __( 'Replaces the icon with your own image icon.', 'so-widgets-bundle' ),
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
				'label' => __( 'Design and Layout', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'width' => array(
						'type' => 'measurement',
						'label' => __( 'Width', 'so-widgets-bundle' ),
						'description' => __( 'Leave blank to let the button resize according to content.', 'so-widgets-bundle' ),
					),

					'align' => array(
						'type' => 'select',
						'label' => __( 'Align', 'so-widgets-bundle' ),
						'default' => 'center',
						'options' => array(
							'left' => __( 'Left', 'so-widgets-bundle' ),
							'right' => __( 'Right', 'so-widgets-bundle' ),
							'center' => __( 'Center', 'so-widgets-bundle' ),
							'justify' => __( 'Justify', 'so-widgets-bundle' ),
						),
					),
					'mobile_align' => array(
						'type' => 'select',
						'label' => __( 'Mobile Align', 'so-widgets-bundle' ),
						'default' => 'center',
						'options' => array(
							'left' => __( 'Left', 'so-widgets-bundle' ),
							'right' => __( 'Right', 'so-widgets-bundle' ),
							'center' => __( 'Center', 'so-widgets-bundle' ),
							'justify' => __( 'Justify', 'so-widgets-bundle' ),
						),
					),
					'theme' => array(
						'type' => 'select',
						'label' => __( 'Button Theme', 'so-widgets-bundle' ),
						'default' => 'flat',
						'options' => array(
							'atom' => __( 'Atom', 'so-widgets-bundle' ),
							'flat' => __( 'Flat', 'so-widgets-bundle' ),
							'wire' => __( 'Wire', 'so-widgets-bundle' ),
						),
					),

					'button_color' => array(
						'type' => 'color',
						'label' => __( 'Button Color', 'so-widgets-bundle' ),
					),

					'text_color' => array(
						'type' => 'color',
						'label' => __( 'Text Color', 'so-widgets-bundle' ),
					),

					'hover' => array(
						'type' => 'checkbox',
						'default' => true,
						'label' => __( 'Use hover effects', 'so-widgets-bundle' ),
						'state_emitter' => array(
							'callback' => 'conditional',
							'args'     => array(
								'hover[show]: val',
								'hover[hide]: ! val',
							),
						),
					),

					'hover_background_color' => array(
						'type' => 'color',
						'label' => __( 'Hover Background Color', 'so-widgets-bundle' ),
						'state_handler' => array(
							'hover[show]' => array( 'show' ),
							'hover[hide]' => array( 'hide' ),
						),
					),

					'hover_text_color' => array(
						'type' => 'color',
						'label' => __( 'Hover Text Color', 'so-widgets-bundle' ),
						'state_handler' => array(
							'hover[show]' => array( 'show' ),
							'hover[hide]' => array( 'hide' ),
						),
					),

					'font' => array(
						'type' => 'font',
						'label' => __( 'Font', 'so-widgets-bundle' ),
						'default' => 'default',
					),

					'font_size' => array(
						'type' => 'measurement',
						'label' => __( 'Font Size', 'so-widgets-bundle' ),
						'default' => '1em',
					),

					'padding' => array(
						'type' => 'measurement',
						'label' => __( 'Padding', 'so-widgets-bundle' ),
						'default' => '1em',
					),

					'rounding' => array(
						'type' => 'multi-measurement',
						'label' => __( 'Rounding', 'so-widgets-bundle' ),
						'default' => '0.25em 0.25em 0.25em 0.25em',
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
				),
			),

			'attributes' => array(
				'type' => 'section',
				'label' => __( 'Other Attributes and SEO', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'id' => array(
						'type' => 'text',
						'label' => __( 'Button ID', 'so-widgets-bundle' ),
						'description' => __( 'An ID attribute allows you to target this button in JavaScript.', 'so-widgets-bundle' ),
					),

					'classes' => array(
						'type' => 'text',
						'label' => __( 'Button Classes', 'so-widgets-bundle' ),
						'description' => __( 'Additional CSS classes added to the button link.', 'so-widgets-bundle' ),
					),

					'title' => array(
						'type' => 'text',
						'label' => __( 'Title Attribute', 'so-widgets-bundle' ),
						'description' => __( 'Adds a title attribute to the button link.', 'so-widgets-bundle' ),
					),

					'on_click' => array(
						'type' => 'text',
						'label' => __( 'Onclick', 'so-widgets-bundle' ),
						'description' => __( 'Run this JavaScript when the button is clicked. Ideal for tracking.', 'so-widgets-bundle' ),
					),

					'rel' => array(
						'type' => 'text',
						'label' => __( 'Rel Attribute', 'so-widgets-bundle' ),
						'description' => __( 'Adds a rel attribute to the button link.', 'so-widgets-bundle' ),
					),
				),
			),
		);
	}

	public function get_style_name( $instance ) {
		if ( empty( $instance['design']['theme'] ) ) {
			return 'atom';
		}

		return $instance['design']['theme'];
	}

	/**
	 * Get the variables for the Button Widget.
	 *
	 * @return array
	 */
	public function get_template_variables( $instance, $args ) {
		$button_attributes = array();

		$attributes = $instance['attributes'];

		$classes = ! empty( $attributes['classes'] ) ? $attributes['classes'] : '';

		if ( ! empty( $classes ) ) {
			$classes .= ' ';
		}
		$classes .= 'ow-icon-placement-' . $instance['button_icon']['icon_placement'];

		if ( ! empty( $instance['design']['hover'] ) ) {
			$classes .= ' ow-button-hover';
		}

		$button_attributes['class'] = implode(
			' ',
			array_map(
				'sanitize_html_class',
				explode( ' ', $classes )
			)
		);

		if ( ! empty( $instance['new_window'] ) ) {
			$button_attributes['target'] = '_blank';
			$button_attributes['rel'] = 'noopener noreferrer';
		}

		if ( ! empty( $instance['download'] ) ) {
			$button_attributes['download'] = null;
		}

		if ( ! empty( $attributes['id'] ) ) {
			$button_attributes['id'] = $attributes['id'];
		}

		if ( ! empty( $attributes['title'] ) ) {
			$button_attributes['title'] = $attributes['title'];
		}

		if ( ! empty( $attributes['rel'] ) ) {
			if ( isset( $button_attributes['rel'] ) ) {
				$button_attributes['rel'] .= " $attributes[rel]";
			} else {
				$button_attributes['rel'] = $attributes['rel'];
			}
		}

		$icon_image_url = '';

		if ( ! empty( $instance['button_icon']['icon'] ) ) {
			$attachment = wp_get_attachment_image_src( $instance['button_icon']['icon'] );

			if ( ! empty( $attachment ) ) {
				$icon_image_url = $attachment[0];
			}
		}

		return array(
			'button_attributes' => $button_attributes,
			'href' => ! empty( $instance['url'] ) ? $instance['url'] : '#',
			'on_click' => ! empty( $attributes['on_click'] ) ? $attributes['on_click'] : '',
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
	 * @return array
	 */
	public function get_less_variables( $instance ) {
		if ( empty( $instance ) || empty( $instance['design'] ) ) {
			return array();
		}

		$text_color = isset( $instance['design']['text_color'] ) ? $instance['design']['text_color'] : '';
		$button_color = isset( $instance['design']['button_color'] ) ? $instance['design']['button_color'] : '';

		$less_vars = array(
			'button_width' => isset( $instance['design']['width'] ) ? $instance['design']['width'] : '',
			'button_color' => $button_color,
			'text_color' => $text_color,
			'hover_text_color' => ! empty( $instance['design']['hover_text_color'] ) ? $instance['design']['hover_text_color'] : $text_color,
			'hover_background_color' => ! empty( $instance['design']['hover_background_color'] ) ? $instance['design']['hover_background_color'] : $button_color,
			'font_size' => isset( $instance['design']['font_size'] ) ? $instance['design']['font_size'] : '',
			'rounding' => isset( $instance['design']['rounding'] ) ? $instance['design']['rounding'] : '',
			'padding' => isset( $instance['design']['padding'] ) ? $instance['design']['padding'] : '',
			'has_text' => empty( $instance['text'] ) ? 'false' : 'true',
			'responsive_breakpoint' => $this->get_global_settings( 'responsive_breakpoint' ),
			'align' => $instance['design']['align'],
			'mobile_align' => $instance['design']['mobile_align'],
		);

		if ( ! empty( $instance['design']['font'] ) ) {
			$font = siteorigin_widget_get_font( $instance['design']['font'] );
			$less_vars['button_font'] = $font['family'];

			if ( ! empty( $font['weight'] ) ) {
				$less_vars['button_font_weight'] = $font['weight_raw'];
				$less_vars['button_font_style'] = $font['style'];
			}
		}

		return $less_vars;
	}

	/**
	 * Make sure the instance is the most up to date version.
	 *
	 * @return mixed
	 */
	public function modify_instance( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

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
				'hover_text_color',
				'hover_background_color',
				'font_size',
				'rounding',
				'padding',
			),
			'attributes' => array(
				'id',
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

		// Migrate onclick setting to prevent Wordfence flag.
		if (
			! empty( $instance['attributes'] ) &&
			! empty( $instance['attributes']['onclick'] )
		) {
			$instance['attributes']['on_click'] = $instance['attributes']['onclick'];
		}

		// If the mobile_align setting isn't set, set it to the same value as the align value.
		if (
			! empty( $instance['design'] ) &&
			! empty( $instance['design']['align'] ) &&
			empty( $instance['design']['mobile_align'] )
		) {
			$instance['design']['mobile_align'] = $instance['design']['align'];
		}

		// Migrate predefined settings to more customizable settings.
		if ( ! empty( $instance['design']['font_size'] ) && is_numeric( $instance['design']['font_size'] ) ) {
			$instance['design']['font_size'] .= 'em';
		}

		if ( ! empty( $instance['design']['padding'] ) && is_numeric( $instance['design']['padding'] ) ) {
			$instance['design']['padding'] .= 'em';
		}

		if ( ! empty( $instance['design']['rounding'] ) && is_numeric( $instance['design']['rounding'] ) ) {
			$instance['design']['rounding'] = $instance['design']['rounding'] . 'em ' . $instance['design']['rounding'] . 'em ' . $instance['design']['rounding'] . 'em ' . $instance['design']['rounding'] . 'em';
		}

		return $instance;
	}

	public function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return array(
			sprintf(
				__( 'Add a beautiful tooltip to the Button Widget with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/tooltip" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
		);
	}
}

siteorigin_widget_register( 'sow-button', __FILE__, 'SiteOrigin_Widget_Button_Widget' );

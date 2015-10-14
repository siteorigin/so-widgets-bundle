<?php
/*
Widget Name: Features
Description: Displays a block of features with icons.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

class SiteOrigin_Widget_Features_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-features',
			__( 'SiteOrigin Features', 'so-widgets-bundle' ),
			array(
				'description' => __( 'Displays a list of features.', 'so-widgets-bundle' ),
				'help'        => 'https://siteorigin.com/widgets-bundle/features-widget-documentation/'
			),
			array(),
			array(
				'features' => array(
					'type' => 'repeater',
					'label' => __('Features', 'so-widgets-bundle'),
					'item_name' => __('Feature', 'so-widgets-bundle'),
					'item_label' => array(
						'selector' => "[id*='features-title']",
						'update_event' => 'change',
						'value_method' => 'val'
					),
					'fields' => array(

						// The container shape

						'container_color' => array(
							'type' => 'color',
							'label' => __('Container color', 'so-widgets-bundle'),
							'default' => '#404040',
						),

						// The Icon

						'icon' => array(
							'type' => 'icon',
							'label' => __('Icon', 'so-widgets-bundle'),
						),

						'icon_color' => array(
							'type' => 'color',
							'label' => __('Icon color', 'so-widgets-bundle'),
							'default' => '#FFFFFF',
						),

						'icon_image' => array(
							'type' => 'media',
							'library' => 'image',
							'label' => __('Icon image', 'so-widgets-bundle'),
							'description' => __('Use your own icon image.', 'so-widgets-bundle'),
						),

						// The text under the icon

						'title' => array(
							'type' => 'text',
							'label' => __('Title text', 'so-widgets-bundle'),
						),

						'text' => array(
							'type' => 'text',
							'label' => __('Text', 'so-widgets-bundle')
						),

						'more_text' => array(
							'type' => 'text',
							'label' => __('More link text', 'so-widgets-bundle'),
						),

						'more_url' => array(
							'type' => 'link',
							'label' => __('More link URL', 'so-widgets-bundle'),
						),
					),
				),

				'title_options' => array(
					'type' => 'section',
					'label' => __( 'Title Style', 'so-widgets-bundle' ),
					'fields' => array(
						'font' => array(
							'type' => 'font',
							'label' => __( 'Font', 'so-widgets-bundle' ),
							'default' => 'default'
						),
						'size' => array(
							'type' => 'select',
							'label' => __( 'Size', 'so-widgets-bundle' ),
							'default' => '12px',
							'options' => array(
								'8px' => '8px',
								'9px' => '9px',
								'10px' => '10px',
								'11px' => '11px',
								'12px' => '12px',
								'14px' => '14px',
								'16px' => '16px',
								'18px' => '18px',
								'20px' => '20px',
							)
						),
						'color' => array(
							'type' => 'color',
							'label' => __( 'Color', 'so-widgets-bundle' ),
							'default' => '#000000'
						)
					)
				),

				'text_options' => array(
					'type' => 'section',
					'label' => __( 'Text Style', 'so-widgets-bundle' ),
					'fields' => array(
						'font' => array(
							'type' => 'font',
							'label' => __( 'Font', 'so-widgets-bundle' ),
							'default' => 'default'
						),
						'size' => array(
							'type' => 'select',
							'label' => __( 'Size', 'so-widgets-bundle' ),
							'default' => '12px',
							'options' => array(
								'8px' => '8px',
								'9px' => '9px',
								'10px' => '10px',
								'11px' => '11px',
								'12px' => '12px',
								'14px' => '14px',
								'16px' => '16px',
								'18px' => '18px',
								'20px' => '20px',
							)
						),
						'color' => array(
							'type' => 'color',
							'label' => __( 'Color', 'so-widgets-bundle' ),
							'default' => '#000000'
						)
					)
				),

				'more_text_options' => array(
					'type' => 'section',
					'label' => __( 'More Link Text Style', 'so-widgets-bundle' ),
					'fields' => array(
						'font' => array(
							'type' => 'font',
							'label' => __( 'Font', 'so-widgets-bundle' ),
							'default' => 'default'
						),
						'size' => array(
							'type' => 'select',
							'label' => __( 'Size', 'so-widgets-bundle' ),
							'default' => '12px',
							'options' => array(
								'8px' => '8px',
								'9px' => '9px',
								'10px' => '10px',
								'11px' => '11px',
								'12px' => '12px',
								'14px' => '14px',
								'16px' => '16px',
								'18px' => '18px',
								'20px' => '20px',
							)
						),
						'color' => array(
							'type' => 'color',
							'label' => __( 'Color', 'so-widgets-bundle' ),
							'default' => '#000000'
						)
					)
				),

				'container_shape' => array(
					'type' => 'select',
					'label' => __('Container shape', 'so-widgets-bundle'),
					'default' => 'round',
					'options' => array(
					),
				),

				'container_size' => array(
					'type' => 'number',
					'label' => __('Container size', 'so-widgets-bundle'),
					'default' => 84,
				),

				'icon_size' => array(
					'type' => 'number',
					'label' => __('Icon size', 'so-widgets-bundle'),
					'default' => 24,
				),

				'per_row' => array(
					'type' => 'number',
					'label' => __('Features per row', 'so-widgets-bundle'),
					'default' => 3,
				),

				'responsive' => array(
					'type' => 'checkbox',
					'label' => __('Responsive layout', 'so-widgets-bundle'),
					'default' => true,
				),

				'title_link' => array(
					'type' => 'checkbox',
					'label' => __('Link feature title to more URL', 'so-widgets-bundle'),
					'default' => false,
				),

				'icon_link' => array(
					'type' => 'checkbox',
					'label' => __('Link icon to more URL', 'so-widgets-bundle'),
					'default' => false,
				),

				'new_window' => array(
					'type' => 'checkbox',
					'label' => __('Open more URL in a new window', 'so-widgets-bundle'),
					'default' => false,
				),

			),
			plugin_dir_path(__FILE__).'../'
		);
	}

	function initialize() {
		$this->register_frontend_styles(
			array(
				array(
					'siteorigin-widgets',
					plugin_dir_url(__FILE__) . 'css/style.css',
					array(),
					SOW_BUNDLE_VERSION
				)
			)
		);
	}

	function get_style_name($instance){
		return 'features';
	}

	function get_less_variables( $instance ) {
		$less_vars = array();

		if ( ! empty( $instance['title_options'] ) ) {
			$title_styles = $instance['title_options'];
			if ( ! empty( $title_styles['size'] ) ) {
				$less_vars['title_size'] = $title_styles['size'];
			}
			if ( ! empty( $title_styles['color'] ) ) {
				$less_vars['title_color'] = $title_styles['color'];
			}
			if ( ! empty( $title_styles['font'] ) ) {
				$font = siteorigin_widget_get_font( $title_styles['font'] );
				$less_vars['title_font'] = $font['family'];
				if ( ! empty( $font['weight'] ) ) {
					$less_vars['title_font_weight'] = $font['weight'];
				}
			}
		}

		if ( ! empty( $instance['text_options'] ) ) {
			$text_styles = $instance['text_options'];
			if ( ! empty( $text_styles['size'] ) ) {
				$less_vars['text_size'] = $text_styles['size'];
			}
			if ( ! empty( $text_styles['color'] ) ) {
				$less_vars['text_color'] = $text_styles['color'];
			}
			if ( ! empty( $text_styles['font'] ) ) {
				$font = siteorigin_widget_get_font( $text_styles['font'] );
				$less_vars['text_font'] = $font['family'];
				if ( ! empty( $font['weight'] ) ) {
					$less_vars['text_font_weight'] = $font['weight'];
				}
			}
		}

		if ( ! empty( $instance['more_text_options'] ) ) {
			$more_text_styles = $instance['more_text_options'];
			if ( ! empty( $more_text_styles['size'] ) ) {
				$less_vars['more_text_size'] = $more_text_styles['size'];
			}
			if ( ! empty( $more_text_styles['color'] ) ) {
				$less_vars['more_text_color'] = $more_text_styles['color'];
			}
			if ( ! empty( $more_text_styles['font'] ) ) {
				$font = siteorigin_widget_get_font( $more_text_styles['font'] );
				$less_vars['more_text_font'] = $font['family'];
				if ( ! empty( $font['weight'] ) ) {
					$less_vars['more_text_font_weight'] = $font['weight'];
				}
			}
		}

		return $less_vars;
	}

	/**
	 * Less function for importing Google web fonts.
	 *
	 * @param $instance
	 * @param $args
	 *
	 * @return string
	 */
	function less_import_google_font($instance, $args) {
		if( empty( $instance ) ) return;

		$font_imports = array(
			siteorigin_widget_get_font( $instance['title_options']['font'] ),
			siteorigin_widget_get_font( $instance['text_options']['font'] ),
			siteorigin_widget_get_font( $instance['more_text_options']['font'] ),
		);

		$import_strings = array();
		foreach( $font_imports as $import ) {
			$import_strings[] = !empty($import['css_import']) ? $import['css_import'] : '';
		}

		// Remove empty and duplicate items from the array
		$import_strings = array_filter($import_strings);
		$import_strings = array_unique($import_strings);

		return implode("\n", $import_strings);
	}

	function get_template_name($instance){
		return 'base';
	}

	function modify_form( $form ){
		$form['container_shape']['options'] = include dirname( __FILE__ ) . '/inc/containers.php';
		return $form;
	}
}

siteorigin_widget_register('sow-features', __FILE__, 'SiteOrigin_Widget_Features_Widget');
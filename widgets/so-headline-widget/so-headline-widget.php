<?php

/*
Widget Name: Headline widget
Description: A headline to headline all headlines.
Author: SiteOrigin
Author URI: http://siteorigin.com
*/

class SiteOrigin_Widget_Headline_Widget extends SiteOrigin_Widget {

	function __construct() {

		parent::__construct(
			'sow-headline',
			__( 'SiteOrigin Headline', 'siteorigin-widgets' ),
			array(
				'description' => __( 'A headline widget.', 'siteorigin-widgets' ),
				'help'        => 'http://siteorigin.com/widgets-bundle/headline-widget-documentation/'
			),
			array(),
			array(
				'headline' => array(
					'type' => 'section',
					'label'  => __( 'Headline', 'siteorigin-widgets' ),
					'hide'   => false,
					'fields' => array(
						'text' => array(
							'type' => 'text',
							'label' => __( 'Text', 'siteorigin-widgets' ),
						),
						'font' => array(
							'type' => 'font',
							'label' => __( 'Font', 'siteorigin-widgets' ),
							'default' => 'default'
						),
						'color' => array(
							'type' => 'color',
							'label' => __('Color', 'siteorigin-widgets'),
							'default' => '#000000'
						),
						'align' => array(
							'type' => 'select',
							'label' => __( 'Align', 'siteorigin-widgets' ),
							'default' => 'center',
							'options' => array(
								'center' => __( 'Center', 'siteorigin-widgets' ),
								'left' => __( 'Left', 'siteorigin-widgets' ),
								'right' => __( 'Right', 'siteorigin-widgets' ),
								'justify' => __( 'Justify', 'siteorigin-widgets' )
							)
						)
					)
				),
				'sub_headline' => array(
					'type' => 'section',
					'label'  => __( 'Sub headline', 'siteorigin-widgets' ),
					'hide'   => true,
					'fields' => array(
						'text' => array(
							'type' => 'text',
							'label' => __('Text', 'siteorigin-widgets')
						),
						'font' => array(
							'type' => 'font',
							'label' => __( 'Font', 'siteorigin-widgets' ),
							'default' => 'default'
						),
						'color' => array(
							'type' => 'color',
							'label' => __('Color', 'siteorigin-widgets'),
							'default' => '#000000'
						),
						'align' => array(
							'type' => 'select',
							'label' => __( 'Align', 'siteorigin-widgets' ),
							'default' => 'center',
							'options' => array(
								'center' => __( 'Center', 'siteorigin-widgets' ),
								'left' => __( 'Left', 'siteorigin-widgets' ),
								'right' => __( 'Right', 'siteorigin-widgets' ),
								'justify' => __( 'Justify', 'siteorigin-widgets' )
							)
						)
					)
				),
				'divider' => array(
					'type' => 'section',
					'label' => __( 'Divider', 'siteorigin-widgets' ),
					'hide' => true,
					'fields' => array(
						'style' => array(
							'type' => 'select',
							'label' => __( 'Style', 'siteorigin-widgets' ),
							'default' => 'solid',
							'options' => array(
								'none' => __('None', 'siteorigin-widgets'),
								'solid' => __('Solid', 'siteorigin-widgets'),
								'dotted' => __('Dotted', 'siteorigin-widgets'),
								'dashed' => __('Dashed', 'siteorigin-widgets'),
								'double' => __('Double', 'siteorigin-widgets'),
								'groove' => __('Groove', 'siteorigin-widgets'),
								'ridge' => __('Ridge', 'siteorigin-widgets'),
								'inset' => __('Inset', 'siteorigin-widgets'),
								'outset' => __('Outset', 'siteorigin-widgets'),
							)
						),
						'weight' => array(
							'type' => 'select',
							'label' => __( 'Weight', 'siteorigin-widgets' ),
							'default' => 'thin',
							'options' => array(
								'thin' => __( 'Thin', 'siteorigin-widgets' ),
								'medium' => __( 'Medium', 'siteorigin-widgets' ),
								'thick' => __( 'Thick', 'siteorigin-widgets' ),
							)
						),
						'color' => array(
							'type' => 'color',
							'label' => __('Color', 'siteorigin-widgets'),
							'default' => '#EEEEEE'
						)
					)
				)
			)
		);
	}

	function get_style_name( $instance ) {
		return 'sow-headline';
	}

	function get_less_variables( $instance ) {
		$less_vars = array();

		if ( ! empty( $instance['headline'] ) ) {
			$headline_styles = $instance['headline'];
			if ( ! empty( $headline_styles['align'] ) ) {
				$less_vars['headline_align'] = $headline_styles['align'];
			}
			if ( ! empty( $headline_styles['color'] ) ) {
				$less_vars['headline_color'] = $headline_styles['color'];
			}
			if ( ! empty( $headline_styles['font'] ) ) {
				$font = siteorigin_widget_get_font( $headline_styles['font'] );
				$less_vars['headline_font'] = $font['family'];
				if ( ! empty( $font['weight'] ) ) {
					$less_vars['headline_font_weight'] = $font['weight'];
				}
			}
		}

		if ( ! empty( $instance['sub_headline'] ) ) {
			$sub_headline_styles = $instance['sub_headline'];
			if ( ! empty( $sub_headline_styles['align'] ) ) {
				$less_vars['sub_headline_align'] = $sub_headline_styles['align'];
			}
			if ( ! empty( $sub_headline_styles['color'] ) ) {
				$less_vars['sub_headline_color'] = $sub_headline_styles['color'];
			}
			if ( ! empty( $sub_headline_styles['font'] ) ) {
				$font = siteorigin_widget_get_font( $sub_headline_styles['font'] );
				$less_vars['sub_headline_font'] = $font['family'];
				if ( ! empty( $font['weight'] ) ) {
					$less_vars['sub_headline_font_weight'] = $font['weight'];
				}
			}
		}

		if ( ! empty( $instance['divider'] ) ) {
			$divider_styles = $instance['divider'];

			if ( ! empty( $divider_styles['style'] ) ) {
				$less_vars['divider_style'] = $divider_styles['style'];
			}

			if ( ! empty( $divider_styles['weight'] ) ) {
				$less_vars['divider_weight'] = $divider_styles['weight'];
			}

			if ( ! empty( $divider_styles['color'] ) ) {
				$less_vars['divider_color'] = $divider_styles['color'];
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
			siteorigin_widget_get_font( $instance['headline']['font'] ),
			siteorigin_widget_get_font( $instance['sub_headline']['font'] ),
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

	/**
	 * Get the template for the headline widget
	 *
	 * @param $instance
	 *
	 * @return mixed|string
	 */
	function get_template_name( $instance ) {
		return 'headline';
	}

	/**
	 * Get the template variables for the headline
	 *
	 * @param $instance
	 * @param $args
	 *
	 * @return array
	 */
	function get_template_variables( $instance, $args ) {
		if( empty( $instance ) ) return array();

		return array(
			'headline' => $instance['headline']['text'],
			'sub_headline' => $instance['sub_headline']['text'],
			'has_divider' => ! empty( $instance['divider'] ) && $instance['divider']['style'] != 'none'
		);
	}
}

siteorigin_widget_register('headline', __FILE__);
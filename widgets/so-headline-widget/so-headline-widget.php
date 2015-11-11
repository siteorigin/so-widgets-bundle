<?php

/*
Widget Name: Headline
Description: A headline to headline all headlines.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

class SiteOrigin_Widget_Headline_Widget extends SiteOrigin_Widget {

	function __construct() {

		parent::__construct(
			'sow-headline',
			__( 'SiteOrigin Headline', 'so-widgets-bundle' ),
			array(
				'description' => __( 'A headline widget.', 'so-widgets-bundle' )
			),
			array(),
			array(
				'headline' => array(
					'type' => 'section',
					'label'  => __( 'Headline', 'so-widgets-bundle' ),
					'hide'   => false,
					'fields' => array(
						'text' => array(
							'type' => 'text',
							'label' => __( 'Text', 'so-widgets-bundle' ),
						),
						'tag' => array(
							'type' => 'select',
							'label' => __( 'H Tag', 'so-widgets-bundle' ),
							'default' => 'h1',
							'options' => array(
								'h1' => __( 'H1', 'so-widgets-bundle' ),
								'h2' => __( 'H2', 'so-widgets-bundle' ),
								'h3' => __( 'H3', 'so-widgets-bundle' ),
								'h4' => __( 'H4', 'so-widgets-bundle' ),
								'h5' => __( 'H5', 'so-widgets-bundle' ),
								'h6' => __( 'H6', 'so-widgets-bundle' ),
							)
						),
						'font' => array(
							'type' => 'font',
							'label' => __( 'Font', 'so-widgets-bundle' ),
							'default' => 'default'
						),
						'color' => array(
							'type' => 'color',
							'label' => __('Color', 'so-widgets-bundle'),
						),
						'align' => array(
							'type' => 'select',
							'label' => __( 'Align', 'so-widgets-bundle' ),
							'default' => 'center',
							'options' => array(
								'center' => __( 'Center', 'so-widgets-bundle' ),
								'left' => __( 'Left', 'so-widgets-bundle' ),
								'right' => __( 'Right', 'so-widgets-bundle' ),
								'justify' => __( 'Justify', 'so-widgets-bundle' )
							)
						)
					)
				),
				'sub_headline' => array(
					'type' => 'section',
					'label'  => __( 'Sub headline', 'so-widgets-bundle' ),
					'hide'   => true,
					'fields' => array(
						'text' => array(
							'type' => 'text',
							'label' => __('Text', 'so-widgets-bundle')
						),
						'tag' => array(
							'type' => 'select',
							'label' => __( 'H Tag', 'so-widgets-bundle' ),
							'default' => 'h3',
							'options' => array(
								'h1' => __( 'H1', 'so-widgets-bundle' ),
								'h2' => __( 'H2', 'so-widgets-bundle' ),
								'h3' => __( 'H3', 'so-widgets-bundle' ),
								'h4' => __( 'H4', 'so-widgets-bundle' ),
								'h5' => __( 'H5', 'so-widgets-bundle' ),
								'h6' => __( 'H6', 'so-widgets-bundle' ),
							)
						),
						'font' => array(
							'type' => 'font',
							'label' => __( 'Font', 'so-widgets-bundle' ),
							'default' => 'default'
						),
						'color' => array(
							'type' => 'color',
							'label' => __('Color', 'so-widgets-bundle'),
						),
						'align' => array(
							'type' => 'select',
							'label' => __( 'Align', 'so-widgets-bundle' ),
							'default' => 'center',
							'options' => array(
								'center' => __( 'Center', 'so-widgets-bundle' ),
								'left' => __( 'Left', 'so-widgets-bundle' ),
								'right' => __( 'Right', 'so-widgets-bundle' ),
								'justify' => __( 'Justify', 'so-widgets-bundle' )
							)
						)
					)
				),
				'divider' => array(
					'type' => 'section',
					'label' => __( 'Divider', 'so-widgets-bundle' ),
					'hide' => true,
					'fields' => array(
						'style' => array(
							'type' => 'select',
							'label' => __( 'Style', 'so-widgets-bundle' ),
							'default' => 'solid',
							'options' => array(
								'none' => __('None', 'so-widgets-bundle'),
								'solid' => __('Solid', 'so-widgets-bundle'),
								'dotted' => __('Dotted', 'so-widgets-bundle'),
								'dashed' => __('Dashed', 'so-widgets-bundle'),
								'double' => __('Double', 'so-widgets-bundle'),
								'groove' => __('Groove', 'so-widgets-bundle'),
								'ridge' => __('Ridge', 'so-widgets-bundle'),
								'inset' => __('Inset', 'so-widgets-bundle'),
								'outset' => __('Outset', 'so-widgets-bundle'),
							)
						),
						'weight' => array(
							'type' => 'select',
							'label' => __( 'Weight', 'so-widgets-bundle' ),
							'default' => 'thin',
							'options' => array(
								'thin' => __( 'Thin', 'so-widgets-bundle' ),
								'medium' => __( 'Medium', 'so-widgets-bundle' ),
								'thick' => __( 'Thick', 'so-widgets-bundle' ),
							)
						),
						'color' => array(
							'type' => 'color',
							'label' => __('Color', 'so-widgets-bundle'),
							'default' => '#EEEEEE'
						),
						'side_margin' => array(
							'type' => 'measurement',
							'label' => __('Side Margin', 'so-widgets-bundle'),
							'default' => '60px',
						),
						'top_margin' => array(
							'type' => 'measurement',
							'label' => __('Top/Bottom Margin', 'so-widgets-bundle'),
							'default' => '20px',
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
			if ( ! empty( $headline_styles['tag'] ) ) {
				$less_vars['headline_tag'] = $headline_styles['tag'];
			}
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
			if ( ! empty( $sub_headline_styles['tag'] ) ) {
				$less_vars['sub_headline_tag'] = $sub_headline_styles['tag'];
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

			if ( !empty( $divider_styles['top_margin'] ) && !empty( $divider_styles['top_margin_unit'] ) ) {
				$less_vars['divider_top_margin'] = $divider_styles['top_margin'] . $divider_styles['top_margin_unit'];
			}

			if ( !empty( $divider_styles['side_margin'] ) && !empty( $divider_styles['side_margin_unit'] ) ) {
				$less_vars['divider_side_margin'] = $divider_styles['side_margin'] . $divider_styles['side_margin_unit'];
			}


		}

		return $less_vars;
	}

	function get_google_font_fields( $instance ) {

		return array(
			$instance['headline']['font'],
			$instance['sub_headline']['font'],
		);
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
			'headline_tag' => $instance['headline']['tag'],
			'sub_headline' => $instance['sub_headline']['text'],
			'sub_headline_tag' => $instance['sub_headline']['tag'],
			'has_divider' => ! empty( $instance['divider'] ) && $instance['divider']['style'] != 'none'
		);
	}
}

siteorigin_widget_register('sow-headline', __FILE__, 'SiteOrigin_Widget_Headline_Widget');
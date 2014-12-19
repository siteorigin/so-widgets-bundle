<?php

/*
Widget Name: Headline widget
Description: A headline to headline all headlines.
Author: SiteOrigin
Author URI: http://siteorigin.com
*/

class SiteOrigin_Widget_Headline_Widget extends SiteOrigin_Widget {

	static $web_safe = array(
		'Helvetica Neue' => 'Arial, Helvetica, Geneva, sans-serif',
		'Lucida Grande' => 'Lucida, Verdana, sans-serif',
		'Georgia' => '"Times New Roman", Times, serif',
		'Courier New' => 'Courier, mono',
	);

	static $google_web_fonts;

	function __construct() {
		self::$google_web_fonts = include ( dirname(__FILE__) . '/data/google-fonts.php' );

		// Add the default fonts
		$fonts = array(
			'Helvetica Neue' => 'Helvetica Neue',
			'Lucida Grande' => 'Lucida Grande',
			'Georgia' => 'Georgia',
			'Courier New' => 'Courier New',
		);

		foreach ( self::$google_web_fonts as $font => $variants ) {
			foreach ( $variants as $variant ) {
				if ( $variant == 'regular' || $variant == 400 ) {
					$fonts[ $font ] = $font;
				}
				else {
					$fonts[ $font . ':' . $variant ] = $font . ' (' . $variant . ')';
				}
			}
		}

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
							'label' => __( 'Text', 'siteorigin-wdigets' ),
						),
						'font' => array(
							'type' => 'select',
							'label' => __( 'Font', 'siteorigin-widgets' ),
							'options' => $fonts
						),
						'color' => array(
							'type' => 'color',
							'label' => __('Color', 'siteorigin-widgets')
						),
						'align' => array(
							'type' => 'select',
							'label' => __( 'Align', 'siteorigin-widgets' ),
							'options' => array(
								'left' => __( 'Left', 'siteorigin-widgets' ),
								'right' => __( 'Right', 'siteorigin-widgets' ),
								'center' => __( 'Center', 'siteorigin-widgets' ),
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
							'label' => __('Text', 'siteorigin-wdigets')
						),
						'font' => array(
							'type' => 'select',
							'label' => __( 'Font', 'siteorigin-widgets' ),
							'options' => $fonts
						),
						'color' => array(
							'type' => 'color',
							'label' => __('Color', 'siteorigin-widgets')
						),
						'align' => array(
							'type' => 'select',
							'label' => __( 'Align', 'siteorigin-widgets' ),
							'options' => array(
								'left' => __( 'Left', 'siteorigin-widgets' ),
								'right' => __( 'Right', 'siteorigin-widgets' ),
								'center' => __( 'Center', 'siteorigin-widgets' ),
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
						'weight' => array(
							'type' => 'select',
							'label' => __( 'Weight', 'siteorigin-widgets' ),
							'options' => array(
								'thin' => __( 'Thin', 'siteorigin-widgets' ),
								'medium' => __( 'Medium', 'siteorigin-widgets' ),
								'thick' => __( 'Thick', 'siteorigin-widgets' ),
							)
						),
						'style' => array(
							'type' => 'select',
							'label' => __( 'Style', 'siteorigin-widgets' ),
							'options' => array(
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
						'color' => array(
							'type' => 'color',
							'label' => __('Color', 'siteorigin-widgets'),
							'default' => '#000000'
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
				$font = $headline_styles['font'];
				if ( isset( self::$web_safe[ $font ] ) ) {
					$less_vars['headline_font'] = self::$web_safe[ $font ];
				}
				else {
					$font_parts = explode( ':', $font );
					$less_vars['headline_font'] = $font_parts[0];
					if ( count( $font_parts ) > 1 ) {
						$less_vars['headline_font_weight'] = $font_parts[1];
					}
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
				$font = $sub_headline_styles['font'];
				if ( isset( self::$web_safe[ $font ] ) ) {
					$less_vars['sub_headline_font'] = self::$web_safe[ $font ];
				}
				else {
					$font_parts = explode( ':', $font );
					$less_vars['sub_headline_font'] = $font_parts[0];
					if ( count( $font_parts ) > 1 ) {
						$less_vars['sub_headline_font_weight'] = $font_parts[1];
					}
				}
			}
		}

		if ( ! empty( $instance['divider'] ) ) {
			$divider_styles = $instance['divider'];

			if ( ! empty( $divider_styles['weight'] ) ) {
				$less_vars['divider_weight'] = $divider_styles['weight'];
			}

			if ( ! empty( $divider_styles['style'] ) ) {
				$less_vars['divider_style'] = $divider_styles['style'];
			}

			if ( ! empty( $divider_styles['color'] ) ) {
				$less_vars['divider_color'] = $divider_styles['color'];
			}
		}

		return $less_vars;
	}

	function less_import_google_font($instance, $args) {
		$this->extract_google_font( $instance['headline']['font'], $fonts );
		$this->extract_google_font( $instance['sub_headline']['font'], $fonts );

		return empty( $fonts ) ? '' : '@import url(http' . ( is_ssl() ? 's' : '' ) . '://fonts.googleapis.com/css?family=' . implode( '|', $fonts ) . '); ';
	}

	private function extract_google_font($font, &$fonts) {
		$font_parts = explode( ':', $font );
		if ( isset( self::$google_web_fonts[ $font_parts[0] ] ) ) {
			$gfont = urlencode( $font_parts[0] );
			if ( count( $font_parts ) > 1 ) {
				$gfont .= ':' . $font_parts[1];
			}
			$fonts[] = $gfont;
		}
	}

	function get_template_name( $instance ) {
		return 'headline';
	}

	function get_template_variables( $instance, $args ) {
		return array(
			'headline' => $instance['headline']['text'],
			'sub_headline' => $instance['sub_headline']['text'],
		);
	}
}

siteorigin_widget_register('headline', __FILE__);
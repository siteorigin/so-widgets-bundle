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
							'label' => __( 'Headline', 'siteorigin-wdigets' ),
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
							'label' => __('Sub headline', 'siteorigin-wdigets')
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
				)
			)
		);
	}

	function get_style_name( $instance ) {
		return 'sow-headline';
	}

	function get_less_variables( $instance ) {
		$headline_font = $instance['headline']['font'];
		$sub_headline_font = $instance['sub_headline']['font'];
		if ( isset( self::$web_safe[ $headline_font ] ) ) {
			$headline_font = self::$web_safe[ $headline_font ];
		}
		if ( isset( self::$web_safe[ $sub_headline_font ] ) ) {
			$sub_headline_font = self::$web_safe[ $sub_headline_font ];
		}
		return array(
			'headline_align' => $instance['headline']['align'],
			'headline_color' => $instance['headline']['color'],
			'headline_font' => $headline_font,
			'sub_headline_align' => $instance['sub_headline']['align'],
			'sub_headline_color' => $instance['sub_headline']['color'],
			'sub_headline_font' => $sub_headline_font,
		);
	}

	function less_import_google_font($instance, $args) {
		$headline_font = $instance['headline']['font'];
		$sub_headline_font = $instance['sub_headline']['font'];
		$fonts = array();
		if ( isset( self::$google_web_fonts[ $headline_font ] ) ) {
			$fonts[] = $headline_font;
		}
		if ( isset( self::$google_web_fonts[ $sub_headline_font ] ) ) {
			$fonts[] = $sub_headline_font;
		}
		$fams = implode( '|', $fonts );
		return empty( $fonts ) ? '' : '@import url(http' . ( is_ssl() ? 's' : '' ) . '://fonts.googleapis.com/css?family=' . $fams . '); ';
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
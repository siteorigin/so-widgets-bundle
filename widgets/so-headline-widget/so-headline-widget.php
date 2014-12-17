<?php

/*
Widget Name: Headline widget
Description: A headline to headline all headlines.
Author: SiteOrigin
Author URI: http://siteorigin.com
*/

class SiteOrigin_Widget_Headline_Widget extends SiteOrigin_Widget {
	function __construct() {
		$google_web_fonts = include ( dirname(__FILE__) . '/data/google-fonts.php' );

		// Add the default fonts
		$fonts = array(
			'Helvetica Neue' => 'Helvetica Neue',
			'Lucida Grande' => 'Lucida Grande',
			'Georgia' => 'Georgia',
			'Courier New' => 'Courier New',
		);

		foreach ( $google_web_fonts as $font => $variants ) {
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
		return '';
	}

	function get_template_name( $instance ) {
		return 'headline';
	}
}

siteorigin_widget_register('headline', __FILE__);
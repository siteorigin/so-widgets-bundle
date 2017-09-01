<?php
/*
Widget Name: Accordion
Description: An accordion to squeeze a lot of content into a small space.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

class SiteOrigin_Widget_Accordion_Widget extends SiteOrigin_Widget {
	function __construct() {
		
		parent::__construct(
			'sow-accordion',
			__( 'SiteOrigin Accordion', 'so-widgets-bundle' ),
			array(
				'description' => __( 'An accordion widget.', 'so-widgets-bundle' ),
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}
	
	function get_widget_form() {
		
		$panel_content = array(
			'type'  => 'tinymce',
			'label' => __( 'Text', 'so-widgets-bundle' ),
		);
		$page_builder_active = false;
		if ( $page_builder_active ) {
			$panel_content = array(
				'type'  => 'widget',
				'label' => __( 'Layout', 'so-widgets-bundle' ),
				'class' => 'SiteOrigin_Panels_Widgets_Layout',
			);
		}
		
		return array(
			'panels' => array(
				'type' => 'repeater',
				'label' => __( 'Panels', 'so-widgets-bundle' ),
				'fields' => array(
					'title' => array(
						'type' => 'text',
						'label' => __( 'Title', 'so-widgets-bundle' ),
					),
					'panel_content' => $panel_content,
					'initial_state_open' => array(
						'type' => 'checkbox',
						'label' => __( 'Open?' )
					),
				),
			),
			'max_open_panels' => array(
				'type' => 'number',
				'label' => __( 'Maximum number of simultaneous open panels', 'so-widgets-bundle' ),
			),
			'design' => array(
				'type' =>  'section',
				'label' => __( 'Design', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'heading' => array(
						'type' => 'section',
						'label' => __( 'Headings', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'background_color' => array(
								'type' => 'color',
								'label' => __( 'Background color', 'so-widgets-bundle' ),
								'default' => '#828282',
							),
							'background_hover_color' => array(
								'type' => 'color',
								'label' => __( 'Background hover color', 'so-widgets-bundle' ),
								'default' => '#8C8C8C',
							),
							'title_color' => array(
								'type' => 'color',
								'label' => __( 'Title color', 'so-widgets-bundle' ),
								'default' => '#FFFFFF',
							),
							'title_hover_color' => array(
								'type' => 'color',
								'label' => __( 'Title hover color', 'so-widgets-bundle' ),
							),
							'title_icon' => array(
								'type' => 'icon',
								'label' => __( 'Title icon', 'so-widgets-bundle' ),
							),
							'title_icon_location' => array(
								'type' => 'select',
								'label' => __( 'Title icon location', 'so-widgets-bundle' ),
								'options' => array(
									'left' => __( 'Left', 'so-widgets-bundle' ),
									'right' => __( 'Right', 'so-widgets-bundle' ),
								),
								'default' => 'left',
							),
							'title_font_family' => array(
								'type' => 'font',
								'label' => __( 'Title font', 'so-widgets-bundle' ),
							),
							'border_color' => array(
								'type' => 'color',
								'label' => __( 'Border color', 'so-widgets-bundle' ),
							),
							'border_hover_color' => array(
								'type' => 'color',
								'label' => __( 'Border hover color', 'so-widgets-bundle' ),
							),
							'border_width' => array(
								'type' => 'measurement',
								'label' => __( 'Border width', 'so-widgets-bundle' ),
							),
							'border_radius' => array(
								'type' => 'measurement',
								'label' => __( 'Border radius', 'so-widgets-bundle' ),
							),
							'icon_open' => array(
								'type' => 'icon',
								'label' => __( 'Open icon', 'so-widgets-bundle' ),
								'default' => 'fontawesome-plus',
							),
							'icon_close' => array(
								'type' => 'icon',
								'label' => __( 'Close icon', 'so-widgets-bundle' ),
								'default' => 'fontawesome-minus',
							),
							'open_close_icon_location' => array(
								'type' => 'select',
								'label' => __( 'Open/close icon location', 'so-widgets-bundle' ),
								'options' => array(
									'left' => __( 'Left', 'so-widgets-bundle' ),
									'right' => __( 'Right', 'so-widgets-bundle' ),
								),
								'default' => 'right',
							),
							'open_close_icon_color' => array(
								'type' => 'color',
								'label' => __( 'Open/close icon color', 'so-widgets-bundle' ),
								'default' => '#FFFFFF',
							),
							'open_close_icon_hover_color' => array(
								'type' => 'color',
								'label' => __( 'Open/close icon hover color', 'so-widgets-bundle' ),
							),
							'padding' => array(
								'type' => 'measurement',
								'label' => __( 'Padding', 'so-widgets-bundle' ),
//								'default' => '30px',
							),
						),
					),
					'panels' => array(
						'type' => 'section',
						'label' => __( 'Panels', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'background_color' => array(
								'type' => 'color',
								'label' => __( 'Background color' . 'so-widgets-bundle' ),
								'default' => '#F9F9F9',
							),
							// Not sure whether these font settings are appropriate.
							'text_color' => array(
								'type' => 'color',
								'label' => __( 'Text color', 'so-widgets-bundle' ),
							),
							'text_font_family' => array(
								'type' => 'font',
								'label' => __( 'Text font family', 'so-widgets-bundle' ),
							),
							'border_color' => array(
								'type' => 'color',
								'label' => __( 'Border color', 'so-widgets-bundle' ),
							),
							'border_width' => array(
								'type' => 'measurement',
								'label' => __( 'Border width', 'so-widgets-bundle' ),
							),
							'border_radius' => array(
								'type' => 'measurement',
								'label' => __( 'Border radius', 'so-widgets-bundle' ),
							),
							'padding' => array(
								'type' => 'measurement',
								'label' => __( 'Padding', 'so-widgets-bundle' ),
//								'default' => '30px',
							),
							'margin_bottom' => array(
								'type' => 'measurement',
								'label' => __( 'Bottom margin', 'so-widgets-bundle' ),
								'default' => '10px',
							),
						),
					),
				),
			),
		);
	}
}

siteorigin_widget_register('sow-accordion', __FILE__, 'SiteOrigin_Widget_Accordion_Widget');

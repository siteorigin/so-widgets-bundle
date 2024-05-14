<?php
/*
Widget Name: Button Grid
Description: Add multiple buttons in one go, customize individually, and present them in a neat grid layout.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/button-grid-widget/
*/

class SiteOrigin_Widget_Button_Grid_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'sow-button-grid',
			__( 'SiteOrigin Button Grid', 'so-widgets-bundle' ),
			array(
				'description' => __( 'Add multiple buttons in one go, customize individually, and present them in a neat grid layout.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/button-grid-widget/',
				'instance_storage' => true,
				'panels_title' => false,
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	public function get_settings_form() {
		return array(
			'responsive_breakpoint' => array(
				'type' => 'measurement',
				'label' => __( 'Responsive Breakpoint', 'so-widgets-bundle' ),
				'default' => '780px',
				'description' => __( 'Device width, in pixels, to collapse into a mobile view.', 'so-widgets-bundle' ),
			),
		);
	}

	function get_widget_form() {
		return array(
			'buttons' => array(
				'type' => 'repeater',
				'label' => __( 'Buttons', 'so-widgets-bundle' ),
				'item_name' => __( 'Button', 'so-widgets-bundle' ),
				'item_label' => array(
					'selector' => "[id*='text']",
					'update_event' => 'change',
					'value_method' => 'val',
				),

				'fields' => array(
					'widget' => array(
						'type' => 'widget',
						'collapsible' => false,
						'class' => 'SiteOrigin_Widget_Button_Widget',
					),
				),
			),

			'layout' => array(
				'type' => 'section',
				'label' => __( 'Layout', 'so-widgets-bundle' ),
				'fields' => array(
					'desktop' => array(
						'type' => 'section',
						'label' => __( 'Desktop', 'so-widgets-bundle' ),
						'fields' => array(
							'columns' => array(
								'type' => 'number',
								'label' => __( 'Buttons Per Line', 'so-widgets-bundle' ),
								'default' => 3,
							),
							'alignment' => array(
								'type' => 'select',
								'label' => __( 'Button Alignment', 'so-widgets-bundle' ),
								'default' => 'space-between',
								'options' => array(
									'space-between' => __( 'Space Between Items', 'so-widgets-bundle' ),
									'space-evenly' => __( 'Items Spaced Evenly', 'so-widgets-bundle' ),
									'left' => __( 'Left', 'so-widgets-bundle' ),
									'center' => __( 'Center', 'so-widgets-bundle' ),
									'end' => __( 'Right', 'so-widgets-bundle' ),
								),
							),
							'gap' => array(
								'type' => 'multi-measurement',
								'label' => __( 'Gap', 'so-widgets-bundle' ),
								'default' => '20px 20px',
								'measurements' => array(
									'row' => __( 'Row', 'so-widgets-bundle' ),
									'column' => __( 'Column', 'so-widgets-bundle' ),
								),
							),
						),
					),
					'mobile' => array(
						'type' => 'section',
						'label' => __( 'Mobile', 'so-widgets-bundle' ),
						'fields' => array(
							'columns' => array(
								'type' => 'number',
								'label' => __( 'Buttons Per Line', 'so-widgets-bundle' ),
								'default' => 1,
							),
							'alignment' => array(
								'type' => 'select',
								'label' => __( 'Button Alignment', 'so-widgets-bundle' ),
								'default' => 'space-between',
								'options' => array(
									'space-between' => __( 'Space Between Items', 'so-widgets-bundle' ),
									'below' => __( 'Evenly', 'so-widgets-bundle' ),
									'space-evenaly' => __( 'Items Spaced Evenly', 'so-widgets-bundle' ),
									'left' => __( 'Left', 'so-widgets-bundle' ),
									'center' => __( 'Center', 'so-widgets-bundle' ),
									'end' => __( 'Right', 'so-widgets-bundle' ),
								),
							),
							'gap' => array(
								'type' => 'multi-measurement',
								'label' => __( 'Gap', 'so-widgets-bundle' ),
								'default' => '20px 20px',
								'measurements' => array(
									'row' => __( 'Row', 'so-widgets-bundle' ),
									'column' => __( 'Column', 'so-widgets-bundle' ),
								),
							),

						),
					),
				),
			),
		);
	}

	public function get_less_variables( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		return array(
			'responsive_breakpoint' => $this->get_global_settings( 'responsive_breakpoint' ),
			'columns' => ! empty( $instance['layout']['desktop']['columns'] ) ? 100 / $instance['layout']['desktop']['columns'] . '%' : 33.33,
			'alignment' => ! empty( $instance['layout']['desktop']['alignment'] ) ? $instance['layout']['desktop']['alignment'] : 'space-between',
			'mobile_columns' => ! empty( $instance['layout']['mobile']['columns'] ) ? 100 / $instance['layout']['mobile']['columns'] . '%' : 100,
			'mobile_alignment' => ! empty( $instance['layout']['mobile']['alignment'] ) ? $instance['layout']['mobile']['alignment'] : 'space-between',
			'gap' => ! empty( $instance['layout']['desktop']['gap'] ) ? $instance['layout']['desktop']['gap'] : '20px',
			'mobile_gap' => ! empty( $instance['layout']['mobile']['gap'] ) ? $instance['layout']['mobile']['gap'] : '20px',
		);
	}
}
siteorigin_widget_register( 'sow-button-grid', __FILE__, 'SiteOrigin_Widget_Button_Grid_Widget' );

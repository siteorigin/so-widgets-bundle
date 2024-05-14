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
							'system' => array(
								'type' => 'radio',
								'label' => __( 'Layout System', 'so-widgets-bundle' ),
								'default' => 'grid',
								'options' => array(
									'grid' => __( 'Grid', 'so-widgets-bundle' ),
									'flex' => __( 'Flex', 'so-widgets-bundle' ),
								),
								'state_emitter' => array(
									'callback' => 'select',
									'args' => array( 'system' )
								),
							),
							'alignment_flex' => array(
								'type' => 'select',
								'label' => __( 'Button Alignment', 'so-widgets-bundle' ),
								'default' => 'space-between',
								'state_handler' => array(
									'system[flex]' => array( 'show' ),
									'_else[system]' => array( 'hide' ),
								),
								'options' => array(
									'space-between' => __( 'Space Between Items', 'so-widgets-bundle' ),
									'space-evenly' => __( 'Items Spaced Evenly', 'so-widgets-bundle' ),
									'left' => __( 'Left', 'so-widgets-bundle' ),
									'center' => __( 'Center', 'so-widgets-bundle' ),
									'end' => __( 'Right', 'so-widgets-bundle' ),
								),
							),
							'columns' => array(
								'type' => 'number',
								'label' => __( 'Buttons Per Line', 'so-widgets-bundle' ),
								'default' => 3,
								'state_handler' => array(
									'system[grid]' => array( 'show' ),
									'_else[system]' => array( 'hide' ),
								),
							),
							'alignment_grid' => array(
								'type' => 'select',
								'label' => __( 'Button Alignment', 'so-widgets-bundle' ),
								'default' => 'center',
								'state_handler' => array(
									'system[grid]' => array( 'show' ),
									'_else[system]' => array( 'hide' ),
								),
								'options' => array(
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
							'system' => array(
								'type' => 'radio',
								'label' => __( 'Layout System', 'so-widgets-bundle' ),
								'default' => 'grid',
								'options' => array(
									'grid' => __( 'Grid', 'so-widgets-bundle' ),
									'flex' => __( 'Flex', 'so-widgets-bundle' ),
								),
								'state_emitter' => array(
									'callback' => 'select',
									'args' => array( 'system_mobile' )
								),
							),
							'alignment_flex' => array(
								'type' => 'select',
								'label' => __( 'Button Alignment', 'so-widgets-bundle' ),
								'default' => 'space-between',
								'state_handler' => array(
									'system_mobile[flex]' => array( 'show' ),
									'_else[system_mobile]' => array( 'hide' ),
								),
								'options' => array(
									'space-between' => __( 'Space Between Items', 'so-widgets-bundle' ),
									'space-evenly' => __( 'Items Spaced Evenly', 'so-widgets-bundle' ),
									'left' => __( 'Left', 'so-widgets-bundle' ),
									'center' => __( 'Center', 'so-widgets-bundle' ),
									'end' => __( 'Right', 'so-widgets-bundle' ),
								),
							),
							'columns' => array(
								'type' => 'number',
								'label' => __( 'Buttons Per Line', 'so-widgets-bundle' ),
								'default' => 3,
								'state_handler' => array(
									'system_mobile[grid]' => array( 'show' ),
									'_else[system_mobile]' => array( 'hide' ),
								),
							),
							'alignment_grid' => array(
								'type' => 'select',
								'label' => __( 'Button Alignment', 'so-widgets-bundle' ),
								'default' => 'center',
								'state_handler' => array(
									'system_mobile[grid]' => array( 'show' ),
									'_else[system_mobile]' => array( 'hide' ),
								),
								'options' => array(
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

		$settings = array(
			'responsive_breakpoint' => $this->get_global_settings( 'responsive_breakpoint' ),
			'gap' => ! empty( $instance['layout']['desktop']['gap'] ) ? $instance['layout']['desktop']['gap'] : '20px',
			'mobile_gap' => ! empty( $instance['layout']['mobile']['gap'] ) ? $instance['layout']['mobile']['gap'] : '20px',
		);

		$settings = $this->generate_system_css(
			$settings,
			$instance['layout']['desktop'],
			'desktop'
		);

		$settings = $this->generate_system_css(
			$settings,
			$instance['layout']['mobile'],
			'mobile'
		);

		return $settings;
	}

	private function generate_system_css( $settings, $context_settings, $context ) {
		if ( $context_settings['system'] === 'grid' ) {
			$settings[ $context . '_system' ] = 'grid';
			$settings[ $context . '_columns' ] = ! empty( $context_settings['columns'] ) ? (int) $context_settings['columns'] : 3;
			$settings[ $context . '_alignment' ] = ! empty( $context_settings['alignment_grid'] ) ? $context_settings['alignment_grid'] : 'center';
		} else {
			$settings[ $context . '_system' ] = 'flex';
			$settings[ $context . '_alignment' ] = ! empty( $context_settings['alignment_flex'] ) ? $context_settings['alignment_flex'] : 'space-between';
		}

		return $settings;
	}
}
siteorigin_widget_register( 'sow-button-grid', __FILE__, 'SiteOrigin_Widget_Button_Grid_Widget' );

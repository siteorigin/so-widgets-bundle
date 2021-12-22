<?php

abstract class SiteOrigin_Widget_Base_Carousel extends SiteOrigin_Widget {

	/**
	 * Register all the frontend scripts and styles for the base carousel.
	 */
	function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'slick',
					plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/lib/slick' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' ),
					'1.8.1'
				),
				array(
					'sow-carousel',
					plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/carousel' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery', 'slick' ),
					SOW_BUNDLE_VERSION,
					true
				),
			)
		);

		$this->register_frontend_styles(
			array(
				array(
					'slick',
					plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'css/lib/slick.css',
					array(),
					'1.8.1'
				),
			)
		);

	}

	/**
	 * Allow widgets to override settings.
	 *
	 * @return array If overridden, an array is expected.
	 */
	function override_carousel_settings() {
		// Intentionally left blank.
	}

	/**
	 * Handle carousel specific settings and defaults.
	 *
	 * @return array
	 */
	private function get_carousel_settings() {
		return wp_parse_args(
			$this->override_carousel_settings(),
			array(
				'breakpoints' => array(
					'tablet_landscape' => 1366,
					'tablet_portrait' => 1025,
					'mobile' => 480,
				),
				'slides_to_scroll' => array(
					'desktop' => 3,
					'tablet_landscape' => 3,
					'tablet_portrait' => 2,
					'mobile' => 1,
				),
				'slides_to_show' => array(
					'desktop' => 3,
					'tablet_landscape' => 3,
					'tablet_portrait' => 2,
					'mobile' => 1,
				),
				'navigation' => array(
					'desktop' => true,
					'tablet_landscape' => true,
					'tablet_portrait' => true,
					'mobile' => true,
				),
				'navigation_label' => __( 'Display navigation arrows', 'so-widgets-bundle' ),
			)
		);
	}

	/**
	 * Utility method for adding section groups.
	 *
	 * @param array $field Field data
	 * @param string $value_type Whether the field is a placeholder or standard field. This controls whether the field data is stored by default.
	 *
	 * @return array The structured section group.
	 */
	private function add_section_group( $field, $value_type ) {		
		$carousel_settings = $this->get_carousel_settings();

		$section = array(
			'type' => 'section',
			'label' => $field['label'],
			'hide' => true,
			'fields' => array(),
		);

		if ( isset( $field['fields'] ) ) {
			foreach ( $field['fields'] as $sub_field_key => $sub_field ) {
				$section['fields'][ $sub_field_key ] = $this->add_section_group( $sub_field, $value_type );
			}
		} else {
			if ( isset( $field['breakpoint'] ) ) {
				$section['fields']['breakpoint'] = array(
					'type' => 'number',
					'label' => __( 'Breakpoint', 'so-widgets-bundle' ),
					$value_type => $field['breakpoint'],
				);
			}

			$section['fields']['slides_to_scroll'] = array(
				'type' => 'number',
				'label' => __( 'Slides to scroll', 'so-widgets-bundle' ),
				'description' => sprintf(
					__( 'Set the number of slides to scroll per navigation click or swipe on %s', 'so-widgets-bundle' ),
					strtolower( $field['label'] )
				),
				$value_type => $field['slides_to_scroll'],
			);

			if ( ! empty( $carousel_settings['slides_to_show'] ) ) {
				$section['fields']['slides_to_show'] = array(
					'type' => 'number',
					'label' => __( 'Slides to show ', 'so-widgets-bundle' ),
					'description' => sprintf(
						__( 'The number of slides to show on %s.', 'so-widgets-bundle' ),
						strtolower( $field['label'] )
					),
					$value_type => $field['slides_to_show'],
				);
			}

			if ( isset( $field['navigation'] ) ) {
				$section['fields']['navigation'] = array(
					'type' => 'checkbox',
					'label' => $carousel_settings['navigation_label'],
					'default' => $field['navigation'],
				);
			}
		}

		return $section;

	}

	function responsive_form_fields( $context = 'widget' ) {
		$carousel_settings = $this->get_carousel_settings();

		// If the context is a widget, the global values are displayed using a
		// placeholder to prevent the values from being stored.
		$value_type = $context == 'widget' ? 'placeholder' : 'default';
		$fields = array(
			'desktop' => array(
				'label' => __( 'Desktop', 'so-widgets-bundle' ),
				'slides_to_scroll' => $carousel_settings['slides_to_scroll']['desktop'],
				'navigation' => $carousel_settings['navigation']['desktop'],
			),
			'tablet' => array(
				'label' => __( 'Tablet', 'so-widgets-bundle' ),
				'fields' => array(
					'landscape' => array(
						'label' => __( 'Landscape', 'so-widgets-bundle' ),
						'breakpoint' => $carousel_settings['breakpoints']['tablet_landscape'],
						'slides_to_scroll' => $carousel_settings['slides_to_scroll']['tablet_landscape'],
						'navigation' => $carousel_settings['navigation']['tablet_landscape'],
					),
					'portrait' => array(
						'label' => __( 'Portrait', 'so-widgets-bundle' ),
						'breakpoint' => $carousel_settings['breakpoints']['tablet_portrait'],
						'slides_to_scroll' => $carousel_settings['slides_to_scroll']['tablet_portrait'],
						'navigation' => $carousel_settings['navigation']['tablet_portrait'],
					),
				),
			),
			'mobile' => array(
				'label' => __( 'Mobile', 'so-widgets-bundle' ),
				'breakpoint' => $carousel_settings['breakpoints']['mobile'],
				'slides_to_scroll' => $carousel_settings['slides_to_scroll']['mobile'],
				'navigation' => $carousel_settings['navigation']['mobile'],
			),
		);

		// Add slides to show settings if this widget uses them.
		if ( ! empty( $carousel_settings['slides_to_show'] ) ) {
			$fields['desktop']['slides_to_show'] = $carousel_settings['slides_to_show']['desktop'];
			$fields['tablet']['fields']['landscape']['slides_to_show'] = $carousel_settings['slides_to_show']['tablet_landscape'];
			$fields['tablet']['fields']['portrait']['slides_to_show'] = $carousel_settings['slides_to_show']['tablet_portrait'];
			$fields['mobile']['slides_to_show'] = $carousel_settings['slides_to_show']['mobile'];
		}

		$generated_fields = array();
		foreach ( $fields as $field_key => $field ) {
			$generated_fields[ $field_key ] = $this->add_section_group( $field, $value_type );
		}

		return array(
			'type' => 'section',
			'label' => __( 'Responsive', 'so-widgets-bundle' ),
			'hide' => $context == 'widget',
			'fields' => $generated_fields,
		);
	}

	function carousel_settings_form_fields() {
		return array(
			'type' => 'section',
			'label' => __( 'Settings', 'so-widgets-bundle' ),
			'hide' => true,
			'fields' => array(
				'loop' => array(
					'type' => 'checkbox',
					'label' => __( 'Loop Items', 'so-widgets-bundle' ),
					'description' => __( 'Automatically return to the first item after the last item.', 'so-widgets-bundle' ),
					'default' => true,
				),
				'dots' => array(
					'type' => 'checkbox',
					'label' => __( 'Navigation dots', 'so-widgets-bundle' ),
				),
				'animation' => array(
					'type' => 'select',
					'label' => __( 'Animation', 'so-widgets-bundle' ),
					'default' => 'Ease',
					'options' => array(
						'ease' => __( 'Ease', 'so-widgets-bundle' ),
						'linear' => __( 'Linear', 'so-widgets-bundle' ),
					),
				),
				'animation_speed' => array(
					'type' => 'number',
					'label' => __( 'Animation speed', 'so-widgets-bundle' ),
					'default' => 800,
				),
				'autoplay' => array(
					'type' => 'checkbox',
					'label' => __( 'Autoplay', 'so-widgets-bundle' ),
					'state_emitter' => array(
						'callback' => 'conditional',
						'args' => array(
							'autoplay[show]: val',
							'autoplay[hide]: ! val',
						),
					)
				),
				'autoplay_pause_hover' => array(
					'type' => 'checkbox',
					'label' => __( 'Autoplay pause on hover', 'so-widgets-bundle' ),
					'state_handler' => array(
						'autoplay[show]' => array( 'show' ),
						'autoplay[hide]' => array( 'hide' ),
					),
				),

				'timeout' => array(
					'type' => 'number',
					'label' => __( 'Timeout', 'so-widgets-bundle' ),
					'default' => 8000,
					'state_handler' => array(
						'autoplay[show]' => array( 'show' ),
						'autoplay[hide]' => array( 'hide' ),
					),
				),
			),
		);
	}


	function design_settings_form_fields( $settings = array() ) {
		$fields = array(
			'type' => 'section',
			'label' => __( 'Design', 'so-widgets-bundle' ),
			'hide' => true,
			'fields' => array(
				'item_title' => array(
					'type' => 'section',
					'label' => __( 'Item title', 'so-widgets-bundle' ),
					'hide' => true,
					'fields' => array(
						'tag' => array(
							'type' => 'select',
							'label' => __( 'HTML Tag', 'so-widgets-bundle' ),
							'default' => 'h4',
							'options' => array(
								'h1' => __( 'H1', 'so-widgets-bundle' ),
								'h2' => __( 'H2', 'so-widgets-bundle' ),
								'h3' => __( 'H3', 'so-widgets-bundle' ),
								'h4' => __( 'H4', 'so-widgets-bundle' ),
								'h5' => __( 'H5', 'so-widgets-bundle' ),
								'h6' => __( 'H6', 'so-widgets-bundle' ),
								'p' => __( 'Paragraph', 'so-widgets-bundle' ),
							),
						),
						'font' => array(
							'type' => 'font',
							'label' => __( 'Font', 'so-widgets-bundle' ),
						),
						'size' => array(
							'type' => 'measurement',
							'label' => __( 'Font size', 'so-widgets-bundle' ),
						),
						'color' => array(
							'type' => 'color',
							'label' => __( 'Color', 'so-widgets-bundle' ),
						),
					),
				),
			),
		);

		if ( ! empty( $settings ) ) {
			$fields = array_merge_recursive( $fields, array( 'fields' => $settings ) );
		}

		return $fields;
	}

	function get_settings_form() {
		return array(
			'responsive' => $this->responsive_form_fields( 'global' ),
		);
	}

	function responsive_template_variables( $responsive, $encode = true ) {
		$carousel_settings = $this->get_carousel_settings();

		$variables = array(
			'desktop_slides_to_scroll' => ! empty( $responsive['desktop']['slides_to_scroll'] ) ? $responsive['desktop']['slides_to_scroll'] : $carousel_settings['slides_to_scroll']['desktop'],
			'tablet_landscape_breakpoint' => ! empty( $responsive['tablet']['landscape']['breakpoint'] ) ? $responsive['tablet']['landscape']['breakpoint'] : $carousel_settings['breakpoints']['tablet_landscape'],
			'tablet_landscape_slides_to_scroll' => ! empty( $responsive['tablet']['landscape']['slides_to_scroll'] ) ? $responsive['tablet']['landscape']['slides_to_scroll'] : $carousel_settings['slides_to_scroll']['tablet_landscape'],
			'tablet_portrait_breakpoint' => ! empty( $responsive['tablet']['portrait']['breakpoint'] ) ? $responsive['tablet']['portrait']['breakpoint'] : $carousel_settings['breakpoints']['tablet_portrait'],
			'tablet_portrait_slides_to_scroll' => ! empty( $responsive['tablet']['portrait']['slides_to_scroll'] ) ? $responsive['tablet']['portrait']['slides_to_scroll'] : $carousel_settings['slides_to_scroll']['tablet_portrait'],
			'mobile_breakpoint' => ! empty( $responsive['mobile']['breakpoint'] ) ? $responsive['mobile']['breakpoint'] : $carousel_settings['breakpoints']['mobile'],
			'mobile_slides_to_scroll' => ! empty( $responsive['mobile']['slides_to_scroll'] ) ? $responsive['mobile']['slides_to_scroll'] : $carousel_settings['slides_to_scroll']['mobile'],
		);

		if ( ! empty( $carousel_settings['slides_to_show'] ) ) {
			$variables['desktop_slides_to_show'] = ! empty( $responsive['desktop']['slides_to_show'] ) ? $responsive['desktop']['slides_to_show'] : $carousel_settings['slides_to_show']['desktop'];
			$variables['tablet_landscape_slides_to_show'] = ! empty( $responsive['tablet']['landscape']['slides_to_show'] ) ? $responsive['tablet']['landscape']['slides_to_show'] : $carousel_settings['slides_to_show']['tablet_landscape'];
			$variables['tablet_portrait_slides_to_show'] = ! empty( $responsive['tablet']['portrait']['slides_to_show'] ) ? $responsive['tablet']['portrait']['slides_to_show'] : $carousel_settings['slides_to_show']['tablet_portrait'];
			$variables['mobile_slides_to_show'] = ! empty( $responsive['mobile']['slides_to_show'] ) ? $responsive['mobile']['slides_to_show'] : $carousel_settings['slides_to_show']['mobile'];
		}

		return $encode ? json_encode( $variables ) : $variables;
	}

	function responsive_less_variables( $less_vars, $instance ) {
		$carousel_settings = $this->get_carousel_settings();
		// Breakpoint
		$less_vars['breakpoint_tablet_landscape'] = ( ! empty( $instance['responsive']['tablet_landscape']['breakpoint'] ) ? $instance['responsive']['tablet_landscape']['breakpoint'] : $carousel_settings['breakpoints']['tablet_landscape'] ) .'px';
		$less_vars['breakpoint_tablet_portrait'] = ( ! empty( $instance['responsive']['tablet_portrait']['breakpoint'] ) ? $instance['responsive']['tablet_portrait']['breakpoint'] : $carousel_settings['breakpoints']['tablet_portrait'] ) .'px';
		$less_vars['breakpoint_mobile'] = ( ! empty( $instance['responsive']['mobile']['breakpoint'] ) ? $instance['responsive']['mobile']['breakpoint'] : $carousel_settings['breakpoints']['mobile'] ) .'px';

		// Navigation
		$less_vars['navigation_desktop'] = isset( $instance['responsive']['desktop']['navigation'] ) ? ! empty( $instance['responsive']['desktop']['navigation'] ) : $carousel_settings['navigation']['desktop'];
		$less_vars['navigation_tablet_landscape'] = isset( $instance['responsive']['tablet']['landscape']['navigation'] ) ? ! empty( $instance['responsive']['tablet']['landscape']['navigation'] ) : $carousel_settings['navigation']['tablet_landscape'];
		$less_vars['navigation_tablet_portrait'] = isset( $instance['responsive']['tablet']['portrait']['navigation'] ) ? ! empty( $instance['responsive']['tablet']['portrait']['navigation'] ) : $carousel_settings['navigation']['tablet_portrait'];
		$less_vars['navigation_mobile'] = isset( $instance['responsive']['mobile']['navigation'] ) ? ! empty( $instance['responsive']['mobile']['navigation'] ) : $carousel_settings['navigation']['mobile'];

		return $less_vars;
	}

	function carousel_settings_template_variables( $settings, $encode = true ) {
		$variables = array(
			'loop' => isset( $settings['loop'] ) ? $settings['loop'] : true,
			'dots' => isset( $settings['dots'] ) ? $settings['dots'] : true,
			'animation' => isset( $settings['animation'] ) ? $settings['animation'] : 'ease',
			'animation_speed' => ! empty( $settings['animation_speed'] ) ? $settings['animation_speed'] : 800,
			'autoplay' => isset( $settings['autoplay'] ) ? $settings['autoplay'] : false,
			'pauseOnHover' => isset( $settings['autoplay_pause_hover'] ) ? $settings['autoplay_pause_hover'] : false,
			'autoplaySpeed' => ! empty( $settings['timeout'] ) ? $settings['timeout'] : 8000,
			'item_overflow' => isset( $settings['item_overflow'] ) ? $settings['item_overflow'] : false,
		);

		return $encode ? json_encode( $variables ) : $variables;
	}

	function render_template( $settings, $args ) {
		include plugin_dir_path( __FILE__ ) . 'tpl/carousel.php';
	}

	function render_navigation( $nav ) {
		if ( $nav == 'next' || $nav == 'both' ) {
			?>
			<a href="#" class="sow-carousel-next" title="<?php esc_attr_e( 'Next', 'so-widgets-bundle' ); ?>" aria-label="<?php esc_attr_e( 'Next Posts', 'so-widgets-bundle' ); ?>" role="button"></a>
			<?php
		}

		if ( $nav == 'prev' || $nav == 'both' ) {
			?>
			<a href="#" class="sow-carousel-previous" title="<?php esc_attr_e( 'Previous', 'so-widgets-bundle' ); ?>" aria-label="<?php esc_attr_e( 'Previous Posts', 'so-widgets-bundle' ); ?>" role="button"></a>
			<?php
		}
	}

}

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
				'slides_to_scroll_text' => array(
					'label' => __( 'Slides to scroll', 'so-widgets-bundle' ),
					'description' => __( 'Set the number of slides to scroll per navigation click or swipe on %s', 'so-widgets-bundle' ),
				),
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
			$section['fields']['slides_to_scroll'] =  array(
				'type' => 'number',
				'label' => $carousel_settings['slides_to_scroll_text']['label'],
				'description' => sprintf(
					$carousel_settings['slides_to_scroll_text']['description'],
					strtolower( $field['label'] )
				),
				$value_type => $field['value'],
			);

			if ( isset( $field['breakpoint'] ) ) {
				$section['fields']['breakpoint'] = array(
					'type' => 'number',
					'label' => __( 'Breakpoint', 'so-widgets-bundle' ),
					$value_type => $field['breakpoint'],
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
				'value' => $carousel_settings['slides_to_scroll']['desktop'],
			),
			'tablet' => array(
				'label' => __( 'Tablet', 'so-widgets-bundle' ),
				'fields' => array(
					'landscape' => array(
						'label' => __( 'Landscape', 'so-widgets-bundle' ),
						'breakpoint' => $carousel_settings['breakpoints']['tablet_landscape'],
						'value' => $carousel_settings['slides_to_scroll']['tablet_landscape'],
					),
					'portrait' => array(
						'label' => __( 'Portrait', 'so-widgets-bundle' ),
						'breakpoint' => $carousel_settings['breakpoints']['tablet_portrait'],
						'value' => $carousel_settings['slides_to_scroll']['tablet_portrait'],
					),
				),
			),
			'mobile' => array(
				'label' => __( 'Mobile', 'so-widgets-bundle' ),
				'breakpoint' => $carousel_settings['breakpoints']['mobile'],
				'value' => $carousel_settings['slides_to_scroll']['mobile'],
			),
		);

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
			'label' => __( 'Carousel Settings', 'so-widgets-bundle' ),
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
						'args'     => array(
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

	function get_settings_form() {
		return array(
			'responsive' => $this->responsive_form_fields( 'global' ),
		);
	}

	function responsive_template_variables( $responsive, $encode = true ) {
		$carousel_settings = $this->get_carousel_settings();

		$variables = array(
			'desktop_slides' => ! empty( $responsive['desktop']['slides_to_scroll'] ) ? $responsive['desktop']['slides_to_scroll'] : $carousel_settings['slides_to_scroll']['desktop'],
			'tablet_landscape_breakpoint' => ! empty( $responsive['tablet']['landscape']['breakpoint'] ) ? $responsive['tablet']['landscape']['breakpoint'] : $carousel_settings['breakpoints']['tablet_landscape'],
			'tablet_landscape_slides' => ! empty( $responsive['tablet']['landscape']['slides_to_scroll'] ) ? $responsive['tablet']['landscape']['slides_to_scroll'] : $carousel_settings['slides_to_scroll']['tablet_landscape'],
			'tablet_portrait_breakpoint' => ! empty( $responsive['tablet']['portrait']['breakpoint'] ) ? $responsive['tablet']['portrait']['breakpoint'] : $carousel_settings['breakpoints']['tablet_portrait'],
			'tablet_portrait_slides' => ! empty( $responsive['tablet']['portrait']['slides_to_scroll'] ) ? $responsive['tablet']['portrait']['slides_to_scroll'] : $carousel_settings['slides_to_scroll']['tablet_portrait'],
			'mobile_breakpoint' => ! empty( $responsive['mobile']['breakpoint'] ) ? $responsive['mobile']['breakpoint'] : $carousel_settings['breakpoints']['mobile'],
			'mobile_slides' => ! empty( $responsive['mobile']['slides_to_scroll'] ) ? $responsive['mobile']['slides_to_scroll'] : $carousel_settings['slides_to_scroll']['mobile'],
		);

		return $encode ? json_encode( $variables ) : $variables;
	}

	function carousel_settings_template_variables( $settings, $encode = true ) {
		$variables = array(
			'loop' => isset( $settings['loop'] ) ? $settings['loop'] : true,
			'dots' => isset( $settings['dots'] ) ? $settings['dots'] : true,
			'animation_speed' => ! empty( $settings['animation_speed'] ) ? $settings['animation_speed'] : 800,
			'autoplay' => isset( $settings['autoplay'] ) ? $settings['autoplay'] : false,
			'pauseOnHover' => isset( $settings['autoplay_pause_hover'] ) ? $settings['autoplay_pause_hover'] : false,
			'autoplaySpeed' => ! empty( $settings['timeout'] ) ? $settings['timeout'] : 8000,
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

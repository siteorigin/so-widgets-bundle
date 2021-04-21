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
				array(
					'sow-carousel',
					plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'css/carousel/style.css',
				),
			)
		);

	}

	// Allow widgets to override the breakpoint default values.
	function get_breakpoints() {
		return array(
			'tablet_landscape' => 1366,
			'tablet_portrait' => 1025,
			'mobile' => 480,
		);
	}

	// Allow widgets to override the slides_to_scroll text.
	function get_slides_to_scroll_text () {
		return array(
			'label' => __( 'Slides to scroll', 'so-widgets-bundle' ),
			'description' => __( 'Set the number of slides to scroll per navigation click or swipe on %s', 'so-widgets-bundle' ),
		);
	}

	private function add_section_group( $field, $value_type ) {
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
			$slides_to_scroll = $this->get_slides_to_scroll_text();
			$section['fields']['slides_to_scroll'] =  array(
				'type' => 'number',
				'label' => $slides_to_scroll['label'],
				'description' => sprintf(
					$slides_to_scroll['description'],
					strtolower( $field['label'] )
				),
				$value_type => 1,
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
		$breakpoints = $this->get_breakpoints();

		// If the context is a widget, the global values are displayed using a
		// placeholder to prevent the values from being stored.
		$value_type = $context == 'widget' ? 'placeholder' : 'default';
		$fields = array(
			'desktop' => array(
				'label' => __( 'Desktop', 'so-widgets-bundle' ),
				'value' => 1
			),
			'tablet' => array(
				'label' => __( 'Tablet', 'so-widgets-bundle' ),
				'fields' => array(
					'landscape' => array(
						'label' => __( 'Landscape', 'so-widgets-bundle' ),
						'breakpoint' => $breakpoints['tablet_landscape'],
						'value' => 2
					),
					'portrait' => array(
						'label' => __( 'Portrait', 'so-widgets-bundle' ),
						'breakpoint' => $breakpoints['tablet_portrait'],
						'value' => 2
					),
				),
			),
			'mobile' => array(
				'label' => __( 'Mobile', 'so-widgets-bundle' ),
				'breakpoint' => $breakpoints['mobile'],
				'value' => 1
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
		$breakpoints = $this->get_breakpoints();

		$variables = array(
			'desktop_slides' => ! empty( $responsive['desktop']['slides_to_scroll'] ) ? $responsive['desktop']['slides_to_scroll'] : 1,
			'tablet_portrait_slides' => ! empty( $responsive['tablet']['portrait']['slides_to_scroll'] ) ? $responsive['tablet']['portrait']['slides_to_scroll'] : 2,
			'tablet_portrait_breakpoint' => ! empty( $responsive['tablet']['portrait']['breakpoint'] ) ? $responsive['tablet']['portrait']['breakpoint'] : $breakpoints['tablet_portrait'],
			'tablet_landscape_slides' => ! empty( $responsive['tablet']['landscape']['slides_to_scroll'] ) ? $responsive['tablet']['landscape']['slides_to_scroll'] : 2,
			'tablet_landscape_breakpoint' => ! empty( $responsive['tablet']['landscape']['breakpoint'] ) ? $responsive['tablet']['landscape']['breakpoint'] : $breakpoints['tablet_landscape'],
			'mobile_breakpoint' => ! empty( $responsive['mobile']['breakpoint'] ) ? $responsive['mobile']['breakpoint'] : $breakpoints['mobile'],
			'mobile_slides' => ! empty( $responsive['mobile']['slides_to_scroll'] ) ? $responsive['mobile']['slides_to_scroll'] : 1,
		);

		return $encode ? json_encode( $variables ) : $variables;
	}

	function carousel_settings_template_variables( $settings, $encode = true ) {
		$variables = array(
			'loop' => ! empty( $settings['loop'] ) ? $settings['loop'] : true,
			'dots' => ! empty( $settings['dots'] ) ? $settings['dots'] : true,
			'animation_speed' => ! empty( $settings['animation_speed'] ) ? $settings['animation_speed'] : 800,
			'autoplay' => ! empty( $settings['autoplay'] ) ? $settings['autoplay'] : false,
			'pauseOnHover' => ! empty( $settings['autoplay_pause_hover'] ) ? $settings['autoplay_pause_hover'] : false,
			'autoplaySpeed' => ! empty( $settings['timeout'] ) ? $settings['timeout'] : 8000,
		);

		return $encode ? json_encode( $variables ) : $variables;
	}

	function render_template( $settings ) {
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

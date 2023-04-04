<?php
/*
Widget Name: Layout Slider
Description: A slider that allows you to create responsive columnized content for each slide.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/layout-slider-widget/
*/

if ( ! class_exists( 'SiteOrigin_Widget_Base_Slider' ) ) {
	include_once plugin_dir_path( SOW_BUNDLE_BASE_FILE ) . '/base/inc/widgets/base-slider.class.php';
}

class SiteOrigin_Widget_LayoutSlider_Widget extends SiteOrigin_Widget_Base_Slider {
	protected $buttons = array();

	public function __construct() {
		parent::__construct(
			'sow-layout-slider',
			__( 'SiteOrigin Layout Slider', 'so-widgets-bundle' ),
			array(
				'description' => __( 'A slider that allows you to create responsive columnized content for each slide.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/layout-slider-widget/',
				'panels_title' => false,
			),
			array( ),
			false,
			plugin_dir_path( __FILE__ )
		);

		add_action( 'siteorigin_widgets_enqueue_frontend_scripts_sow-layout-slider', array( $this, 'register_shortcode_script' ) );
	}

	public function get_widget_form() {
		$show_heading_fields = apply_filters( 'sow_layout_slider_show_heading_fields', false );

		return parent::widget_form( array(
			'frames' => array(
				'type' => 'repeater',
				'label' => __( 'Slider frames', 'so-widgets-bundle' ),
				'item_name' => __( 'Frame', 'so-widgets-bundle' ),
				'item_label' => array(
					'selectorArray' => array(
						array(
							'selector' => '.siteorigin-widget-field-image .media-field-wrapper .current .title',
							'valueMethod' => 'html',
						),
						array(
							'selector' => '.siteorigin-widget-field-videos .siteorigin-widget-field-repeater-items  .media-field-wrapper .current .title',
							'valueMethod' => 'html',
						),
						array(
							'selector' => '.siteorigin-widget-field-videos .siteorigin-widget-field-repeater-items  .media-field-wrapper .current .title',
							'valueMethod' => 'html',
						),
						array(
							'selector' => ".siteorigin-widget-field-videos [id*='url']",
							'update_event' => 'change',
							'value_method' => 'val',
						),
					),
				),

				'fields' => array(
					'content' => array(
						'type' => 'builder',
						'builder_type' => 'layout_slider_builder',
						'label' => __( 'Content', 'so-widgets-bundle' ),
					),

					'background' => array(
						'type' => 'section',
						'label' => __( 'Background', 'so-widgets-bundle' ),
						'fields' => array(
							'image' => array(
								'type' => 'media',
								'label' => __( 'Background image', 'so-widgets-bundle' ),
								'library' => 'image',
								'fallback' => true,
							),

							'image_type' => array(
								'type' => 'select',
								'label' => __( 'Background image type', 'so-widgets-bundle' ),
								'options' => array(
									'cover' => __( 'Cover', 'so-widgets-bundle ' ),
									'tile' => __( 'Tile', 'so-widgets-bundle' ),
								),
								'default' => 'cover',
							),

							'opacity' => array(
								'label' => __( 'Background image opacity', 'so-widgets-bundle' ),
								'type' => 'slider',
								'min' => 0,
								'max' => 100,
								'default' => 100,
							),

							'color' => array(
								'type' => 'color',
								'label' => __( 'Background color', 'so-widgets-bundle' ),
								'default' => '#333333',
							),

							'url' => array(
								'type' => 'link',
								'label' => __( 'Destination URL', 'so-widgets-bundle' ),
							),

							'new_window' => array(
								'type' => 'checkbox',
								'label' => __( 'Open URL in a new window', 'so-widgets-bundle' ),
							),

							'videos' => array(
								'type' => 'repeater',
								'item_name' => __( 'Video', 'so-widgets-bundle' ),
								'label' => __( 'Background videos', 'so-widgets-bundle' ),
								'item_label' => array(
									'selectorArray' => array(
										array(
											'selector' => '.siteorigin-widget-field-file .media-field-wrapper .current .title',
											'valueMethod' => 'html',
										),
										array(
											'selector' => "[id*='url']",
											'update_event' => 'change',
											'value_method' => 'val',
										),
									),
								),
								'fields' => $this->video_form_fields(),
							),
						),
					),
				),
			),

			'controls' => array(
				'type' => 'section',
				'label' => __( 'Slider Controls', 'so-widgets-bundle' ),
				'fields' => $this->control_form_fields(),
			),

			'layout' => array(
				'type' => 'section',
				'label' => __( 'Layout', 'so-widgets-bundle' ),
				'fields' => array(
					'desktop' => array(
						'type' => 'section',
						'label' => __( 'Desktop', 'so-widgets-bundle' ),
						'fields' => array(
							'height' => array(
								'type' => 'measurement',
								'label' => __( 'Height', 'so-widgets-bundle' ),
							),

							'padding' => array(
								'type' => 'measurement',
								'label' => __( 'Top and bottom padding', 'so-widgets-bundle' ),
								'default' => '50px',
							),

							'extra_top_padding' => array(
								'type' => 'measurement',
								'label' => __( 'Extra top padding', 'so-widgets-bundle' ),
								'description' => __( 'Additional padding added to the top of the slider', 'so-widgets-bundle' ),
								'default' => '0px',
							),

							'padding_sides' => array(
								'type' => 'measurement',
								'label' => __( 'Side padding', 'so-widgets-bundle' ),
								'default' => '20px',
							),

							'width' => array(
								'type' => 'measurement',
								'label' => __( 'Maximum container width', 'so-widgets-bundle' ),
								'default' => '1280px',
							),
						),
					),
					'mobile' => array(
						'type' => 'section',
						'label' => __( 'Mobile', 'so-widgets-bundle' ),
						'fields' => array(
							'height_responsive' => array(
								'type' => 'measurement',
								'label' => __( 'Height', 'so-widgets-bundle' ),
							),

							'padding' => array(
								'type' => 'measurement',
								'label' => __( 'Top and bottom padding', 'so-widgets-bundle' ),
							),

							'extra_top_padding' => array(
								'type' => 'measurement',
								'label' => __( 'Extra top padding', 'so-widgets-bundle' ),
								'description' => __( 'Additional padding added to the top of the slider', 'so-widgets-bundle' ),
							),

							'padding_sides' => array(
								'type' => 'measurement',
								'label' => __( 'Side padding', 'so-widgets-bundle' ),
							),
						),
					),
					'vertically_align' => array(
						'type' => 'checkbox',
						'label' => __( 'Vertically center align slide contents', 'so-widgets-bundle' ),
						'description' => __( 'For perfect centering, consider setting the Extra top padding setting to 0 when enabling this setting.', 'so-widgets-bundle' ),
					),
				),
			),

			'design' => array(
				'type' => 'section',
				'label' => __( 'Design', 'so-widgets-bundle' ),
				'state_emitter' => array(
					'callback' => 'conditional',
					'args'     => array(
						'meh[hide]: ' . ( $show_heading_fields ? 'false' : 'true' ),
					),
				),
				'state_handler' => array(
					'meh[hide]' => array( 'hide' ),
				),
				'fields' => array(
					'heading_color' => array(
						'type' => 'color',
						'label' => __( 'Heading color', 'so-widgets-bundle' ),
					),

					'heading_shadow' => array(
						'type' => 'slider',
						'label' => __( 'Heading shadow intensity', 'so-widgets-bundle' ),
						'max' => 100,
						'min' => 0,
					),

					'text_size' => array(
						'type' => 'measurement',
						'label' => __( 'Text size', 'so-widgets-bundle' ),
					),

					'text_color' => array(
						'type' => 'color',
						'label' => __( 'Text color', 'so-widgets-bundle' ),
					),
				),
			),
		) );
	}

	public function form( $instance, $form_type = 'widget' ) {
		if ( ( is_admin() || ( defined( 'REST_REQUEST' ) && function_exists( 'register_block_type' ) ) ) && defined( 'SITEORIGIN_PANELS_VERSION' ) ) {
			parent::form( $instance, $form_type );
		} else {
			?>
			<p>
				<?php _e( 'This widget requires: ', 'so-widgets-bundle' ); ?>
				<a href="https://siteorigin.com/page-builder/" target="_blank" rel="noopener noreferrer"><?php _e( 'SiteOrigin Page Builder', 'so-widgets-bundle' ); ?></a>
			</p>
			<?php
		}
	}

	/**
	 * Get everything necessary for the background image.
	 *
	 * @return array
	 */
	public function get_frame_background( $i, $frame ) {
		$background_image = siteorigin_widgets_get_attachment_image_src(
			$frame['background']['image'],
			'full',
			! empty( $frame['background']['image_fallback'] ) ? $frame['background']['image_fallback'] : ''
		);

		return array(
			'color' => ! empty( $frame['background']['color'] ) ? $frame['background']['color'] : false,
			'image' => ! empty( $background_image[0] ) ? $background_image[0] : false,
			'image-width' => ! empty( $background_image[1] ) ? $background_image[1] : 0,
			'image-height' => ! empty( $background_image[2] ) ? $background_image[2] : 0,
			'image-sizing' => $frame['background']['image_type'],
			'url' => ! empty( $frame['background']['url'] ) ? $frame['background']['url'] : false,
			'new_window' => ! empty( $frame['background']['new_window'] ),
			'videos' => $frame['background']['videos'],
			'video-sizing' => 'background',
			'opacity' => (int) $frame['background']['opacity'] / 100,
		);
	}

	/**
	 * Render the actual content of the frame
	 */
	public function render_frame_contents( $i, $frame ) {
		?>
		<div class="sow-slider-image-container">
			<div class="sow-slider-image-wrapper">
				<?php echo $this->process_content( $frame['content'], $frame ); ?>
			</div>
		</div>
		<?php
	}

	public function register_shortcode_script() {
		wp_register_script(
			'sow-layout-slide-control',
			plugin_dir_url( __FILE__ ) . 'js/slide-control' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery', 'sow-slider-slider' ),
			SOW_BUNDLE_VERSION,
			true
		);
	}

	public function add_shortcode( $atts ) {
		ob_start();
		$atts = shortcode_atts( array(
			'slide' => 'next',
			'label' => '',
		), $atts );

		wp_enqueue_script( 'sow-layout-slide-control' );

		if ( is_numeric( $atts['slide'] ) ) {
			$label = sprintf( __( 'Show slide %d', 'so-widgets-bundle' ), $atts['slide'] );
		} elseif (
			$atts['slide'] == 'next' ||
			$atts['slide'] == 'prev' ||
			$atts['slide'] == 'prev' ||
			$atts['slide'] == 'previous' ||
			$atts['slide'] == 'first' ||
			$atts['slide'] == 'last'
		) {
			if ( $atts['slide'] == 'prev' ) {
				$atts['slide'] = 'previous';
			}

			$label = sprintf( __( '%s slide', 'so-widgets-bundle' ), ucfirst( $atts['slide'] ) );
		} else {
			_e( 'Slide control shortcode error: invalid slide value.', 'so-widgets-bundle' );
		}

		if ( isset( $label ) ) {
			// Handle label overriding.
			$label = empty( $atts['label'] ) ? $label : $atts['label'];

			echo '<a class="sow-slide-control" href="#' . esc_attr( $atts['slide'] ) . '" role="button">' . esc_attr( $label ) . '</a>';
		}

		return ob_get_clean();
	}

	/**
	 * Process the content.
	 *
	 * @return string
	 */
	public function process_content( $content, $frame ) {
		if ( function_exists( 'siteorigin_panels_render' ) ) {
			add_shortcode( 'slide_control', array( $this, 'add_shortcode' ) );
			$content_builder_id = substr( md5( json_encode( $content ) ), 0, 8 );
			echo siteorigin_panels_render( 'w' . $content_builder_id, true, $content );
			remove_shortcode( 'slide_control' );
		} else {
			echo __( 'This widget requires Page Builder.', 'so-widgets-bundle' );
		}
	}

	/**
	 * The less variables to control the design of the slider
	 *
	 * @return array
	 */
	public function get_less_variables( $instance ) {
		$less = array();

		if ( empty( $instance ) ) {
			return $less;
		}

		// Slider navigation controls
		$less['nav_color_hex'] = $instance['controls']['nav_color_hex'];
		$less['nav_size'] = $instance['controls']['nav_size'];

		// Measurement field type options
		$meas_options = array();

		// Layouts settings.
		if ( ! empty( $instance['layout'] ) ) {
			if ( ! empty( $instance['layout']['desktop'] ) ) {
				$settings = $instance['layout']['desktop'];

				$meas_options['slide_height'] = ! empty( $settings['height'] ) ? $settings['height'] : '';
				$meas_options['slide_padding'] = ! empty( $settings['padding'] ) ? $settings['padding'] : '';
				$meas_options['slide_padding_extra_top'] = ! empty( $settings['extra_top_padding'] ) ? $settings['extra_top_padding'] : '';
				$meas_options['slide_padding_sides'] = ! empty( $settings['padding_sides'] ) ? $settings['padding_sides'] : '';
				$meas_options['slide_width'] = ! empty( $settings['width'] ) ? $settings['width'] : '';
			}

			if ( ! empty( $instance['layout']['mobile'] ) ) {
				$settings = $instance['layout']['mobile'];

				$meas_options['slide_height_responsive'] = ! empty( $settings['height_responsive'] ) ? $settings['height_responsive'] : '';
				$meas_options['slide_padding_responsive'] = ! empty( $settings['padding'] ) ? $settings['padding'] : '';
				$meas_options['slide_padding_sides_responsive'] = ! empty( $settings['padding_sides'] ) ? $settings['padding_sides'] : '';

				if ( ! empty( $settings['extra_top_padding'] ) ) {
					// Add extra padding to top padidng.
					$meas_options['slide_padding_top_responsive'] = (int) $meas_options['slide_padding_responsive'] + (int) $settings['extra_top_padding'];
				}
			}
		}

		if ( ! empty( $instance['design']['text_size'] ) ) {
			$meas_options['text_size'] = $instance['design']['text_size'];
		}

		foreach ( $meas_options as $key => $val ) {
			$less[ $key ] = $this->add_default_measurement_unit( $val );
		}

		$less['vertically_align'] = empty( $instance['layout']['vertically_align'] ) ? 'false' : 'true';

		if ( ! empty( $instance['design']['heading_shadow'] ) ) {
			$less['heading_shadow'] = (int) $instance['design']['heading_shadow'];
		}

		if ( ! empty( $instance['design']['heading_color'] ) ) {
			$less['heading_color'] = $instance['design']['heading_color'];
		}

		if ( ! empty( $instance['design']['text_color'] ) ) {
			$less['text_color'] = $instance['design']['text_color'];
		}

		$global_settings = $this->get_global_settings();

		if ( ! empty( $global_settings['responsive_breakpoint'] ) ) {
			$less['responsive_breakpoint'] = $global_settings['responsive_breakpoint'];
		}

		return $less;
	}

	public function add_default_measurement_unit( $val ) {
		if ( ! empty( $val ) ) {
			if ( ! preg_match( '/\d+([a-zA-Z%]+)/', $val ) ) {
				$val .= 'px';
			}
		}

		return $val;
	}

	public function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return array(
			sprintf(
				__( 'Add multiple Layout Slider frames in one go with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/multiple-media" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
			sprintf(
				__( 'Add parallax and fixed background images with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/parallax-sliders" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
		);
	}
}

siteorigin_widget_register( 'sow-layout-slider', __FILE__, 'SiteOrigin_Widget_LayoutSlider_Widget' );

<?php

abstract class SiteOrigin_Widget_Base_Slider extends SiteOrigin_Widget {
	/**
	 * Register all the frontend scripts and styles for the base slider.
	 */
	public function initialize() {
		$frontend_scripts = array();
		$frontend_scripts[] = array(
			'sow-slider-slider-cycle2',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/jquery.cycle' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION,
		);

		$frontend_scripts[] = array(
			'sow-slider-slider',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/slider/jquery.slider' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION,
		);

		$this->register_frontend_scripts( $frontend_scripts );
		$this->register_frontend_styles(
			array(
				array(
					'sow-slider-slider',
					plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'css/slider/slider.css',
					array(),
					SOW_BUNDLE_VERSION,
				),
			)
		);
		add_action( 'wp_enqueue_scripts', array( $this, 'register_cycle_swipe' ) );

		// Add Unmute icon LESS.
		add_filter( 'siteorigin_widgets_less_variables_' . $this->id_base, array( $this, 'add_less_variables' ), 10, 3 );
		add_filter( 'siteorigin_widgets_less_vars_' . $this->id_base, array( $this, 'add_unmute_less' ), 10, 4 );
	}

	public function register_cycle_swipe() {
		wp_register_script(
			'sow-slider-slider-cycle2-swipe',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/jquery.cycle.swipe' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION
		);
	}

	/**
	 * The control array required for the slider
	 *
	 * @return array
	 */
	public function control_form_fields() {
		return array(
			'autoplay' => array(
				'type' => 'checkbox',
				'label' => __( 'Autoplay', 'so-widgets-bundle' ),
				'description' => __( 'Change slides automatically without user interaction.', 'so-widgets-bundle' ),
				'default' => true,
				'state_emitter' => array(
					'callback' => 'conditional',
					'args'     => array(
						'autoplay[autoplay]: val',
						'autoplay[static]: ! val',
					),
				),
			),

			'autoplay_hover' => array(
				'type' => 'checkbox',
				'label' => __( 'Autoplay pause on hover', 'so-widgets-bundle' ),
				'default' => false,
				'state_handler' => array(
					'autoplay[autoplay]' => array( 'show' ),
					'autoplay[static]' => array( 'hide' ),
				),
			),
			'speed' => array(
				'type' => 'number',
				'label' => __( 'Animation speed', 'so-widgets-bundle' ),
				'description' => __( 'Animation speed in milliseconds.', 'so-widgets-bundle' ),
				'default' => 800,
			),

			'timeout' => array(
				'type' => 'number',
				'label' => __( 'Timeout', 'so-widgets-bundle' ),
				'description' => __( 'How long each frame is displayed for in milliseconds.', 'so-widgets-bundle' ),
				'default' => 8000,
				'state_handler' => array(
					'autoplay[autoplay]' => array( 'show' ),
					'autoplay[static]' => array( 'hide' ),
				),
			),

			'nav_color_hex' => array(
				'type' => 'color',
				'label' => __( 'Navigation color', 'so-widgets-bundle' ),
				'default' => '#FFFFFF',
			),

			'nav_style' => array(
				'type' => 'select',
				'label' => __( 'Navigation style', 'so-widgets-bundle' ),
				'default' => 'thin',
				'options' => array(
					'ultra-thin' => __( 'Ultra thin', 'so-widgets-bundle' ),
					'thin' => __( 'Thin', 'so-widgets-bundle' ),
					'medium' => __( 'Medium', 'so-widgets-bundle' ),
					'thick' => __( 'Thick', 'so-widgets-bundle' ),
					'ultra-thin-rounded' => __( 'Rounded ultra thin', 'so-widgets-bundle' ),
					'thin-rounded' => __( 'Rounded thin', 'so-widgets-bundle' ),
					'medium-rounded' => __( 'Rounded medium', 'so-widgets-bundle' ),
					'thick-rounded' => __( 'Rounded thick', 'so-widgets-bundle' ),
				),
			),

			'nav_size' => array(
				'type' => 'number',
				'label' => __( 'Navigation size', 'so-widgets-bundle' ),
				'default' => '25',
			),

			'nav_always_show_desktop' => array(
				'type' => 'checkbox',
				'label' => __( 'Always show navigation on desktop', 'so-widgets-bundle' ),
			),

			'nav_always_show_mobile' => array(
				'type' => 'checkbox',
				'label' => __( 'Always show navigation on mobile', 'so-widgets-bundle' ),
			),

			'swipe' => array(
				'type' => 'checkbox',
				'label' => __( 'Swipe control', 'so-widgets-bundle' ),
				'description' => __( 'Allow users to swipe through frames on mobile devices.', 'so-widgets-bundle' ),
				'default' => true,
			),

			'unmute' => array(
				'type' => 'checkbox',
				'label' => __( 'Unmute icon', 'so-widgets-bundle' ),
				'description' => __( 'Slide background videos are muted. Enable to display an unmute/mute icon. Only applies to self-hosted videos.', 'so-widgets-bundle' ),
				'default' => false,
				'state_emitter' => array(
					'callback' => 'conditional',
					'args' => array(
						'unmute_slider[show]: val',
						'unmute_slider[hide]: ! val',
					),
				),
			),

			'unmute_position' => array(
				'type' => 'select',
				'label' => __( 'Unmute icon position', 'so-widgets-bundle' ),
				'default' => 'top_right',
				'options' => array(
					'top_right' => __( 'Top right', 'so-widgets-bundle' ),
					'bottom_right' => __( 'Bottom right', 'so-widgets-bundle' ),
					'bottom_left' => __( 'Bottom left', 'so-widgets-bundle' ),
					'top_left' => __( 'Top left', 'so-widgets-bundle' ),
				),
				'state_handler' => array(
					'unmute_slider[show]' => array( 'show' ),
					'unmute_slider[hide]' => array( 'hide' ),
				),
			),

			'background_video_mobile' => array(
				'type' => 'checkbox',
				'label' => __( 'Show slide background videos on mobile', 'so-widgets-bundle' ),
				'description' => __( 'Allow slide background videos to appear on mobile devices that support autoplay.', 'so-widgets-bundle' ),
				'default' => true,
			),
		);
	}

	public function video_form_fields() {
		return array(
			'file' => array(
				'type' => 'media',
				'library' => 'video',
				'label' => __( 'Video file', 'so-widgets-bundle' ),
			),

			'url' => array(
				'type' => 'text',
				'sanitize' => 'url',
				'label' => __( 'Video URL', 'so-widgets-bundle' ),
				'optional' => 'true',
				'description' => __( 'An external URL of the video. Overrides video file.', 'so-widgets-bundle' ),
			),

			'autoplay' => array(
				'type' => 'checkbox',
				'label' => __( 'Autoplay', 'so-widgets-bundle' ),
				'default' => false,
				'description' => __( 'Autoplay can only be disabled for YouTube videos.', 'so-widgets-bundle' ),
			),

			'format' => array(
				'type' => 'select',
				'label' => __( 'Video format', 'so-widgets-bundle' ),
				'options' => array(
					'video/mp4' => 'MP4',
					'video/webm' => 'WebM',
					'video/ogg' => 'Ogg',
				),
			),
		);
	}

	public function get_settings_form() {
		return array(
			'responsive_breakpoint' => array(
				'type'        => 'measurement',
				'label'       => __( 'Responsive Breakpoint', 'so-widgets-bundle' ),
				'default'     => '780px',
				'description' => __( 'This setting controls when the Slider will switch to the responsive mode. This breakpoint will only be used if always show navigation on mobile is enabled. The default value is 780px.', 'so-widgets-bundle' ),
			),
		);
	}

	public function slider_settings( $controls ) {
		$slider_settings = array(
			'pagination'               => true,
			'speed'                    => empty( $controls['speed'] ) ? 1 : $controls['speed'],
			'timeout'                  => $controls['timeout'],
			'paused'                   => empty( $controls['autoplay'] ) ?: false,
			'pause_on_hover'           => ! empty( $controls['autoplay_hover'] ) ?: false,
			'swipe'                    => $controls['swipe'],
			'nav_always_show_desktop'  => ! empty( $controls['nav_always_show_desktop'] ) ? true : '',
			'nav_always_show_mobile'   => ! empty( $controls['nav_always_show_mobile'] ) ? true : '',
			'breakpoint'               => ! empty( $controls['breakpoint'] ) ? $controls['breakpoint'] : '780px',
			'unmute'                   => ! empty( $controls['unmute'] ),
			'anchor'                   => ! empty( $controls['anchor'] ) ? $controls['anchor'] : null,
		);

		// Add the unmute translations.
		// We're not able to reliably localize the script using `wp_localize_script` as
		// it's too late to do that at this point.
		if ( $slider_settings['unmute'] ) {
			$slider_settings['unmuteLoc'] = __( 'Unmute slide', 'so-widgets-bundle' );
			$slider_settings['muteLoc'] = __( 'Mute slide', 'so-widgets-bundle' );
		}

		return $slider_settings;
	}

	public function widget_form( $form_options ) {
		if ( isset( $form_options ) && isset( $form_options['frames'] ) ) {
			$loop_setting = array(
				'type' => 'checkbox',
				'label' => __( 'Loop slide background videos', 'so-widgets-bundle' ),
				'default' => false,
			);
			$video_opacity = array(
				'label' => __( 'Background video opacity', 'so-widgets-bundle' ),
				'type' => 'slider',
				'min' => 0,
				'max' => 100,
				'default' => 100,
			);

			if ( isset( $form_options['frames']['fields']['background_videos'] ) ) {
				// Add setting to SiteOrigin Slider widget.
				siteorigin_widgets_array_insert(
					$form_options['frames']['fields'],
					'background_image',
					array(
						'loop_background_videos' => $loop_setting,
						'background_video_opacity' => $video_opacity,
					)
				);
			} elseif ( isset( $form_options['frames']['fields']['background'] ) ) {
				// Add setting to all other slider widgets.
				$form_options['frames']['fields']['background']['fields']['loop_background_videos'] = $loop_setting;
				$form_options['frames']['fields']['background']['fields']['background_video_opacity'] = $video_opacity;
			}
		}

		return $form_options;
	}

	/**
	 * Migrate Slider settings.
	 *
	 * @return mixed
	 */
	public function modify_instance( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		// Migrate global slider loop_background_videos setting to frame specific setting.
		if ( ! empty( $instance['controls']['loop_background_videos'] ) ) {
			unset( $instance['controls']['loop_background_videos'] );

			if ( ! empty( $instance['frames'] ) ) {
				$is_slider_widget = $this->widget_class == 'SiteOrigin_Widget_Slider_Widget';

				foreach ( $instance['frames'] as $k => $frame ) {
					if ( $is_slider_widget ) {
						$instance['frames'][ $k ]['loop_background_videos'] = 'on';
					} else {
						$instance['frames'][ $k ]['background']['loop_background_videos'] = 'on';
					}
				}
			}
		}

		// Migrate Hero and Layout Slider Layouts and Design settings to separate section.
		if (
			(
				$this->widget_class == 'SiteOrigin_Widget_Hero_Widget' ||
				$this->widget_class == 'SiteOrigin_Widget_LayoutSlider_Widget'
			) &&
			! empty( $instance['design'] ) &&
			empty( $instance['layout'] )
		) {
			$migrate_layout_settings = array(
				'vertically_align' => true,
				'desktop' => array(
					'height',
					'height_unit',
					'padding',
					'padding_unit',
					'extra_top_padding',
					'extra_top_padding_unit',
					'padding_sides',
					'padding_sides_unit',
					'width',
					'width_unit',
				),
				'mobile' => array(
					'height_responsive',
					'height_responsive_unit',
				),
			);

			$instance['layout'] = array();
			$instance['layout']['desktop'] = array();
			$instance['layout']['mobile'] = array();

			foreach ( $migrate_layout_settings as $setting => $sub_section ) {
				if ( is_array( $sub_section ) ) {
					foreach ( $sub_section as $responsive_setting ) {
						if ( ! empty( $instance['design'][ $responsive_setting ] ) ) {
							$instance['layout'][ $setting ][ $responsive_setting ] = $instance['design'][ $responsive_setting ];
						}
					}
				} elseif ( ! empty( $instance['design'][ $setting ] ) ) {
					$instance['layout'][ $setting ] = $instance['design'][ $setting ];

					unset( $instance['design'][ $setting ] );
				}
			}
		}

		return $instance;
	}

	public function render_template( $controls, $frames, $layout = array() ) {
		$this->render_template_part( 'before_slider', $controls, $frames );
		$this->render_template_part( 'before_slides', $controls, $frames, $layout );

		foreach ( $frames as $i => $frame ) {
			$this->render_frame( $i, $frame, $controls );
		}

		$this->render_template_part( 'after_slides', $controls, $frames );
		$this->render_template_part( 'navigation', $controls, $frames );
		$this->render_template_part( 'after_slider', $controls, $frames );
	}

	public function render_template_part( $part, $controls, $frames, $layout = array() ) {
		switch( $part ) {
			case 'before_slider':
				?>
				<div class="sow-slider-base" style="display: none">
					<?php
					if ( isset( $controls['unmute'] ) && $controls['unmute'] ) {
						?>
						<span class="sow-player-controls-sound" style="display: none;"></span>
						<?php
					}
				break;

			case 'before_slides':
				$settings = $this->slider_settings( $controls );

				if ( ! empty( $controls['anchor'] ) ) {
					$anchorId = $controls['anchor'];
				}

				if ( $settings['swipe'] ) {
					wp_enqueue_script( 'sow-slider-slider-cycle2-swipe' );
				}

				if ( ! empty( $layout['desktop'] ) && ! empty( $layout['desktop']['height'] ) ) {
					$height = $layout['desktop']['height'];
				}
				?><ul
					class="sow-slider-images"
					data-settings="<?php echo esc_attr( json_encode( $settings ) ); ?>"
					<?php echo ! empty( $layout['desktop'] ) && ! empty( $layout['desktop']['height'] ) ? 'style="min-height: ' . esc_attr( $layout['desktop']['height'] ) . '"' : ''; ?>
					data-anchor-id="<?php echo ! empty( $controls['anchor'] ) ? esc_attr( $controls['anchor'] ) : ''; ?>"
				><?php
				break;

			case 'after_slides':
				?></ul><?php
				break;

			case 'navigation':
				?>
				<ol class="sow-slider-pagination">
					<?php foreach ( $frames as $i => $frame ) { ?>
						<li><a href="#" data-goto="<?php echo $i; ?>" aria-label="<?php printf( __( 'display slide %s', 'so-widgets-bundle' ), $i + 1 ); ?>"></a></li>
					<?php } ?>
				</ol>

				<div class="sow-slide-nav sow-slide-nav-next">
					<a href="#" data-goto="next" aria-label="<?php _e( 'next slide', 'so-widgets-bundle' ); ?>" data-action="next">
						<em class="sow-sld-icon-<?php echo sanitize_html_class( $controls['nav_style'] ); ?>-right"></em>
					</a>
				</div>

				<div class="sow-slide-nav sow-slide-nav-prev">
					<a href="#" data-goto="previous" aria-label="<?php _e( 'previous slide', 'so-widgets-bundle' ); ?>" data-action="prev">
						<em class="sow-sld-icon-<?php echo sanitize_html_class( $controls['nav_style'] ); ?>-left"></em>
					</a>
				</div>
				<?php
				break;

			case 'after_slider':
				?></div><?php
				break;
		}
	}

	/**
	 * Get the frame background information from the frame. This can be overwritten by child classes.
	 *
	 * @return array
	 */
	public function get_frame_background( $i, $frame ) {
		return array( );
	}

	/**
	 * This is mainly for rendering the frame wrapper
	 */
	public function render_frame( $i, $frame, $controls ) {
		$background = wp_parse_args( $this->get_frame_background( $i, $frame ), array(
			'color' => false,
			'image' => false,
			'image-width' => 0,
			'image-height' => 0,
			'opacity' => 1,
			'url' => false,
			'new_window' => false,
			'image-sizing' => 'cover', // options for image sizing are cover and contain
			'videos' => false,
			'videos-sizing' => 'background', // options for video sizing are background or full
		) );

		$wrapper_attributes = array(
			'class' => array( 'sow-slider-image' ),
			// Prevent potentially showing all slides on load if the slide is transparent, and an animation is set.
			'style' => array( 'visibility: ' . ( $i === 0 ? 'visible' : 'hidden' ) . ';' ),
		);

		if ( ! empty( $background['color'] ) ) {
			$wrapper_attributes['style'][] = 'background-color: ' . esc_attr( $background['color'] );
		}

		if ( ! empty( $background['image'] ) && $background['opacity'] >= 1 && empty( $frame['no_output'] ) ) {
			$wrapper_attributes['style'][] = 'background-image: url(' . esc_url( $background['image'] ) . ')';
		}

		if ( ! empty( $background['url'] ) ) {
			$wrapper_attributes['style'][] = 'cursor: pointer;';
		}

		if ( ! empty( $background['image'] ) && ! empty( $background['image-sizing'] ) ) {
			$wrapper_attributes['class'][] = ' ' . 'sow-slider-image-' . $background['image-sizing'];
		}

		if ( ! empty( $background['url'] ) ) {
			$wrapper_attributes['data-url'] = json_encode( array(
				'url' => sow_esc_url( $background['url'] ),
				'new_window' => ! empty( $background['new_window'] ),
			) );
		}
		$wrapper_attributes = apply_filters( 'siteorigin_widgets_slider_wrapper_attributes', $wrapper_attributes, $frame, $background );

		$wrapper_attributes['class'] = implode( ' ', $wrapper_attributes['class'] );
		$wrapper_attributes['style'] = implode( ';', $wrapper_attributes['style'] );

		?>
		<li <?php foreach ( $wrapper_attributes as $attr => $val ) {
			echo $attr . '="' . esc_attr( $val ) . '" ';
		} ?>>
			<?php
			do_action( 'siteorigin_widgets_slider_before_contents', $frame );
			$this->render_frame_contents( $i, $frame );
			do_action( 'siteorigin_widgets_slider_after_contents', $frame );

			if ( ! empty( $background['videos'] ) ) {
				$classes = array( 'sow-' . $background['video-sizing'] . '-element' );

				if ( ! empty( $controls['background_video_mobile'] ) ) {
					$classes[] = 'sow-mobile-video_enabled';
				}

				// If loop_background_videos is enabled, pass it to the video embed as a control.
				if ( ! empty( $frame['loop_background_videos'] ) ) {
					// SiteOrigin Slider Widget.
					$controls['loop_background_videos'] = $frame['loop_background_videos'];
				} elseif ( ! empty( $frame['background']['loop_background_videos'] ) ) {
					// All other slider widgets.
					$controls['loop_background_videos'] = $frame['background']['loop_background_videos'];
				}

				// If loop_background_videos is present, pass it to the video embed as a control.
				if ( isset( $frame['background_video_opacity'] ) ) {
					// SiteOrigin Slider Widget.
					$controls['opacity'] = $frame['background_video_opacity'];
				} elseif ( isset( $frame['background']['loop_background_videos'] ) ) {
					// All other slider widgets.
					$controls['opacity'] = $frame['background']['background_video_opacity'];
				}

				$this->video_code( $background['videos'], $classes, $controls );
			}

			if ( $background['opacity'] < 1 && ! empty( $background['image'] ) ) {
				$overlay_attributes = array(
					'class' => array( 'sow-slider-image-overlay', 'sow-slider-image-' . $background['image-sizing'] ),
					'style' => array(
						'background-image: url(' . $background['image'] . ')',
						'opacity: ' . (float) $background['opacity'],
					),
				);
				$overlay_attributes = apply_filters( 'siteorigin_widgets_slider_overlay_attributes', $overlay_attributes, $frame, $background );

				$overlay_attributes['class'] = empty( $overlay_attributes['class'] ) ? '' : implode( ' ', $overlay_attributes['class'] );
				$overlay_attributes['style'] = empty( $overlay_attributes['style'] ) ? '' : implode( ';', $overlay_attributes['style'] );

				?><div <?php foreach ( $overlay_attributes as $attr => $val ) {
					echo $attr . '="' . esc_attr( $val ) . '" ';
				} ?> ></div><?php
			}

			?>
		</li>
		<?php

	}

	/**
	 * Render the actual content of the frame.
	 */
	abstract public function render_frame_contents( $i, $frame );

	/**
	 * Render the background videos
	 *
	 * @param array $classes
	 */
	public function video_code( $videos, $classes = array(), $controls = array() ) {
		if ( empty( $videos ) ) {
			return;
		}
		$loop = ! empty( $controls['loop_background_videos'] ) && $controls['loop_background_videos'] ? 'loop' : '';
		$opacity = isset( $controls['opacity'] ) ? 'style="opacity: ' . ( $controls['opacity'] / 100 ) . '"' : '';

		$video_element = '<video class="' . esc_attr( implode( ' ', $classes ) ) . '" autoplay ' . $loop . ' ' . $opacity . ' muted playsinline>';
		$so_video = new SiteOrigin_Video();

		foreach ( $videos as $video ) {
			if ( empty( $video['file'] ) && empty( $video['url'] ) ) {
				continue;
			}
			// If video is an external file, try and display it using oEmbed
			if ( ! empty( $video['url'] ) ) {
				$can_oembed = $so_video->can_oembed( $video['url'] );

				// Check if we can oEmbed the video or not
				if ( ! $can_oembed ) {
					$video_file = sow_esc_url( $video['url'] );
				} else {
					echo '<div class="sow-slide-video-oembed" ' . $opacity . '>';
					echo $so_video->get_video_oembed( $video['url'], ! empty( $video['autoplay'] ), false, $loop, true );
					echo '</div>';
					continue;
				}
			}

			// If $video_file isn't set video is a local file
			if ( ! isset( $video_file ) ) {
				$video_file = wp_get_attachment_url( $video['file'] );
			}
			$video_element .= '<source src="' . sow_esc_url( $video_file ) . '" type="' . esc_attr( $video['format'] ) . '">';
		}

		if ( strpos( $video_element, 'source' ) !== false ) {
			$video_element .= '</video>';
			echo $video_element;
		}
	}

	/**
	 * If the Unmute icon is enabled, inject unmute LESS.
	 *
	 * @param string            $less     The LESS content.
	 * @param array             $vars     The widget LESS variables.
	 * @param array             $instance The widget instance.
	 * @param SiteOrigin_Widget $widget   The widget object.
	 */
	public function add_unmute_less( $less, $vars, $instance, $widget ) {
		if (
			empty( $less ) ||
			$widget->id_base != $this->id_base ||
			empty( $instance['controls']['unmute'] )
		) {
			return $less;
		}

		$less .= file_get_contents( plugin_dir_path( __FILE__ ) . 'less/unmute.less' );

		return $less;
	}

	/**
	 * If the Unmute icon is enabled, add unmute_position LESS variable.
	 *
	 * @param array $less     An array containing all LESS variables.
	 * @param array $instance The widget instance.
	 */
	public function add_less_variables( $less_variables, $instance, $widget ) {
		if (
			empty( $less_variables ) ||
			$widget->id_base != $this->id_base ||
			empty( $instance['controls']['unmute'] )
		) {
			return $less_variables;
		}

		$less_variables['unmute_position'] = ! empty( $instance['controls']['unmute_position'] ) ? $instance['controls']['unmute_position'] : 'top_right';

		// Pass the Widgets Bundle directory path to allow us to include the volume controls font.
		$sow_plugin_dir_url = str_replace( site_url(), '', plugin_dir_url( SOW_BUNDLE_BASE_FILE ) );
		$less_variables['volume_controls_font'] = "'${sow_plugin_dir_url}css/slider/fonts/volume-controls'";

		return $less_variables;
	}
}

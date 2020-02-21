<?php

abstract class SiteOrigin_Widget_Base_Slider extends SiteOrigin_Widget {

	/**
	 * Register all the frontend scripts and styles for the base slider.
	 */
	function initialize() {

		$frontend_scripts = array();
		$frontend_scripts[] = array(
			'sow-slider-slider-cycle2',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/jquery.cycle' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION
		);
		if( function_exists('wp_is_mobile') && wp_is_mobile() ) {
			$frontend_scripts[] = array(
				'sow-slider-slider-cycle2-swipe',
				plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/jquery.cycle.swipe' . SOW_BUNDLE_JS_SUFFIX . '.js',
				array( 'jquery' ),
				SOW_BUNDLE_VERSION
			);
		}
		$frontend_scripts[] = array(
			'sow-slider-slider',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/slider/jquery.slider' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION
		);

		$this->register_frontend_scripts( $frontend_scripts );
		$this->register_frontend_styles(
			array(
				array(
					'sow-slider-slider',
					plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'css/slider/slider.css',
					array(),
					SOW_BUNDLE_VERSION
				)
			)
		);
	}

	/**
	 * The control array required for the slider
	 *
	 * @return array
	 */
	function control_form_fields(){
		return array(
			'speed' => array(
				'type' => 'number',
				'label' => __('Animation speed', 'so-widgets-bundle'),
				'description' => __('Animation speed in milliseconds.', 'so-widgets-bundle'),
				'default' => 800,
			),

			'timeout' => array(
				'type' => 'number',
				'label' => __('Timeout', 'so-widgets-bundle'),
				'description' => __('How long each frame is displayed for in milliseconds.', 'so-widgets-bundle'),
				'default' => 8000,
			),

			'nav_color_hex' => array(
				'type' => 'color',
				'label' => __('Navigation color', 'so-widgets-bundle'),
				'default' => '#FFFFFF',
			),

			'nav_style' => array(
				'type' => 'select',
				'label' => __('Navigation style', 'so-widgets-bundle'),
				'default' => 'thin',
				'options' => array(
					'ultra-thin' => __('Ultra thin', 'so-widgets-bundle'),
					'thin' => __('Thin', 'so-widgets-bundle'),
					'medium' => __('Medium', 'so-widgets-bundle'),
					'thick' => __('Thick', 'so-widgets-bundle'),
					'ultra-thin-rounded' => __('Rounded ultra thin', 'so-widgets-bundle'),
					'thin-rounded' => __('Rounded thin', 'so-widgets-bundle'),
					'medium-rounded' => __('Rounded medium', 'so-widgets-bundle'),
					'thick-rounded' => __('Rounded thick', 'so-widgets-bundle'),
				)
			),

			'nav_size' => array(
				'type' => 'number',
				'label' => __('Navigation size', 'so-widgets-bundle'),
				'default' => '25',
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

			'background_video_mobile' => array(
				'type' => 'checkbox',
				'label' => __( 'Show slide background videos on mobile', 'so-widgets-bundle' ),
				'description' => __( 'Allow slide background videos to appear on mobile devices that support autoplay.', 'so-widgets-bundle' ),
			)
		);
	}

	function video_form_fields(){
		return array(
			'file' => array(
				'type' => 'media',
				'library' => 'video',
				'label' => __('Video file', 'so-widgets-bundle'),
			),

			'url' => array(
				'type' => 'text',
				'sanitize' => 'url',
				'label' => __('Video URL', 'so-widgets-bundle'),
				'optional' => 'true',
				'description' => __('An external URL of the video. Overrides video file.', 'so-widgets-bundle')
			),
			
			'autoplay' => array(
				'type' => 'checkbox',
				'label' => __( 'Autoplay', 'so-widgets-bundle' ),
				'default' => false,
				'description' => __( 'Currently only for YouTube videos.', 'so-widgets-bundle' ),
			),

			'format' => array(
				'type' => 'select',
				'label' => __('Video format', 'so-widgets-bundle'),
				'options' => array(
					'video/mp4' => 'MP4',
					'video/webm' => 'WebM',
					'video/ogg' => 'Ogg',
				),
			),
		);
	}

	function get_settings_form() {
		return array(
			'responsive_breakpoint' => array(
				'type'        => 'measurement',
				'label'       => __( 'Responsive Breakpoint', 'so-widgets-bundle' ),
				'default'     => '780px',
				'description' => __( "This setting controls when the Slider will switch to the responsive mode. This breakpoint will only be used if always show navigation on mobile is enabled. The default value is 780px.", 'so-widgets-bundle' )
			)
		);
	}

	function slider_settings( $controls ){
		return array(
			'pagination'               => true,
			'speed'                    => empty( $controls['speed'] ) ? 1 : $controls['speed'],
			'timeout'                  => $controls['timeout'],
			'swipe'                    => $controls['swipe'],
			'nav_always_show_mobile'   => ! empty( $controls['nav_always_show_mobile'] ) ? true : '',
			'breakpoint'               => ! empty( $controls['breakpoint'] ) ? $controls['breakpoint'] : '780px',
		);
	}

	function render_template( $controls, $frames ){
		$this->render_template_part('before_slider', $controls, $frames);
		$this->render_template_part('before_slides', $controls, $frames);

		foreach( $frames as $i => $frame ) {
			$this->render_frame( $i, $frame, $controls );
		}

		$this->render_template_part('after_slides', $controls, $frames);
		$this->render_template_part('navigation', $controls, $frames);
		$this->render_template_part('after_slider', $controls, $frames);
	}

	function render_template_part( $part, $controls, $frames ) {
		switch( $part ) {
			case 'before_slider':
				?><div class="sow-slider-base <?php if( wp_is_mobile() ) echo 'sow-slider-is-mobile' ?>" style="display: none"><?php
				break;
			case 'before_slides':
				$settings = $this->slider_settings( $controls );
				?><ul class="sow-slider-images" data-settings="<?php echo esc_attr( json_encode($settings) ) ?>"><?php
				break;
			case 'after_slides':
				?></ul><?php
				break;
			case 'navigation':
				?>
				<ol class="sow-slider-pagination">
					<?php foreach($frames as $i => $frame) : ?>
						<li><a href="#" data-goto="<?php echo $i ?>" aria-label="<?php printf( __( 'display slide %s', 'so-widgets-bundle' ), $i+1 ) ?>"></a></li>
					<?php endforeach; ?>
				</ol>

				<div class="sow-slide-nav sow-slide-nav-next">
					<a href="#" data-goto="next" aria-label="<?php _e( 'next slide', 'so-widgets-bundle' ) ?>" data-action="next">
						<em class="sow-sld-icon-<?php echo sanitize_html_class( $controls['nav_style'] ) ?>-right"></em>
					</a>
				</div>

				<div class="sow-slide-nav sow-slide-nav-prev">
					<a href="#" data-goto="previous" aria-label="<?php _e( 'previous slide', 'so-widgets-bundle' ) ?>" data-action="prev">
						<em class="sow-sld-icon-<?php echo sanitize_html_class( $controls['nav_style'] ) ?>-left"></em>
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
	 * @param $frame
	 *
	 * @return array
	 */
	function get_frame_background( $i, $frame ) {
		return array( );
	}

	/**
	 * This is mainly for rendering the frame wrapper
	 *
	 * @param $i
	 * @param $frame
	 */
	function render_frame( $i, $frame, $controls ){
		$background = wp_parse_args( $this->get_frame_background( $i, $frame ), array(
			'color' => false,
			'image' => false,
			'image-width' => 0,
			'image-height' => 0,
			'opacity' => 1,
			'url' => false,
			'new_window' => false,
			'image-sizing' => 'cover',              // options for image sizing are cover and contain
			'videos' => false,
			'videos-sizing' => 'background',        // options for video sizing are background or full
		) );

		$wrapper_attributes = array(
			'class' => array( 'sow-slider-image' ),
			'style' => array(),
		);

		if( !empty($background['color']) ) {
			$wrapper_attributes['style'][] = 'background-color: ' . esc_attr($background['color']);
		}

		if( $background['opacity'] >= 1 ) {
			if( !empty($background['image']) ) {
				$wrapper_attributes['style'][] = 'background-image: url(' . esc_url($background['image']) . ')';
			}
		}

		if( ! empty( $background['url'] ) ) {
			$wrapper_attributes['style'][] = 'cursor: pointer;';
		}

		if( !empty($background['image']) && !empty($background['image-sizing']) ) {
			$wrapper_attributes['class'][] = ' ' . 'sow-slider-image-' . $background['image-sizing'];
		}
		if( !empty( $background['url'] ) ) {
			$wrapper_attributes['data-url'] = json_encode( array(
				'url' => sow_esc_url($background['url']),
				'new_window' => !empty( $background['new_window'] )
			) );
		}
		$wrapper_attributes = apply_filters( 'siteorigin_widgets_slider_wrapper_attributes', $wrapper_attributes, $frame, $background );

		$wrapper_attributes['class'] = implode( ' ', $wrapper_attributes['class'] );
		$wrapper_attributes['style'] = implode( ';', $wrapper_attributes['style'] );

		?>
		<li <?php foreach( $wrapper_attributes as $attr => $val ) echo $attr . '="' . esc_attr( $val ) . '" '; ?>>
			<?php
			$this->render_frame_contents( $i, $frame );
			if( !empty( $background['videos'] ) ) {

				$classes = array( 'sow-' . $background['video-sizing'] . '-element' );
				if ( ! empty( $controls['background_video_mobile'] ) ) {
					$classes[] = 'sow-mobile-video_enabled';
				}

				$this->video_code( $background['videos'], $classes );
			}

			if( $background['opacity'] < 1 && !empty($background['image']) ) {
				$overlay_attributes = array(
					'class' => array( 'sow-slider-image-overlay', 'sow-slider-image-' . $background['image-sizing'] ),
					'style' => array(
						'background-image: url(' . $background['image'] . ')',
						'opacity: ' . floatval( $background['opacity'] ),
					)
				);
				$overlay_attributes = apply_filters( 'siteorigin_widgets_slider_overlay_attributes', $overlay_attributes, $frame, $background );

				$overlay_attributes['class'] = implode( ' ', $overlay_attributes['class'] );
				$overlay_attributes['style'] = implode( ';', $overlay_attributes['style'] );

				?><div <?php foreach( $overlay_attributes as $attr => $val ) echo $attr . '="' . esc_attr( $val ) . '" '; ?> ></div><?php
			}

			?>
		</li>
		<?php

	}

	/**
	 * Render the actual content of the frame.
	 *
	 * @param $i
	 * @param $frame
	 */
	abstract function render_frame_contents( $i, $frame );

	/**
	 * Render the background videos
	 *
	 * @param $videos
	 * @param array $classes
	 */
	function video_code( $videos, $classes = array() ){
		if( empty( $videos ) ) return;
		$video_element = '<video class="' . esc_attr( implode( ' ', $classes ) ) . '" autoplay loop muted playsinline>';

		$so_video = new SiteOrigin_Video();
		foreach( $videos as $video ) {
			if( empty( $video['file'] ) && empty ( $video['url'] ) ) continue;
			// If video is an external file, try and display it using oEmbed
			if( !empty( $video['url'] ) ) {

				$can_oembed = $so_video->can_oembed( $video['url'] );

				// Check if we can oEmbed the video or not
				if( ! $can_oembed ) {
					$video_file = sow_esc_url( $video['url'] );
				} else {
					echo $so_video->get_video_oembed( $video['url'], ! empty( $video['autoplay'] ) );
					continue;
				}
			}

			// If $video_file isn't set video is a local file
			if( !isset( $video_file ) ) {
				$video_file = wp_get_attachment_url( $video['file'] );
			}
			$video_element .= '<source src="' . sow_esc_url( $video_file ) . '" type="' . esc_attr( $video['format'] ) . '">';
		}
		if ( strpos( $video_element, 'source' ) !== false ) {
			$video_element .= '</video>';
			echo $video_element;
		}
	}

}

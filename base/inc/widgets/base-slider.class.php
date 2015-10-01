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

			'format' => array(
				'type' => 'select',
				'label' => __('Video format', 'so-widgets-bundle'),
				'options' => array(
					'video/mp4' => 'MP4',
					'video/webm' => 'WebM',
					'video/ogg' => 'Ogg',
				),
			),

			'height' => array(
				'type' => 'number',
				'label' => __( 'Maximum height', 'so-widgets-bundle' )
			),

		);
	}

	function slider_settings( $controls ){
		return array(
			'pagination' => true,
			'speed' => $controls['speed'],
			'timeout' => $controls['timeout'],
		);
	}

	function render_template( $controls, $frames ){
		$this->render_template_part('before_slider', $controls, $frames);
		$this->render_template_part('before_slides', $controls, $frames);

		foreach( $frames as $i => $frame ) {
			$this->render_frame( $i, $frame );
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
						<li><a href="#" data-goto="<?php echo $i ?>"><?php echo $i+1 ?></a></li>
					<?php endforeach; ?>
				</ol>

				<div class="sow-slide-nav sow-slide-nav-next">
					<a href="#" data-goto="next" data-action="next">
						<em class="sow-sld-icon-<?php echo sanitize_html_class( $controls['nav_style'] ) ?>-right"></em>
					</a>
				</div>

				<div class="sow-slide-nav sow-slide-nav-prev">
					<a href="#" data-goto="previous" data-action="prev">
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
	function render_frame( $i, $frame ){
		$background = $this->get_frame_background( $i, $frame );
		$background = wp_parse_args($background, array(
			'color' => false,
			'image' => false,
			'opacity' => 1,
			'url' => false,
			'new_window' => false,
			'image-sizing' => 'cover',              // options for image sizing are cover and contain
			'videos' => false,
			'videos-sizing' => 'background',        // options for video sizing are background or full
		) );

		$background_style = array();
		if( !empty($background['color']) ) $background_style[] = 'background-color: ' . esc_attr($background['color']);

		if( $background['opacity'] >= 1 ) {
			if( !empty($background['image']) ) $background_style[] = 'background-image: url(' . esc_url($background['image']) . ')';
		}

		if( ! empty( $background['url'] ) ) {
			$background_style[] = 'cursor: pointer;';
		}

		?>
		<li
			class="sow-slider-image <?php if( !empty($background['image']) && !empty($background['image-sizing']) ) echo 'sow-slider-image-' . $background['image-sizing'] ?>"
			<?php if( !empty( $background['url'] ) ) echo 'data-url=\'' . json_encode(array( 'url' => sow_esc_url($background['url']), 'new_window' => !empty( $background['new_window'] ) ) ) . '\'' ; ?>
			<?php if( !empty($background_style) ) echo 'style="' . implode(';', $background_style) . '"' ?>>
			<?php
			$this->render_frame_contents( $i, $frame );
			if( !empty( $background['videos'] ) ) {
				$this->video_code( $background['videos'], array('sow-' . $background['video-sizing'] . '-element') );
			}

			if( $background['opacity'] < 1 && !empty($background['image']) ) {
				?><div class="sow-slider-image-overlay <?php echo 'sow-slider-image-' . $background['image-sizing'] ?>" style="background-image: url(<?php echo esc_url( $background['image'] ) ?>); opacity: <?php echo floatval( $background['opacity'] ) ?>;" ></div><?php
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
		if(empty($videos)) return;
		$video_element = '<video class="' . esc_attr( implode(',', $classes) ) . '" autoplay loop muted>';

		foreach($videos as $video) {
			if( empty( $video['file'] ) && empty ( $video['url'] ) ) continue;

			if( empty( $video['url'] ) ) {
				$video_file = wp_get_attachment_url($video['file']);
				$video_element .= '<source src="' . sow_esc_url( $video_file ) . '" type="' . esc_attr( $video['format'] ) . '">';
			}
			else {
				$args = '';
				if ( ! empty( $video['height'] ) ) {
					$args['height'] = $video['height'];
				}

				echo wp_oembed_get( $video['url'], $args );
			}
		}
		if ( strpos( $video_element, 'source' ) !== false ) {
			$video_element .= '</video>';
			echo $video_element;
		}
	}

}
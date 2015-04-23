<?php
/*
Widget Name: Slider widget
Description: A very simple slider widget.
Author: Greg Priday
Author URI: http://siteorigin.com
*/

class SiteOrigin_Widget_Slider_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-slider',
			__('SiteOrigin Slider', 'siteorigin-widgets'),
			array(
				'description' => __('A responsive slider widget that supports images and video.', 'siteorigin-widgets'),
				'help' => 'http://siteorigin.com/widgets-bundle/slider-widget-documentation/',
				'panels_title' => false,
			),
			array(

			),
			array(

				'frames' => array(
					'type' => 'repeater',
					'label' => __('Slider frames', 'siteorigin-widgets'),
					'item_name' => __('Frame', 'siteorigin-widgets'),
					'item_label' => array(
						'selector' => "[id*='frames-url']",
						'update_event' => 'change',
						'value_method' => 'val'
					),
					'fields' => array(
						'background_videos' => array(
							'type' => 'repeater',
							'item_name' => __('Video', 'siteorigin-widgets'),
							'label' => __('Background videos', 'siteorigin-widgets'),
							'item_label' => array(
								'selector' => "[id*='frames-background_videos-url']",
								'update_event' => 'change',
								'value_method' => 'val'
							),
							'fields' => array(
								'file' => array(
									'type' => 'media',
									'library' => 'video',
									'label' => __('Video file', 'siteorigin-widgets'),
								),

								'url' => array(
									'type' => 'text',
									'sanitize' => 'url',
									'label' => __('Video URL', 'siteorigin-widgets'),
									'optional' => 'true',
									'description' => __('An external URL of the video. Overrides video file.', 'siteorigin-widgets')
								),

								'format' => array(
									'type' => 'select',
									'label' => __('Video format', 'siteorigin-widgets'),
									'options' => array(
										'video/mp4' => 'MP4',
										'video/webm' => 'WebM',
										'video/ogg' => 'Ogg',
									),
								),

								'height' => array(
									'type' => 'number',
									'label' => __( 'Maximum height', 'siteorigin-widgets' )
								),

							),
						),

						'background_image' => array(
							'type' => 'media',
							'library' => 'image',
							'label' => __('Background image', 'siteorigin-widgets'),
						),

						'background_image_type' => array(
							'type' => 'select',
							'label' => __('Background image type', 'siteorigin-widgets'),
							'options' => array(
								'cover' => __('Cover', 'siteorigin-widgets'),
								'tile' => __('Tile', 'siteorigin-widgets'),
							),
							'default' => 'cover',
						),

						'foreground_image' => array(
							'type' => 'media',
							'library' => 'image',
							'label' => __('Foreground image', 'siteorigin-widgets'),
						),

						'url' => array(
							'type' => 'link',
							'label' => __('Destination URL', 'siteorigin-widgets'),
						),

						'new_window' => array(
							'type' => 'checkbox',
							'label' => __('Open in new window', 'siteorigin-widgets'),
							'default' => false,
						),
					),
				),

				'speed' => array(
					'type' => 'number',
					'label' => __('Animation speed', 'siteorigin-widgets'),
					'description' => __('Animation speed in milliseconds.', 'siteorigin-widgets'),
					'default' => 800,
				),

				'timeout' => array(
					'type' => 'number',
					'label' => __('Timeout', 'siteorigin-widgets'),
					'description' => __('How long each slide is displayed for in milliseconds.', 'siteorigin-widgets'),
					'default' => 8000,
				),

				'nav_color_hex' => array(
					'type' => 'color',
					'label' => __('Navigation color', 'siteorigin-widgets'),
					'default' => '#FFFFFF',
				),

				'nav_style' => array(
					'type' => 'select',
					'label' => __('Navigation style', 'siteorigin-widgets'),
					'default' => 'thin',
					'options' => array(
						'ultra-thin' => __('Ultra thin', 'siteorigin-widgets'),
						'thin' => __('Thin', 'siteorigin-widgets'),
						'medium' => __('Medium', 'siteorigin-widgets'),
						'thick' => __('Thick', 'siteorigin-widgets'),
						'ultra-thin-rounded' => __('Rounded ultra thin', 'siteorigin-widgets'),
						'thin-rounded' => __('Rounded thin', 'siteorigin-widgets'),
						'medium-rounded' => __('Rounded medium', 'siteorigin-widgets'),
						'thick-rounded' => __('Rounded thick', 'siteorigin-widgets'),
					)
				),

				'nav_size' => array(
					'type' => 'number',
					'label' => __('Navigation size', 'siteorigin-widgets'),
					'default' => '25',
				),

			),
			plugin_dir_path(__FILE__).'../'
		);
	}

	function initialize() {

		$frontend_scripts = array();
		$frontend_scripts[] = array(
			'sow-slider-slider-cycle2',
			siteorigin_widget_get_plugin_dir_url( 'slider' ) . 'js/jquery.cycle' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION
		);
		if( wp_is_mobile() ) {
			$frontend_scripts[] = array(
				'sow-slider-slider-cycle2-swipe',
				siteorigin_widget_get_plugin_dir_url( 'slider' ) . 'js/jquery.cycle.swipe' . SOW_BUNDLE_JS_SUFFIX . '.js',
				array( 'jquery' ),
				SOW_BUNDLE_VERSION
			);
		}
		$frontend_scripts[] = array(
			'sow-slider-slider',
			siteorigin_widget_get_plugin_dir_url( 'slider' ) . 'js/slider' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION
		);

		$this->register_frontend_scripts( $frontend_scripts );
		$this->register_frontend_styles(
			array(
				array(
					'sow-slider-slider',
					siteorigin_widget_get_plugin_dir_url( 'slider' ) . 'css/slider.css',
					array(),
					SOW_BUNDLE_VERSION
				)
			)
		);
	}

	function video_code($videos, $classes = array()){
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

	/**
	 * Creates an instance from a gallery shortcode.
	 *
	 * @param $shortcode
	 */
	static function instance_from_gallery($shortcode){

	}

	function get_style_name($instance){
		return 'base';
	}

	function get_template_name($instance){
		return 'base';
	}

	function get_less_variables($instance){
		if ( empty( $instance ) ) return array();

		return array(
			'nav_color_hex' => $instance['nav_color_hex'],
			'nav_size' => $instance['nav_size'],
		);
	}

}

siteorigin_widget_register('slider', __FILE__);

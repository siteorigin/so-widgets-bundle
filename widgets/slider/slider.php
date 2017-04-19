<?php
/*
Widget Name: Image Slider
Description: A very simple slider widget.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

if( !class_exists( 'SiteOrigin_Widget_Base_Slider' ) ) include_once plugin_dir_path(SOW_BUNDLE_BASE_FILE) . '/base/inc/widgets/base-slider.class.php';

class SiteOrigin_Widget_Slider_Widget extends SiteOrigin_Widget_Base_Slider {
	function __construct() {
		parent::__construct(
			'sow-slider',
			__('SiteOrigin Slider', 'so-widgets-bundle'),
			array(
				'description' => __('A responsive slider widget that supports images and video.', 'so-widgets-bundle'),
				'help' => 'https://siteorigin.com/widgets-bundle/slider-widget-documentation/',
				'panels_title' => false,
			),
			array(

			),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	function get_widget_form(){
		return array(
			'frames' => array(
				'type' => 'repeater',
				'label' => __('Slider frames', 'so-widgets-bundle'),
				'item_name' => __('Frame', 'so-widgets-bundle'),
				'item_label' => array(
					'selector' => "[id*='frames-url']",
					'update_event' => 'change',
					'value_method' => 'val'
				),
				'fields' => array(
					'background_videos' => array(
						'type' => 'repeater',
						'item_name' => __('Video', 'so-widgets-bundle'),
						'label' => __('Background videos', 'so-widgets-bundle'),
						'item_label' => array(
							'selector' => "[id*='frames-background_videos-url']",
							'update_event' => 'change',
							'value_method' => 'val'
						),
						'fields' => $this->video_form_fields(),
					),

					'background_image' => array(
						'type' => 'media',
						'library' => 'image',
						'label' => __('Background image', 'so-widgets-bundle'),
						'fallback' => true,
					),

					'background_color' => array(
						'type' => 'color',
						'label' => __('Background Color', 'so-widgets-bundle'),
					),

					'background_image_type' => array(
						'type' => 'select',
						'label' => __('Background image type', 'so-widgets-bundle'),
						'options' => array(
							'cover' => __('Cover', 'so-widgets-bundle'),
							'tile' => __('Tile', 'so-widgets-bundle'),
						),
						'default' => 'cover',
					),

					'foreground_image' => array(
						'type' => 'media',
						'library' => 'image',
						'label' => __('Foreground image', 'so-widgets-bundle'),
						'fallback' => true,
					),

					'url' => array(
						'type' => 'link',
						'label' => __('Destination URL', 'so-widgets-bundle'),
					),

					'new_window' => array(
						'type' => 'checkbox',
						'label' => __('Open in new window', 'so-widgets-bundle'),
						'default' => false,
					),
				),
			),
			'controls' => array(
				'type' => 'section',
				'label' => __('Controls', 'so-widgets-bundle'),
				'fields' => $this->control_form_fields()
			)
		);
	}

	function get_frame_background( $i, $frame ){
		$background_image = siteorigin_widgets_get_attachment_image_src(
			$frame['background_image'],
			'full',
			!empty( $frame['background_image_fallback'] ) ? $frame['background_image_fallback'] : ''
		);

		return array(
			'color' => !empty( $frame['background_color'] ) ? $frame['background_color'] : false,
			'image' => !empty( $background_image ) ? $background_image[0] : false,
			'image-width' => !empty( $background_image[1] ) ? $background_image[1] : 0,
			'image-height' => !empty( $background_image[2] ) ? $background_image[2] : 0,
			'image-sizing' => $frame['background_image_type'],
			'opacity' => 1,
			'videos' => $frame['background_videos'],
			'video-sizing' => empty($frame['foreground_image']) ? 'full' : 'background',
			'url' => ! empty( $frame['url'] ) ? $frame['url'] : false,
			'new_window' => ! empty( $frame['new_window'] ) ? $frame['new_window'] : false,
		);
	}

	function render_frame_contents($i, $frame) {

		// Clear out any empty background videos
		if( !empty($frame['background_videos']) && is_array($frame['background_videos']) ){
			for( $i = 0; $i < count($frame['background_videos']); $i++ ){
				if( empty( $frame['background_videos'][$i]['file'] ) && empty($frame['background_videos'][$i]['url']) ) {
					unset($frame['background_videos'][$i]);
				}
			}
		}

		$foreground_src = siteorigin_widgets_get_attachment_image_src(
			$frame['foreground_image'],
			'full',
			!empty( $frame['foreground_image_fallback'] ) ? $frame['foreground_image_fallback'] : ''
		);

		if( !empty($foreground_src) ) {
			?>
			<div class="sow-slider-image-container">
				<div class="sow-slider-image-wrapper" style="<?php if(!empty($foreground_src[1])) echo 'max-width: ' . intval($foreground_src[1]) . 'px' ?>">
					<?php
					if(!empty($frame['url'])) echo '<a href="' . sow_esc_url($frame['url']) . '" ' . ( !empty($frame['new_window']) ? 'target="_blank"' : '' ) . '>';
					echo siteorigin_widgets_get_attachment_image(
						$frame['foreground_image'],
						'full',
						!empty( $frame['foreground_image_fallback'] ) ? $frame['foreground_image_fallback'] : ''
					);
					if(!empty($frame['url'])) echo '</a>';
					?>
				</div>
			</div>
			<?php
		}
		else if( empty($frame['background_videos']) ) {
			// We need to find another background
			if(!empty($frame['url'])) echo '<a href="' . sow_esc_url($frame['url']) . '" ' . ( !empty($frame['new_window']) ? 'target="_blank"' : '' ) . '>';

			// Lets use the background image
			echo siteorigin_widgets_get_attachment_image(
				$frame['background_image'],
				'full',
				!empty( $frame['background_image_fallback'] ) ? $frame['background_image_fallback'] : ''
			);

			if( !empty($frame['url']) ) echo '</a>';
		}

	}

	/**
	 * The less variables to control the design of the slider
	 *
	 * @param $instance
	 *
	 * @return array
	 */
	function get_less_variables($instance) {
		$less = array();

		if( !empty($instance['controls']['nav_color_hex']) ) $less['nav_color_hex'] = $instance['controls']['nav_color_hex'];
		if( !empty($instance['controls']['nav_size']) ) $less['nav_size'] = $instance['controls']['nav_size'];

		return $less;
	}

	/**
	 * Change the instance to the new one we're using for sliders
	 *
	 * @param $instance
	 *
	 * @return mixed
	 */
	function modify_instance( $instance ){
		if( empty($instance['controls']) ) {
			if ( ! empty( $instance['speed'] ) ) {
				$instance['controls']['speed'] = $instance['speed'];
				unset($instance['speed']);
			}
			if ( ! empty( $instance['timeout'] ) ) {
				$instance['controls']['timeout'] = $instance['timeout'];
				unset($instance['timeout']);
			}
			if ( ! empty( $instance['nav_color_hex'] ) ) {
				$instance['controls']['nav_color_hex'] = $instance['nav_color_hex'];
				unset($instance['nav_color_hex']);
			}
			if ( ! empty( $instance['nav_style'] ) ) {
				$instance['controls']['nav_style'] = $instance['nav_style'];
				unset($instance['nav_style']);
			}
			if ( ! empty( $instance['nav_size'] ) ) {
				$instance['controls']['nav_size'] = $instance['nav_size'];
				unset($instance['nav_size']);
			}

		}

		return $instance;
	}
}

siteorigin_widget_register('sow-slider', __FILE__, 'SiteOrigin_Widget_Slider_Widget');

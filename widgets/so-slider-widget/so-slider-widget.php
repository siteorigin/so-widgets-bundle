<?php
/*
Widget Name: Slider widget
Description: A very simple slider widget.
Author: Greg Priday
Author URI: http://siteorigin.com
*/

if( !class_exists( 'SiteOrigin_Widget_Base_Slider' ) ) include_once plugin_dir_path(SOW_BUNDLE_BASE_FILE) . '/base/inc/widgets/base-slider.class.php';

class SiteOrigin_Widget_Slider_Widget extends SiteOrigin_Widget_Base_Slider {
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
							'fields' => $this->video_form_fields(),
						),

						'background_image' => array(
							'type' => 'media',
							'library' => 'image',
							'label' => __('Background image', 'siteorigin-widgets'),
						),

						'background_color' => array(
							'type' => 'color',
							'label' => __('Background Color', 'siteorigin-widgets'),
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
				'controls' => array(
					'type' => 'section',
					'label' => __('Controls', 'siteorigin-widget'),
					'fields' => $this->control_form_fields()
				)
			),
			plugin_dir_path(__FILE__).'../'
		);
	}

	function get_frame_background( $i, $frame ){
		if( empty($frame['background_image']) ) $background_image = false;
		else $background_image = wp_get_attachment_image_src($frame['background_image'], 'full');

		return array(
			'color' => !empty( $frame['background_color'] ) ? $frame['background_color'] : '#a0a0a0',
			'image' => !empty( $background_image ) ? $background_image[0] : false,
			'opacity' => 1,
			'image-sizing' => 'cover',
			'videos' => $frame['background_videos'],
			'video-sizing' => empty($frame['foreground_image']) ? 'full' : 'background',
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

		if( !empty($frame['foreground_image']) ) {
			$foreground_image = wp_get_attachment_image_src($frame['foreground_image'], 'full');
			?>
			<div class="sow-slider-image-container">
				<div class="sow-slider-image-wrapper" style="<?php if(!empty($foreground_image[1])) echo 'max-width: ' . intval($foreground_image[1]) . 'px' ?>">
					<?php
					if(!empty($frame['url'])) echo '<a href="' . sow_esc_url($frame['url']) . '">';
					echo wp_get_attachment_image($frame['foreground_image'], 'full');
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
			echo wp_get_attachment_image($frame['background_image'], 'full');

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
	 * @return mixed|void
	 */
	function modify_instance( $instance ){
		if( empty($instance['controls']) ) {
			if( !empty($instance['speed']) ) $instance['controls']['speed'] = $instance['speed'];
			if( !empty($instance['timeout']) ) $instance['controls']['timeout'] = $instance['timeout'];
			if( !empty($instance['nav_color_hex']) ) $instance['controls']['nav_color_hex'] = $instance['nav_color_hex'];
			if( !empty($instance['nav_style']) ) $instance['controls']['nav_style'] = $instance['nav_style'];
			if( !empty($instance['nav_size']) ) $instance['controls']['nav_size'] = $instance['nav_size'];

			unset($instance['speed']);
			unset($instance['timeout']);
			unset($instance['nav_color_hex']);
			unset($instance['nav_style']);
			unset($instance['nav_size']);
		}

		return $instance;
	}
}

siteorigin_widget_register('slider', __FILE__);

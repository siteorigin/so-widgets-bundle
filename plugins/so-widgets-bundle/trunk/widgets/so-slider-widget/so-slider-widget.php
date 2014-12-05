<?php
/*
Widget Name: Slider Widget
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
					'label' => __('Slider Frames', 'siteorigin-widgets'),
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
							'label' => __('Background Videos', 'siteorigin-widgets'),
							'item_label' => array(
								'selector' => "[id*='frames-background_videos-url']",
								'update_event' => 'change',
								'value_method' => 'val'
							),
							'fields' => array(
								'file' => array(
									'type' => 'media',
									'library' => 'video',
									'label' => __('Video File', 'siteorigin-widgets'),
								),

								'url' => array(
									'type' => 'text',
									'sanitize' => 'url',
									'label' => __('Video URL', 'siteorigin-widgets'),
									'optional' => 'true',
									'description' => __('An external URL of the video. Overrides Video File.')
								),

								'format' => array(
									'type' => 'select',
									'label' => __('Video Format', 'siteorigin-widgets'),
									'options' => array(
										'video/mp4' => 'MP4',
										'video/webm' => 'WebM',
										'video/ogg' => 'Ogg',
									),
								),
							),
						),

						'background_image' => array(
							'type' => 'media',
							'library' => 'image',
							'label' => __('Background Image', 'siteorigin-widgets'),
						),

						'background_image_type' => array(
							'type' => 'select',
							'label' => __('Background Image Type', 'siteorigin-widgets'),
							'options' => array(
								'cover' => __('Cover', 'siteorigin-widgets'),
								'tile' => __('Tile', 'siteorigin-widgets'),
							),
							'default' => 'cover',
						),

						'foreground_image' => array(
							'type' => 'media',
							'library' => 'image',
							'label' => __('Foreground Image', 'siteorigin-widgets'),
						),

						'url' => array(
							'type' => 'text',
							'label' => __('Destination URL', 'siteorigin-widgets'),
							'sanitize' => 'url',
						),
					),
				),

				'speed' => array(
					'type' => 'number',
					'label' => __('Animation Speed', 'siteorigin-widgets'),
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
					'label' => __('Navigation Color', 'siteorigin-widgets'),
					'default' => '#FFFFFF',
				),

				'nav_style' => array(
					'type' => 'select',
					'label' => __('Navigation Style', 'siteorigin-widgets'),
					'default' => 'thin',
					'options' => array(
						'ultra-thin' => __('Ultra Thin', 'siteorigin-widgets'),
						'thin' => __('Thin', 'siteorigin-widgets'),
						'medium' => __('Medium', 'siteorigin-widgets'),
						'thick' => __('Thick', 'siteorigin-widgets'),
						'ultra-thin-rounded' => __('Rounded Ultra Thin', 'siteorigin-widgets'),
						'thin-rounded' => __('Rounded Thin', 'siteorigin-widgets'),
						'medium-rounded' => __('Rounded Medium', 'siteorigin-widgets'),
						'thick-rounded' => __('Rounded Thick', 'siteorigin-widgets'),
					)
				),

				'nav_size' => array(
					'type' => 'number',
					'label' => __('Navigation Size', 'siteorigin-widgets'),
					'default' => '25',
				),

			),
			plugin_dir_path(__FILE__).'../'
		);
	}

	function video_code($videos, $classes = array()){
		if(empty($videos)) return;




		?>
		<video class="<?php echo esc_attr( implode(',', $classes) ) ?>" autoplay loop muted>

			<?php
			foreach($videos as $video) {
				if( empty( $video['file'] ) && empty ( $video['url'] ) ) continue;

				if( empty( $video['url'] ) ) $video_file = wp_get_attachment_url($video['file']);
				else $video_file = $video['url'];

				?><source src="<?php echo esc_url($video_file) ?>" type="<?php echo esc_attr($video['format']) ?>"><?php
			}
			?>
		</video>
	<?php
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
		return array(
			'nav_color_hex' => $instance['nav_color_hex'],
			'nav_size' => $instance['nav_size'],
		);
	}

	/**
	 * Enqueue the slider scripts
	 */
	function enqueue_frontend_scripts(){
		wp_enqueue_style('sow-slider-slider');
		wp_enqueue_script('sow-slider-slider-cycle2');
		if( wp_is_mobile() ) wp_enqueue_script('sow-slider-slider-cycle2-swipe');
		wp_enqueue_script('sow-slider-slider');
	}
}

siteorigin_widget_register('slider', __FILE__);

/**
 * Register all the slider scripts
 */
function sow_slider_register_scripts(){
	wp_register_style('sow-slider-slider', siteorigin_widget_get_plugin_dir_url('slider').'css/slider.css', array(), SOW_BUNDLE_VERSION);
	wp_register_script('sow-slider-slider-cycle2', siteorigin_widget_get_plugin_dir_url('slider').'js/jquery.cycle.js', array('jquery'), SOW_BUNDLE_VERSION);
	wp_register_script('sow-slider-slider-cycle2-swipe', siteorigin_widget_get_plugin_dir_url('slider').'js/jquery.cycle.swipe.js', array('jquery'), SOW_BUNDLE_VERSION);
	wp_register_script('sow-slider-slider', siteorigin_widget_get_plugin_dir_url('slider').'js/slider.js', array('jquery'), SOW_BUNDLE_VERSION);
}
add_action('wp_enqueue_scripts', 'sow_slider_register_scripts', 1);
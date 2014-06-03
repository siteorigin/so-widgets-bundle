<?php

class SiteOrigin_Widget_Slider_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-slider',
			__('SiteOrigin Slider', 'sow-slider'),
			array(
				'description' => __('A simple slider widget.', 'sow-slider'),
				'help' => 'http://siteorigin.com/widgets-bundle/slider-widget-documentation/'
			),
			array(

			),
			array(

				'frames' => array(
					'type' => 'repeater',
					'label' => __('Slider Frames', 'sow-slider'),
					'item_name' => __('Frame', 'sow-slider'),
					'fields' => array(
						'background_videos' => array(
							'type' => 'repeater',
							'item_name' => __('Video', 'sow-slider'),
							'label' => __('Background Videos', 'sow-slider'),
							'fields' => array(
								'file' => array(
									'type' => 'media',
									'library' => 'video',
									'label' => __('Video File', 'sow-slider'),
								),

								'url' => array(
									'type' => 'text',
									'sanitize' => 'url',
									'label' => __('Video URL', 'sow-slider'),
									'optional' => 'true',
									'description' => __('An external URL of the video. Overrides Video File.')
								),

								'format' => array(
									'type' => 'select',
									'label' => __('Video Format', 'sow-slider'),
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
							'label' => __('Background Image', 'sow-slider'),
						),

						'background_image_type' => array(
							'type' => 'select',
							'label' => __('Background Image Type', 'sow-slider'),
							'options' => array(
								'cover' => __('Cover', 'sow-slider'),
								'tile' => __('Tile', 'sow-slider'),
							),
							'default' => 'cover',
						),

						'foreground_image' => array(
							'type' => 'media',
							'library' => 'image',
							'label' => __('Foreground Image', 'sow-slider'),
						),

						'url' => array(
							'type' => 'text',
							'label' => __('Destination URL', 'sow-slider'),
							'sanitize' => 'url',
						),
					),
				),

				'speed' => array(
					'type' => 'number',
					'label' => __('Animation Speed', 'sow-slider'),
					'description' => __('Animation speed in milliseconds.', 'sow-slider'),
					'default' => 800,
				),

				'timeout' => array(
					'type' => 'number',
					'label' => __('Timeout', 'sow-slider'),
					'description' => __('How long each slide is displayed for in milliseconds.', 'sow-slider'),
					'default' => 8000,
				),

				'nav_color_hex' => array(
					'type' => 'color',
					'label' => __('Navigation Color', 'sow-slider'),
					'default' => '#FFFFFF',
				),

				'nav_style' => array(
					'type' => 'select',
					'label' => __('Navigation Style', 'sow-slider'),
					'default' => 'thin',
					'options' => array(
						'ultra-thin' => __('Ultra Thin', 'sow-slider'),
						'thin' => __('Thin', 'sow-slider'),
						'medium' => __('Medium', 'sow-slider'),
						'thick' => __('Thick', 'sow-slider'),
						'ultra-thin-rounded' => __('Rounded Ultra Thin', 'sow-slider'),
						'thin-rounded' => __('Rounded Thin', 'sow-slider'),
						'medium-rounded' => __('Rounded Medium', 'sow-slider'),
						'thick-rounded' => __('Rounded Thick', 'sow-slider'),
					)
				),

				'nav_size' => array(
					'type' => 'number',
					'label' => __('Navigation Size', 'sow-slider'),
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

function sow_slider_register_widget(){
	register_widget('SiteOrigin_Widget_Slider_Widget');
}
add_action('widgets_init', 'sow_slider_register_widget');
<?php
/*
Widget Name: Layout Slider
Description: A slider that allows you to create responsive columnized content for each slide.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

if( !class_exists( 'SiteOrigin_Widget_Base_Slider' ) ) include_once plugin_dir_path(SOW_BUNDLE_BASE_FILE) . '/base/inc/widgets/base-slider.class.php';

class SiteOrigin_Widget_LayoutSlider_Widget extends SiteOrigin_Widget_Base_Slider {

	protected $buttons = array();

	function __construct() {
		parent::__construct(
			'sow-layout-slider',
			__('SiteOrigin Layout Slider', 'so-widgets-bundle'),
			array(
				'description' => __('A slider that allows you to create responsive columnized content for each slide.', 'so-widgets-bundle'),
				'help' => 'https://siteorigin.com/widgets-bundle/layout-slider-widget/',
				'panels_title' => false,
			),
			array( ),
			false,
			plugin_dir_path(__FILE__)
		);
	}

	function get_widget_form(){
		return array(
			'frames' => array(
				'type' => 'repeater',
				'label' => __('Slider frames', 'so-widgets-bundle'),
				'item_name' => __('Frame', 'so-widgets-bundle'),
				'item_label' => array(
					'selector' => "[id*='frames-title']",
					'update_event' => 'change',
					'value_method' => 'val'
				),

				'fields' => array(

					'content' => array(
						'type' => 'builder',
						'builder_type' => 'layout_slider_builder',
						'label' => __( 'Content', 'so-widgets-bundle' ),
					),

					'background' => array(
						'type' => 'section',
						'label' => __('Background', 'so-widgets-bundle'),
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
									'cover' => __( 'Cover', 'so-widgets-bundle '),
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
								'item_name' => __('Video', 'so-widgets-bundle'),
								'label' => __('Background videos', 'so-widgets-bundle'),
								'item_label' => array(
									'selector' => "[id*='frames-background_videos-url']",
									'update_event' => 'change',
									'value_method' => 'val'
								),
								'fields' => $this->video_form_fields(),
							),
						)
					),
				),
			),

			'controls' => array(
				'type' => 'section',
				'label' => __('Slider Controls', 'so-widgets-bundle'),
				'fields' => $this->control_form_fields()
			),

			'design' => array(
				'type' => 'section',
				'label' => __('Design and Layout', 'so-widgets-bundle'),
				'fields' => array(

					'height' => array(
						'type' => 'measurement',
						'label' => __( 'Height', 'so-widgets-bundle' ),
						'default' => 'default',
					),

					'padding' => array(
						'type' => 'measurement',
						'label' => __('Top and bottom padding', 'so-widgets-bundle'),
						'default' => '50px',
					),

					'extra_top_padding' => array(
						'type' => 'measurement',
						'label' => __('Extra top padding', 'so-widgets-bundle'),
						'description' => __('Additional padding added to the top of the slider', 'so-widgets-bundle'),
						'default' => '0px',
					),

					'padding_sides' => array(
						'type' => 'measurement',
						'label' => __('Side padding', 'so-widgets-bundle'),
						'default' => '20px',
					),

					'width' => array(
						'type' => 'measurement',
						'label' => __('Maximum container width', 'so-widgets-bundle'),
						'default' => '1280px',
					),

					'heading_color' => array(
						'type' => 'color',
						'label' => __('Heading color', 'so-widgets-bundle'),
						'default' => '#FFFFFF',
					),

					'heading_size' => array(
						'type' => 'measurement',
						'label' => __('Heading size', 'so-widgets-bundle'),
						'default' => '38px',
					),

					'heading_shadow' => array(
						'type' => 'slider',
						'label' => __('Heading shadow intensity', 'so-widgets-bundle'),
						'max' => 100,
						'min' => 0,
						'default' => 50,
					),

					'text_size' => array(
						'type' => 'measurement',
						'label' => __('Text size', 'so-widgets-bundle'),
						'default' => '16px',
					),

					'text_color' => array(
						'type' => 'color',
						'label' => __('Text color', 'so-widgets-bundle'),
						'default' => '#F6F6F6',
					),

				)
			),
		);
	}

	function form( $instance, $form_type = 'widget' ) {
		if( defined('SITEORIGIN_PANELS_VERSION') ) {
			parent::form( $instance, $form_type );
		} else {
			?>
			<p>
				<?php _e( 'This widget requires: ', 'so-widgets-bundle' ) ?>
				<a href="https://siteorigin.com/page-builder/" target="_blank"><?php _e( 'SiteOrigin Page Builder', 'so-widgets-bundle' ) ?></a>
			</p>
			<?php
		}
	}

	/**
	 * Get everything necessary for the background image.
	 *
	 * @param $i
	 * @param $frame
	 *
	 * @return array
	 */
	function get_frame_background( $i, $frame ){
		$background_image = siteorigin_widgets_get_attachment_image_src(
			$frame['background']['image'],
			'full',
			!empty( $frame['background']['image_fallback'] ) ? $frame['background']['image_fallback'] : ''
		);

		return array(
			'color' => !empty( $frame['background']['color'] ) ? $frame['background']['color'] : false,
			'image' => !empty( $background_image[0] ) ? $background_image[0] : false,
			'image-width' => !empty( $background_image[1] ) ? $background_image[1] : 0,
			'image-height' => !empty( $background_image[2] ) ? $background_image[2] : 0,
			'image-sizing' => $frame['background']['image_type'],
			'url' => !empty( $frame['background']['url'] ) ? $frame['background']['url'] : false,
			'new_window' => !empty( $frame['background']['new_window'] ),
			'videos' => $frame['background']['videos'],
			'video-sizing' => 'background',
			'opacity' => intval($frame['background']['opacity'])/100,
		);
	}

	/**
	 * Render the actual content of the frame
	 *
	 * @param $i
	 * @param $frame
	 */
	function render_frame_contents($i, $frame) {
		?>
		<div class="sow-slider-image-container">
			<div class="sow-slider-image-wrapper">
				<?php echo $this->process_content( $frame['content'], $frame ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Process the content.
	 *
	 * @param $content
	 * @param $frame
	 *
	 * @return string
	 */
	function process_content( $content, $frame ) {
		if( function_exists( 'siteorigin_panels_render' ) ) {
			$content_builder_id = substr( md5( json_encode( $content ) ), 0, 8 );
			echo siteorigin_panels_render( 'w'.$content_builder_id, true, $content );
		}
		else {
			echo __( 'This widget requires Page Builder.', 'so-widgets-bundle' );
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

		// Slider navigation controls
		$less['nav_color_hex'] = $instance['controls']['nav_color_hex'];
		$less['nav_size'] = $instance['controls']['nav_size'];

		// Hero specific design
		//Measurement field type options
		$meas_options = array();
		$meas_options['slide_padding'] = $instance['design']['padding'];
		$meas_options['slide_padding_extra_top'] = $instance['design']['extra_top_padding'];
		$meas_options['slide_padding_sides'] = $instance['design']['padding_sides'];
		$meas_options['slide_width'] = $instance['design']['width'];
		$meas_options['slide_height'] = $instance['design']['height'];

		$meas_options['heading_size'] = $instance['design']['heading_size'];
		$meas_options['text_size'] = $instance['design']['text_size'];

		foreach ( $meas_options as $key => $val ) {
			$less[ $key ] = $this->add_default_measurement_unit( $val );
		}

		$less['heading_shadow'] = intval( $instance['design']['heading_shadow'] );

		$less['heading_color'] = $instance['design']['heading_color'];
		$less['text_color'] = $instance['design']['text_color'];

		return $less;
	}

	function add_default_measurement_unit($val) {
		if (!empty($val)) {
			if (!preg_match('/\d+([a-zA-Z%]+)/', $val)) {
				$val .= 'px';
			}
		}
		return $val;
	}
}

siteorigin_widget_register('sow-layout-slider', __FILE__, 'SiteOrigin_Widget_LayoutSlider_Widget');

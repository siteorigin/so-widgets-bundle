<?php
/*
Widget Name: Hero Image
Description: A big hero image with a few settings to make it your own.
Author: Greg Priday
Author URI: http://siteorigin.com
*/

if( !class_exists( 'SiteOrigin_Widget_Base_Slider' ) ) include_once plugin_dir_path(SOW_BUNDLE_BASE_FILE) . '/base/inc/widgets/base-slider.class.php';

class SiteOrigin_Widget_Hero_Widget extends SiteOrigin_Widget_Base_Slider {

	protected $buttons = array();

	function __construct() {
		parent::__construct(
			'sow-hero',
			__('SiteOrigin Hero', 'siteorigin-widgets'),
			array(
				'description' => __('A big hero image with a few settings to make it your own.', 'siteorigin-widgets'),
				'help' => 'https://siteorigin.com/widgets-bundle/hero-image-widget/',
				'panels_title' => false,
			),
			array( ),
			array(
				'frames' => array(
					'type' => 'repeater',
					'label' => __('Hero frames', 'siteorigin-widgets'),
					'item_name' => __('Frame', 'siteorigin-widgets'),
					'item_label' => array(
						'selector' => "[id*='frames-title']",
						'update_event' => 'change',
						'value_method' => 'val'
					),

					'fields' => array(

						'content' => array(
							'type' => 'tinymce',
							'label' => __( 'Content', 'siteorigin-widgets' ),
						),

						'buttons' => array(
							'type' => 'repeater',
							'label' => __('Buttons', 'siteorigin-widgets'),
							'item_name' => __('Button', 'siteorigin-widgets'),
							'description' => __('Add [buttons] shortcode to the content to insert these buttons.', 'siteorigin-widgets'),

							'item_label' => array(
								'selector' => "[id*='buttons-button-text']",
								'update_event' => 'change',
								'value_method' => 'val'
							),
							'fields' => array(
								'button' => array(
									'type' => 'widget',
									'class' => 'SiteOrigin_Widget_Button_Widget',
									'label' => __('Button', 'siteorigin-widgets'),
									'collapsible' => false,
								)
							)
						),

						'background' => array(
							'type' => 'section',
							'label' => __('Background', 'siteorigin-widget'),
							'fields' => array(
								'image' => array(
									'type' => 'media',
									'label' => __( 'Background image', 'siteorigin-widgets' ),
									'library' => 'image',
								),

								'opacity' => array(
									'label' => __( 'Background image opacity', 'siteorigin-widgets' ),
									'type' => 'slider',
									'min' => 0,
									'max' => 100,
									'default' => 100,
								),

								'color' => array(
									'type' => 'color',
									'label' => __( 'Background color', 'siteorigin-widgets' ),
									'default' => '#333333',
								),

								'videos' => array(
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
							)
						),
					),
				),

				'controls' => array(
					'type' => 'section',
					'label' => __('Slider Controls', 'siteorigin-widget'),
					'fields' => $this->control_form_fields()
				),

				'design' => array(
					'type' => 'section',
					'label' => __('Design and Layout', 'siteorigin-widgets'),
					'fields' => array(

						'padding' => array(
							'type' => 'slider',
							'label' => __('Padding', 'siteorigin-widgets'),
							'max' => 150,
							'min' => 0,
							'default' => 50,
						),

						'width' => array(
							'type' => 'slider',
							'label' => __('Maximum Container Width', 'siteorigin-widgets'),
							'max' => 1920,
							'min' => 280,
							'default' => 1280,
						),

						'heading_size' => array(
							'type' => 'slider',
							'label' => __('Heading Size', 'siteorigin-widgets'),
							'max' => 72,
							'min' => 6,
							'default' => 38,
						),

						'text_size' => array(
							'type' => 'slider',
							'label' => __('Text Size', 'siteorigin-widgets'),
							'max' => 48,
							'min' => 6,
							'default' => 16,
						),

					)
				),
			)
		);
	}

	function initialize(){
		if( !class_exists('SiteOrigin_Widget_Button_Widget') ) {
			// We need to include the button
			include plugin_dir_path( SOW_BUNDLE_BASE_FILE ) . 'widgets/so-button-widget/so-button-widget.php';
			siteorigin_widget_register( 'button', plugin_dir_path( SOW_BUNDLE_BASE_FILE ) . 'widgets/so-button-widget/so-button-widget.php' );
		}

		// Let the slider base class do its initialization
		parent::initialize();
	}

	/**
	 * Get everything neccessary for the background image.
	 *
	 * @param $i
	 * @param $frame
	 *
	 * @return array
	 */
	function get_frame_background( $i, $frame ){
		if( empty($frame['background']['image']) ) $background_image = false;
		else $background_image = wp_get_attachment_image_src($frame['background']['image'], 'full');

		return array(
			'color' => !empty( $frame['background']['color'] ) ? $frame['background']['color'] : false,
			'image' => !empty( $background_image ) ? $background_image[0] : false,
			'image-sizing' => 'cover',
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
	 * Process the content. Most importantly add the buttons by replacing [buttons] in the content
	 *
	 * @param $content
	 * @param $frame
	 *
	 * @return string
	 */
	function process_content( $content, $frame ) {
		ob_start();
		foreach( $frame['buttons'] as $button ) {
			$this->sub_widget('SiteOrigin_Widget_Button_Widget', array(), $button['button']);
		}
		$button_code = ob_get_clean();

		// Add in the button code
		$content = preg_replace('/<p *([^>]*)> *\[ *buttons *\] *<\/p>/i', '<div class="sow-hero-buttons" $1>' . $button_code . '</div>', wp_kses_post( $content ) );
		return $content;
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
		$less['slide_padding'] = intval( $instance['design']['padding'] ) . 'px';
		$less['slide_width'] = intval( $instance['design']['width'] ) . 'px';
		$less['heading_size'] = intval( $instance['design']['heading_size'] ) . 'px';
		$less['text_size'] = intval( $instance['design']['text_size'] ) . 'px';

		return $less;
	}

}

siteorigin_widget_register('hero', __FILE__);
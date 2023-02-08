<?php
/*
Widget Name: Accordion
Description: An accordion to squeeze a lot of content into a small space.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/accordion-widget/
*/

class SiteOrigin_Widget_Accordion_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'sow-accordion',
			__( 'SiteOrigin Accordion', 'so-widgets-bundle' ),
			array(
				'description' => __( 'An accordion to squeeze a lot of content into a small space.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/accordion-widget/',
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	/**
	 * Initialize the Accordion Widget.
	 */
	public function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'sow-accordion',
					plugin_dir_url( __FILE__ ) . 'js/accordion' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' ),
					SOW_BUNDLE_VERSION,
				),
			)
		);

		add_action( 'siteorigin_widgets_enqueue_frontend_scripts_sow-accordion', array( $this, 'enqueue_widget_scripts' ) );
	}

	public function get_settings_form() {
		return array(
			'scrollto_after_change' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Scroll top', 'so-widgets-bundle' ),
				'default'     => true,
				'description' => __( 'When opening the panel, scroll the user to the top of the panel.', 'so-widgets-bundle' ),
			),
		);
	}

	public function enqueue_widget_scripts() {
		$global_settings = $this->get_global_settings();
		wp_localize_script(
			'sow-accordion',
			'sowAccordion',
			array(
				'scrollto_after_change' => ! empty( $global_settings['scrollto_after_change'] ),
				'scrollto_offset' => (int) apply_filters( 'siteorigin_widgets_accordion_scrollto_offset', 80 ),
			)
		);
	}

	public function get_widget_form() {
		return array(
			'title' => array(
				'type' => 'text',
				'label' => __( 'Title', 'so-widgets-bundle' ),
			),
			'panels' => array(
				'type' => 'repeater',
				'label' => __( 'Panels', 'so-widgets-bundle' ),
				'item_label' => array(
					'selector' => "[id*='panels-title']",
					'update_event' => 'change',
					'value_method' => 'val',
				),
				'fields' => array(
					'title' => array(
						'type' => 'text',
						'label' => __( 'Title', 'so-widgets-bundle' ),
					),
					'content_text' => array(
						'type'  => 'tinymce',
						'label' => __( 'Content', 'so-widgets-bundle' ),
						'wpautop_toggle_field' => '.siteorigin-widget-field-autop input[type="checkbox"]',
					),
					'autop' => array(
						'type' => 'checkbox',
						'default' => false,
						'label' => __( 'Automatically add paragraphs', 'so-widgets-bundle' ),
					),
					'initial_state' => array(
						'type' => 'radio',
						'label' => __( 'Initial state', 'so-widgets-bundle' ),
						'description' => __( 'Whether this panel should be open or closed when the page first loads.', 'so-widgets-bundle' ),
						'options' => array(
							'open' => __( 'Open', 'so-widgets-bundle' ),
							'closed' => __( 'Closed', 'so-widgets-bundle' ),
						),
						'default' => 'closed',
					),
				),
			),
			'design' => array(
				'type' =>  'section',
				'label' => __( 'Design', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'heading' => array(
						'type' => 'section',
						'label' => __( 'Headings', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'background_color' => array(
								'type' => 'color',
								'label' => __( 'Background color', 'so-widgets-bundle' ),
								'default' => '#828282',
							),
							'background_hover_color' => array(
								'type' => 'color',
								'label' => __( 'Background hover color', 'so-widgets-bundle' ),
								'default' => '#8c8c8c',
							),
							'title_color' => array(
								'type' => 'color',
								'label' => __( 'Title color', 'so-widgets-bundle' ),
								'default' => '#fff',
							),
							'title_hover_color' => array(
								'type' => 'color',
								'label' => __( 'Title hover color', 'so-widgets-bundle' ),
							),
							'border_color' => array(
								'type' => 'color',
								'label' => __( 'Border color', 'so-widgets-bundle' ),
							),
							'border_hover_color' => array(
								'type' => 'color',
								'label' => __( 'Border hover color', 'so-widgets-bundle' ),
							),
							'border_width' => array(
								'type' => 'measurement',
								'label' => __( 'Border width', 'so-widgets-bundle' ),
							),
						),
					),
					'panels' => array(
						'type' => 'section',
						'label' => __( 'Panels', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'background_color' => array(
								'type' => 'color',
								'label' => __( 'Background color', 'so-widgets-bundle' ),
								'default' => '#f9f9f9',
							),
							'font_color' => array(
								'type' => 'color',
								'label' => __( 'Font color', 'so-widgets-bundle' ),
							),
							'border_color' => array(
								'type' => 'color',
								'label' => __( 'Border color', 'so-widgets-bundle' ),
							),
							'border_width' => array(
								'type' => 'measurement',
								'label' => __( 'Border width', 'so-widgets-bundle' ),
							),
							'margin_bottom' => array(
								'type' => 'measurement',
								'label' => __( 'Bottom margin', 'so-widgets-bundle' ),
								'default' => '10px',
							),
						),
					),
				),
			),
		);
	}

	public function get_less_variables( $instance ) {
		if ( empty( $instance['design'] ) ) {
			return array();
		}

		$design = $instance['design'];

		return array(
			'heading_background_color' => $design['heading']['background_color'],
			'heading_background_hover_color' => $design['heading']['background_hover_color'],
			'title_color' => $design['heading']['title_color'],
			'title_hover_color' => $design['heading']['title_hover_color'],
			'heading_border_color' => $design['heading']['border_color'],
			'heading_border_hover_color' => $design['heading']['border_hover_color'],
			'heading_border_width' => $design['heading']['border_width'],
			'has_heading_border_width' => empty( $design['heading']['border_width'] ) ? 'false' : 'true',
			'panels_background_color' => $design['panels']['background_color'],
			'panels_font_color' => $design['panels']['font_color'],
			'panels_border_color' => $design['panels']['border_color'],
			'panels_border_width' => $design['panels']['border_width'],
			'has_panels_border_width' => empty( $design['panels']['border_width'] ) ? 'false' : 'true',
			'panels_margin_bottom' => $design['panels']['margin_bottom'],
		);
	}

	public function get_template_variables( $instance, $args ) {
		if ( empty( $instance ) ) {
			return array();
		}

		$panels = empty( $instance['panels'] ) ? array() : $instance['panels'];

		$anchor_list = array();

		foreach ( $panels as $i => &$panel ) {
			if ( empty( $panel['before_title'] ) ) {
				$panel['before_title'] = '';
			}

			if ( empty( $panel['after_title'] ) ) {
				$panel['after_title'] = '';
			}

			if ( empty( $panel['title'] ) ) {
				$id = $this->id_base;

				if ( ! empty( $instance['_sow_form_id'] ) ) {
					$id .= '-' . $instance['_sow_form_id'];
				} elseif ( ! empty( $args['widget_id'] ) ) {
					$id .= '-' . $args['widget_id'];
				}
				$panel['anchor'] = $id . '-' . $i;
			} elseif ( isset( $anchor_list[ strtolower( $panel['title'] ) ] ) ) {
				// Ensure this anchor is unique, if it's not, append the array key to the anchor.
				$panel['anchor'] = $panel['title'] . "-$i-" . uniqid();
			} else {
				$panel['anchor'] = $panel['title'];
			}

			$anchor_list[ strtolower( $panel['anchor'] ) ] = true;
		}

		if ( empty( $instance['design']['heading']['icon_open'] ) ) {
			$instance['design']['heading']['icon_open'] = 'ionicons-plus';
		}

		if ( empty( $instance['design']['heading']['icon_close'] ) ) {
			$instance['design']['heading']['icon_close'] = 'ionicons-minus';
		}

		return array(
			'panels' => $panels,
			'icon_open' => $instance['design']['heading']['icon_open'],
			'icon_close' => $instance['design']['heading']['icon_close'],
		);
	}

	public function render_panel_content( $panel, $instance ) {
		$content = $panel['autop'] ? wpautop( $panel['content_text'] ) : $panel['content_text'];

		echo apply_filters( 'siteorigin_widgets_accordion_render_panel_content', $content, $panel, $instance );
	}

	public function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return array(
			sprintf(
				__( 'Get more customization options and the ability to use widgets and layouts as your accordion content with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/accordion" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
			sprintf(
				__( 'Use Google Fonts right inside the Accordion Widget with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/accordion" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
		);
	}
}

siteorigin_widget_register( 'sow-accordion', __FILE__, 'SiteOrigin_Widget_Accordion_Widget' );

<?php
/*
Widget Name: Call To Action
Description: Insert a title, subtitle, and button. Get visitors moving in the right direction.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/call-action-widget/
*/

class SiteOrigin_Widget_Cta_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'sow-cta',
			__( 'SiteOrigin Call To Action', 'so-widgets-bundle' ),
			array(
				'description' => __( 'Insert a title, subtitle, and button. Get visitors moving in the right direction.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/call-action-widget/',
			),
			array(
			),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	/**
	 * Initialize the CTA Widget.
	 */
	public function initialize() {
		// This widget requires the Button Widget.
		if ( ! class_exists( 'SiteOrigin_Widget_Button_Widget' ) ) {
			SiteOrigin_Widgets_Bundle::single()->include_widget( 'button' );
		}
		$this->register_frontend_styles(
			array(
				array(
					'sow-cta-main',
					plugin_dir_url( __FILE__ ) . 'css/style.css',
					array(),
					SOW_BUNDLE_VERSION,
				),
			)
		);
		$this->register_frontend_scripts(
			array(
				array(
					'sow-cta-main',
					plugin_dir_url( __FILE__ ) . 'js/cta' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' ),
					SOW_BUNDLE_VERSION,
				),
			)
		);


		add_filter( 'siteorigin_widgets_google_font_fields_sow-cta', array( $this, 'add_google_font_fields' ), 10, 3 );
	}

	public function get_settings_form() {
		return array(
			'responsive_breakpoint' => array(
				'type'        => 'measurement',
				'label'       => __( 'Responsive Breakpoint', 'so-widgets-bundle' ),
				'default'     => '780px',
				'description' => __( 'This setting controls when the mobile alignment will be used. The default value is 780px.', 'so-widgets-bundle' ),
			),
		);
	}

	public function get_widget_form() {
		return array(
			'title' => array(
				'type' => 'text',
				'label' => __( 'Title', 'so-widgets-bundle' ),
			),

			'sub_title' => array(
				'type' => 'text',
				'label' => __( 'Subtitle', 'so-widgets-bundle' ),
			),

			'design' => array(
				'type' => 'section',
				'label' => __( 'Design', 'so-widgets-bundle' ),
				'fields' => array(
					'colors' => array(
						'type' => 'section',
						'label' => __( 'Colors', 'so-widgets-bundle' ),
						'fields' => array(
							'background_color' => array(
								'type' => 'color',
								'label' => __( 'Background Color', 'so-widgets-bundle' ),
								'default' => '#f8f8f8',
							),
							'border_color' => array(
								'type' => 'color',
								'label' => __( 'Border Color', 'so-widgets-bundle' ),
								'default' => '#e3e3e3',
							),
							'title_color' => array(
								'type' => 'color',
								'label' => __( 'Title Color', 'so-widgets-bundle' ),
							),
							'subtitle_color' => array(
								'type' => 'color',
								'label' => __( 'Subtitle Color', 'so-widgets-bundle' ),
							),
						),
					),
					'fonts' => array(
						'type' => 'section',
						'label' => __( 'Fonts', 'so-widgets-bundle' ),
						'fields' => array(
							'title_tag' => array(
								'type' => 'select',
								'label' => __( 'Title HTML Tag', 'siteorigin-premium' ),
								'default' => 'h4',
								'options' => array(
									'h1' => __( 'H1', 'so-widgets-bundle' ),
									'h2' => __( 'H2', 'so-widgets-bundle' ),
									'h3' => __( 'H3', 'so-widgets-bundle' ),
									'h4' => __( 'H4', 'so-widgets-bundle' ),
									'h5' => __( 'H5', 'so-widgets-bundle' ),
									'h6' => __( 'H6', 'so-widgets-bundle' ),
									'p' => __( 'Paragraph', 'so-widgets-bundle' ),
								),
							),
							'title_font_family' => array(
								'type' => 'font',
								'label' => __( 'Title Font Family', 'so-widgets-bundle' ),
							),
							'title_font_size' => array(
								'type' => 'measurement',
								'label' => __( 'Title Font Size', 'so-widgets-bundle' ),
							),
							'sub_title_tag' => array(
								'type' => 'select',
								'label' => __( 'Subtitle HTML Tag', 'so-widgets-bundle' ),
								'default' => 'h5',
								'options' => array(
									'h1' => __( 'H1', 'so-widgets-bundle' ),
									'h2' => __( 'H2', 'so-widgets-bundle' ),
									'h3' => __( 'H3', 'so-widgets-bundle' ),
									'h4' => __( 'H4', 'so-widgets-bundle' ),
									'h5' => __( 'H5', 'so-widgets-bundle' ),
									'h6' => __( 'H6', 'so-widgets-bundle' ),
									'p' => __( 'Paragraph', 'so-widgets-bundle' ),
								),
							),
							'subtitle_font_family' => array(
								'type' => 'font',
								'label' => __( 'Subtitle Font Family', 'so-widgets-bundle' ),
							),
							'subtitle_font_size' => array(
								'type' => 'measurement',
								'label' => __( 'Subtitle Font Size', 'so-widgets-bundle' ),
							),
						),
					),
					'layout' => array(
						'type' => 'section',
						'label' => __( 'Layout', 'so-widgets-bundle' ),
						'fields' => array(
							'desktop' => array(
								'type' => 'select',
								'label' => __( 'Desktop Button Align', 'so-widgets-bundle' ),
								'default' => 'right',
								'options' => array(
									'top' => __( 'Center Top', 'so-widgets-bundle' ),
									'left' => __( 'Left', 'so-widgets-bundle' ),
									'bottom' => __( 'Center Bottom', 'so-widgets-bundle' ),
									'right' => __( 'Right', 'so-widgets-bundle' ),
								),
							),
							'mobile' => array(
								'type' => 'select',
								'label' => __( 'Mobile Button Align', 'so-widgets-bundle' ),
								'default' => 'right',
								'options' => array(
									'' => __( 'Desktop Button Align', 'so-widgets-bundle' ),
									'above' => __( 'Center Top', 'so-widgets-bundle' ),
									'below' => __( 'Center Bottom', 'so-widgets-bundle' ),
								),
							),
						),
					),
				),
			),

			'button' => array(
				'type' => 'widget',
				'class' => 'SiteOrigin_Widget_Button_Widget',
				'label' => __( 'Button', 'so-widgets-bundle' ),
			),
		);
	}

	public function modify_child_widget_form( $child_widget_form, $child_widget ) {
		unset( $child_widget_form['design']['fields']['align'] );
		unset( $child_widget_form['design']['fields']['mobile_align'] );

		return $child_widget_form;
	}

	public function modify_instance( $instance ) {
		if ( empty( $instance ) || empty( $instance['design'] ) ) {
			return array();
		}

		if ( isset( $instance['design']['background_color'] ) ) {
			$instance['design']['colors'] = array();
			$instance['design']['colors']['background_color'] = $instance['design']['background_color'];
			$instance['design']['colors']['title_color'] = $instance['design']['title_color'];
			$instance['design']['colors']['subtitle_color'] = $instance['design']['subtitle_color'];
			$instance['design']['layout'] = array();
			$instance['design']['layout']['desktop'] = $instance['design']['button_align'];
		}

		return $instance;
	}

	public function get_less_variables( $instance ) {
		if ( empty( $instance ) || empty( $instance['design'] ) ) {
			return array();
		}

		$less_vars = array(
			'border_color' => ! empty( $instance['design']['colors']['border_color'] ) ? $instance['design']['colors']['border_color'] : '',
			'background_color' => ! empty( $instance['design']['colors']['background_color'] ) ? $instance['design']['colors']['background_color'] : '',
			'title_color' => ! empty( $instance['design']['colors']['title_color'] ) ? $instance['design']['colors']['title_color'] : '',
			'subtitle_color' => ! empty( $instance['design']['colors']['subtitle_color'] ) ? $instance['design']['colors']['subtitle_color'] : '',
			'button_align' => ! empty( $instance['design']['layout']['desktop'] ) ? $instance['design']['layout']['desktop'] : '',
			'mobile_button_align' => ! empty( $instance['design']['layout']['mobile'] ) ? $instance['design']['layout']['mobile'] : '',
		);

		$global_settings = $this->get_global_settings();

		if ( ! empty( $global_settings['responsive_breakpoint'] ) ) {
			$less_vars['responsive_breakpoint'] = ! empty( $global_settings['responsive_breakpoint'] ) ? $global_settings['responsive_breakpoint'] : '780px';
		}

		$fonts = empty( $instance['design']['fonts'] ) ? array() : $instance['design']['fonts'];

		if ( ! empty( $fonts['title_font_family'] ) ) {
			$font = siteorigin_widget_get_font( $fonts['title_font_family'] );
			$less_vars['title_font_family'] = $font['family'];

			if ( ! empty( $font['weight'] ) ) {
				$less_vars['title_font_weight'] = $font['weight'];
			}
		}

		if ( ! empty( $fonts['title_font_size'] ) ) {
			$less_vars['title_font_size'] = $fonts['title_font_size'];
		}

		if ( ! empty( $fonts['subtitle_font_family'] ) ) {
			$font = siteorigin_widget_get_font( $fonts['subtitle_font_family'] );
			$less_vars['subtitle_font_family'] = $font['family'];

			if ( ! empty( $font['weight'] ) ) {
				$less_vars['subtitle_font_weight'] = $font['weight'];
			}
		}

		if ( ! empty( $fonts['subtitle_font_size'] ) ) {
			$less_vars['subtitle_font_size'] = $fonts['subtitle_font_size'];
		}

		return $less_vars;
	}

	public function get_template_variables( $instance, $args ) {
		$template_vars = array(
			'title' => ! empty( $instance['title'] ) ? $instance['title'] : '',
			'sub_title' => ! empty( $instance['sub_title'] ) ? $instance['sub_title'] : '',
			'button' => $instance['button'],
			'title_tag' => ! empty( $instance['design']['fonts']['title_tag'] ) ? $instance['design']['fonts']['title_tag'] : 'h4',
			'sub_title_tag' => ! empty( $instance['design']['fonts']['title_tag'] ) ? $instance['design']['fonts']['sub_title_tag'] : 'h5',
		);

		return $template_vars;
	}

	public function add_google_font_fields( $fields, $instance, $widget ) {
		if ( ! empty( $instance['design']['fonts']['title_font_family'] ) ) {
			$fields[] = $instance['design']['fonts']['title_font_family'];
		}

		if ( ! empty( $instance['design']['fonts']['subtitle_font_family'] ) ) {
			$fields[] = $instance['design']['fonts']['subtitle_font_family'];
		}

		return $fields;
	}

	public function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return sprintf(
			__( 'Get more font customization options with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
			'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/cta" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);
	}
}

siteorigin_widget_register( 'sow-cta', __FILE__, 'SiteOrigin_Widget_Cta_Widget' );

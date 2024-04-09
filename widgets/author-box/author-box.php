<?php
/*
Widget Name: Author Box
Description: Display author information, including avatar, name, bio, and post links in a customizable box.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/author-box-widget/
*/

class SiteOrigin_Widget_Author_Box_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'sow-author-box',
			__( 'SiteOrigin Author Box', 'so-widgets-bundle' ),
			array(
				'description' => __( 'Display author information, including avatar, name, bio, and post links in a customizable box.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/author-box-widget/',
				'has_preview' => false,
				'panels_title' => false,
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	function get_settings_form() {
		return array(
			'responsive_breakpoint' => array(
				'type'        => 'measurement',
				'label'       => __( 'Responsive Breakpoint', 'so-widgets-bundle' ),
				'default'     => '780px',
				'description' => __( 'Device width, in pixels, to collapse into a mobile view.', 'so-widgets-bundle' )
			)
		);
	}

	public function get_widget_form() {
		return array(
			'settings' => array(
				'type' => 'section',
				'label' => __( 'Settings', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'avatar' => array(
						'type' => 'checkbox',
						'default' => true,
						'label' => __( 'Author Avatar', 'so-widgets-bundle' ),
						'state_emitter' => array(
							'callback' => 'conditional',
							'args' => array(
								'avatar[show]: val',
								'avatar[hide]: ! val',
							),
						),
					),
					'link_avatar' => array(
						'type' => 'checkbox',
						'default' => true,
						'label' => __( 'Link Author Avatar', 'so-widgets-bundle' ),
					),
					'link_name' => array(
						'type' => 'checkbox',
						'default' => true,
						'label' => __( 'Link Author Name', 'so-widgets-bundle' ),
					),
					'link_all_posts' => array(
						'type' => 'checkbox',
						'default' => true,
						'label' => __( 'Add All Posts by Author Link', 'so-widgets-bundle' ),
					),
					'author_bio' => array(
						'type' => 'checkbox',
						'default' => true,
						'label' => __( 'Author Bio', 'so-widgets-bundle' ),
					),
				),
			),
			'design' => array(
				'type' => 'section',
				'label' => __( 'Design', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'container' => array(
						'type' => 'section',
						'label' => __( 'Container', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'background_color' => array(
								'type' => 'color',
								'label' => __( 'Background', 'so-widgets-bundle' ),
								'alpha' => true,
							),
							'border_color' => array(
								'type' => 'color',
								'label' => __( 'Border Color', 'so-widgets-bundle' ),
								'default' => '#D2D2D2',
							),
							'border_radius' => array(
								'type' => 'slider',
								'label' => __( 'Border Radius', 'so-widgets-bundle' ),
								'max' => 50,
								'min' => 0,
								'step' => 1,
							),
							'border_thickness' => array(
								'type' => 'multi-measurement',
								'label' => __( 'Border Thickness', 'so-widgets-bundle' ),
								'default' => '1px 0 0 0',
								'measurements' => array(
									'top' => __( 'Top', 'so-widgets-bundle' ),
									'right' => __( 'Right', 'so-widgets-bundle' ),
									'bottom' => __( 'Bottom', 'so-widgets-bundle' ),
									'left' => __( 'Left', 'so-widgets-bundle' ),
								),
							),
							'padding' => array(
								'type' => 'multi-measurement',
								'label' => __( 'Padding', 'so-widgets-bundle' ),
								'default' => '40px 0 0 0',
								'measurements' => array(
									'top' => __( 'Top', 'so-widgets-bundle' ),
									'right' => __( 'Right', 'so-widgets-bundle' ),
									'bottom' => __( 'Bottom', 'so-widgets-bundle' ),
									'left' => __( 'Left', 'so-widgets-bundle' ),
								),
							),
						),
					),
					'avatar' => array(
						'type' => 'section',
						'label' => __( 'Author Avatar', 'so-widgets-bundle' ),
						'hide' => true,
						'state_handler' => array(
							'avatar[show]' => array( 'show' ),
							'avatar[hide]' => array( 'hide' ),
						),
						'fields' => array(
							'border_radius' => array(
								'type' => 'slider',
								'label' => __( 'Border Radius', 'so-widgets-bundle' ),
								'max' => 50,
								'min' => 0,
								'step' => 1,
								'default' => 50,
							),
							'size' => array(
								'type' => 'number',
								'label' => __( 'Image Size', 'so-widgets-bundle' ),
								'default' => 100,
								'unit' => 'px',
								'width' => 70,
							),
							'position' => array(
								'type' => 'radio',
								'label' => __( 'Position', 'so-widgets-bundle' ),
								'default' => 'left',
								'options' => array(
									'left' => __( 'Left', 'so-widgets-bundle' ),
									'right' => __( 'Right', 'so-widgets-bundle' ),
								),
							),
						),
					),
					'name' => array(
						'type' => 'section',
						'label' => __( 'Author Name', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'font' => array(
								'type' => 'font',
								'label' => __( 'Font', 'so-widgets-bundle' ),
							),
							'font_size' => array(
								'type' => 'measurement',
								'label' => __( 'Font Size', 'so-widgets-bundle' ),
								'default' => '15px',
							),
							'color' => array(
								'type' => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
								'default' => '#2d2d2d',
							),
							'color_hover' => array(
								'type' => 'color',
								'label' => __( 'Hover Color', 'so-widgets-bundle' ),
								'default' => '#626262',
							),
							'margin_bottom' => array(
								'type' => 'measurement',
								'label' => __( 'Bottom Margin', 'so-widgets-bundle' ),
								'default' => '5px',
							),
						),
					),
					'all' => array(
						'type' => 'section',
						'label' => __( 'Author All Posts Link', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'font' => array(
								'type' => 'font',
								'label' => __( 'Font', 'so-widgets-bundle' ),
							),
							'size' => array(
								'type' => 'measurement',
								'label' => __( 'Font Size', 'so-widgets-bundle' ),
								'default' => '13px',
							),
							'color' => array(
								'type' => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
								'default' => '#626262',
							),
							'color_hover' => array(
								'type' => 'color',
								'label' => __( 'Hover Color', 'so-widgets-bundle' ),
								'default' => '#2d2d2d',
							),
							'spacing' => array(
								'type' => 'multi-measurement',
								'label' => __( 'Margin', 'so-widgets-bundle' ),
								'default' => '0 10px',
								'measurements' => array(
									'top' => __( 'Top', 'so-widgets-bundle' ),
									'bottom' => __( 'Bottom', 'so-widgets-bundle' ),
								),
							),
						),
					),
					'bio' => array(
						'type' => 'section',
						'label' => __( 'Author Bio', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'font' => array(
								'type' => 'font',
								'label' => __( 'Font', 'so-widgets-bundle' ),
							),
							'font_size' => array(
								'type' => 'measurement',
								'label' => __( 'Font Size', 'so-widgets-bundle' ),
								'default' => '14px',
							),
							'color' => array(
								'type' => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
								'default' => '#626262',
							),
							'link' => array(
								'type' => 'color',
								'label' => __( 'Link Color', 'so-widgets-bundle' ),
								'default' => '#2d2d2d',
							),
							'link_hover' => array(
								'type' => 'color',
								'label' => __( 'Link Hover Color', 'so-widgets-bundle' ),
								'default' => '#626262',
							),
						),
					),
				),
			),
		);
	}

	public function get_template_variables( $instance, $args ) {
		return array(
			'responsive_breakpoint' => $this->get_global_settings( 'responsive_breakpoint' ),
			'show_avatar' => ! empty( $instance['settings']['avatar'] ),
			'link_avatar' => ! empty( $instance['settings']['link_avatar'] ),
			'link_name' => ! empty( $instance['settings']['link_name'] ),
			'author_bio' => ! empty( $instance['settings']['author_bio'] ),
			'link_all_posts' => ! empty( $instance['settings']['link_all_posts'] ),
			'avatar_image_size' => ! empty( $instance['design']['avatar']['size'] ) ? (int) $instance['design']['avatar']['size'] . 'px' : '100px',
		);
	}

	public function get_less_variables( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		$less_vars = array();

		// Container.
		$less_vars['container_background'] = ! empty( $instance['design']['container']['background_color'] ) ? $instance['design']['container']['background_color'] : '';
		$less_vars['container_border_radius'] = ! empty( $instance['design']['container']['border_radius'] ) ? $instance['design']['container']['border_radius'] . 'px' : '';
		$less_vars['container_border_color'] = ! empty( $instance['design']['container']['border_color'] ) ? $instance['design']['container']['border_color'] : '';
		$less_vars['container_border_thickness'] = ! empty( $instance['design']['container']['border_thickness'] ) ? $instance['design']['container']['border_thickness'] : '1px 0 0 0';
		$less_vars['container_padding'] = ! empty( $instance['design']['container']['padding'] ) ? $instance['design']['container']['padding'] : '40px 0 0 0';

		// Author Name.
		if ( ! empty( $instance['design']['name']['font'] ) ) {
			$font = siteorigin_widget_get_font( $instance['design']['name']['font'] );
			$less_vars['name_font'] = $font['family'];

			if ( ! empty( $font['weight'] ) ) {
				$less_vars['name_font_style'] = $font['style'];
				$less_vars['name_font_weight'] = $font['weight_raw'];
			}
		}
		$less_vars['name_font_size'] = ! empty( $instance['design']['name']['font_size'] ) ? $instance['design']['name']['font_size'] : '';
		$less_vars['name_color'] = ! empty( $instance['design']['name']['color'] ) ? $instance['design']['name']['color'] : '';
		$less_vars['name_color_hover'] = ! empty( $instance['design']['name']['color_hover'] ) ? $instance['design']['name']['color_hover'] : '';
		$less_vars['name_margin_bottom'] = ! empty( $instance['design']['name']['margin_bottom'] ) ? $instance['design']['name']['margin_bottom'] : '';
		$less_vars['name_link'] = ! empty( $instance['settings']['link_name'] );

		// Author Avatar.
		$less_vars['avatar_border_radius'] = ! empty( $instance['design']['avatar']['border_radius'] ) ? $instance['design']['avatar']['border_radius'] . 'px' : '';
		$less_vars['avatar_border_position'] = ! empty( $instance['design']['avatar']['position'] ) ? $instance['design']['avatar']['position'] : '';

		// Bio.
		if ( ! empty( $instance['design']['bio']['font'] ) ) {
			$font = siteorigin_widget_get_font( $instance['design']['bio']['font'] );
			$less_vars['bio_font'] = $font['family'];

			if ( ! empty( $font['weight'] ) ) {
				$less_vars['bio_font_style'] = $font['style'];
				$less_vars['bio_font_weight'] = $font['weight_raw'];
			}
		}
		$less_vars['bio_font_size'] = ! empty( $instance['design']['bio']['font_size'] ) ? $instance['design']['bio']['font_size'] : '';
		$less_vars['bio_color'] = ! empty( $instance['design']['bio']['color'] ) ? $instance['design']['bio']['color'] : '';
		$less_vars['bio_link'] = ! empty( $instance['design']['bio']['link'] ) ? $instance['design']['bio']['link'] : '';
		$less_vars['bio_link_hover'] = ! empty( $instance['design']['bio']['link_hover'] ) ? $instance['design']['bio']['link_hover'] : '';

		// All link.
		if ( ! empty( $instance['design']['all']['font'] ) ) {
			$font = siteorigin_widget_get_font( $instance['design']['all']['font'] );
			$less_vars['all_font'] = $font['family'];

			if ( ! empty( $font['weight'] ) ) {
				$less_vars['all_font_style'] = $font['style'];
				$less_vars['all_font_weight'] = $font['weight_raw'];
			}
		}
		$less_vars['all_font_size'] = ! empty( $instance['design']['all']['size'] ) ? $instance['design']['all']['size'] : '';
		$less_vars['all_color'] = ! empty( $instance['design']['all']['color'] ) ? $instance['design']['all']['color'] : '';
		$less_vars['all_color_hover'] = ! empty( $instance['design']['all']['color_hover'] ) ? $instance['design']['all']['color_hover'] : '';

		if ( ! empty( $instance['design']['all']['spacing'] ) ) {
			$spacing = explode( ' ', $instance['design']['all']['spacing'] );
			$less_vars['all_spacing'] = implode( ' 0 ', $spacing );
		} else {
			$less_vars['all_spacing'] = '';
		}

		return $less_vars;
	}

	public function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return array(
			sprintf(
				__( '%sSiteOrigin Premium%s adds depth to the Author Box Widget with placement options, author bios, recent post visibility, social buttons, and design customizations. Enjoy centralized global control of author boxes', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/author-box" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
		);
	}
}

siteorigin_widget_register( 'sow-author-box', __FILE__, 'SiteOrigin_Widget_Author_Box_Widget' );

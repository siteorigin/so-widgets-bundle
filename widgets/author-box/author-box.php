<?php
/*
Widget Name: Author Box
Description: Placeholder.
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
				'description' => __( 'Placeholder', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/author-box-widget/',
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	public function get_widget_form() {
		return array(
			'avatar' => array(
				'type' => 'checkbox',
				'default' => true,
				'label' => __( 'Author Avatar', 'so-widgets-bundle' ),
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
				'label' => __( 'Show Author Bio', 'so-widgets-bundle' ),
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
								'default' => '#000',
							),
							'background_opacity' => array(
								'type' => 'slider',
								'label' => __( 'Background Opacity', 'so-widgets-bundle' ),
								'min' => 0,
								'max' => 1,
								'step' => 0.01,
								'default' => 0.05,
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
								'default' => '18px',
							),
							'color' => array(
								'type' => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
							),
							'color_hover' => array(
								'type' => 'color',
								'label' => __( 'Hover Color', 'so-widgets-bundle' ),
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
							),
							'color' => array(
								'type' => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
							),
							'link' => array(
								'type' => 'color',
								'label' => __( 'Link Color', 'so-widgets-bundle' ),
							),
							'link_hover' => array(
								'type' => 'color',
								'label' => __( 'Link Hover Color', 'so-widgets-bundle' ),
							),
						),
					),
				),
			),
		);
	}

	public function get_template_variables( $instance, $args ) {
		return array(
			'show_avatar' => ! empty( $instance['avatar'] ),
			'link_avatar' => ! empty( $instance['link_avatar'] ),
			'link_name' => ! empty( $instance['link_name'] ),
			'author_bio' => ! empty( $instance['author_bio'] ),
			'link_all_posts' => ! empty( $instance['link_all_posts'] ),
		);
	}

	public function get_less_variables( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		$less_vars = array();

		$color = ! empty( $instance['design']['container']['background_color'] ) ? $instance['design']['container']['background_color'] : '#000';
		$rgb = ltrim( $color, '#' );
		$rgb = array_map( 'hexdec', str_split( $rgb, strlen( $rgb ) == 6 ? 2 : 1 ) );
		$opacity = ! empty( $instance['design']['container']['background_opacity'] ) ? $instance['design']['container']['background_opacity'] : 0.05;
		$less_vars['box_background'] = "rgba( $rgb[0], $rgb[1], $rgb[2], $opacity )";

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

		return $less_vars;
	}

	public function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return array(
			sprintf(
				__( 'Placeholder teaser for %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/addon-link" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
		);
	}
}

siteorigin_widget_register( 'sow-author-box', __FILE__, 'SiteOrigin_Widget_Author_Box_Widget' );

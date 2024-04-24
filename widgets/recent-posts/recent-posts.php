<?php
/*
Widget Name: Recent Posts
Description: Customize and display your site’s recent posts with adjustable HTML tags, title links, date view, and design elements, ensuring a responsive and engaging user experience.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/recent-posts-widget/
*/

class SiteOrigin_Widget_Recent_Posts_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'sow-recent-posts',
			__( 'SiteOrigin Recent Posts', 'so-widgets-bundle' ),
			array(
				'description' => __( 'Placeholder.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/recent-posts-widget/',
				'panels_title' => false,
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
		add_filter( 'siteorigin_widgets_block_exclude_widget', array( $this, 'exclude_from_widgets_block_cache' ), 10, 2 );
	}

	public function exclude_from_widgets_block_cache( $exclude, $widget_class ) {
		if ( $widget_class == 'SiteOrigin_Widget_Recent_Posts_Widget' ) {
			$exclude = true;
		}

		return $exclude;
	}

	public function get_settings_form() {
		return array(
			'responsive_breakpoint' => array(
				'type' => 'measurement',
				'label' => __( 'Responsive Breakpoint', 'so-widgets-bundle' ),
				'default' => '780px',
				'description' => __( 'Device width, in pixels, to collapse into a mobile view.', 'so-widgets-bundle' ),
			),
		);
	}

	function get_widget_form() {
		return array(
			'link_title' => array(
				'type' => 'checkbox',
				'label' => __( 'Link Post Title', 'so-widgets-bundle' ),
				'default' => true,
				'state_emitter' => array(
					'callback' => 'conditional',
					'args' => array(
						'link_title[true]: val',
						'link_title[false]: ! val',
					),
				),
			),
			'new_window' => array(
				'type' => 'checkbox',
				'label' => __( 'Open In New Window', 'so-widgets-bundle' ),
				'state_handler' => array(
					'link_title[true]' => array( 'show' ),
					'link_title[false]' => array( 'hide' ),
				),
			),
			'date' => array(
				'type' => 'checkbox',
				'label' => __( 'Display Post Date', 'so-widgets-bundle' ),
				'default' => true,
				'state_emitter' => array(
					'callback' => 'conditional',
					'args' => array(
						'post_date[true]: val',
						'post_date[false]: ! val',
					),
				),
			),
			'design' => array(
				'type' => 'section',
				'label' => __( 'Design', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'title' => array(
						'type' => 'section',
						'label' => __( 'Post Title', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'font' => array(
								'type' => 'font',
								'label' => __( 'Font', 'so-widgets-bundle' ),
							),
							'font_size' => array(
								'type' => 'measurement',
								'label' => __( 'Font Size', 'so-widgets-bundle' ),
								'default' => '20px',
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
								'state_handler' => array(
									'link_title[true]' => array( 'show' ),
									'link_title[false]' => array( 'hide' ),
								),
							),
						),
					),
					'date' => array(
						'type' => 'section',
						'label' => __( 'Post Date', 'so-widgets-bundle' ),
						'hide' => true,
						'state_handler' => array(
							'link_title[true]' => array( 'show' ),
							'link_title[false]' => array( 'hide' ),
						),
						'fields' => array(
							'font' => array(
								'type' => 'font',
								'label' => __( 'Font', 'so-widgets-bundle' ),
							),
							'font_size' => array(
								'type' => 'measurement',
								'label' => __( 'Font Size', 'so-widgets-bundle' ),
								'default' => '0.87rem',
							),
							'color' => array(
								'type' => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
								'default' => '#929292',
							),
						),
					),
					'list_style' => array(
						'type' => 'section',
						'label' => __( 'List Style', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'type' => array(
								'type' => 'select',
								'label' => __( 'Type', 'so-widgets-bundle' ),
								'default' => 'disc',
								'state_emitter' => array(
									'callback' => 'select',
									'args' => array( 'list_type' ),
								),
								'options' => array(
									'none' => __( 'None', 'so-widgets-bundle' ),
									'disc' => __( 'disc', 'so-widgets-bundle' ),
									'circle' => __( 'circle', 'so-widgets-bundle' ),
									'square' => __( 'square', 'so-widgets-bundle' ),
									'decimal' => __( 'decimal', 'so-widgets-bundle' ),
									'decimal-leading-zero' => __( 'decimal-leading-zero', 'so-widgets-bundle' ),
									'cjk-decimal' => __( 'cjk-decimal', 'so-widgets-bundle' ),
									'lower-roman' => __( 'lower-roman', 'so-widgets-bundle' ),
									'upper-roman' => __( 'upper-roman', 'so-widgets-bundle' ),
									'lower-greek' => __( 'lower-greek', 'so-widgets-bundle' ),
									'lower-alpha, lower-latin' => __( 'lower-alpha, lower-latin', 'so-widgets-bundle' ),
									'upper-alpha, upper-latin' => __( 'upper-alpha, upper-latin', 'so-widgets-bundle' ),
									'arabic-indic' => __( 'arabic-indic', 'so-widgets-bundle' ),
									'armenian' => __( 'armenian', 'so-widgets-bundle' ),
									'bengali' => __( 'bengali', 'so-widgets-bundle' ),
									'cambodian' => __( 'cambodian', 'so-widgets-bundle' ),
									'cjk-earthly-branch' => __( 'cjk-earthly-branch', 'so-widgets-bundle' ),
									'cjk-heavenly-stem' => __( 'cjk-heavenly-stem', 'so-widgets-bundle' ),
									'cjk-ideographic' => __( 'cjk-ideographic', 'so-widgets-bundle' ),
									'devanagari' => __( 'devanagari', 'so-widgets-bundle' ),
									'ethiopic-numeric' => __( 'ethiopic-numeric', 'so-widgets-bundle' ),
									'georgian' => __( 'georgian', 'so-widgets-bundle' ),
									'gujarati' => __( 'gujarati', 'so-widgets-bundle' ),
									'gurmukhi' => __( 'gurmukhi', 'so-widgets-bundle' ),
									'hebrew' => __( 'hebrew', 'so-widgets-bundle' ),
									'hiragana' => __( 'hiragana', 'so-widgets-bundle' ),
									'hiragana-iroha' => __( 'hiragana-iroha', 'so-widgets-bundle' ),
									'japanese-formal' => __( 'japanese-formal', 'so-widgets-bundle' ),
									'japanese-informal' => __( 'japanese-informal', 'so-widgets-bundle' ),
									'kannada' => __( 'kannada', 'so-widgets-bundle' ),
									'katakana' => __( 'katakana', 'so-widgets-bundle' ),
									'katakana-iroha' => __( 'katakana-iroha', 'so-widgets-bundle' ),
									'khmer' => __( 'khmer', 'so-widgets-bundle' ),
									'korean-hangul-formal' => __( 'korean-hangul-formal', 'so-widgets-bundle' ),
									'korean-hanja-formal' => __( 'korean-hanja-formal', 'so-widgets-bundle' ),
									'korean-hanja-informal' => __( 'korean-hanja-informal', 'so-widgets-bundle' ),
									'lao' => __( 'lao', 'so-widgets-bundle' ),
									'lower-armenian' => __( 'lower-armenian', 'so-widgets-bundle' ),
									'malayalam' => __( 'malayalam', 'so-widgets-bundle' ),
									'mongolian' => __( 'mongolian', 'so-widgets-bundle' ),
									'myanmar' => __( 'myanmar', 'so-widgets-bundle' ),
									'oriya' => __( 'oriya', 'so-widgets-bundle' ),
									'persian' => __( 'persian', 'so-widgets-bundle' ),
									'simp-chinese-formal' => __( 'simp-chinese-formal', 'so-widgets-bundle' ),
									'simp-chinese-informal' => __( 'simp-chinese-informal', 'so-widgets-bundle' ),
									'tamil' => __( 'tamil', 'so-widgets-bundle' ),
									'telugu' => __( 'telugu', 'so-widgets-bundle' ),
									'thai' => __( 'thai', 'so-widgets-bundle' ),
									'tibetan' => __( 'tibetan', 'so-widgets-bundle' ),
									'trad-chinese-formal' => __( 'trad-chinese-formal', 'so-widgets-bundle' ),
									'trad-chinese-informal' => __( 'trad-chinese-informal', 'so-widgets-bundle' ),
									'upper-armenian' => __( 'upper-armenian', 'so-widgets-bundle' ),
									'disclosure-open' => __( 'disclosure-open', 'so-widgets-bundle' ),
									'disclosure-closed' => __( 'disclosure-closed', 'so-widgets-bundle' ),
									'-moz-ethiopic-halehame' => __( '-moz-ethiopic-halehame', 'so-widgets-bundle' ),
									'-moz-ethiopic-halehame-am' => __( '-moz-ethiopic-halehame-am', 'so-widgets-bundle' ),
									'ethiopic-halehame-ti-er' => __( 'ethiopic-halehame-ti-er', 'so-widgets-bundle' ),
									'ethiopic-halehame-ti-et' => __( 'ethiopic-halehame-ti-et', 'so-widgets-bundle' ),
									'hangul' => __( 'hangul', 'so-widgets-bundle' ),
									'hangul-consonant' => __( 'hangul-consonant', 'so-widgets-bundle' ),
									'urdu' => __( 'urdu', 'so-widgets-bundle' ),
									'-moz-ethiopic-halehame-ti-er' => __( '-moz-ethiopic-halehame-ti-er', 'so-widgets-bundle' ),
									'-moz-ethiopic-halehame-ti-et' => __( '-moz-ethiopic-halehame-ti-et', 'so-widgets-bundle' ),
									'-moz-hangul' => __( '-moz-hangul', 'so-widgets-bundle' ),
									'-moz-hangul-consonant' => __( '-moz-hangul-consonant', 'so-widgets-bundle' ),
									'-moz-urdu' => __( '-moz-urdu', 'so-widgets-bundle' ),
								),
							),
							'color' => array(
								'type' => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
								'default' => '#929292',
								'state_handler' => array(
									'list_type[none]' => array( 'hide' ),
									'_else[list_type]' => array( 'show' ),
								),
							),
							'position' => array(
								'type' => 'radio',
								'label' => __( 'Position', 'so-widgets-bundle' ),
								'default' => 'outside',
								'state_handler' => array(
									'list_type[none]' => array( 'hide' ),
									'_else[list_type]' => array( 'show' ),
								),
								'options' => array(
									'outside' => __( 'Outside', 'so-widgets-bundle' ),
									'inside' => __( 'Inside', 'so-widgets-bundle' ),
								),
							),
						),
					),
				),
			),
			'query' => array(
				'type' => 'posts',
				'label' => __( 'Posts Query', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'post__in' => array(
						'remove' => true,
					),
					'posts_per_page' => array(
						'default' => 5,
					),
				),
			),
		);
	}

	public function get_less_variables( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		$less_vars = array(
			'responsive_breakpoint' => $this->get_global_settings( 'responsive_breakpoint' ),
			'date' => ! empty( $instance['date'] ) ? $instance['date'] : '',
			'list_style_type' => ! empty( $instance['design']['list_style']['type'] ) ? $instance['design']['list_style']['type'] : 'disc',
			'list_style_color' => ! empty( $instance['design']['list_style']['color'] ) ? $instance['design']['list_style']['color'] : '',
			'list_style_position' => ! empty( $instance['design']['list_style']['position'] ) ? $instance['design']['list_style']['position'] : 'outside',
			'title_font_size' => ! empty( $instance['design']['title']['font_size'] ) ? $instance['design']['title']['font_size'] : '',
			'title_color' => ! empty( $instance['design']['title']['color'] ) ? $instance['design']['title']['color'] : '',
			'title_color_hover' => ! empty( $instance['design']['title']['color_hover'] ) ? $instance['design']['title']['color_hover'] : '',
			'date_font_size' => ! empty( $instance['design']['date']['font_size'] ) ? $instance['design']['date']['font_size'] : '',
			'date_color' => ! empty( $instance['design']['date']['color'] ) ? $instance['design']['date']['color'] : '',
		);

		if ( ! empty( $instance['design']['title']['font'] ) ) {
			$font = siteorigin_widget_get_font( $instance['design']['title']['font'] );
			$less_vars['title_font'] = $font['family'];
			if ( ! empty( $font['weight'] ) ) {
				$less_vars['title_font_style'] = $font['style'];
				$less_vars['title_font_weight'] = $font['weight_raw'];
			}
		}

		if ( ! empty( $instance['design']['date']['font'] ) ) {
			$font = siteorigin_widget_get_font( $instance['design']['date']['font'] );
			$less_vars['date_font'] = $font['family'];
			if ( ! empty( $font['weight'] ) ) {
				$less_vars['date_font_style'] = $font['style'];
				$less_vars['date_font_weight'] = $font['weight_raw'];
			}
		}

		return $less_vars;
	}
}
siteorigin_widget_register( 'sow-recent-posts', __FILE__, 'SiteOrigin_Widget_Recent_Posts_Widget' );

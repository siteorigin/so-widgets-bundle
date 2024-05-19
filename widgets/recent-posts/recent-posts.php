<?php
/*
Widget Name: Recent Posts
Description: Drive traffic to your latest content with a visually appealing, fully customizable recent posts showcase.
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
				'description' => __( 'Drive traffic to your latest content with a visually appealing, fully customizable recent posts showcase.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/recent-posts-widget/',
				'panels_title' => false,
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);

	}
	public function initialize() {
		add_filter( 'siteorigin_widgets_block_exclude_widget', array( $this, 'exclude_from_widgets_block_cache' ), 10, 2 );
		add_action( 'wp_loaded', array( $this, 'register_image_size' ) );
	}

	public function exclude_from_widgets_block_cache( $exclude, $widget_class ) {
		if ( $widget_class == 'SiteOrigin_Widget_Recent_Posts_Widget' ) {
			$exclude = true;
		}

		return $exclude;
	}

	public function register_image_size() {
		add_image_size( 'sow-recent-post', 216, 216, array( 'center', 'center' ) );
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
			'title' => array(
				'type' => 'text',
				'label' => __( 'Title', 'so-widgets-bundle' ),
			),
			'recent_settings' => array(
				'type' => 'section',
				'label' => __( 'Settings', 'so-widgets-bundle' ),
				'fields' => array(
					'featured_image' => array(
						'type' => 'checkbox',
						'label' => __( 'Featured Image', 'so-widgets-bundle' ),
						'default' => true,
						'state_emitter' => array(
							'callback' => 'conditional',
							'args' => array(
								'featured_image[show]: val',
								'featured_image[hide]: ! val',
							),
						),
					),
					'post_title' => array(
						'type' => 'checkbox',
						'label' => __( 'Post Title', 'so-widgets-bundle' ),
						'default' => true,
						'state_emitter' => array(
							'callback' => 'conditional',
							'args' => array(
								'post_title[true]: val',
								'post_title[false]: ! val',
							),
						),
					),
					'tag' => array(
						'type' => 'select',
						'label' => __( 'Post Title HTML Tag', 'so-widgets-bundle' ),
						'default' => 'h3',
						'state_handler' => array(
							'post_title[true]' => array( 'show' ),
							'post_title[false]' => array( 'hide' ),
						),
						'options' => array(
							'h1' => __( 'H1', 'so-widgets-bundle' ),
							'h2' => __( 'H2', 'so-widgets-bundle' ),
							'h3' => __( 'H3', 'so-widgets-bundle' ),
							'h4' => __( 'H4', 'so-widgets-bundle' ),
							'h5' => __( 'H5', 'so-widgets-bundle' ),
							'h6' => __( 'H6', 'so-widgets-bundle' ),
							'p' => __( 'Paragraph', 'so-widgets-bundle' ),
						)
					),
					'new_window' => array(
						'type' => 'checkbox',
						'label' => __( 'Open Post in New Tab', 'so-widgets-bundle' ),
					),
					'date' => array(
						'type' => 'checkbox',
						'label' => __( 'Post Date', 'so-widgets-bundle' ),
						'default' => true,
						'state_emitter' => array(
							'callback' => 'conditional',
							'args' => array(
								'post_date[true]: val',
								'post_date[false]: ! val',
							),
						),
					),
					'date_format' => array(
						'type' => 'select',
						'label' => __( 'Post Date Format', 'so-widgets-bundle' ),
						'default' => 'default',
						'state_handler' => array(
							'post_date[true]' => array( 'show' ),
							'post_date[false]' => array( 'hide' ),
						),
						'default' => sanitize_option( 'date_format', get_option( 'date_format' ) ),
						'options' => array(
							'' => sprintf(
								__( 'Default (%s)', 'so-widgets-bundle' ),
								date(
									sanitize_option(
										'date_format',
										get_option( 'date_format' )
									)
								)
							),
							'Y-m-d' => sprintf( __( 'yyyy-mm-dd (%s)', 'so-widgets-bundle' ), date( 'Y/m/d' ) ),
							'm/d/Y' => sprintf( __( 'mm/dd/yyyy (%s)', 'so-widgets-bundle' ), date( 'm/d/Y' ) ),
							'd/m/Y' => sprintf( __( 'dd/mm/yyyy (%s)', 'so-widgets-bundle' ), date( 'd/m/Y' ) ),
						),
					),
					'post_content' => array(
						'type' => 'select',
						'label' => __( 'Post Content', 'so-widgets-bundle' ),
						'state_emitter' => array(
							'callback' => 'select',
							'args' => array( 'post_content' ),
						),
						'options' => array(
							'' => __( 'None', 'so-widgets-bundle' ),
							'excerpt' => __( 'Excerpt', 'so-widgets-bundle' ),
						),
					),
					'excerpt_length' => array(
						'type' => 'number',
						'label' => __( 'Excerpt Length', 'so-widgets-bundle' ),
						'default' => 10,
						'state_handler' => array(
							'post_content[excerpt]' => array( 'show' ),
							'_else[post_content]' => array( 'hide' ),
						),
					),
					'excerpt_trim' => array(
						'type' => 'text',
						'label' => __( 'Post Excerpt Trim Indicator', 'so-widgets-bundle' ),
						'default' => '...',
						'state_handler' => array(
							'post_content[excerpt]' => array( 'show' ),
							'_else[post_content]' => array( 'hide' ),
						),
					),
					'read_more' => array(
						'type' => 'checkbox',
						'label' => __( 'Read More Link', 'so-widgets-bundle' ),
						'state_handler' => array(
							'post_content[excerpt]' => array( 'show' ),
							'_else[post_content]' => array( 'hide' ),
						),
						'state_emitter' => array(
							'callback' => 'conditional',
							'args' => array(
								'read_more[true]: val',
								'read_more[false]: ! val',
							),
						),
					),
					'read_more_text' => array(
						'type' => 'text',
						'label' => __( 'Read More Text', 'so-widgets-bundle' ),
						'default' => __( 'Continue reading', 'so-widgets-bundle' ),
						'state_handler' => array(
							'read_more[true]' => array( 'show' ),
							'read_more[false]' => array( 'hide' ),
						),
					),
				),
			),

			'recent_design' => array(
				'type' => 'section',
				'label' => __( 'Design', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'post' => array(
						'type' => 'section',
						'label' => __( 'Post', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'bottom_margin' => array(
								'type' => 'measurement',
								'label' => __( 'Bottom Margin', 'so-widgets-bundle' ),
								'default' => '18px',
							)
						),
					),
					'featured_image' => array(
						'type' => 'section',
						'label' => __( 'Featured Image', 'so-widgets-bundle' ),
						'hide' => true,
						'state_handler' => array(
							'featured_image[show]' => array( 'show' ),
							'featured_image[hide]' => array( 'hide' ),
						),
						'fields' => array(
							'placement' => array(
								'type' => 'select',
								'label' => __( 'Placement', 'so-widgets-bundle' ),
								'default' => 'left',
								'options' => array(
									'above' => __( 'Above', 'so-widgets-bundle' ),
									'right' => __( 'Right', 'so-widgets-bundle' ),
									'below' => __( 'Below', 'so-widgets-bundle' ),
									'left' => __( 'Left', 'so-widgets-bundle' ),
								),
							),
							'gutter' => array(
								'type' => 'measurement',
								'label' => __( 'Gutter', 'so-widgets-bundle' ),
								'default' => '12px',
							),
							'max_width' => array(
								'type' => 'measurement',
								'label' => __( 'Max Image Size', 'so-widgets-bundle' ),
								'default' => '72px',
							),
							'padding' => array(
								'type' => 'measurement',
								'label' => __( 'Padding', 'so-widgets-bundle' ),
								'default' => '0px',
							),
							'border_style' => array(
								'type' => 'select',
								'label' => __( 'Border Style', 'so-widgets-bundle' ),
								'default' => 'none',
								'state_emitter' => array(
									'callback' => 'select',
									'args' => array( 'border_style' ),
								),
								'options' => array(
									'none' => __( 'None', 'so-widgets-bundle' ),
									'solid' => __( 'Solid', 'so-widgets-bundle' ),
									'dotted' => __( 'Dotted', 'so-widgets-bundle' ),
									'dashed' => __( 'Dashed', 'so-widgets-bundle' ),
									'double' => __( 'Double', 'so-widgets-bundle' ),
									'groove' => __( 'Groove', 'so-widgets-bundle' ),
									'ridge' => __( 'Ridge', 'so-widgets-bundle' ),
									'inset' => __( 'Inset', 'so-widgets-bundle' ),
									'outset' => __( 'Outset', 'so-widgets-bundle' ),
								),
							),
							'border_color' => array(
								'type' => 'color',
								'label' => __( 'Border Color', 'so-widgets-bundle' ),
								'default' => '#e6e6e6',
								'state_handler' => array(
									'border_style[none]' => array( 'hide' ),
									'_else[border_style]' => array( 'show' ),
								),
							),
							'border_thickness' => array(
								'type' => 'measurement',
								'label' => __( 'Border Thickness', 'so-widgets-bundle' ),
								'default' => '1px',
								'state_handler' => array(
									'border_style[none]' => array( 'hide' ),
									'_else[border_style]' => array( 'show' ),
								),
							),
						),
					),
					'title' => array(
						'type' => 'section',
						'label' => __( 'Post Title', 'so-widgets-bundle' ),
						'hide' => true,
						'state_handler' => array(
							'post_title[true]' => array( 'show' ),
							'post_title[false]' => array( 'hide' ),
						),
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
								'default' => '#2d2d2d',
							),
							'color_hover' => array(
								'type' => 'color',
								'label' => __( 'Hover Color', 'so-widgets-bundle' ),
								'default' => '#626262',
							),
						),
					),
					'date' => array(
						'type' => 'section',
						'label' => __( 'Post Date', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'font' => array(
								'type' => 'font',
								'label' => __( 'Font', 'so-widgets-bundle' ),
							),
							'font_size' => array(
								'type' => 'measurement',
								'label' => __( 'Font Size', 'so-widgets-bundle' ),
								'default' => '12px',
							),
							'color' => array(
								'type' => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
								'default' => '#929292',
							),
						),
					),
					'excerpt' => array(
						'type' => 'section',
						'label' => __( 'Post Excerpt', 'so-widgets-bundle' ),
						'hide' => true,
						'state_handler' => array(
							'post_content[excerpt]' => array( 'show' ),
							'_else[post_content]' => array( 'hide' ),
						),
						'fields' => array(
							'font' => array(
								'type' => 'font',
								'label' => __( 'Font', 'so-widgets-bundle' ),
							),
							'font_size' => array(
								'type' => 'measurement',
								'label' => __( 'Font Size', 'so-widgets-bundle' ),
								'default' => '13px',
							),
							'color' => array(
								'type' => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
								'default' => '#626262',
							),
						),
					),
					'read_more' => array(
						'type' => 'section',
						'label' => __( 'Read More Link', 'so-widgets-bundle' ),
						'hide' => true,
						'state_handler' => array(
							'read_more[true]' => array( 'show' ),
							'read_more[false]' => array( 'hide' ),
						),
						'fields' => array(
							'font' => array(
								'type' => 'font',
								'label' => __( 'Font', 'so-widgets-bundle' ),
							),
							'font_size' => array(
								'type' => 'measurement',
								'label' => __( 'Font Size', 'so-widgets-bundle' ),
								'default' => '13px',
							),
							'color' => array(
								'type' => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
							),
							'color_hover' => array(
								'type' => 'color',
								'label' => __( 'Color Hover', 'so-widgets-bundle' ),
							),
							'top_margin' => array(
								'type' => 'measurement',
								'label' => __( 'Top Margin', 'so-widgets-bundle' ),
							),
						),
					),
					'list_style' => array(
						'type' => 'section',
						'label' => __( 'List Style', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'icon' => array(
								'type' => 'select',
								'label' => __( 'Icon', 'so-widgets-bundle' ),
								'default' => 'none',
								'state_emitter' => array(
									'callback' => 'select',
									'args' => array( 'list_icon' ),
								),
								'options' => array(
									'none' => __( 'None', 'so-widgets-bundle' ),
									'image' => __( 'Image', 'so-widgets-bundle' ),
									'disc' => __( 'Disc', 'so-widgets-bundle' ),
									'circle' => __( 'Circle', 'so-widgets-bundle' ),
									'square' => __( 'Square', 'so-widgets-bundle' ),
								),
							),
							'image' => array(
								'type' => 'media',
								'label' => __( 'Image', 'so-widgets-bundle' ),
								'fallback' => true,
								'state_handler' => array(
									'list_icon[image]' => array( 'show' ),
									'_else[list_icon]' => array( 'hide' ),
								),
							),
							'indent' => array(
								'type' => 'measurement',
								'label' => __( 'Indent', 'so-widgets-bundle' ),
								'default' => '10px',
								'state_handler' => array(
									'list_icon[none]' => array( 'hide' ),
									'_else[list_icon]' => array( 'show' ),
								),
							),
							'max_size' => array(
								'type' => 'measurement',
								'label' => __( 'Max Icon Size', 'so-widgets-bundle' ),
								'default' => '25px',

								'state_handler' => array(
									'list_icon[image]' => array( 'show' ),
									'_else[list_icon]' => array( 'hide' ),
								),
							),
							'color' => array(
								'type' => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
								'default' => '#929292',
								'state_handler' => array(
									'list_icon[none,image]' => array( 'hide' ),
									'_else[list_icon]' => array( 'show' ),
								),
							),
						),
					),
				),
			),
			'recent_query' => array(
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
			'list_style_icon' => ! empty( $instance['recent_design']['list_style']['icon'] ) ? $instance['recent_design']['list_style']['icon'] : 'disc',
			'list_style_indent' => ! empty( $instance['recent_design']['list_style']['indent'] ) ? $instance['recent_design']['list_style']['indent'] : '5px',
			'list_style_color' => ! empty( $instance['recent_design']['list_style']['color'] ) ? $instance['recent_design']['list_style']['color'] : '',
			'list_style_image_max_size' => ! empty( $instance['recent_design']['list_style']['max_size'] ) ? $instance['recent_design']['list_style']['max_size'] : '24px',
			'title_font_size' => ! empty( $instance['recent_design']['title']['font_size'] ) ? $instance['recent_design']['title']['font_size'] : '',
			'title_color' => ! empty( $instance['recent_design']['title']['color'] ) ? $instance['recent_design']['title']['color'] : '',
			'title_color_hover' => ! empty( $instance['recent_design']['title']['color_hover'] ) ? $instance['recent_design']['title']['color_hover'] : '',
			'date_font_size' => ! empty( $instance['recent_design']['date']['font_size'] ) ? $instance['recent_design']['date']['font_size'] : '',
			'date_color' => ! empty( $instance['recent_design']['date']['color'] ) ? $instance['recent_design']['date']['color'] : '',
			'bottom_margin' => ! empty( $instance['recent_design']['post']['bottom_margin'] ) ? $instance['recent_design']['post']['bottom_margin'] : '0',
			'excerpt_font_size' => ! empty( $instance['recent_design']['excerpt']['font_size'] ) ? $instance['recent_design']['excerpt']['font_size'] : '',
			'excerpt_color' => ! empty( $instance['recent_design']['excerpt']['color'] ) ? $instance['recent_design']['excerpt']['color'] : '',
			'read_more_font_size' => ! empty( $instance['recent_design']['read_more']['font_size'] ) ? $instance['recent_design']['read_more']['font_size'] : '',
			'read_more_color' => ! empty( $instance['recent_design']['read_more']['color'] ) ? $instance['recent_design']['read_more']['color'] : '',
			'read_more_top_margin' => ! empty( $instance['recent_design']['read_more']['top_margin'] ) ? $instance['recent_design']['read_more']['top_margin'] : '',
			'read_more_color' => ! empty( $instance['recent_design']['read_more']['color'] ) ? $instance['recent_design']['read_more']['color'] : '',
			'read_more_color_hover' => ! empty( $instance['recent_design']['read_more']['color_hover'] ) ? $instance['recent_design']['read_more']['color_hover'] : '',
		);

		if ( ! empty( $instance['recent_design']['title']['font'] ) ) {
			$font = siteorigin_widget_get_font( $instance['recent_design']['title']['font'] );
			$less_vars['title_font'] = $font['family'];
			if ( ! empty( $font['weight'] ) ) {
				$less_vars['title_font_style'] = $font['style'];
				$less_vars['title_font_weight'] = $font['weight_raw'];
			}
		}

		if ( ! empty( $instance['recent_design']['date']['font'] ) ) {
			$font = siteorigin_widget_get_font( $instance['recent_design']['date']['font'] );
			$less_vars['date_font'] = $font['family'];
			if ( ! empty( $font['weight'] ) ) {
				$less_vars['date_font_style'] = $font['style'];
				$less_vars['date_font_weight'] = $font['weight_raw'];
			}
		}

		if ( ! empty( $instance['recent_design']['excerpt']['font'] ) ) {
			$font = siteorigin_widget_get_font( $instance['recent_design']['excerpt']['font'] );
			$less_vars['excerpt_font'] = $font['family'];
			if ( ! empty( $font['weight'] ) ) {
				$less_vars['excerpt_font_style'] = $font['style'];
				$less_vars['excerpt_font_weight'] = $font['weight_raw'];
			}
		}

		if ( ! empty( $instance['recent_design']['read_more']['font'] ) ) {
			$font = siteorigin_widget_get_font( $instance['recent_design']['read_more']['font'] );
			$less_vars['read_more_font'] = $font['family'];
			if ( ! empty( $font['weight'] ) ) {
				$less_vars['read_more_font_style'] = $font['style'];
				$less_vars['read_more_font_weight'] = $font['weight_raw'];
			}
		}

		if ( ! empty( $instance['recent_settings']['featured_image'] ) ) {
			$less_vars['featured_image'] = true;
			$less_vars['featured_image_gutter'] = ! empty( $instance['recent_design']['featured_image']['gutter'] ) ? $instance['recent_design']['featured_image']['gutter'] : '';
			$less_vars['featured_image_max_width'] = ! empty( $instance['recent_design']['featured_image']['max_width'] ) ? $instance['recent_design']['featured_image']['max_width'] : '';
			$less_vars['featured_image_padding'] = ! empty( $instance['recent_design']['featured_image']['padding'] ) ? $instance['recent_design']['featured_image']['padding'] : '';
			$less_vars['featured_image_placement'] = ! empty( $instance['recent_design']['featured_image']['placement'] ) ? $instance['recent_design']['featured_image']['placement'] : '';
			$less_vars['featured_image_border_style'] = ! empty( $instance['recent_design']['featured_image']['border_style'] ) ? $instance['recent_design']['featured_image']['border_style'] : '';
			$less_vars['featured_image_border_thickness'] = ! empty( $instance['recent_design']['featured_image']['border_thickness'] ) ? $instance['recent_design']['featured_image']['border_thickness'] : '1px';
			$less_vars['featured_image_border_color'] = ! empty( $instance['recent_design']['featured_image']['border_color'] ) ? $instance['recent_design']['featured_image']['border_color'] : '#e6e6e6';
		}

		if (
			$instance['recent_design']['list_style']['icon'] == 'image' &&
			(
				! empty( $instance['recent_design']['list_style']['image'] ) ||
				! empty( $instance['recent_design']['list_style']['image_fallback'] )
			)
		) {
			$icon_image_size = ! empty( (int) $instance['recent_design']['list_style']['max_size'] ) ? (int) $instance['recent_design']['list_style']['max_size'] * 3 : 75;
			$src = siteorigin_widgets_get_attachment_image_src(
				$instance['recent_design']['list_style']['image'],
				array( $icon_image_size, $icon_image_size ),
				! empty( $instance['recent_design']['list_style']['image_fallback'] ) ? $instance['recent_design']['list_style']['image_fallback'] : false
			);

			if ( ! empty( $src ) ) {
				$less_vars['list_style_image'] = 'url( "' . esc_url( $src[0] ) . '")';
			}
		}

		return $less_vars;
	}

	public function get_template_variables( $instance, $args ) {
		$processed_query = siteorigin_widget_post_selector_process_query( $instance['recent_query'] );
		return array(
			'query' => new WP_Query( $processed_query ),
			'settings' => ! empty( $instance ) ? $instance['recent_settings'] : array(),
		);
	}

	public static function featured_image( $settings ) {
		if ( empty( $settings['featured_image'] ) || ! has_post_thumbnail() ) {
			return;
		}

		ob_start();
		?>
		<a
			class="sow-recent-posts-thumbnail"
			href="<?php the_permalink(); ?>"
			<?php echo ! empty( $settings['new_window'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
		>
			<?php
			the_post_thumbnail( 'sow-recent-post' );
			?>
		</a>
		<?php
		echo apply_filters( 'siteorigin_widgets_recent_posts_featured_image_markup', ob_get_clean(), $settings );
	}

	public static function post_title( $settings ) {
		if ( empty( $settings['post_title'] ) ) {
			return;
		}

		$tag = siteorigin_widget_valid_tag(
			! empty( $settings['tag'] ) ? $settings['tag'] : 'h3',
			'h3'
		);
		?>
		<<?php echo esc_html( $tag ); ?> class="sow-recent-posts-title">
			<a
				href="<?php echo esc_url( get_the_permalink() ); ?>"
				<?php echo ! empty( $settings['new_window'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
			>
				<?php echo get_the_title(); ?>
			</a>
		</<?php echo esc_html( $tag ); ?>>
		<?php
	}

	public static function post_date( $settings ) {
		if ( empty( $settings['date'] ) ) {
			return;
		}

		$date_format = ! empty( $settings['date_format'] ) ? $settings['date_format'] : sanitize_option(
			'date_format',
			get_option( 'date_format' )
		);
		?>
		<span class="sow-recent-posts-date">
			<time
				class="published"
				datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"
				aria-label="<?php esc_attr_e( 'Published on:', 'so-widgets-bundle' ); ?>"
			>
			<?php echo esc_html( get_the_date( $date_format ) ); ?>
		</time>

		<time
				class="updated"
				datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"
				aria-label="<?php esc_attr_e( 'Last updated on:', 'so-widgets-bundle' ); ?>"
			>
				<?php echo esc_html( get_the_modified_date( $date_format ) ); ?>
			</time>
		</span>
		<?php
	}

	public static function content( $settings ) {
		if ( empty( $settings['post_content'] ) ) {
			return;
		}

		$excerpt = get_the_excerpt();
		if ( ! has_excerpt() ) {
			$length = ! empty( $settings['excerpt_length'] ) ? $settings['excerpt_length'] : 10;
			$excerpt_trim = empty( $settings['excerpt_trim'] ) ? '...' : $settings['excerpt_trim'];
			$excerpt = wp_trim_words(
				$excerpt,
				$length,
				$excerpt_trim
			);
		}

		if ( empty( $excerpt ) ) {
			return;
		}

		echo '<p class="sow-recent-posts-excerpt">' . wp_kses_post( $excerpt ) . '</p>';
	}

	public static function read_more( $settings ) {
		if ( empty( $settings['read_more'] ) ) {
			return;
		}
		?>
		<a
			class="sow-recent-posts-read-more"
			href="<?php esc_url( the_permalink() ); ?>"
			<?php echo ! empty( $settings['new_window'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
		>
			<?php echo esc_html( $settings['read_more_text'] ); ?>
		</a>
		<?php
	}
}
siteorigin_widget_register( 'sow-recent-posts', __FILE__, 'SiteOrigin_Widget_Recent_Posts_Widget' );

<?php
/*
Widget Name: Post Carousel
Description: Display blog posts or custom post types in a responsive, customizable carousel layout.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/post-carousel-widget/
Keywords: blog, query
*/

/**
 * Add the carousel image sizes.
 */
function sow_carousel_register_image_sizes() {
	add_image_size( 'sow-carousel-default', 272, 182, true );
}
add_action( 'init', 'sow_carousel_register_image_sizes' );

/**
 * Handles post limits for carousel widgets.
 *
 * Enforces limits from filters, query settings, and pagination constraints.
 * Calculates correct post slice based on current page and sets 'sow-total_posts'
 * for pagination UI.
 *
 * @param WP_Query $posts The query result object containing posts.
 * @param int $paged Current page number (0-indexed).
 *
 * @return WP_Query Modified query object with correct post limits applied.
 */
function sow_carousel_handle_post_limit( $posts, $paged = 0 ) {
	$post_limit = apply_filters( 'siteorigin_widgets_post_carousel_post_limit', false );

	// Account for the Post Query Max Posts setting.
	if ( ! empty( $posts->query['posts_limit'] ) ) {
		$post_limit = min(
			$posts->found_posts,
			$posts->query['posts_limit']
		);
	}

	// If there's no limit, or the limit is higher than the total number of
	// posts, return the posts without modification.
	if ( ! is_numeric( $post_limit ) || $posts->found_posts < $post_limit ) {
		set_query_var( 'sow-total_posts', $posts->found_posts );
		return $posts;
	}

	$posts_per_page = $posts->query['posts_per_page'];
	$current = $posts_per_page * ( $paged - 1 );

	if ( $current < 0 ) {
		$current = $posts_per_page;
	}

	set_query_var( 'sow-total_posts', $post_limit - 1 );

	if ( $current >= $post_limit ) {
		// Check if we've exceeded the expected pagination.
		if ( $paged !== 0 && $current > $post_limit ) {
			$posts->posts = array();
			$posts->post_count = 0;
		} else {
			// We haven't. Limit the number of posts to the remaining amount.
			$remaining = max( 1, $post_limit - ( $current - $posts_per_page ) );
			$posts->post_count = min( $posts_per_page, $remaining );

			// Make sure we're not trying to slice with zero length.
			if ( $posts->post_count > 0 ) {
				$posts->posts = array_slice(
					$posts->posts,
					0,
					$posts->post_count
				);
			}
		}
	}

	return $posts;
}

function sow_carousel_get_next_posts_page() {
	if (
		empty( $_REQUEST['_widgets_nonce'] ) ||
		! wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ||
		empty( $_GET['instance_hash'] )
	) {
		die();
	}

	$instance_hash = $_GET['instance_hash'];
	global $wp_widget_factory;
	/** @var SiteOrigin_Widget $widget */
	$widget = ! empty( $wp_widget_factory->widgets['SiteOrigin_Widget_PostCarousel_Widget'] ) ?
	$wp_widget_factory->widgets['SiteOrigin_Widget_PostCarousel_Widget'] : null;
	if ( empty( $widget ) ) {
		die();
	}

	// Try to get the widget instance.
	$instance = $widget->get_stored_instance( $instance_hash );
	if ( empty( $instance ) ) {
		// Couldn't detect instance. Try to get it from the filter.
		$widget_instance = apply_filters( 'siteorigin_widgets_post_carousel_ajax_widget_instance', $instance_hash );

		if ( empty( $widget_instance ) || ! is_array( $widget_instance ) ) {
			die();
		}

		$instance = $widget_instance['instance'];
		$widget = $widget_instance['widget'];
	}

	// Let's set up the template variables, and try to load posts.
	$instance['paged'] = (int) $_GET['paged'];
	$template_vars = $widget->get_template_variables( $instance, array() );
	// If there aren't any settings, we can't output a template.
	if ( empty( $template_vars['settings'] ) ) {
		die();
	}

	$settings = $template_vars['settings'];
	$settings['posts'] = sow_carousel_handle_post_limit(
		$template_vars['settings']['posts'],
		$instance['paged']
	);

	// Don't output anything if there are no posts to return.
	if ( empty( $settings['posts'] ) ) {
		exit();
	}

	ob_start();
	include apply_filters( 'siteorigin_post_carousel_ajax_item_template', 'tpl/item.php', $instance );
	wp_send_json(
		array(
			'html' => ob_get_clean(),
		)
	);
}
add_action( 'wp_ajax_sow_carousel_load', 'sow_carousel_get_next_posts_page' );
add_action( 'wp_ajax_nopriv_sow_carousel_load', 'sow_carousel_get_next_posts_page' );

if ( ! class_exists( 'SiteOrigin_Widget_Base_Carousel' ) ) {
	include_once plugin_dir_path( SOW_BUNDLE_BASE_FILE ) . '/base/inc/widgets/base-carousel.class.php';
}

class SiteOrigin_Widget_PostCarousel_Widget extends SiteOrigin_Widget_Base_Carousel {
	public function __construct() {
		parent::__construct(
			'sow-post-carousel',
			__( 'SiteOrigin Post Carousel', 'so-widgets-bundle' ),
			array(
				'description' => __( 'Display blog posts or custom post types in a responsive, customizable carousel layout.', 'so-widgets-bundle' ),
				'instance_storage' => true,
				'help' => 'https://siteorigin.com/widgets-bundle/post-carousel-widget/',
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	public function initialize() {
		// Let the carousel base class do its initialization.
		parent::initialize();

		$this->register_frontend_scripts(
			array(
				array(
					'sow-post-carousel',
					plugin_dir_url( __FILE__ ) . 'js/script' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery', 'slick' ),
					SOW_BUNDLE_VERSION,
					true,
				),
			)
		);

		// Is this a widget preview?
		if (
			! empty( $_POST ) &&
			! empty( $_POST['action'] ) &&
			$_POST['action'] == 'so_widgets_preview'
		) {
			$this->register_theme_assets();
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'register_theme_assets' ) );
			add_action( 'enqueue_block_assets', array( $this, 'register_theme_assets' ) );
		}
	}

	public function override_carousel_settings() {
		return apply_filters(
			'siteorigin_widgets_post_carousel_settings_form',
			array(
				'breakpoints' => apply_filters(
					'siteorigin_widgets_post_carousel_breakpoints',
					array(
						'tablet_landscape' => 1366,
						'tablet_portrait'  => 1025,
						'mobile'           => 480,
					)
				),
				'slides_to_scroll' => array(
					'desktop' => 1,
					'tablet_landscape' => 2,
					'tablet_portrait' => 2,
					'mobile' => 1,
				),
				'navigation' => array(
					'desktop' => true,
					'tablet_landscape' => true,
					'tablet_portrait' => true,
					'mobile' => false,
				),
				'slides_to_show' => array(),
				'navigation_dots_label' => '',
			)
		);
	}

	public function register_theme_assets() {
		wp_register_style( 'sow-post-carousel-base', plugin_dir_url( __FILE__ ) . 'css/base.css' );
		do_action( 'siteorigin_widgets_post_carousel_theme_assets' );

		if ( $this->is_block_editor_page() ) {
			wp_enqueue_style( 'sow-post-carousel-base' );
		}
	}

	public function get_style_name( $instance ) {
		$theme = self::get_theme( $instance );

		// Try to load carousel theme stylesheet.
		wp_enqueue_style( 'sow-post-carousel-' . $theme );

		add_action( 'wp_head', array( $this, 'prevent_potential_shifts' ), 10, 0 );

		return $theme;
	}

	public function get_widget_form() {
		$design_settings = $this->design_settings_form_fields(
			array(
				'navigation' => array(
					'type' => 'section',
					'label' => __( 'Navigation', 'so-widgets-bundle' ),
					'hide' => true,
					'fields' => array(
						'navigation_color' => array(
							'type' => 'color',
							'label' => __( 'Arrow Color', 'so-widgets-bundle' ),
							'default' => '#fff',
						),
						'navigation_color_hover' => array(
							'type' => 'color',
							'label' => __( 'Arrow Hover Color', 'so-widgets-bundle' ),
						),
						'navigation_background' => array(
							'type' => 'color',
							'label' => __( 'Background', 'so-widgets-bundle' ),
							'default' => '#333',
						),
						'navigation_hover_background' => array(
							'type' => 'color',
							'label' => __( 'Hover Background', 'so-widgets-bundle' ),
							'default' => '#444',
						),
					),
				),
			)
		);

		// Override defaults.
		$design_settings['fields']['item_title']['label'] = __( 'Post Title', 'so-widgets-bundle' );
		$design_settings['fields']['item_title']['fields']['tag']['default'] = 'h3';

		// Reposition thumbnail settings.
		$design_settings['fields'] = array_merge(
			array(
				'thumbnail' => array(
					'type' => 'section',
					'label' => __( 'Post Thumbnail', 'so-widgets-bundle' ),
					'hide' => true,
					'fields' => array(
						'thumbnail_overlay_hover_color' => array(
							'type' => 'color',
							'label' => __( 'Thumbnail Overlay Hover Color', 'so-widgets-bundle' ),
							'default' => '#3279bb',
						),
						'thumbnail_overlay_hover_opacity' => array(
							'type' => 'slider',
							'label' => __( 'Thumbnail Overlay Hover Opacity', 'so-widgets-bundle' ),
							'default' => '0.5',
							'min' => 0,
							'max' => 1,
							'step' => 0.1,
						),
					),
				),
			),
			$design_settings['fields']
		);

		$carousel_settings = $this->carousel_settings_form_fields();
		$carousel_settings['fields']['loop']['description'] = __( 'Automatically return to the first post after the last post.', 'so-widgets-bundle' );

		$carousel_settings['fields']['autoplay'] = array(
			'type' => 'presets',
			'label' => __( 'Autoplay', 'so-widgets-bundle' ),
			'default_preset' => 'off',
			'state_emitter' => array(
				'callback' => 'select',
				'args' => array( 'autoplay' ),
			),
			'options' => array(
				'off' => array(
					'label' => __( 'Off', 'so-widgets-bundle' ),
					'values' => array(
						'carousel_settings' => array(
							'animation_speed' => 400,
							'timeout' => 8000,
						),
					),
				),
				'on' => array(
					'label' => __( 'On', 'so-widgets-bundle' ),
					'values' => array(
						'carousel_settings' => array(
							'animation_speed' => 400,
							'timeout' => 8000,
						),
					),
				),
				'continuous' => array(
					'label' => __( 'Continuous', 'so-widgets-bundle' ),
					'values' => array(
						'carousel_settings' => array(
							'animation' => 'linear',
							'animation_speed' => 8000,
							'timeout' => 400,
							'autoplay_pause_hover' => true,
						),
					),
				),
			),
		);

		// Continuous autoplay doesn't have navigation, so we need to hide the setting.
		$carousel_settings['fields']['arrows']['state_handler'] = array(
			'autoplay[continuous]' => array( 'hide' ),
			'_else[autoplay]' => array( 'show' ),
		);

		// Hide autoplay specific settings if autoplay is off.
		$if_off_hide = array(
			'autoplay[off]' => array( 'hide' ),
			'_else[autoplay]' => array( 'show' ),
		);
		$carousel_settings['fields']['autoplay_pause_hover']['state_handler'] = $if_off_hide;
		$carousel_settings['fields']['timeout']['state_handler'] = $if_off_hide;

		return array(
			'title' => array(
				'type' => 'text',
				'label' => __( 'Title', 'so-widgets-bundle' ),
			),

			'default_thumbnail' => array(
				'type'     => 'media',
				'library'  => 'image',
				'label'    => __( 'Default Thumbnail', 'so-widgets-bundle' ),
				'choose'   => __( 'Choose Thumbnail', 'so-widgets-bundle' ),
				'update'   => __( 'Set Thumbnail', 'so-widgets-bundle' ),
				'fallback' => true,
			),

			'image_size' => array(
				'type' => 'image-size',
				'label' => __( 'Featured Image Size', 'so-widgets-bundle' ),
				'default' => 'sow-carousel-default',
			),

			'link_target' => array(
				'type' => 'select',
				'label' => __( 'Link Target', 'so-widgets-bundle' ),
				'description' => __( 'Choose where to open each carousel item.', 'so-widgets-bundle' ),
				'options' => array(
					'same'    => __( 'Same window ', 'so-widgets-bundle' ),
					'new'    => __( 'New window ', 'so-widgets-bundle' ),
				),
			),

			'carousel_settings' => $carousel_settings,

			'posts' => array(
				'type' => 'posts',
				'label' => __( 'Posts Query', 'so-widgets-bundle' ),
				'hide' => true,
				'posts_limit' => true,
				'fields' => array(
					'posts_per_page' => array(
						'label' => __( 'Posts Per Load', 'so-widgets-bundle' ),
						'description' => __( 'Set the number of posts preloaded in the background when clicking next. The default is 10.', 'so-widgets-bundle' ),
					),
				),
			),
			'design' => $design_settings,
			'responsive' => $this->responsive_form_fields(),
		);
	}

	public function modify_instance( $instance ) {
		// Migrate old Design settings to new settings structure.
		if (
			is_array( $instance ) &&
			isset( $instance['design'] ) &&
			isset( $instance['design']['thumbnail_overlay_hover_color'] )
		) {
			$instance['design']['thumbnail'] = array(
				'thumbnail_overlay_hover_color' => ! empty( $instance['design']['thumbnail_overlay_hover_color'] ) ? $instance['design']['thumbnail_overlay_hover_color'] : '#3279bb',
				'thumbnail_overlay_hover_opacity' => ! empty( $instance['design']['thumbnail_overlay_hover_opacity'] ) ? $instance['design']['thumbnail_overlay_hover_opacity'] : 0.5,
			);
			$instance['design']['navigation'] = array(
				'navigation_color' => ! empty( $instance['design']['navigation_color'] ) ? $instance['design']['navigation_color'] : '#fff',
				'navigation_color_hover' => ! empty( $instance['design']['navigation_color_hover'] ) ? $instance['design']['navigation_color_hover'] : '',
				'navigation_background' => ! empty( $instance['design']['navigation_background'] ) ? $instance['design']['navigation_background'] : '#333',
				'navigation_hover_background' => ! empty( $instance['design']['navigation_hover_background'] ) ? $instance['design']['navigation_hover_background'] : '#444',
			);
		}

		// Migrate settings to the Settings section.
		if ( isset( $instance['loop_posts'] ) ) {
			if ( empty( $instance['carousel_settings'] ) ) {
				$instance['carousel_settings'] = array();
			}

			$instance['carousel_settings']['loop'] = $instance['loop_posts'];
			unset( $instance['loop_posts'] );
		}

		// Migrate autoplay settings.
		if (
			isset( $instance['carousel_settings']['autoplay'] ) &&
			is_bool( $instance['carousel_settings']['autoplay'] )
		) {
			if ( empty( $instance['carousel_settings']['autoplay'] ) ) {
				$instance['carousel_settings']['autoplay'] = 'off';
			} elseif ( ! empty( $instance['carousel_settings']['autoplay_continuous_scroll'] ) ) {
				$instance['carousel_settings']['autoplay'] = 'continuous';

				// Adjust timeout/autoplay if they're using defaults.
				if ( $instance['carousel_settings']['timeout'] == 8000 ) {
					$instance['carousel_settings']['timeout'] = 400;
				}

				if ( $instance['carousel_settings']['autoplay'] == 400 ) {
					$instance['carousel_settings']['autoplay'] = 4000;
				}
			} else {
				$instance['carousel_settings']['autoplay'] = 'on';
			}
		}

		return $instance;
	}

	public function get_less_variables( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		$size = siteorigin_widgets_get_image_size( $instance['image_size'] );

		$thumb_width = '';
		$thumb_height = '';
		$thumb_hover_width = '';
		$thumb_hover_height = '';

		if ( ! ( empty( $size['width'] ) || empty( $size['height'] ) ) ) {
			$thumb_width = $size['width'] - $size['width'] * 0.1;
			$thumb_height = $size['height'] - $size['height'] * 0.1;
			$thumb_hover_width = $size['width'];
			$thumb_hover_height = $size['height'];
		}

		$less_vars = array(
			'thumbnail_width' => $thumb_width . 'px',
			'thumbnail_height' => $thumb_height . 'px',
			'thumbnail_hover_width' => $thumb_hover_width . 'px',
			'thumbnail_hover_height' => $thumb_hover_height . 'px',
			'thumbnail_overlay_hover_color' => ! empty( $instance['design']['thumbnail']['thumbnail_overlay_hover_color'] ) ? $instance['design']['thumbnail']['thumbnail_overlay_hover_color'] : '',
			'thumbnail_overlay_hover_opacity' => ! empty( $instance['design']['thumbnail']['thumbnail_overlay_hover_opacity'] ) ? $instance['design']['thumbnail']['thumbnail_overlay_hover_opacity'] : 0.5,
			'navigation_color' => ! empty( $instance['design']['navigation']['navigation_color'] ) ? $instance['design']['navigation']['navigation_color'] : '',
			'navigation_color_hover' => ! empty( $instance['design']['navigation']['navigation_color_hover'] ) ? $instance['design']['navigation']['navigation_color_hover'] : '',
			'navigation_background' => ! empty( $instance['design']['navigation']['navigation_background'] ) ? $instance['design']['navigation']['navigation_background'] : '',
			'navigation_hover_background' => ! empty( $instance['design']['navigation']['navigation_hover_background'] ) ? $instance['design']['navigation']['navigation_hover_background'] : '',
			'item_title_tag' => ! empty( $instance['design']['item_title']['tag'] ) ? $instance['design']['item_title']['tag'] : '',
			'item_title_font_size' => ! empty( $instance['design']['item_title']['size'] ) ? $instance['design']['item_title']['size'] : '',
			'item_title_color' => ! empty( $instance['design']['item_title']['color'] ) ? $instance['design']['item_title']['color'] : '',
		);
		$less_vars = $this->responsive_less_variables( $less_vars, $instance );

		if (
			! empty( $instance['design']['item_title'] ) &&
			! empty( $instance['design']['item_title']['font'] )
		) {
			$item_title_font = siteorigin_widget_get_font( $instance['design']['item_title']['font'] );
			$less_vars['title_font'] = $item_title_font['family'];

			if ( ! empty( $item_title_font['weight'] ) ) {
				$less_vars['title_font_style'] = $item_title_font['style'];
				$less_vars['title_font_weight'] = $item_title_font['weight_raw'];
			}
		}

		return $less_vars;
	}

	public static function get_theme( $instance ) {
		return empty( $instance['design']['theme'] ) || ! class_exists( 'SiteOrigin_Premium_Plugin_Carousel' ) ? 'base' : $instance['design']['theme'];
	}

	public function get_template_variables( $instance, $args ) {
		$theme = self::get_theme( $instance );

		if (
			! empty( $instance['default_thumbnail'] ) ||
			! empty( $instance['default_thumbnail_fallback'] )
		) {
			$default_thumbnail = siteorigin_widgets_get_attachment_image_src( $instance['default_thumbnail'], $instance['image_size'], $instance['default_thumbnail_fallback'] );
		}

		$query = siteorigin_widget_post_selector_process_query(
			wp_parse_args(
				$instance['posts'],
				array(
					'paged' => empty( $instance['paged'] ) ? 1 : $instance['paged'],
				)
			)
		);
		$posts = new WP_Query( $query );

		$carousel_settings = $this->carousel_settings_template_variables( $instance['carousel_settings'], false );
		$carousel_settings['autoplay_continuous_scroll'] = $instance['carousel_settings']['autoplay'] === 'continuous';
		// The base theme doesn't support dot navigation so let's remove it.
		if ( $theme == 'base' ) {
			unset( $carousel_settings['dots'] );
		}

		$carousel_settings['loop'] = ! empty( $instance['carousel_settings']['loop'] );
		$carousel_settings['item_overflow'] = true;
		$carousel_settings = apply_filters( 'siteorigin_widgets_post_carousel_settings_frontend', $carousel_settings, $instance );

		$size = siteorigin_widgets_get_image_size( $instance['image_size'] );

		$navigation = 'title';

		// If continuous scroll is enabled, hide the navigation.
		if (
			! empty( $instance['carousel_settings']['autoplay'] ) &&
			$instance['carousel_settings']['autoplay'] === 'continuous'
		) {
			$navigation = 'hidden';
		}

		return array(
			'settings' => array(
				'args' => $args,
				'title' => $instance['title'],
				'theme' => $theme,
				'posts' => sow_carousel_handle_post_limit( $posts ),
				'default_thumbnail' => ! empty( $default_thumbnail ) ? $default_thumbnail[0] : false,
				'image_size' => $instance['image_size'],
				'link_target' => ! empty( $instance['link_target'] ) ? $instance['link_target'] : 'same',
				'item_template' => apply_filters( 'siteorigin_post_carousel_item_template', plugin_dir_path( __FILE__ ) . 'tpl/item.php' ),
				'navigation' => $navigation,
				'navigation_arrows' => isset( $instance['carousel_settings']['arrows'] ) ? ! empty( $instance['carousel_settings']['arrows'] ) : true,
				'navigation_dots' => isset( $instance['carousel_settings']['dots'] ) ? ! empty( $instance['carousel_settings']['dots'] ) : false,
				'height' => ! empty( $size['height'] ) ? 'min-height: ' . $size['height'] . 'px' : '',
				'item_title_tag' => siteorigin_widget_valid_tag(
					! empty( $instance['design']['item_title']['tag'] ) ? $instance['design']['item_title']['tag'] : 'h3',
					'h3'
				),
				'attributes' => array(
					'widget' => 'post',
					'fetching' => 'false',
					'page' => 1,
					'ajax-url' => sow_esc_url( wp_nonce_url( admin_url( 'admin-ajax.php' ), 'widgets_action', '_widgets_nonce' ) ),

					// Base carousel specific settings.
					'item_count' => get_query_var( 'sow-total_posts' ),
					'carousel_settings' => json_encode( $carousel_settings ),
					'responsive' => $this->responsive_template_variables( $instance['responsive'] ),
					'variable_width' => 'true',
				),
			),
		);
	}

	public function get_template_name( $instance ) {
		return 'base';
	}

	public function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return sprintf(
			__( 'Get access to additional carousel themes with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
			'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/carousel" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);
	}

	/**
	 * Prevent potential layout shifts during carousel initialization
	 *
	 * Adds CSS to ensure carousels have a minimum height while loading
	 * and only become visible after Slick initialization is complete.
	 * Uses static variable to ensure styles are only added once per page load.
	 */
	public function prevent_potential_shifts(): void {
		// Use static variable to ensure this only runs once
		static $carousel_styles_added = false;

		if ( $carousel_styles_added ) {
			return;
		}
		?><style type="text/css">
			.sow-carousel-wrapper:has(.slick-initialized) {
				visibility: visible !important;
				opacity: 1 !important;
			}

			.sow-post-carousel-wrapper:not(:has(.slick-initialized)) .sow-carousel-items {
				visibility: hidden;
			}
		</style>
		<?php
		$carousel_styles_added = true;
	}
}

siteorigin_widget_register( 'sow-post-carousel', __FILE__, 'SiteOrigin_Widget_PostCarousel_Widget' );

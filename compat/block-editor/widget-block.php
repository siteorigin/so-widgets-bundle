<?php

class SiteOrigin_Widgets_Bundle_Widget_Block {
	public $widgetAnchor;
	/**
	 * Get the singleton instance
	 *
	 * @return SiteOrigin_Widgets_Bundle_Widget_Block
	 */
	public static function single() {
		static $single;

		return empty( $single ) ? $single = new self() : $single;
	}

	public function __construct() {
		add_action( 'init', array( $this, 'register_widget_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_widget_block_editor_assets' ) );

		$post_types = get_post_types( array( 'public' => true ), 'names' );
		if ( empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}
		foreach ( $post_types as $post_type ) {
			add_action( 'rest_pre_insert_' . $post_type, array( $this, 'server_side_validation' ), 10, 2 );
		}
	}

	public function register_widget_block() {
		register_block_type( 'sowb/widget-block', array(
			'render_callback' => array( $this, 'render_widget_block' ),
		) );
	}

	public function enqueue_widget_block_editor_assets() {
		$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		wp_enqueue_script(
			'sowb-widget-block',
			plugins_url( 'widget-block' . SOW_BUNDLE_JS_SUFFIX . '.js', __FILE__ ),
			array(
				// The WP 5.8 Widget Area requires a specific editor script to be used.
				is_object( $current_screen ) && $current_screen->base == 'widgets' ? 'wp-edit-widgets' : 'wp-editor',
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-components',
				'wp-compose',
				'wp-data',
			),
			SOW_BUNDLE_VERSION
		);

		wp_enqueue_style(
			'sowb-widget-block',
			plugins_url( 'widget-block.css', __FILE__ )
		);

		$widgets_metadata_list = SiteOrigin_Widgets_Bundle::single()->get_widgets_list();
		$widgets_manager = SiteOrigin_Widgets_Widget_Manager::single();

		$so_widgets = array();
		// Add data for any inactive widgets.
		foreach ( $widgets_metadata_list as $widget ) {
			if ( ! $widget['Active'] ) {
				include_once wp_normalize_path( $widget['File'] );
				// The last class will always be from the widget file we just loaded.
				$classes = get_declared_classes();
				$widget_class = end( $classes );

				// Append author's name to third-party widget names, if not already
				// present, to help distinguish widgets with similar names.
				if (
					! empty( $widget['Author'] ) &&
					$widget['Author'] != 'SiteOrigin' &&
					strpos( $widget['Name'], $widget['Author'] ) === false
				) {
					$widget_name = sprintf( __( '%s by %s', 'so-widgets-bundle' ), $widget['Name'], $widget['Author'] );
				} else {
					$widget_name = $widget['Name'];
				}

				$so_widgets[] = array(
					'name' => $widget_name,
					'class' => $widget_class,
				);
			}
		}

		global $wp_widget_factory;
		$third_party_widgets = array();

		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			if ( ! empty( $widget_obj ) && is_object( $widget_obj ) && is_subclass_of( $widget_obj, 'SiteOrigin_Widget' ) ) {
				/** @var SiteOrigin_Widget $widget_obj */
				$author = '';
				// Try to find a widget's author from its file metadata, by matching the filename to the ID (which is derived from the filename).
				foreach ( $widgets_metadata_list as $widget_metadata ) {
					if ( $widgets_manager->get_class_from_path( wp_normalize_path( $widget_metadata['File'] ) ) == $class ) {
						$author = $widget_metadata['Author'];
						break;
					}
				}
				// For SiteOrigin widgets, just display the widget's name. For third party widgets, display the Author
				// to try avoid confusion when the widgets have the same name.
				if ( preg_match( '/^SiteOrigin /', $widget_obj->name ) == 1 && $author == 'SiteOrigin' ) {
					$name = preg_replace( '/^SiteOrigin /', '', $widget_obj->name );

					$so_widgets[] = array(
						'name' => $name,
						'class' => $class,
					);
				} else {
					$name = sprintf( __( '%s by %s', 'so-widgets-bundle' ), $widget_obj->name, $author );
					$third_party_widgets[] = array(
						'name' => $name,
						'class' => $class,
					);
				}
			}
		}
		// Sort the list of widgets so SiteOrigin widgets are at the top and then third party widgets.
		sort( $so_widgets );
		sort( $third_party_widgets );
		$so_widgets = array_merge( $so_widgets, $third_party_widgets );

		wp_localize_script(
			'sowb-widget-block',
			'sowbBlockEditorAdmin',
			array(
				'widgets' => $so_widgets,
				'restUrl' => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'confirmChangeWidget' => __( 'Selecting a different widget will revert any changes. Continue?', 'so-widgets-bundle' ),
			)
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'sowb-widget-block', 'so-widgets-bundle' );
		}

		$so_widgets_bundle = SiteOrigin_Widgets_Bundle::single();
		// This is to ensure necessary scripts can be enqueued for previews.
		$so_widgets_bundle->register_general_scripts();
		$so_widgets_bundle->enqueue_registered_widgets_scripts();
	}

	public function add_widget_id( $id, $instance, $widget ) {
		return $this->widgetAnchor;
	}

	public function render_widget_block( $attributes ) {
		if ( empty( $attributes['widgetClass'] ) ) {
			return '<div>' .
				__( 'You need to select a widget type before you\'ll see anything here. :)', 'so-widgets-bundle' ) .
				'</div>';
		}

		$widget_class = $attributes['widgetClass'];
		global $wp_widget_factory;

		$widget = ! empty( $wp_widget_factory->widgets[ $widget_class ] ) ? $wp_widget_factory->widgets[ $widget_class ] : false;
		// Attempt to activate the widget if it's not already active.
		if ( ! empty( $widget_class ) && empty( $widget ) ) {
			$widget = SiteOrigin_Widgets_Bundle::single()->load_missing_widget( false, $widget_class );
		}

		// Support for Additional CSS classes.
		$add_custom_class_name = function ( $class_names ) use ( $attributes ) {
			if ( ! empty( $attributes['className'] ) ) {
				$class_names = array_merge( $class_names, explode( ' ', $attributes['className'] ) );
			}

			return $class_names;
		};

		if ( ! empty( $widget ) && is_object( $widget ) && is_subclass_of( $widget, 'SiteOrigin_Widget' ) ) {
			$GLOBALS['SITEORIGIN_WIDGET_BLOCK_RENDER'] = true;
			$instance = $attributes['widgetData'];
			add_filter( 'siteorigin_widgets_wrapper_classes_' . $widget->id_base, $add_custom_class_name );

			ob_start();
			/*
			 * If we have pre-generated widgetMarkup or there's a valid $_POST, generate the widget.
			 * There are certain situations where we bypass the cache:
			 *
			 * - We don't show the pre-generated widget when there's a valid $_POST
			 * as widgets will likely change when that happens.
			 *
			 * - Pages with an active WPML translation will bypass cache.
			 *
			 * - We also exclude certain widgets from the cache.
			 */
			$current_page_id = get_the_ID();

			if (
				empty( $attributes['widgetMarkup'] ) ||
				! empty( $_POST ) ||
				$attributes['widgetClass'] == 'SiteOrigin_Widget_PostCarousel_Widget' ||
				$attributes['widgetClass'] == 'SiteOrigin_Widgets_ContactForm_Widget' ||
				$attributes['widgetClass'] == 'SiteOrigin_Widget_Blog_Widget' ||
				apply_filters( 'siteorigin_widgets_block_exclude_widget', false, $attributes['widgetClass'], $instance ) ||
				// Is WPML active? If so, is there a translation for this page?
				(
					defined( 'ICL_LANGUAGE_CODE' ) &&
					is_numeric(
						apply_filters(
							'wpml_object_id',
							$current_page_id,
							get_post_type( $current_page_id ),
							false,
							ICL_LANGUAGE_CODE
						)
					)
				)
			) {
				// Add anchor to widget wrapper.
				if ( ! empty( $attributes['anchor'] ) ) {
					$this->widgetAnchor = $attributes['anchor'];
					add_filter( 'siteorigin_widgets_wrapper_id_' . $widget->id_base, array( $this, 'add_widget_id' ), 10, 3 );
				}
				/* @var $widget SiteOrigin_Widget */
				$instance = $widget->update( $instance, $instance );
				$widget->widget( array(
					'before_widget' => '',
					'after_widget' => '',
					'before_title' => '<h3 class="widget-title">',
					'after_title' => '</h3>',
				), $instance );

				if ( ! empty( $attributes['anchor'] ) ) {
					remove_filter( 'siteorigin_widgets_wrapper_id_' . $widget->id_base, array( $this, 'add_widget_id' ), 10 );
				}
			} else {
				$widget->generate_and_enqueue_instance_styles( $instance );
				$widget->enqueue_frontend_scripts( $instance );

				// Check if this widget uses any icons that need to be enqueued.
				if ( ! empty( $attributes['widgetIcons'] ) ) {
					$widget_icon_families = apply_filters( 'siteorigin_widgets_icon_families', array() );

					foreach ( $attributes['widgetIcons'] as $icon_font ) {
						if ( ! wp_style_is( $icon_font ) ) {
							$font_family = explode( 'siteorigin-widget-icon-font-', $icon_font )[1];
							wp_enqueue_style( $icon_font, $widget_icon_families[ $font_family ]['style_uri'] );
						}
					}
				}
				echo $attributes['widgetMarkup'];
			}

			$rendered_widget = ob_get_clean();
			remove_filter( 'siteorigin_widgets_wrapper_classes_' . $widget->id_base, $add_custom_class_name );
			unset( $GLOBALS['SITEORIGIN_WIDGET_BLOCK_RENDER'] );
		} else {
			return
				'<div>' .
					sprintf(
						__( 'Invalid widget class %s. Please make sure the widget has been activated in %sSiteOrigin Widgets%s.', 'so-widgets-bundle' ),
						$widget_class,
						'<a href="' . admin_url( 'plugins.php?page=so-widgets-plugins' ) . '">',
						'</a>'
					) .
				'</div>';
		}

		return $rendered_widget;
	}

	public function server_side_validation( $prepared_post, $request ) {
		if ( empty( $prepared_post->post_content ) ) {
			return $prepared_post;
		}

		$blocks = parse_blocks( $prepared_post->post_content );
		if ( empty( $blocks ) ) {
			return $prepared_post;
		}

		foreach( $blocks as &$block ) {
			$block = $this->sanitize_blocks( $block, true );
		}
		$prepared_post->post_content = serialize_blocks( $blocks );

		return $prepared_post;
	}

	public function sanitize_blocks( $block ) {
		if (
			! empty( $block['blockName'] ) &&
			$block['blockName'] === 'sowb/widget-block'
		) {
			$block = $this->sanitize_block( $block );
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			foreach( $block['innerBlocks'] as $i => $inner ) {
				$block['innerBlocks'][$i] = $this->sanitize_blocks( $inner );
			}
		}

		return $block;
	}

	public function sanitize_block( $block ) {
		if (
			empty( $block['attrs'] ) ||
			empty( $block['attrs']['widgetClass'] )
		) {
			return $block;
		}

		$rendered_widget = $this->get_widget_preview( $block['attrs'], false );
		if ( is_wp_error( $rendered_widget ) ) {
			return rest_ensure_response( $rendered_widget );
		}

		if ( empty( $rendered_widget ) ) {
			return new WP_Error( 'rest_invalid_param', __( 'Invalid Widgets Bundle data', 'so-widgets-bundle' ), array( 'status' => 400 ) );
		}

		$block['attrs'] = $rendered_widget;
		return $block;
	}

	public function get_widget_preview( $block, $just_html = true ) {
		$widget_class = $block['widgetClass'];
		$widget_data = $block['widgetData'];

		$widget = SiteOrigin_Widgets_Widget_Manager::get_widget_instance( $widget_class );
		// Attempt to activate the widget if it's not already active.
		if ( ! empty( $widget_class ) && empty( $widget ) ) {
			$widget = SiteOrigin_Widgets_Bundle::single()->load_missing_widget( false, $widget_class );
		}

		// This ensures styles are added inline.
		add_filter( 'siteorigin_widgets_is_preview', '__return_true' );
		$GLOBALS[ 'SO_WIDGETS_BUNDLE_PREVIEW_RENDER' ] = true;

		$valid_widget_class = ! empty( $widget ) &&
							  is_object( $widget ) &&
							  is_subclass_of( $widget, 'SiteOrigin_Widget' );

		if ( $valid_widget_class && ! empty( $widget_data ) ) {
			ob_start();
			// Add anchor to widget wrapper.
			if ( ! empty( $block['anchor'] ) ) {
				$this->widgetAnchor = $block['anchor'];
				add_filter( 'siteorigin_widgets_wrapper_id_' . $widget->id_base, array( $this, 'add_widget_id' ), 10, 3 );
			}
			/* @var $widget SiteOrigin_Widget */
			$instance = $widget->update( $widget_data, $widget_data );
			$widget->widget( array(), $instance );
			$rendered_widget = array();
			$rendered_widget['html'] = ob_get_clean();

			if ( ! empty( $block['anchor'] ) ) {
				remove_filter( 'siteorigin_widgets_wrapper_id_' . $widget->id_base, array( $this, 'add_widget_id' ), 10 );
			}

			// Check if this widget loaded any icons, and if it has, store them.
			$styles = wp_styles();

			if ( ! empty( $styles->queue ) ) {
				$rendered_widget['widgetIcons'] = array();

				foreach ( $styles->queue as $style ) {
					if ( strpos( $style, 'siteorigin-widget-icon-font' ) !== false ) {
						$rendered_widget['widgetIcons'][] = $style;
					}
				}
			}
		} else {
			if ( empty( $valid_widget_class ) ) {
				$rendered_widget = new WP_Error(
					400,
					'Invalid or missing widget class: ' . $widget_class,
					array(
						'status' => 400,
					)
				);
			} elseif ( empty( $widget_data ) ) {
				$rendered_widget = new WP_Error(
					400,
					'Unable to render preview. Invalid or missing widget data.',
					array(
						'status' => 400,
					)
				);
			}
		}

		unset( $GLOBALS['SO_WIDGETS_BUNDLE_PREVIEW_RENDER'] );

		if ( $just_html || is_wp_error( $rendered_widget ) ) {
			return $rendered_widget;
		}

		// If there's a style tag, we can't set set widgetMarkup.
		if ( strpos( $rendered_widget['html'], '<style' ) !== false ) {
			$rendered_widget['widgetMarkup'] = '';
		} else {
			$rendered_widget['widgetMarkup'] = $rendered_widget['html'];
		}

		return array(
			'widgetClass' => $widget_class,
			'widgetData' => $widget_data,
			'widgetMarkup' => $rendered_widget['widgetMarkup'],
			'html' => $rendered_widget['html'],
			'widgetIcons' => isset( $rendered_widget['css'] ) ? $rendered_widget['widgetIcons'] : array(),
		);
	}
}

SiteOrigin_Widgets_Bundle_Widget_Block::single();

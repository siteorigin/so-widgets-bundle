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
				// For SiteOrigin widgets, just display the widget's name. For third party widgets, display the Author
				// to try avoid confusion when the widgets have the same name.
				if ( $widget['Author'] != 'SiteOrigin' && strpos( $widget['Name'], $widget['Author'] ) === false ) {
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
			 * If we have pre-generated widgetHtml or there's a valid $_POST, generate the widget.
			 * There are certain sitautions where we bypass the cache:
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
				empty( $attributes['widgetHtml'] ) ||
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
				echo $attributes['widgetHtml'];
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
}

SiteOrigin_Widgets_Bundle_Widget_Block::single();

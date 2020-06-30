<?php

class SiteOrigin_Widgets_Bundle_Widget_Block {
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
		wp_enqueue_script(
			'sowb-widget-block',
			plugins_url( 'widget-block' . SOW_BUNDLE_JS_SUFFIX . '.js', __FILE__ ),
			array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-compose' ),
			SOW_BUNDLE_VERSION
		);

		$widgets_metadata_list = SiteOrigin_Widgets_Bundle::single()->get_widgets_list();
		$widgets_manager = SiteOrigin_Widgets_Widget_Manager::single();

		global $wp_widget_factory;
		$so_widgets = array();
		$third_party_widgets = array();
		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			if ( ! empty( $widget_obj ) && is_object( $widget_obj ) && is_subclass_of( $widget_obj, 'SiteOrigin_Widget' ) ) {
				/** @var SiteOrigin_Widget $widget_obj */
				$author = '';
				// Try to find a widget's author from its file metadata, by matching the filename to the ID (which is derived from the filename).
				foreach ( $widgets_metadata_list as $widget_metadata ) {
					$filename = $widgets_manager->get_widget_filename( $widget_obj->id_base );
					if ( $widget_metadata['ID'] == $filename ) {
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

	public function render_widget_block( $attributes ) {
		if ( empty( $attributes['widgetClass'] ) ) {
			return '<div>'.
				   __( 'You need to select a widget type before you\'ll see anything here. :)', 'so-widgets-bundle' ) .
				   '</div>';
		}

		$widget_class = $attributes['widgetClass'];

		global $wp_widget_factory;

		$widget = ! empty( $wp_widget_factory->widgets[ $widget_class ] ) ? $wp_widget_factory->widgets[ $widget_class ] : false;

		$instance = $attributes['widgetData'];

		// Support for Additional CSS classes.
		$add_custom_class_name = function( $class_names ) use ( $attributes ) {
			if ( ! empty( $attributes['className'] ) ) {
				$class_names = array_merge( $class_names, explode( ' ', $attributes['className'] ) );
			}
			return $class_names;
		};

		if ( ! empty( $widget ) && is_object( $widget ) && is_subclass_of( $widget, 'SiteOrigin_Widget' ) ) {
			$GLOBALS['SITEORIGIN_WIDGET_BLOCK_RENDER'] = true;
			add_filter( 'siteorigin_widgets_wrapper_classes_' . $widget->id_base, $add_custom_class_name );
			ob_start();
			/* @var $widget SiteOrigin_Widget */
			$instance = $widget->update( $instance, $instance );
			$widget->widget( array(
				'before_widget' => '',
				'after_widget' => '',
				'before_title' => '<h3 class="widget-title">',
				'after_title' => '</h3>',
			), $instance );
			$rendered_widget = ob_get_clean();
			remove_filter( 'siteorigin_widgets_wrapper_classes_' . $widget->id_base, $add_custom_class_name );
			unset( $GLOBALS['SITEORIGIN_WIDGET_BLOCK_RENDER'] );
		} else {
			return '<div>'.
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

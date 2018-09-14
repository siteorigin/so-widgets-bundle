<?php

class SiteOrigin_Widgets_Bundle_Gutenberg_Block {
	/**
	 * Get the singleton instance
	 *
	 * @return SiteOrigin_Widgets_Bundle_Gutenberg_Block
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
		wp_localize_script(
			'sowb-widget-block',
			'sowbGutenbergAdmin',
			array(
				'restUrl' => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
		
		$so_widgets_bundle = SiteOrigin_Widgets_Bundle::single();
		// This is to ensure necessary scripts can be enqueued for previews.
		$so_widgets_bundle->register_general_scripts();
		
		global $wp_widget_factory;
		
		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			if ( ! empty( $widget_obj ) && is_object( $widget_obj ) && is_subclass_of( $widget_obj, 'SiteOrigin_Widget' ) ) {
				/* @var $widget_obj SiteOrigin_Widget */
				ob_start();
				$widget_obj->form( array() );
				// Enqueue scripts for previews.
				$widget_obj->widget( array(), array() );
				ob_clean();
			}
		}
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
		
		if ( ! empty( $widget ) && is_object( $widget ) && is_subclass_of( $widget, 'SiteOrigin_Widget' ) ) {
			ob_start();
			/* @var $widget SiteOrigin_Widget */
			$instance = $widget->update( $instance, $instance );
			$widget->widget( array(), $instance );
			$rendered_widget = ob_get_clean();
		} else {
			$rendered_widget = new WP_Error( '', 'Invalid widget class.' );
		}
		return $rendered_widget;
	}
}

SiteOrigin_Widgets_Bundle_Gutenberg_Block::single();

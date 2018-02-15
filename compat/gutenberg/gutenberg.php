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
		
		add_action( 'wp_default_scripts', array( $this, 'gutenberg_shim_fix_api_request_plain_permalinks' ) );
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
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components' ),
			SOW_BUNDLE_VERSION
		);
		wp_enqueue_style(
			'sowb-widget-block',
			plugins_url( 'styles.css', __FILE__ ),
			array(),
			SOW_BUNDLE_VERSION
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
		$widget_class = $attributes['widgetClass'];
		
		global $wp_widget_factory;
		
		$widget = ! empty( $wp_widget_factory->widgets[ $widget_class ] ) ? $wp_widget_factory->widgets[ $widget_class ] : false;
		
		if ( ! empty( $widget ) && is_object( $widget ) && is_subclass_of( $widget, 'SiteOrigin_Widget' ) ) {
			ob_start();
			/* @var $widget SiteOrigin_Widget */
			$widget->widget( array(), $attributes['widgetData'] );
			$rendered_widget = ob_get_clean();
		} else {
			$rendered_widget = new WP_Error( '', 'Invalid widget class.' );
		}
		return $rendered_widget;
	}
	
	/**
	 * This is copied from this PR: https://github.com/WordPress/gutenberg/pull/4877
	 * Can be removed when the PR has been merged or the WP Core issue linked below has been fixed.
	 *
	 * Shims fix for apiRequest on sites configured to use plain permalinks.
	 *
	 * @see https://core.trac.wordpress.org/ticket/42382
	 *
	 * @param WP_Scripts $scripts WP_Scripts instance (passed by reference).
	 */
	function gutenberg_shim_fix_api_request_plain_permalinks( $scripts ) {
		$api_request_fix = <<<JS
( function( wp, wpApiSettings ) {
	var buildAjaxOptions;
	if ( 'string' !== typeof wpApiSettings.root ||
			-1 === wpApiSettings.root.indexOf( '?' ) ) {
		return;
	}
	buildAjaxOptions = wp.apiRequest.buildAjaxOptions;
	wp.apiRequest.buildAjaxOptions = function( options ) {
		if ( 'string' === typeof options.path ) {
			options.path = options.path.replace( '?', '&' );
		}
		return buildAjaxOptions.call( wp.apiRequest, options );
	};
} )( window.wp, window.wpApiSettings );
JS;
		$scripts->add_inline_script( 'wp-api-request', $api_request_fix, 'after' );
	}
}

SiteOrigin_Widgets_Bundle_Gutenberg_Block::single();

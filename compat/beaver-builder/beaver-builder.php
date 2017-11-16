<?php

class SiteOrigin_Widgets_Bundle_Beaver_Builder {

	/**
	 * Get the singleton instance
	 *
	 * @return SiteOrigin_Widgets_Bundle_Beaver_Builder
	 */
	public static function single() {
		static $single;
		return empty( $single ) ? $single = new self() : $single;
	}

	function __construct() {
		add_action('wp', array( $this, 'init' ), 9 );
	}

	function init() {
		if ( ! FLBuilderModel::is_builder_active() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_active_widgets_scripts' ) );
		add_action( 'wp_print_footer_scripts', array( $this, 'print_footer_templates' ) );

		// Don't want to show the form preview button when using Beaver Builder
		add_filter( 'siteorigin_widgets_form_show_preview_button', '__return_false' );
	}

	function enqueue_active_widgets_scripts() {
		global $wp_widget_factory;

		// Beaver Builder does it's editing in the front end so enqueue required form scripts for active widgets.
		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			if ( ! empty( $widget_obj ) && is_object( $widget_obj ) && is_subclass_of( $widget_obj, 'SiteOrigin_Widget' ) ) {
				ob_start();
				$widget_obj->form( array() );
				ob_clean();
			}
		}

		if ( ! wp_script_is( 'wp-color-picker' ) ) {
			// wp-color-picker hasn't been registered because we're in the front end, so enqueue with full args.
			wp_enqueue_script( 'iris', '/wp-admin/js/iris.min.js', array(
				'jquery-ui-draggable',
				'jquery-ui-slider',
				'jquery-touch-punch'
			), '1.0.7', 1 );

			wp_enqueue_script( 'wp-color-picker', '/wp-admin/js/color-picker' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'iris' ), false, 1 );

			wp_enqueue_style( 'wp-color-picker' );

			// Localization args for when wp-color-picker script hasn't been registered.
			wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', array(
				'clear'         => __( 'Clear', 'so-widgets-bundle' ),
				'defaultString' => __( 'Default', 'so-widgets-bundle' ),
				'pick'          => __( 'Select Color', 'so-widgets-bundle' ),
				'current'       => __( 'Current Color', 'so-widgets-bundle' ),
			) );
		}

		wp_enqueue_style( 'sowb-styles-for-beaver', plugin_dir_url( __FILE__ ) . 'styles.css' );
		
		$deps = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? array( 'jquery', 'fl-builder' ) : array( 'fl-builder-min' );
		wp_enqueue_script(
			'sowb-js-for-beaver',
			plugin_dir_url( __FILE__ ) . 'sowb-beaver-builder' . SOW_BUNDLE_JS_SUFFIX . '.js',
			$deps
		);

		wp_enqueue_style( 'siteorigin-widget-admin', plugin_dir_url(SOW_BUNDLE_BASE_FILE).'base/css/admin.css', array( 'media-views' ), SOW_BUNDLE_VERSION );

	}

	function print_footer_templates() {
		global $wp_widget_factory;

		// Beaver Builder does it's editing in the front end so print required footer templates for active widgets.
		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			if ( ! empty( $widget_obj ) && is_object( $widget_obj ) && is_subclass_of( $widget_obj, 'SiteOrigin_Widget' ) ) {
				$widget_obj->footer_admin_templates();
			}
		}
	}
	
}

SiteOrigin_Widgets_Bundle_Beaver_Builder::single();

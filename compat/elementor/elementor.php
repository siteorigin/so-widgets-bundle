<?php

class SiteOrigin_Widgets_Bundle_Elementor {

	/**
	 * Get the singleton instance
	 *
	 * @return SiteOrigin_Widgets_Bundle_Elementor
	 */
	public static function single() {
		static $single;
		return empty( $single ) ? $single = new self() : $single;
	}

	private $plugin;

	function __construct() {
		$this->plugin = Elementor\Plugin::instance();
		$elementor_editor = $this->plugin->editor;
		if( is_admin() || ! $elementor_editor->is_edit_mode()) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_active_widgets_scripts' ), 9999999 );
		add_action( 'wp_print_footer_scripts', array( $this, 'print_footer_templates' ) );

		// Don't want to show the form preview button when using Elementor
		add_filter( 'siteorigin_widgets_form_show_preview_button', '__return_false' );
	}

	function enqueue_active_widgets_scripts() {

		global $wp_widget_factory;

		// Elementor does it's editing in the front end so enqueue required form scripts for active widgets.
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

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_media();

		wp_enqueue_style( 'sowb-styles-for-elementor', plugin_dir_url( __FILE__ ) . 'styles.css' );

		wp_enqueue_style( 'siteorigin-widget-admin', plugin_dir_url(SOW_BUNDLE_BASE_FILE).'base/css/admin.css', array( 'media-views' ), SOW_BUNDLE_VERSION );

	}

	function print_footer_templates() {
		global $wp_widget_factory;

		// Elementor does it's editing in the front end so print required footer templates for active widgets.
		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			if ( ! empty( $widget_obj ) && is_object( $widget_obj ) && is_subclass_of( $widget_obj, 'SiteOrigin_Widget' ) ) {
				$widget_obj->footer_admin_templates();
			}
		}
	}
}

SiteOrigin_Widgets_Bundle_Elementor::single();

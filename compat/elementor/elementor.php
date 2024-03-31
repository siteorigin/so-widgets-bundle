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

	public function __construct() {
		add_action( 'admin_action_elementor', array( $this, 'init_editor' ) );
		add_action( 'template_redirect', array( $this, 'init_preview' ) );

		add_filter( 'siteorigin_widgets_is_preview', array( $this, 'is_elementor_preview' ) );
		add_action( 'wp_ajax_elementor_editor_get_wp_widget_form', array( $this, 'ajax_render_widget_form' ) );
	}

	public function init_editor() {
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'enqueue_active_widgets_scripts' ) );
	}

	public function init_preview() {
		$this->plugin = Elementor\Plugin::instance();

		if ( ! empty( $this->plugin->preview ) && method_exists( $this->plugin->preview, 'is_preview_mode' ) && $this->plugin->preview->is_preview_mode() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
			add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_frontend_scripts' ) );
		}
	}

	public function enqueue_frontend_scripts() {
		$so_widgets_bundle = SiteOrigin_Widgets_Bundle::single();
		$so_widgets_bundle->register_general_scripts();
		$so_widgets_bundle->enqueue_registered_widgets_scripts( true, false );
	}

	public function enqueue_active_widgets_scripts() {
		add_action( 'wp_print_footer_scripts', array( $this, 'print_footer_templates' ) );

		$so_widgets_bundle = SiteOrigin_Widgets_Bundle::single();
		$so_widgets_bundle->register_general_scripts();
		$so_widgets_bundle->enqueue_registered_widgets_scripts( false, true );

		wp_enqueue_style( 'sowb-styles-for-elementor', plugin_dir_url( __FILE__ ) . 'styles.css' );

		wp_enqueue_script(
			'sowb-js-for-elementor',
			plugin_dir_url( __FILE__ ) . 'sowb-elementor' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' )
		);
	}

	public function print_footer_templates() {
		global $wp_widget_factory;

		// Elementor does it's editing in the front end so print required footer templates for active widgets.
		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			if ( ! empty( $widget_obj ) && is_object( $widget_obj ) && is_subclass_of( $widget_obj, 'SiteOrigin_Widget' ) ) {
				/* @var $widget_obj SiteOrigin_Widget */
				$widget_obj->footer_admin_templates();
			}
		}
	}

	public function is_elementor_preview( $is_preview ) {
		$this->plugin = Elementor\Plugin::instance();
		$is_elementor_preview = ! empty( $this->plugin->preview ) && method_exists( $this->plugin->preview, 'is_preview_mode' ) && $this->plugin->preview->is_preview_mode();
		$is_elementor_edit_mode = $this->plugin->editor->is_edit_mode();

		return $is_preview || $is_elementor_preview || $is_elementor_edit_mode ||
			   ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'elementor_ajax' );
	}

	public function ajax_render_widget_form() {
		// Don't want to show the form preview button when using Elementor
		add_filter( 'siteorigin_widgets_form_show_preview_button', array( $this, '__return_false' ) );
	}
}

SiteOrigin_Widgets_Bundle_Elementor::single();

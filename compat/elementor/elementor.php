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
		add_action( 'admin_action_elementor', array( $this, 'init_editor' ) );
		add_action( 'template_redirect', array( $this, 'init_preview' ) );

		add_action( 'wp_ajax_elementor_render_widget', array( $this, 'ajax_render_widget_preview' ) );
		add_action( 'wp_ajax_elementor_editor_get_wp_widget_form', array( $this, 'ajax_render_widget_form' ) );
	}

	function init_editor() {
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'enqueue_active_widgets_scripts' ) );
	}
	
	function init_preview() {
		$this->plugin = Elementor\Plugin::instance();
		if ( !empty( $this->plugin->preview ) && method_exists( $this->plugin->preview, 'is_preview_mode' ) && $this->plugin->preview->is_preview_mode() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
			add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_preview_scripts' ) );
		}
	}

	function enqueue_frontend_scripts() {

		$post_id = get_the_ID();

		if( defined( 'Elementor\\DB::STATUS_DRAFT' ) && ! empty( $this->plugin->db ) && method_exists( $this->plugin->db, 'get_builder' ) ) {
			// This is necessary to ensure styles and scripts are enqueued. Not sure why this is enough, but I assume
			// Elementor is calling widgets' `widget` method with instance data in the process of retrieving editor data.
			$this->plugin->db->get_builder( $post_id, Elementor\DB::STATUS_DRAFT );
		}
	}
	
	function enqueue_preview_scripts() {
		
		global $wp_widget_factory;
		
		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			if ( ! empty( $widget_obj ) && is_object( $widget_obj ) && is_subclass_of( $widget_obj, 'SiteOrigin_Widget' ) ) {
				/* @var $widget_obj SiteOrigin_Widget */
				ob_start();
				$widget_obj->widget( array(), array() );
				ob_clean();
			}
		}
	
	}

	function enqueue_active_widgets_scripts() {

		add_action( 'wp_print_footer_scripts', array( $this, 'print_footer_templates' ) );

		global $wp_widget_factory;

		// Elementor does it's editing in it's own front end so enqueue required form scripts for active widgets.
		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			if ( ! empty( $widget_obj ) && is_object( $widget_obj ) && is_subclass_of( $widget_obj, 'SiteOrigin_Widget' ) ) {
				/* @var $widget_obj SiteOrigin_Widget */
				ob_start();
				$widget_obj->form( array() );
				ob_clean();
			}
		}

		wp_enqueue_style( 'sowb-styles-for-elementor', plugin_dir_url( __FILE__ ) . 'styles.css' );
		
		wp_enqueue_script(
			'sowb-js-for-elementor',
			plugin_dir_url( __FILE__ ) . 'sowb-elementor' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' )
		);

	}

	function print_footer_templates() {
		global $wp_widget_factory;

		// Elementor does it's editing in the front end so print required footer templates for active widgets.
		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			if ( ! empty( $widget_obj ) && is_object( $widget_obj ) && is_subclass_of( $widget_obj, 'SiteOrigin_Widget' ) ) {
				/* @var $widget_obj SiteOrigin_Widget */
				$widget_obj->footer_admin_templates();
			}
		}
	}

	function ajax_render_widget_preview() {
		add_filter( 'siteorigin_widgets_is_preview', '__return_true' );
		add_filter( 'elementor/widget/render_content', array( $this, 'render_widget_preview' ) );
	}

	function render_widget_preview( $widget_output ) {
		
		siteorigin_widget_print_styles();

		return $widget_output;
	}

	function ajax_render_widget_form() {
		// Don't want to show the form preview button when using Elementor
		add_filter( 'siteorigin_widgets_form_show_preview_button', array( $this, '__return_false' ) );
	}
}

SiteOrigin_Widgets_Bundle_Elementor::single();

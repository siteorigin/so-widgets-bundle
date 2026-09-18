<?php
/**
 * WordPress functions plugin files call while being required, before any
 * test has had a chance to set Brain Monkey up: hook registration at file
 * top level, the widget base class constructor firing do_action(), widget
 * initialize() methods registering filters, and self-registration.
 *
 * Required from bootstrap.php after the autoloader, so Patchwork has already
 * processed this file and Brain Monkey can still redefine any of them for
 * the tests that assert on hooks.
 */

if ( ! function_exists( 'add_action' ) ) {
	function add_action() {
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter() {
		return true;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action() {
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return rtrim( dirname( $file ), '/\\' ) . '/';
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'http://example.test/' . basename( dirname( $file ) ) . '/';
	}
}

if ( ! function_exists( 'siteorigin_widget_register' ) ) {
	function siteorigin_widget_register() {
		return true;
	}
}

/**
 * Stand-in for the plugin's bundle singleton. SiteOrigin_Widget::is_preview()
 * asks it whether the current screen is the block editor.
 */
if ( ! class_exists( 'SiteOrigin_Widgets_Bundle' ) ) {
	class SiteOrigin_Widgets_Bundle {
		public static function single() {
			static $single;

			if ( empty( $single ) ) {
				$single = new self();
			}

			return $single;
		}

		public function is_block_editor() {
			return false;
		}

		public function clear_file_cache() {
		}

		/**
		 * Loads a widget file the way the plugin does for a widget that
		 * depends on another (the Hero widget requires the Button widget).
		 */
		public function include_widget( $widget_id ) {
			$file = dirname( __DIR__, 2 ) . '/widgets/' . $widget_id . '/' . $widget_id . '.php';

			if ( ! file_exists( $file ) ) {
				return false;
			}

			include_once $file;

			return true;
		}
	}
}

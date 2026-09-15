<?php
/**
 * PHPUnit bootstrap.
 *
 * Loading the autoloader here pulls in Patchwork (via Brain Monkey) before any
 * test file is read. Brain Monkey can only redefine a function if Patchwork was
 * loaded first, so anything that defines WordPress functions has to come after
 * this point.
 */

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Plugin files call add_action() at the top level, as they're written to run
 * inside WordPress. That happens while the file is being required, before any
 * test has had a chance to set Brain Monkey up, so add_action has to exist as a
 * real function by then.
 *
 * Declaring it here, after the autoloader, means Patchwork is already in place
 * and Brain Monkey can still redefine it for the tests that assert on hooks.
 */
if ( ! function_exists( 'add_action' ) ) {
	function add_action() {
		return true;
	}
}

/**
 * Stand-in for the widget base class, shared by every test file. Provides the
 * six-argument constructor, is_preview(), get_global_settings() and
 * get_style_hash() - the widest contract any widget under test needs. Widgets read global settings while
 * building their LESS variables, so the stand-in answers with an empty set.
 */
if ( ! class_exists( 'SiteOrigin_Widget' ) ) {
	class SiteOrigin_Widget {
		public function __construct( $id = '', $name = '', $widget_options = array(), $control_options = array(), $form_options = array(), $base_folder = false ) {
		}

		public function is_preview() {
			return false;
		}

		public function get_global_settings() {
			return array();
		}

		/**
		 * Mirrors SiteOrigin_Widget::get_style_hash() in base/siteorigin-widget.class.php
		 * for a widget that builds its hash from get_less_variables(): the first
		 * twelve characters of the md5 of the JSON-encoded variables and the widget
		 * version. The filters the real method applies are omitted, which is what
		 * the hash probes do too.
		 */
		public function get_style_hash( $instance ) {
			$vars    = $this->get_less_variables( $instance );
			$version = property_exists( $this, 'version' ) ? $this->version : '';

			return substr( md5( json_encode( $vars ) . $version ), 0, 12 );
		}
	}
}

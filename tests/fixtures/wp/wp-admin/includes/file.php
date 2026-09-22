<?php
/**
 * Fake of wp-admin/includes/file.php for the unit suite.
 *
 * SiteOrigin_Widget::delete_css() requires this file through ABSPATH and
 * then calls WP_Filesystem(). Returning true with an inert filesystem
 * object lets delete_css() run to the end, which is what puts
 * get_style_hash() and get_less_variables() on the save path under test.
 * Returning false would skip them and the tests would prove nothing.
 * save_css() is not reachable in the suite: it builds the CSS through the
 * LESS compiler and the plugin directory helpers before it touches the
 * filesystem, and the render tests run under the preview flag that skips it.
 */
if ( ! class_exists( 'SiteOrigin_Test_Filesystem' ) ) {
	class SiteOrigin_Test_Filesystem {
		public $deleted = array();
		public $written = array();

		public function delete( $file ) {
			$this->deleted[] = $file;

			return true;
		}

		public function is_dir( $path ) {
			return true;
		}

		public function mkdir( $path ) {
			return true;
		}

		public function put_contents( $file, $contents ) {
			$this->written[ $file ] = $contents;

			return true;
		}
	}
}

if ( ! function_exists( 'WP_Filesystem' ) ) {
	function WP_Filesystem() {
		if ( empty( $GLOBALS['wp_filesystem'] ) ) {
			$GLOBALS['wp_filesystem'] = new SiteOrigin_Test_Filesystem();
		}

		return true;
	}
}

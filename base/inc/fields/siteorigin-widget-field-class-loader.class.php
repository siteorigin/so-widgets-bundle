<?php

/**
 * Autoloader for widget field classes.
 *
 * Class SiteOrigin_Field_Class_Loader
 */
class SiteOrigin_Widget_Field_Class_Loader {

	static function init() {
		$field_class_loader = new SiteOrigin_Widget_Field_Class_Loader();
		$field_class_loader->register();
	}

	public function load_field_class( $field_classname ) {
		if( strpos( $field_classname, 'SiteOrigin_Widget_' ) === false ) return;
		$filename = strtolower( str_replace( '_', '-', $field_classname ) );
		// If it has the 'siteorigin-widget-field-' prefix, remove it to keep filenames neater.
		if ( strpos( $filename, 'siteorigin-widget-field-' ) !== false ) {
			$filename = str_replace( 'siteorigin-widget-field-', '', $filename );
		}
		$filepath = plugin_dir_path( __FILE__ ) . $filename . '.class.php';
		if ( file_exists( $filepath ) ) {
			require $filepath;
		}
	}

	public function register() {
		spl_autoload_register( array( $this, 'load_field_class' ) );
	}
}

SiteOrigin_Widget_Field_Class_Loader::init();
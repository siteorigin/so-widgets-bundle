<?php

/**
 * Autoloader for widget field classes.
 *
 * Class SiteOrigin_Field_Class_Loader
 */
class SiteOrigin_Widget_Field_Class_Loader {

	public function load_field_class( $field_classname ) {
		if( strpos( $field_classname, 'SiteOrigin_Widget_' ) === false ) return;
		$filename = strtolower( str_replace( '_', '-', $field_classname ) );
		$filepath = plugin_dir_path( __FILE__ ) . $filename . '.class.php';
		if ( file_exists( $filepath ) ) {
			require $filepath;
		}
	}

	public function register() {
		spl_autoload_register( array( $this, 'load_field_class' ) );
	}
}

$field_class_loader = new SiteOrigin_Widget_Field_Class_Loader();
$field_class_loader->register();

<?php

/**
 * Autoloader for widget field classes.
 *
 * Class SiteOrigin_Field_Class_Loader
 */
class SiteOrigin_Field_Class_Loader {

	public function load_field_class( $field_classname ) {
		$filename = strtolower( preg_replace( '_', '-', $field_classname ) );
		if ( file_exists( $filename ) ) {
			require plugin_dir_path( __FILE__ ) . $filename . '.class.php';;
		}
	}

	public function register() {
		spl_autoload_register( array( $this, 'load_field_class' ) );
	}
}

$field_class_loader = new SiteOrigin_Field_Class_Loader();
$field_class_loader->register();

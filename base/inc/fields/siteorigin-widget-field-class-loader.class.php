<?php

/**
 * Autoloader for widget field classes.
 *
 * Class SiteOrigin_Field_Class_Loader
 */
class SiteOrigin_Widget_Field_Class_Loader {

	private $class_prefixes;

	public function add_class_prefixes( $class_prefixes ) {
		if( !isset( $this->class_prefixes ) ) $this->class_prefixes = array();
		$this->class_prefixes = array_merge( $this->class_prefixes, $class_prefixes );
	}

	private $class_paths;

	public function add_class_paths( $class_paths ) {
		if( !isset( $this->class_paths ) ) $this->class_paths = array();
		$this->class_paths = array_merge( $this->class_paths, $class_paths );
	}

	public function load_field_class( $field_classname ) {
		$valid_classname = false;
		$class_prefix = '';
		foreach ( $this->class_prefixes as $class_prefix ) {
			$valid_classname = strpos( $field_classname, $class_prefix ) !== false;
			if( $valid_classname ) break;
		}
		if ( ! $valid_classname ) return;

		$filename = strtolower( str_replace( '_', '-', str_replace( $class_prefix, '', $field_classname ) ) );

		foreach( $this->class_paths as $class_path ) {
			$filepath = $class_path . $filename . '.class.php';
			if ( file_exists( $filepath ) ) {
				require_once $filepath;
			}
		}
	}

	public function register() {
		spl_autoload_register( array( $this, 'load_field_class' ) );
	}
}

function siteorigin_widgets_init_and_register_field_class_loader() {
	$field_class_loader = new SiteOrigin_Widget_Field_Class_Loader();
	$class_prefixes = array( 'SiteOrigin_Widget_Field_' );
	$class_prefixes = apply_filters( 'siteorigin_widgets_field_class_prefixes', $class_prefixes );
	$field_class_loader->add_class_prefixes( $class_prefixes );
	$class_paths = array( plugin_dir_path( __FILE__ ) );
	$class_paths = apply_filters( 'siteorigin_widgets_field_class_paths', $class_paths );
	$field_class_loader->add_class_paths( $class_paths );
	$field_class_loader->register();
}
add_action( 'init', 'siteorigin_widgets_init_and_register_field_class_loader', 1 );
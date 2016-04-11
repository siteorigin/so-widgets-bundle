<?php

/**
 * Autoloader for widget field classes.
 *
 * Class SiteOrigin_Field_Class_Loader
 */
class SiteOrigin_Widget_Field_Class_Loader {

	private $class_prefixes;
	private $class_paths;

	function __construct(){
		// Setup the loader with default prefixes and paths
		$this->add_class_prefixes( array( 'SiteOrigin_Widget_Field_' ) );
		$this->add_class_paths( array( plugin_dir_path( __FILE__ ) ) );
		spl_autoload_register( array( $this, 'load_field_class' ) );
	}

	static function single(){
		static $single;
		if( empty( $single ) ) {
			$single = new SiteOrigin_Widget_Field_Class_Loader();
		}

		return $single;
	}

	/**
	 * Regsiter class prefixes to watch for in this loader
	 *
	 * @param $class_prefixes
	 */
	public function add_class_prefixes( $class_prefixes ) {
		if( !isset( $this->class_prefixes ) ) $this->class_prefixes = array();
		$this->class_prefixes = array_merge( $this->class_prefixes, $class_prefixes );
	}

	/**
	 * Register paths where we'll look for these classes.
	 *
	 * @param $class_paths
	 */
	public function add_class_paths( $class_paths ) {
		if( !isset( $this->class_paths ) ) $this->class_paths = array();
		$this->class_paths = array_merge( $this->class_paths, $class_paths );
	}

	/**
	 * Load a class field. This is registered with spl_autoload_register
	 *
	 * @param $field_classname
	 */
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

	/**
	 * Initialize and register the class field loader
	 */
	public function extend(){
		$class_prefixes = apply_filters( 'siteorigin_widgets_field_class_prefixes', array() );
		$this->add_class_prefixes( $class_prefixes );

		$class_paths = apply_filters( 'siteorigin_widgets_field_class_paths', array() );
		$this->add_class_paths( $class_paths );
	}
}

add_action( 'init', array( SiteOrigin_Widget_Field_Class_Loader::single(), 'extend' ) );

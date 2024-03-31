<?php

/**
 * Autoloader for widget field classes.
 *
 * Class SiteOrigin_Field_Class_Loader
 */
class SiteOrigin_Widget_Field_Class_Loader {
	private $class_prefixes;
	private $class_paths;

	public function __construct() {
		// Setup the loader with default prefixes and paths
		$this->add_class_prefixes( array( 'SiteOrigin_Widget_Field_' ), 'base' );
		$this->add_class_paths( array( plugin_dir_path( __FILE__ ) ), 'base' );

		spl_autoload_register( array( $this, 'load_field_class' ) );
	}

	public static function single() {
		static $single;

		if ( empty( $single ) ) {
			$single = new SiteOrigin_Widget_Field_Class_Loader();
		}

		return $single;
	}

	/**
	 * Regsiter class prefixes to watch for in this loader
	 *
	 * @param string|array $class_prefixes
	 * @param string       $group
	 */
	public function add_class_prefixes( $class_prefixes, $group = 'base' ) {
		if ( ! isset( $this->class_prefixes ) ) {
			$this->class_prefixes = array();
		}

		if ( ! isset( $this->class_prefixes[$group] ) ) {
			$this->class_prefixes[$group] = array();
		}

		$this->class_prefixes[$group] = array_merge(
			$this->class_prefixes[$group],
			$class_prefixes
		);
		$this->class_prefixes[$group] = array_unique( $this->class_prefixes[$group] );
	}

	/**
	 * Register paths where we'll look for these classes.
	 *
	 * @param string|array $class_paths
	 * @param string       $group
	 */
	public function add_class_paths( $class_paths, $group = 'base' ) {
		if ( ! isset( $this->class_paths ) ) {
			$this->class_paths = array();
		}

		if ( ! isset( $this->class_paths[$group] ) ) {
			$this->class_paths[$group] = array();
		}

		$this->class_paths[$group] = array_merge(
			$this->class_paths[$group],
			$class_paths
		);
		$this->class_paths[$group] = array_unique( $this->class_paths[$group] );
	}

	/**
	 * Load a class field. This is registered with spl_autoload_register
	 */
	public function load_field_class( $field_classname ) {
		$valid_classname = false;
		$class_prefix = '';
		$class_group = '';

		foreach ( $this->class_prefixes as $class_group => $class_prefixes ) {
			foreach ( $class_prefixes as $class_prefix ) {
				$valid_classname = strpos( $field_classname, $class_prefix ) !== false;

				if ( $valid_classname ) {
					break 2;
				}
			}
		}

		if ( ! $valid_classname ) {
			return;
		}

		$filename = strtolower( str_replace( '_', '-', str_replace( $class_prefix, '', $field_classname ) ) );

		if ( empty( $this->class_paths[$class_group] ) ) {
			return;
		}

		foreach ( $this->class_paths[$class_group] as $class_path ) {
			$filepath = $class_path . $filename . '.class.php';

			if ( file_exists( $filepath ) ) {
				require_once $filepath;
				break;
			}
		}
	}

	/**
	 * Initialize and register the class field loader
	 */
	public function extend() {
		$this->add_class_prefixes(
			apply_filters( 'siteorigin_widgets_field_class_prefixes', array() ),
			'base'
		);

		$this->add_class_paths(
			apply_filters( 'siteorigin_widgets_field_class_paths', array() ),
			'base'
		);

		$this->class_prefixes = apply_filters( 'siteorigin_widgets_field_registered_class_prefixes', $this->class_prefixes );
		$this->class_paths = apply_filters( 'siteorigin_widgets_field_registered_class_paths', $this->class_paths );
	}
}

add_action( 'init', array( SiteOrigin_Widget_Field_Class_Loader::single(), 'extend' ) );

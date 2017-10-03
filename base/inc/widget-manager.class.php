<?php

/**
 * Handles registering Widgets Bundle widgets.
 *
 * Class SiteOrigin_Widgets_Widget_Manager
 */
class SiteOrigin_Widgets_Widget_Manager {
	/**
	 * Regsitered widgets
	 *
	 * @var
	 */
	private $registered;

	function __construct(){
		$this->registered = array();
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
	}

	/**
	 * Get the single instance.
	 *
	 * @return SiteOrigin_Widgets_Widget_Manager
	 */
	static function single() {
		static $single;

		if( empty($single) ) {
			$single = new self();
		}

		return $single;
	}

	/**
	 * @param $id
	 * @param $path
	 * @param bool|false $class
	 * @return mixed
	 */
	public function register( $id, $path, $class = false ){
		$path = wp_normalize_path( $path );
		if ( empty( $class ) ) {
			$class = 'SiteOrigin_Widget_' . str_replace( ' ', '', ucwords( str_replace('-', ' ', $id) ) ) . '_Widget';
		}

		$this->registered[ $id ] = new stdClass();
		$this->registered[ $id ]->path = $path;
		$this->registered[ $id ]->class = $class;
		$this->registered[ $id ]->registered = false;

		return $this->registered[ $id ];
	}

	/**
	 * Initialize all the widgets.
	 */
	public function widgets_init(){
		foreach( $this->registered as $id => & $info ) {
			if( $info->registered ) continue;
			register_widget( $info->class );
			$info->registered = true;
		}
	}

	/**
	 * Get the path of the widget
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public function get_plugin_path( $id ) {
		if( empty( $this->registered[ $id ] ) ) {
			// This call might be using the incorrect ID convention.
			if( substr($id, 0, 4) == 'sow-' ) $id = substr($id, 4);
			else $id = 'sow-' . $id;
		}

		return !empty($this->registered[$id]) ? $this->registered[$id]->path : false;
	}

	/**
	 * @param $id
	 *
	 * @return string
	 *
	 * @todo examine this when using a widget in a theme folder.
	 */
	function get_plugin_dir_path( $id ){
		return plugin_dir_path( $this->get_plugin_path( $id ) );
	}

	function get_plugin_dir_url( $id ){
		return plugin_dir_url( $this->get_plugin_path( $id ) );
	}

	/**
	 * Get a widget ID from a file path
	 *
	 * @param string $path The file path.
	 *
	 * @return string The ID.
	 */
	function get_id_from_path( $path ){
		foreach( $this->registered as $id => $r ) {
			if( $r->path == $path ) return $id;
		}
		return false;
	}

	/**
	 * Get the class name of a widget from the
	 *
	 * @param $path
	 * @return mixed
	 */
	function get_class_from_path( $path ) {
		foreach( $this->registered as $id => $r ) {
			if( $r->path == $path ) return $r->class;
		}
		return false;
	}
}
SiteOrigin_Widgets_Widget_Manager::single();

/**
 * Register a widget
 *
 * @param string $id The ID of the widget
 * @param string $path The path of the widget
 * @param bool|string $class The name of the class
 */
function siteorigin_widget_register( $id, $path, $class = false ){
	SiteOrigin_Widgets_Widget_Manager::single()->register( $id, $path, $class );
}

/**
 * Get the base file of a widget plugin
 *
 * @param $name
 * @return bool
 */
function siteorigin_widget_get_plugin_path($id){
	return SiteOrigin_Widgets_Widget_Manager::single()->get_plugin_path( $id );
}

/**
 * Get the base path folder of a widget plugin.
 *
 * @param $id
 * @return string
 */
function siteorigin_widget_get_plugin_dir_path($id){
	return SiteOrigin_Widgets_Widget_Manager::single()->get_plugin_dir_path( $id );
}

/**
 * Get the base path URL of a widget plugin.
 *
 * @param $id
 * @return string
 */
function siteorigin_widget_get_plugin_dir_url($id){
	return SiteOrigin_Widgets_Widget_Manager::single()->get_plugin_dir_url( $id );
}

<?php

/*
Plugin Name: SiteOrigin Widgets Bundle
Description: A collection of all our widgets, neatly bundled into a single plugin.
Version: trunk
Author: Greg Priday
Author URI: http://siteorigin.com
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
*/

define('SOW_BUNDLE_VERSION', 'trunk');

class SiteOrigin_Widgets_Bundle {
	function __construct(){
		add_action('admin_menu', array($this, 'admin_menu_init') );
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );

		// Initialize the widgets, but do it fairly late
		add_action( 'plugins_loaded', array($this, 'load_widget_plugins'), 1 );

		// These filters are used to activate any widgets that are missing.
		add_filter( 'siteorigin_panels_data', array($this, 'load_missing_widgets') );
		add_filter( 'siteorigin_panels_prebuilt_layout', array($this, 'load_missing_widgets') );


	}

	/**
	 * Get the single of this plugin
	 *
	 * @return SiteOrigin_Widgets_Bundle
	 */
	static function single() {
		static $single;

		if( empty($single) ) {
			$single = new SiteOrigin_Widgets_Bundle();
		}

		return $single;
	}

	function activate_plugin(){

	}

	/**
	 *
	 */
	function load_widget_plugins(){
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$active_widgets = get_option( 'siteorigin_widgets_active', array() );
		foreach( array_keys($active_widgets) as $widget_id ) {

			if( !is_plugin_active( $widget_id.'/'.$widget_id.'.php' ) ) {
				// Lets include this widget file
				include_once plugin_dir_path(__FILE__).'widgets/'.$widget_id.'/'.$widget_id.'.php';
			}
		}
	}

	/**
	 * Include the widget file. Do some checks to make sure we don't break things.
	 */
	function include_widget( $id ){

	}

	/**
	 * Initialize the admin
	 */
	function admin_init(){

	}

	/**
	 * Enqueue the admin page stuff.
	 */
	function admin_enqueue_scripts($prefix) {
		if( $prefix != 'plugins_page_so-widgets-plugins' ) return;
		wp_enqueue_style( 'siteorigin-widgets-bundle-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), SOW_BUNDLE_VERSION );
	}

	/**
	 * Add the admin menu page.
	 */
	function admin_menu_init(){
		add_plugins_page(
			__('SiteOrigin Widgets', 'siteorigin-widgets'),
			__('SiteOrigin Widgets', 'siteorigin-widgets'),
			'install_plugins',
			'so-widgets-plugins',
			array($this, 'admin_page')
		);
	}

	/**
	 * Display the admin page.
	 */
	function admin_page(){
		// Lets start by checking if we're activating a widget
		if( isset($_GET['widget_action']) ) {

			switch($_GET['widget_action']) {
				case 'activate':
					$this->activate_widget( $_GET['widget'] );
					?><div class="updated"><p>Plugin Activated</p></div><?php
					break;

				case 'deactivate':
					$this->deactivate_widget( $_GET['widget'] );
					?><div class="updated"><p>Plugin Deactivated</p></div><?php
					break;
			}

		}

		include plugin_dir_path(__FILE__).'tpl/admin.php';
	}

	/**
	 * Activate a widget
	 *
	 * @param $widget_id
	 *
	 * @return bool
	 */
	function activate_widget( $widget_id ){
		if( !file_exists( plugin_dir_path(__FILE__).'widgets/'.$widget_id.'/'.$widget_id.'.php' ) ) return false;

		// There are times when we activate several widgets at once, so clear the cache.
		// wp_cache_delete( 'siteorigin_widgets_active', 'options' );
		$active_widgets = get_option( 'siteorigin_widgets_active', array() );
		$active_widgets[$widget_id] = true;
		update_option( 'siteorigin_widgets_active', $active_widgets );

		// Load the widget loader and the base if they don't already exist.
		if( !class_exists('SiteOrigin_Widgets_Loader') ) include(plugin_dir_path(__FILE__).'base/loader.php');
		if( !defined('SITEORIGIN_WIDGETS_BASE_PARENT_FILE') ) {
			define('SITEORIGIN_WIDGETS_BASE_PARENT_FILE', __FILE__);
			define('SITEORIGIN_WIDGETS_BASE_VERSION', include plugin_dir_path(__FILE__).'/base/version.php' );
			include plugin_dir_path(__FILE__).'base/inc.php';
			do_action('siteorigin_widgets_base_loaded');
		}

		// Now, lets actually include the file
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if( !is_plugin_active( $widget_id.'/'.$widget_id.'.php' ) ) {
			// Lets include this widget file
			$loader = include_once plugin_dir_path(__FILE__).'widgets/'.$widget_id.'/'.$widget_id.'.php';

			if( is_a($loader, 'SiteOrigin_Widgets_Loader') ) {
				if ( has_action( 'siteorigin_widgets_base_loaded' ) ) $loader->load_register();
				if ( has_action( 'widgets_init' ) ) $loader->widgets_init();
			}

		}

		return true;
	}

	/**
	 * Activate a widget
	 *
	 * @param $id
	 */
	function deactivate_widget($id){
		$active_widgets = get_option( 'siteorigin_widgets_active', array() );
		unset($active_widgets[$id]);
		update_option( 'siteorigin_widgets_active', $active_widgets );
	}

	/**
	 * Gets a list of all available widgets
	 */
	function get_widgets_list(){
		$active = get_option('siteorigin_widgets_active', array());
		$widgets = get_plugins( '/so-widgets-bundle/widgets' );

		foreach($widgets as $file => $widget) {
			$f = pathinfo($file);
			$id = $f['filename'];

			$widgets[$file]['ID'] = $id;
			$widgets[$file]['Active'] = !empty($active[$id]);
		}

		return $widgets;
	}

	function load_missing_widgets($data){
		if(empty($data['widgets'])) return $data;

		global $wp_widget_factory;

		foreach($data['widgets'] as $widget) {
			if( empty($widget['info']['class']) ) continue;
			if( !empty($wp_widget_factory->widgets[$widget['info']['class']] ) ) continue;

			$class = $widget['info']['class'];
			if( preg_match('/SiteOrigin_Widget_([A-Za-z]+)_Widget/', $class, $matches) ) {
				$name = $matches[1];
				$id = 'so'.strtolower( implode( '-', preg_split('/(?=[A-Z])/',$name) ) ).'-widget';

				$this->activate_widget($id, $class);
			}
		}

		return $data;
	}
}

SiteOrigin_Widgets_Bundle::single();
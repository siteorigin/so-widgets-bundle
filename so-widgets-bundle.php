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

// Include the icons if they exist and we haven't already
if( !defined('SITEORIGIN_WIDGETS_ICONS') && file_exists( plugin_dir_path(__FILE__).'/icons/icons.php' ) ) include plugin_dir_path(__FILE__).'/icons/icons.php';
if( !class_exists('SiteOrigin_Widgets_Loader') ) include(plugin_dir_path(__FILE__).'base/loader.php');

class SiteOrigin_Widgets_Bundle {

	private $widget_folders;

	function __construct(){
		add_action('admin_init', array($this, 'admin_activate_widget') );
		add_action('admin_menu', array($this, 'admin_menu_init') );
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );
		add_action('wp_ajax_so_widgets_bundle_manage', array($this, 'admin_ajax_manage_handler') );

		// Initialize the widgets, but do it fairly late
		add_action( 'plugins_loaded', array($this, 'load_widget_plugins'), 1 );

		// Add the action links.
		add_action( 'plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links') );

		// These filters are used to activate any widgets that are missing.
		add_filter( 'siteorigin_panels_data', array($this, 'load_missing_widgets') );
		add_filter( 'siteorigin_panels_prebuilt_layout', array($this, 'load_missing_widgets') );
		add_filter( 'siteorigin_panels_widget_object', array($this, 'load_missing_widget'), 10, 2 );

		$this->widget_folders = apply_filters('siteorigin_widgets_widget_folders', array(
			plugin_dir_path(__FILE__).'widgets/'
		) );
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

	/**
	 * Load all the widgets if their plugins aren't already active.
	 */
	function load_widget_plugins(){

		$run_base_loaded = false;
		if( !defined('SITEORIGIN_WIDGETS_BASE_PARENT_FILE') ) {
			// Always give preference to the base inside the bundle.
			define( 'SITEORIGIN_WIDGETS_BASE_PARENT_FILE', __FILE__ );
			define( 'SITEORIGIN_WIDGETS_BASE_VERSION', include plugin_dir_path( __FILE__ ) . 'base/version.php' );
			include plugin_dir_path( __FILE__ ) . 'base/inc.php';

			$run_base_loaded = true;
		}

		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$active_widgets = get_option( 'siteorigin_widgets_active', array() );
		foreach( array_keys($active_widgets) as $widget_id ) {

			if( !is_plugin_active( $widget_id.'/'.$widget_id.'.php' ) ) {

				foreach( $this->widget_folders as $folder ) {
					if( !file_exists($folder . $widget_id.'/'.$widget_id.'.php') ) continue;

					// Lets include this widget file
					include_once $folder . $widget_id.'/'.$widget_id.'.php';
				}
			}

		}

		if($run_base_loaded) do_action( 'siteorigin_widgets_base_loaded' );
	}

	/**
	 * Enqueue the admin page stuff.
	 */
	function admin_enqueue_scripts($prefix) {
		if( $prefix != 'plugins_page_so-widgets-plugins' ) return;
		wp_enqueue_style( 'siteorigin-widgets-bundle-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), SOW_BUNDLE_VERSION );
		wp_enqueue_script( 'siteorigin-widgets-bundle-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array(), SOW_BUNDLE_VERSION );
	}

	function admin_activate_widget() {
		if(
			!empty($_GET['page'])
			&& $_GET['page'] == 'so-widgets-plugins'
			&& !empty( $_GET['widget_action'] ) && !empty( $_GET['widget'] )
			&& isset($_GET['_wpnonce'])
			&& wp_verify_nonce($_GET['_wpnonce'], 'siteorigin_widget_action')
		) {

			switch($_GET['widget_action']) {
				case 'activate':
					$this->activate_widget( $_GET['widget'] );
					break;

				case 'deactivate':
					$this->deactivate_widget( $_GET['widget'] );
					break;
			}

			// Redirect and clear all the args
			wp_redirect( add_query_arg( array(
				'_wpnonce' => false,
				'widget_action_done' => 'true',
			) ) );

		}
	}

	/**
	 * Handler for activating and deactivating widgets.
	 */
	function admin_ajax_manage_handler(){
		if(!wp_verify_nonce($_GET['_wpnonce'], 'manage_so_widget')) exit();
		if(!current_user_can('install_plugins')) exit();
		if(empty($_GET['widget'])) exit();

		if( $_POST['active'] == 'true' ) $this->activate_widget($_GET['widget']);
		else $this->deactivate_widget($_GET['widget']);

		// Send a kind of dummy response.
		header('content-type: application/json');
		echo json_encode(array('done' => true));
		exit();
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

		$bundle = SiteOrigin_Widgets_Bundle::single();
		$widgets = $bundle->get_widgets_list();

		if(
			isset($_GET['widget_action_done'])
			&& !empty($_GET['widget_action'])
			&& !empty($_GET['widget'])
			&& !empty( $widgets[ $_GET['widget'].'/'.$_GET['widget'].'.php' ] )
		) {

			?>
			<div class="updated">
				<p>
				<?php
				printf(
					__('%s was %s', 'siteorigin-widgets'),
					$widgets[ $_GET['widget'].'/'.$_GET['widget'].'.php' ]['Name'],
					$_GET['widget_action'] == 'activate' ? __('Activated', 'siteorigin-widgets') : __('Deactivated', 'siteorigin-widgets')

				)
				?>
				</p>
			</div>
			<?php
		}

		include plugin_dir_path(__FILE__).'tpl/admin.php';
	}

	/**
	 * Activate a widget
	 *
	 * @param $widget_id
	 *
	 * @param bool $include
	 *
	 * @return bool
	 */
	function activate_widget( $widget_id, $include = true ){
		$exists = false;
		foreach( $this->widget_folders as $folder ) {
			if( !file_exists($folder . $widget_id . '/' . $widget_id . '.php') ) continue;
			$exists = true;
		}

		if( !$exists ) return false;

		// There are times when we activate several widgets at once, so clear the cache.
		// wp_cache_delete( 'siteorigin_widgets_active', 'options' );
		$active_widgets = get_option( 'siteorigin_widgets_active', array() );
		$active_widgets[$widget_id] = true;
		update_option( 'siteorigin_widgets_active', $active_widgets );

		// If we don't want to include the widget files, then our job here is done.
		if(!$include) return;

		// Load the widget loader and the base if they don't already exist.

		if( !defined('SITEORIGIN_WIDGETS_BASE_PARENT_FILE') ) {
			define('SITEORIGIN_WIDGETS_BASE_PARENT_FILE', __FILE__);
			define('SITEORIGIN_WIDGETS_BASE_VERSION', include plugin_dir_path(__FILE__).'/base/version.php' );
			include plugin_dir_path(__FILE__).'base/inc.php';
			do_action( 'siteorigin_widgets_base_loaded' );
		}

		// Now, lets actually include the files
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if( !is_plugin_active( $widget_id.'/'.$widget_id.'.php' ) ) {

			$loader = false;
			foreach( $this->widget_folders as $folder ) {
				if( !file_exists($folder . $widget_id . '/' . $widget_id . '.php') ) continue;
				$loader = include_once $folder . $widget_id . '/' . $widget_id . '.php';
			}

			// Call any loader functions that missed their actions.
			if( !empty($loader) && is_a($loader, 'SiteOrigin_Widgets_Loader') ) {
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

		$widgets = array();
		foreach($this->widget_folders as $folder) {

			$folder = str_replace(WP_PLUGIN_DIR, '', $folder);
			foreach( get_plugins( $folder ) as $file => $widget ) {
				$f = pathinfo($file);
				$id = $f['filename'];

				$widget['ID'] = $id;
				$widget['Active'] = !empty($active[$id]);

				$widgets[$file] = $widget;
			}

		}

		// Sort the widgets alphabetically
		uasort($widgets, array($this, 'widget_uasort'));

		return $widgets;
	}

	function widget_uasort($widget_a, $widget_b) {
		return $widget_a['Name'] > $widget_b['Name'] ? 1 : -1;
	}

	/**
	 * Look in Page Builder data for
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
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

	/**
	 * Attempt to load a missing widget
	 *
	 * @param $the_widget
	 * @param $class
	 *
	 * @return
	 * @internal param $widget_class
	 */
	function load_missing_widget($the_widget, $class){
		// We only want to worry about missing widgets
		if( !empty($the_widget) ) return $the_widget;

		if( preg_match('/SiteOrigin_Widget_([A-Za-z]+)_Widget/', $class, $matches) ) {
			$name = $matches[1];
			$id = 'so'.strtolower( implode( '-', preg_split('/(?=[A-Z])/',$name) ) ).'-widget';

			$this->activate_widget($id, $class);
			global $wp_widget_factory;
			if( !empty($wp_widget_factory->widgets[$class]) ) return $wp_widget_factory->widgets[$class];
		}

		return $the_widget;
	}

	/**
	 * Add action links.
	 */
	function plugin_action_links($links){
		$links[] = '<a href="' . admin_url('plugins.php?page=so-widgets-plugins') . '">'.__('Manage Widgets', 'siteorigin-widgets').'</a>';
		$links[] = '<a href="http://siteorigin.com/thread/" target="_blank">'.__('Support', 'siteorigin-widgets').'</a>';
		return $links;
	}
}

// create the initial single
SiteOrigin_Widgets_Bundle::single();

/**
 * Handles activation of Widget Bundle plugin.
 */
function siteorigin_widgets_bundle_plugin_activate(){
	// Deactivate any legacy plugins and activate the bundled version.
	$bundle = SiteOrigin_Widgets_Bundle::single();
	$widgets = $bundle->get_widgets_list();

	global $deactivate_plugins;
	if(empty($deactivate_plugins)) $deactivate_plugins = array();

	foreach($widgets as $id => $widget) {
		if( is_plugin_active($id) ) {
			$deactivate_plugins[] = $id;
		}
	}
	if(!empty($deactivate_plugins)) add_action('shutdown', 'siteorigin_widgets_bundle_plugin_activate_shutdown');
}
register_activation_hook(__FILE__, 'siteorigin_widgets_bundle_plugin_activate');

/**
 * Run this on shutdown if we have any plugins to deactivate.
 */
function siteorigin_widgets_bundle_plugin_activate_shutdown(){
	global $deactivate_plugins;
	if(empty($deactivate_plugins)) return;

	$bundle = SiteOrigin_Widgets_Bundle::single();
	foreach((array) $deactivate_plugins as $plugin) {
		deactivate_plugins($plugin);
		list($widget_id, $file) = explode('/', $plugin, 2);
		$bundle->activate_widget($widget_id, false);
	}
}
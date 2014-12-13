<?php

/*
Plugin Name: SiteOrigin Widgets Bundle
Description: A collection of all our widgets, neatly bundled into a single plugin.
Version: 1.1
Author: SiteOrigin
Author URI: http://siteorigin.com
Plugin URI: http://siteorigin.com/widgets-bundle/
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
*/

define('SOW_BUNDLE_VERSION', '1.1');
define('SOW_BUNDLE_BASE_FILE', __FILE__);

// We're going to include this check until version 1.2
if( !function_exists('siteorigin_widget_get_plugin_path') ) {
	include plugin_dir_path(__FILE__).'base/inc.php';
	include plugin_dir_path(__FILE__).'icons/icons.php';
}


class SiteOrigin_Widgets_Bundle {

	private $widget_folders;

	/**
	 * @var array The array of default widgets.
	 */
	static $default_active_widgets = array(
		'so-button-widget' => true,
		'so-google-map-widget' => true,
		'so-image-widget' => true,
		'so-slider-widget' => true,
		'so-post-carousel-widget' => true,
	);

	function __construct(){
		add_action('admin_init', array($this, 'admin_activate_widget') );
		add_action('admin_menu', array($this, 'admin_menu_init') );
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );
		add_action('wp_ajax_so_widgets_bundle_manage', array($this, 'admin_ajax_manage_handler') );

		// Initialize the widgets, but do it fairly late
		add_action( 'plugins_loaded', array($this, 'set_plugin_textdomain'), 1 );
		add_action( 'plugins_loaded', array($this, 'load_widget_plugins'), 1 );

		// Add the action links.
		add_action( 'plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links') );

		// Version check for cache clearing
		add_action( 'admin_init', array($this, 'plugin_version_check') );

		// These filters are used to activate any widgets that are missing.
		add_filter( 'siteorigin_panels_data', array($this, 'load_missing_widgets') );
		add_filter( 'siteorigin_panels_prebuilt_layout', array($this, 'load_missing_widgets') );
		add_filter( 'siteorigin_panels_widget_object', array($this, 'load_missing_widget'), 10, 2 );
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
	 * Set the text domain for the plugin
	 *
	 * @action plugins_loaded
	 */
	function set_plugin_textdomain(){
		load_plugin_textdomain('siteorigin-widgets', false, dirname( plugin_basename( __FILE__ ) ). '/languages/');
	}

	/**
	 * This clears the file cache.
	 *
	 * @action admin_init
	 */
	function plugin_version_check(){

		$active_version = get_option( 'siteorigin_widget_bundle_version' );

		if( empty($active_version) || version_compare( $active_version, SOW_BUNDLE_VERSION, '<' ) ) {
			// If this is a new version, then clear the cache.
			update_option( 'siteorigin_widget_bundle_version', SOW_BUNDLE_VERSION );
			siteorigin_widgets_deactivate_legacy_plugins();

			// Remove all cached CSS for SiteOrigin Widgets
			if( function_exists('WP_Filesystem') && WP_Filesystem() ) {
				global $wp_filesystem;
				$upload_dir = wp_upload_dir();

				// Remove any old widget cache files, if they exist.
				$list = $wp_filesystem->dirlist( $upload_dir['basedir'] . '/siteorigin-widgets/' );
				if( !empty($list) ) {
					foreach($list as $file) {
						// Delete the file
						$wp_filesystem->delete( $upload_dir['basedir'] . '/siteorigin-widgets/' . $file['name'] );
					}
				}
			}

			// An action to let widgets handle the updates.
			do_action( 'siteorigin_widgets_version_update', SOW_BUNDLE_VERSION, $active_version );
		}

	}

	/**
	 * Load all the widgets if their plugins are not already active.
	 *
	 * @action plugins_loaded
	 */
	function load_widget_plugins(){

		if( empty($this->widget_folders) ) {
			// We can use this filter to add more folders to use for widgets
			$this->widget_folders = apply_filters('siteorigin_widgets_widget_folders', array(
				plugin_dir_path(__FILE__).'widgets/'
			) );
		}

		// Load all the widget we currently have active and filter them
		$active_widgets = $this->get_active_widgets();

		foreach( array_keys($active_widgets) as $widget_id ) {

			foreach( $this->widget_folders as $folder ) {
				if ( !file_exists($folder . $widget_id.'/'.$widget_id.'.php') ) continue;

				// Include this widget file
				include_once $folder . $widget_id.'/'.$widget_id.'.php';
			}

		}
	}

	/**
	 * Get a list of currently active widgets.
	 *
	 * @param bool $filter
	 *
	 * @return mixed|void
	 */
	function get_active_widgets( $filter = true ){
		// Load all the widget we currently have active and filter them
		$active_widgets = get_option( 'siteorigin_widgets_active', self::$default_active_widgets );
		if($filter) {
			$active_widgets = apply_filters( 'siteorigin_widgets_active_widgets',  $active_widgets);
		}

		return $active_widgets;
	}

	/**
	 * Enqueue the admin page stuff.
	 */
	function admin_enqueue_scripts($prefix) {
		if( $prefix != 'plugins_page_so-widgets-plugins' ) return;
		wp_enqueue_style( 'siteorigin-widgets-bundle-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), SOW_BUNDLE_VERSION );
		wp_enqueue_script( 'siteorigin-widgets-bundle-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array(), SOW_BUNDLE_VERSION );
	}

	/**
	 * The fallback (from ajax) URL handler for activating or deactivating a widget
	 */
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
	 *
	 * @action wp_ajax_so_widgets_bundle_manage
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
	 *
	 * @action admin_menu
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
	 * @param string $widget_id The ID of the widget that we're activating.
	 * @param bool $include Should we include the widget, to make it available in the current request.
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
		wp_cache_delete( 'siteorigin_widgets_active', 'options' );
		$active_widgets = $this->get_active_widgets();
		$active_widgets[$widget_id] = true;
		update_option( 'siteorigin_widgets_active', $active_widgets );

		// If we don't want to include the widget files, then our job here is done.
		if(!$include) return;

		// Now, lets actually include the files
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		foreach( $this->widget_folders as $folder ) {
			if( !file_exists($folder . $widget_id . '/' . $widget_id . '.php') ) continue;
			include_once $folder . $widget_id . '/' . $widget_id . '.php';

			if( has_action('widgets_init') ) {
				siteorigin_widgets_widgets_init();
			}
		}


		return true;
	}

	/**
	 * Deactivate a widget
	 *
	 * @param $id
	 */
	function deactivate_widget($id){
		$active_widgets = $this->get_active_widgets();
		unset($active_widgets[$id]);
		update_option( 'siteorigin_widgets_active', $active_widgets );
	}

	/**
	 * Gets a list of all available widgets
	 */
	function get_widgets_list(){
		$active = $this->get_active_widgets();

		$default_headers = array(
			'Name' => 'Widget Name',
			'Description' => 'Description',
			'Author' => 'Author',
			'AuthorURI' => 'Author URI',
			'WidgetURI' => 'Widget URI',
			'VideoURI' => 'Video URI',
		);

		$widgets = array();
		foreach($this->widget_folders as $folder) {

			$files = glob( $folder.'*/*.php' );
			foreach($files as $file) {
				$widget = get_file_data( $file, $default_headers, 'siteorigin-widget' );
				$f = pathinfo($file);
				$id = $f['filename'];

				$widget['ID'] = $id;
				$widget['Active'] = !empty($active[$id]);
				$widget['File'] = $file;

				$widgets[$file] = $widget;
			}

		}

		// Sort the widgets alphabetically
		uasort( $widgets, array($this, 'widget_uasort') );
		return $widgets;
	}

	/**
	 * Sorting function to sort widgets by name
	 *
	 * @param $widget_a
	 * @param $widget_b
	 *
	 * @return int
	 */
	function widget_uasort($widget_a, $widget_b) {
		return $widget_a['Name'] > $widget_b['Name'] ? 1 : -1;
	}

	/**
	 * Look in Page Builder data for any missing widgets.
	 *
	 * @param $data
	 *
	 * @return mixed
	 *
	 * @action siteorigin_panels_data
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

				$this->activate_widget($id, true);
			}
		}

		return $data;
	}

	/**
	 * Attempt to load a single missing widget.
	 *
	 * @param $the_widget
	 * @param $class
	 *
	 * @return
	 */
	function load_missing_widget($the_widget, $class){
		// We only want to worry about missing widgets
		if( !empty($the_widget) ) return $the_widget;

		if( preg_match('/SiteOrigin_Widget_([A-Za-z]+)_Widget/', $class, $matches) ) {
			$name = $matches[1];
			$id = 'so'.strtolower( implode( '-', preg_split('/(?=[A-Z])/',$name) ) ).'-widget';

			$this->activate_widget($id, true);
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
 * Deactivate any old widget plugins that we used to have on the directory. We'll remove this after version 1.2.
 */
function siteorigin_widgets_deactivate_legacy_plugins(){
	// All we want to do here is disable all legacy widgets
	$the_plugins = get_option('active_plugins');
	foreach($the_plugins as $plugin_id) {
		if( preg_match('/^so-([a-z\-]+)-widget\/so-([a-z\-]+)-widget\.php$/', $plugin_id) ) {
			// Deactivate the legacy plugin
			deactivate_plugins($plugin_id, true);
		}
	}
}
register_activation_hook( __FILE__, 'siteorigin_widgets_deactivate_legacy_plugins' );
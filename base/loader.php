<?php

/**
 * Class SiteOrigin_Widgets_Loader
 *
 * The loader class handles all basic setup tasks for SiteOrigin widgets.
 */
class SiteOrigin_Widgets_Loader {
	private $file;
	private $widget_id;
	private $load_file;
	private $version;

	/**
	 * @param string $widget_id The widget ID
	 * @param string $file The current file
	 * @param string $load_file File that's loaded after the widget base is loaded
	 * @param bool $version
	 */
	function __construct($widget_id, $file, $load_file, $version = false){
		if( empty($version) ) $version = SOW_BUNDLE_VERSION;

		$this->file = $file;
		$this->widget_id = $widget_id;
		$this->load_file = $load_file;
		$this->version = $version;

		add_filter( 'siteorigin_widgets_include_version', array($this, 'version_filter') );
		add_action( 'plugins_loaded', array($this, 'init'), 5 );
		add_action( 'siteorigin_widgets_base_loaded', array($this, 'load_register') );
		add_action( 'widgets_init', array($this, 'widgets_init') );

		if( !empty($this->version) ) {
			add_action( 'admin_init', array($this, 'plugin_version_check') );
		}
	}

	/**
	 * Check the version we're running. Clear the cache and do the update action if we're running a new version.
	 */
	function plugin_version_check( ){
		$active_version = get_option( 'siteorigin_widget_version[' . $this->widget_id . ']' );

		if( empty($active_version) || version_compare( $active_version, $this->version, '<' ) ) {
			// If this is a new version, then clear the cache.
			update_option( 'siteorigin_widget_version[' . $this->widget_id . ']', $this->version );

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
			do_action( 'siteorigin_widgets_version_update_'.plugin_basename($this->file), $this->version, $active_version );
		}

	}

	/**
	 * Lets the current loader know which version we're running.
	 *
	 * @filter siteorigin_widgets_include_version
	 * @param $versions
	 * @return mixed
	 */
	function version_filter($versions){
		if( !file_exists(plugin_dir_path($this->file).'base/version.php') ) return $versions;
		$versions[ plugin_basename($this->file) ] = include( plugin_dir_path($this->file).'base/version.php' );
		return $versions;
	}

	/**
	 * Initialize the base using which ever plugin has the highest version.
	 *
	 * @action plugins_loaded
	 */
	function init(){
		if( defined('SITEORIGIN_WIDGETS_BASE_PARENT_FILE') ) return;

		global $siteorigin_widget_include_versions;
		if( empty( $siteorigin_widget_include_versions ) ) {
			$siteorigin_widget_include_versions = apply_filters( 'siteorigin_widgets_include_version', array() );
			uasort($siteorigin_widget_include_versions, 'version_compare');
		}

		if( is_array($siteorigin_widget_include_versions) ) {
			$keys = array_keys($siteorigin_widget_include_versions);
			if( $keys[count($keys) - 1] == plugin_basename($this->file) ) {

				define('SITEORIGIN_WIDGETS_BASE_PARENT_FILE', $this->file);
				define('SITEORIGIN_WIDGETS_BASE_VERSION', $siteorigin_widget_include_versions[plugin_basename($this->file)]);
				include plugin_dir_path($this->file).'base/inc.php';
				do_action('siteorigin_widgets_base_loaded');
			}
		}
	}

	/**
	 * Register the widget and load its main include file.
	 *
	 * @action siteorigin_widgets_base_loaded
	 */
	function load_register(){
		siteorigin_widget_register_self($this->widget_id, $this->file);
		if( !class_exists( $this->get_class_name() ) ) {
			require_once $this->load_file;
		}
	}

	/**
	 * Registers the widget that was created in this widget plugin.
	 *
	 * @action widgets_init
	 */
	function widgets_init(){
		$class_name = $this->get_class_name();
		if( class_exists( $class_name ) ) {
			register_widget($class_name);
		}
	}

	/**
	 * Get the name of the class used for this widget.
	 *
	 * @return string
	 */
	function get_class_name(){
		return 'SiteOrigin_Widget_' . str_replace( ' ', '', ucwords( str_replace('-', ' ', $this->widget_id) ) ) . '_Widget';
	}

}
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

class SiteOrigin_Widgets_Bundle {
	function __construct(){
		add_action('admin_menu', array($this, 'admin_menu_init') );
	}

	function activate(){

	}

	function init(){

	}

	function admin_init(){

	}

	function admin_menu_init(){
		add_plugins_page(
			__('SiteOrigin Widgets', 'siteorigin-widgets'),
			__('SiteOrigin Widgets', 'siteorigin-widgets'),
			'install_plugins',
			'so-widgets-plugins',
			array($this, 'admin_page')
		);
	}

	function admin_page(){
		include plugin_dir_path(__FILE__).'tpl/admin.php';
	}
}
global $siteorigin_widgets_bundle;
$siteorigin_widgets_bundle = new SiteOrigin_Widgets_Bundle();
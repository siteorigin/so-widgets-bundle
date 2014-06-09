<?php
/*
Plugin Name: Features Widget
Description: Displays a block of features with icons.
Version: trunk
Author: Greg Priday
Author URI: http://siteorigin.com
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
*/

return new SiteOrigin_Widgets_Loader( 'features', __FILE__, plugin_dir_path(__FILE__).'inc/features-widget.php' );
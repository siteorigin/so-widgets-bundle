<?php
/*
Widget Name: Post Carousel Widget
Description: Gives you a widget to display your posts as a carousel.
Version: trunk
Author: Greg Priday
Author URI: http://siteorigin.com
*/

/**
 * Add the carousel image sizes
 */
function sow_carousel_register_image_sizes(){
	add_image_size('sow-carousel-default', 272, 182, true);
}
add_action('init', 'sow_carousel_register_image_sizes');

return new SiteOrigin_Widgets_Loader('post-carousel', __FILE__, plugin_dir_path(__FILE__).'inc/carousel-widget.php');
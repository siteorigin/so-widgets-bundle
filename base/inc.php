<?php

include plugin_dir_path(__FILE__).'siteorigin-widget.class.php';
include plugin_dir_path(__FILE__).'inc/post-selector.php';

global $siteorigin_widgets_registered, $siteorigin_widgets_classes;
$siteorigin_widgets_registered = array();
$siteorigin_widgets_classes = array();

/**
 * Register a plugin
 *
 * @param $name
 * @param $path
 * @param $class
 */
function siteorigin_widget_register($name, $path, $class = false){
	global $siteorigin_widgets_registered, $siteorigin_widgets_classes;
	$siteorigin_widgets_registered[$name] = realpath( $path );
	if ( empty( $class ) ) {
		$class = 'SiteOrigin_Widget_' . str_replace( ' ', '', ucwords( str_replace('-', ' ', $name) ) ) . '_Widget';
	}
	$siteorigin_widgets_classes[] = $class;
}

/**
 * Initialize all widgets
 */
function siteorigin_widgets_widgets_init(){
	global $siteorigin_widgets_classes;
	foreach( $siteorigin_widgets_classes as $class ){
		register_widget($class);
	}
	$siteorigin_widgets_classes = array();
}
add_action('widgets_init', 'siteorigin_widgets_widgets_init');

/**
 * Get the base file of a widget plugin
 *
 * @param $name
 * @return bool
 */
function siteorigin_widget_get_plugin_path($name){
	global $siteorigin_widgets_registered;
	return isset($siteorigin_widgets_registered[$name]) ? $siteorigin_widgets_registered[$name] : false;
}

/**
 * Get the base path folder of a widget plugin.
 *
 * @param $name
 * @return string
 */
function siteorigin_widget_get_plugin_dir_path($name){
	if( strpos($name, 'sow-') === 0 ) $name = substr($name, 4); // Handle raw widget IDs, assuming they're prefixed with sow-
	return plugin_dir_path( siteorigin_widget_get_plugin_path($name) );
}

/**
 * Get the base path URL of a widget plugin.
 *
 * @param $name
 * @return string
 */
function siteorigin_widget_get_plugin_dir_url($name){
	return plugin_dir_url( siteorigin_widget_get_plugin_path($name) );
}

/**
 * Render a preview of the widget.
 */
function siteorigin_widget_render_preview(){
	$class = $_GET['class'];


	if(isset($_POST['widgets'])) {
		$instance = array_pop($_POST['widgets']);
	}
	else {

		foreach($_POST as $n => $v) {
			if(strpos($n, 'widget-') === 0) {
				$instance = array_pop($_POST[$n]);
				break;
			}
		}

	}

	if(!class_exists($class)) exit();
	$widget_obj = new $class();
	if( ! $widget_obj instanceof SiteOrigin_Widget ) exit();

	$instance = $widget_obj->update($instance, $instance);
	$instance['style_hash'] = 'preview';
	include plugin_dir_path(__FILE__).'/inc/preview.tpl.php';

	exit();
}
add_action('wp_ajax_siteorigin_widget_preview', 'siteorigin_widget_render_preview');

/**
 * @param $css
 */
function siteorigin_widget_add_inline_css($css){
	global $siteorigin_widgets_inline_styles;
	if(empty($siteorigin_widgets_inline_styles)) $siteorigin_widgets_inline_styles = '';

	$siteorigin_widgets_inline_styles .= $css;
}

/**
 * Print any inline styles that have been added with siteorigin_widget_add_inline_css
 */
function siteorigin_widget_print_styles(){
	global $siteorigin_widgets_inline_styles;
	if(!empty($siteorigin_widgets_inline_styles)) {
		?><style type="text/css"><?php echo($siteorigin_widgets_inline_styles) ?></style><?php
	}

	$siteorigin_widgets_inline_styles = '';
}
add_action('wp_head', 'siteorigin_widget_print_styles');
add_action('wp_footer', 'siteorigin_widget_print_styles');

/**
 * The ajax handler for getting a list of available icons.
 */
function siteorigin_widget_get_icon_list(){
	if(empty($_GET['family'])) exit();

	$widget_icon_families = apply_filters('siteorigin_widgets_icon_families', array() );

	header('content-type: application/json');
	echo json_encode( !empty($widget_icon_families[$_GET['family']]) ? $widget_icon_families[$_GET['family']] : array() );
	exit();
}
add_action('wp_ajax_siteorigin_widgets_get_icons', 'siteorigin_widget_get_icon_list');

/**
 * @param $icon_value
 * @param bool $icon_styles
 *
 * @return bool|string
 */
function siteorigin_widget_get_icon($icon_value, $icon_styles = false) {
	if( empty( $icon_value ) ) return false;
	list( $family, $icon ) = explode('-', $icon_value, 2);
	if( empty( $family ) || empty( $icon ) ) return false;

	static $widget_icon_families;
	static $widget_icons_enqueued = array();

	if( empty($widget_icon_families) ) $widget_icon_families = apply_filters('siteorigin_widgets_icon_families', array() );
	if( empty($widget_icon_families[$family]) || empty($widget_icon_families[$family]['icons'][$icon]) ) return false;

	if(empty($widget_icons_enqueued[$family]) && !empty($widget_icon_families[$family]['style_uri'])) {
		wp_enqueue_style('siteorigin-widget-icon-font-'.$family, $widget_icon_families[$family]['style_uri'] );
		return '<div class="sow-icon-' . esc_attr($family) . '" data-sow-icon="' . $widget_icon_families[$family]['icons'][$icon] . '" ' . ( !empty($icon_styles) ? 'style="'.implode('; ', $icon_styles).'"' : '' ) . '></div>';
	}
	else {
		return false;
	}

}

/**
 * Action for displaying the widget preview.
 */
function siteorigin_widget_preview_widget_action(){
	if( !class_exists($_POST['class']) ) exit();
	$widget = new $_POST['class'];
	if(!is_a($widget, 'SiteOrigin_Widget')) exit();

	$instance = json_decode( stripslashes_deep($_POST['data']), true);
	$instance = $widget->sanitize($instance);
	$instance['is_preview'] = true;

	// The theme stylesheet will change how the button looks
	wp_enqueue_style( 'theme-css', get_stylesheet_uri(), array(), rand(0,65536) );
	wp_enqueue_style( 'so-widget-preview', plugin_dir_url(__FILE__).'/css/preview.css', array(), rand(0,65536) );

	ob_start();
	$widget->widget(array(
		'before_widget' => '',
		'after_widget' => '',
		'before_title' => '',
		'after_title' => '',
	), $instance);
	$widget_html = ob_get_clean();

	// Print all the scripts and styles
	?>
	<html>
		<head>
			<title><?php _e('Widget Preview', 'siteorigin-widgets') ?></title>
			<?php
			wp_print_scripts();
			wp_print_styles();
			siteorigin_widget_print_styles();
			?>
		</head>
		<body>
			<?php // A lot of themes use entry-content as their main content wrapper ?>
			<div class="entry-content">
				<?php echo $widget_html ?>
			</div>
		</body>
	</html>

	<?php
	exit();
}
add_action('wp_ajax_so_widgets_preview', 'siteorigin_widget_preview_widget_action');

/**
 * Compatibility with Page Builder, add the groups and icons.
 *
 * @param $widgets
 *
 * @return mixed
 */
function siteorigin_widget_add_bundle_groups($widgets){
	foreach( $widgets as $class => &$widget ) {
		if( preg_match('/SiteOrigin_Widget_(.*)_Widget/', $class, $matches) ) {
			$widget['icon'] = 'so-widget-icon so-widget-icon-'.strtolower($matches[1]);
			$widget['groups'] = array('so-widgets-bundle');
		}
	}

	return $widgets;
}
add_filter('siteorigin_panels_widgets', 'siteorigin_widget_add_bundle_groups', 11);
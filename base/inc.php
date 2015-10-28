<?php

include plugin_dir_path(__FILE__).'inc/fields/siteorigin-widget-field-class-loader.class.php';
include plugin_dir_path(__FILE__).'siteorigin-widget.class.php';

include plugin_dir_path(__FILE__).'inc/widget-manager.class.php';
include plugin_dir_path(__FILE__).'inc/meta-box-manager.php';
include plugin_dir_path(__FILE__).'inc/post-selector.php';
include plugin_dir_path(__FILE__).'inc/fonts.php';
include plugin_dir_path(__FILE__).'inc/string-utils.php';
include plugin_dir_path(__FILE__).'inc/attachments.php';

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
	if ( empty( $_REQUEST['_widgets_nonce'] ) || !wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) return;

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
		if( !wp_style_is( 'siteorigin-widget-icon-font-'.$family ) ) {
			wp_enqueue_style('siteorigin-widget-icon-font-'.$family, $widget_icon_families[$family]['style_uri'] );
		}
		return '<span class="sow-icon-' . esc_attr($family) . '" data-sow-icon="' . $widget_icon_families[$family]['icons'][$icon] . '" ' . ( !empty($icon_styles) ? 'style="'.implode('; ', $icon_styles).'"' : '' ) . '></span>';
	}
	else {
		return false;
	}

}

/**
 * @param $font_value
 *
 * @return array
 */
function siteorigin_widget_get_font($font_value) {

	$web_safe = array(
		'Helvetica Neue' => 'Arial, Helvetica, Geneva, sans-serif',
		'Lucida Grande' => 'Lucida, Verdana, sans-serif',
		'Georgia' => '"Times New Roman", Times, serif',
		'Courier New' => 'Courier, mono',
		'default' => 'default',
	);

	$font = array();
	if ( isset( $web_safe[ $font_value ] ) ) {
		$font['family'] = $web_safe[ $font_value ];
	}
	else if( siteorigin_widgets_is_google_webfont( $font_value ) ) {
		$font_parts = explode( ':', $font_value );
		$font['family'] = $font_parts[0];
		$font_url_param = urlencode( $font_parts[0] );
		if ( count( $font_parts ) > 1 ) {
			$font['weight'] = $font_parts[1];
			$font_url_param .= ':' . $font_parts[1];
		}
		$font['css_import'] = '@import url(http' . ( is_ssl() ? 's' : '' ) . '://fonts.googleapis.com/css?family=' . $font_url_param . ');';
	}
	else {
		$font['family'] = $font_value;
		$font = apply_filters( 'siteorigin_widget_get_custom_font_family', $font );
	}

	return $font;
}

/**
 * Action for displaying the widget preview.
 */
function siteorigin_widget_preview_widget_action(){
	if( !class_exists($_POST['class']) ) exit();
	if ( empty( $_REQUEST['_widgets_nonce'] ) || !wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) return;
	$widget = new $_POST['class'];
	if(!is_a($widget, 'SiteOrigin_Widget')) exit();

	$instance = json_decode( stripslashes_deep($_POST['data']), true);
	/* @var $widget SiteOrigin_Widget */
	$instance = $widget->update( $instance, $instance );
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
			<title><?php _e('Widget Preview', 'so-widgets-bundle') ?></title>
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
 * Action to handle searching
 */
function siteorigin_widget_search_posts_action(){
	if ( empty( $_REQUEST['_widgets_nonce'] ) || !wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) return;

	header('content-type: application/json');

	// Get all public post types, besides attachments
	$post_types = (array) get_post_types( array(
		'public'   => true
	) );
	unset($post_types['attachment']);


	global $wpdb;
	if( !empty($_GET['query']) ) {
		$query = "AND post_title LIKE '%" . esc_sql( $_GET['query'] ) . "%'";
	}
	else {
		$query = '';
	}

	$post_types = "'" . implode("', '", array_map( 'esc_sql', $post_types ) ) . "'";

	$results = $wpdb->get_results( "
		SELECT ID, post_title, post_type
		FROM {$wpdb->posts}
		WHERE
			post_type IN ( {$post_types} ) AND post_status = 'publish' {$query}
		ORDER BY post_modified DESC
		LIMIT 20
	", ARRAY_A );

	echo json_encode( $results );
	wp_die();
}
add_action('wp_ajax_so_widgets_search_posts', 'siteorigin_widget_search_posts_action');

/**
 * Compatibility with Page Builder, add the groups and icons.
 *
 * @param $widgets
 *
 * @return mixed
 */
function siteorigin_widget_add_bundle_groups($widgets){
	foreach( $widgets as $class => &$widget ) {
		if( preg_match('/SiteOrigin_Widget_(.*)_Widget/i', $class, $matches) ) {
			$widget['icon'] = 'so-widget-icon so-widget-icon-'.strtolower($matches[1]);
			$widget['groups'] = array('so-widgets-bundle');
		}
	}

	return $widgets;
}
add_filter('siteorigin_panels_widgets', 'siteorigin_widget_add_bundle_groups', 11);

/**
 * Escape a URL
 *
 * @param $url
 *
 * @return string
 */
function sow_esc_url( $url ) {
	if( preg_match('/^post: *([0-9]+)/', $url, $matches) ) {
		// Convert the special post URL into a permalink
		$url = get_the_permalink( intval($matches[1]) );
	}

	$protocols = wp_allowed_protocols();
	$protocols[] = 'skype';
	return esc_url( $url, $protocols );
}

/**
 * A special URL escaping function that handles additional protocols
 *
 * @param $url
 *
 * @return string
 */
function sow_esc_url_raw( $url ) {
	if( preg_match('/^post: *([0-9]+)/', $url, $matches) ) {
		// Convert the special post URL into a permalink
		$url = get_the_permalink( intval($matches[1]) );
	}

	$protocols = wp_allowed_protocols();
	$protocols[] = 'skype';
	return esc_url_raw( $url, $protocols );
}

/**
 * Get all the Google Web Fonts.
 *
 * @return mixed|void
 */
function siteorigin_widgets_fonts_google_webfonts( ) {
	$fonts = include plugin_dir_path(__FILE__) . 'inc/fonts.php';
	$fonts = apply_filters( '', $fonts );
	return $fonts;
}
add_filter('siteorigin_widgets_fonts_google_webfonts', 'siteorigin_widgets_fonts_google_webfonts_filter');

function siteorigin_widgets_is_google_webfont( $font_value ) {
	$google_webfonts = siteorigin_widgets_fonts_google_webfonts();

	$font_family = explode( ':', $font_value );
	$font_family = $font_family[0];

	return isset( $google_webfonts[$font_family] );
}

function siteorigin_widgets_font_families( ){
	// Add the default fonts
	$font_families = array(
		'Helvetica Neue' => 'Helvetica Neue',
		'Lucida Grande' => 'Lucida Grande',
		'Georgia' => 'Georgia',
		'Courier New' => 'Courier New',
	);

	// Add in all the Google font families
	foreach ( siteorigin_widgets_fonts_google_webfonts() as $font => $variants ) {
		foreach ( $variants as $variant ) {
			if ( $variant == 'regular' || $variant == 400 ) {
				$font_families[ $font ] = $font;
			}
			else {
				$font_families[ $font . ':' . $variant ] = $font . ' (' . $variant . ')';
			}
		}
	}

	return apply_filters('siteorigin_widgets_font_families', $font_families);
}

function siteorigin_widgets_tinymce_admin_print_styles() {
	wp_enqueue_style( 'editor-buttons' );
}
add_action( 'admin_print_styles', 'siteorigin_widgets_tinymce_admin_print_styles' );

/**
 * Get list of supported measurements
 *
 * @return array
 */
function siteorigin_widgets_get_measurements_list() {
	$measurements = array(
		'px',
		'%',
		'in',
		'cm',
		'mm',
		'em',
		'ex',
		'pt',
		'pc',
	);

	// Allow themes and plugins to trim or enhance the list.
	return apply_filters('siteorigin_widgets_get_measurements_list', $measurements);
}
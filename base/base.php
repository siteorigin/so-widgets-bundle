<?php

include plugin_dir_path(__FILE__).'inc/fields/siteorigin-widget-field-class-loader.class.php';
include plugin_dir_path(__FILE__).'siteorigin-widget.class.php';

include plugin_dir_path(__FILE__).'inc/widget-manager.class.php';
include plugin_dir_path(__FILE__).'inc/meta-box-manager.php';
include plugin_dir_path(__FILE__).'inc/post-selector.php';
include plugin_dir_path(__FILE__).'inc/string-utils.php';
include plugin_dir_path(__FILE__).'inc/array-utils.php';
include plugin_dir_path(__FILE__).'inc/attachments.php';
include plugin_dir_path(__FILE__).'inc/actions.php';
include plugin_dir_path(__FILE__).'inc/shortcode.php';
include plugin_dir_path(__FILE__).'inc/video.php';
include plugin_dir_path(__FILE__).'inc/routes/sowb-rest-routes.php';

/**
 * @param $css
 */
function siteorigin_widget_add_inline_css($css){
	global $siteorigin_widgets_inline_styles;
	if ( empty( $siteorigin_widgets_inline_styles ) ) {
	    $siteorigin_widgets_inline_styles = array();
    }

	$siteorigin_widgets_inline_styles[] = $css;
}

/**
 * Print any inline styles that have been added with siteorigin_widget_add_inline_css
 */
function siteorigin_widget_print_styles(){
	global $siteorigin_widgets_inline_styles;
	if ( ! empty( $siteorigin_widgets_inline_styles ) ) {
        foreach ($siteorigin_widgets_inline_styles as $widget_css) {
            ?>
            <style type="text/css"><?php echo($widget_css) ?></style><?php
        }
    }

	$siteorigin_widgets_inline_styles = array();
}
add_action('wp_head', 'siteorigin_widget_print_styles');
add_action('wp_footer', 'siteorigin_widget_print_styles');

/**
 * The ajax handler for getting a list of available icons.
 */
function siteorigin_widget_get_icon_list(){
	if ( empty( $_REQUEST['_widgets_nonce'] ) || ! wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) {
		wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 403 );
	}
	
	if ( empty( $_GET['family'] ) ) {
		wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 400 );
	}
	
	$widget_icon_families = apply_filters( 'siteorigin_widgets_icon_families', array() );
	$icons = ! empty( $widget_icon_families[ $_GET['family'] ] ) ? $widget_icon_families[ $_GET['family'] ] : array();
	wp_send_json( $icons );
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
	$value_parts = SiteOrigin_Widget_Field_Icon::get_value_parts( $icon_value );
	$family = $value_parts['family'];
	$style = empty( $value_parts['style'] ) ? null : $value_parts['style'];
	$icon = $value_parts['icon'];
	if( empty( $family ) || empty( $icon ) ) return false;

	static $widget_icon_families;
	static $widget_icons_enqueued = array();
	
	if ( empty( $widget_icon_families ) ) {
		$widget_icon_families = apply_filters('siteorigin_widgets_icon_families', array() );
	}
	if ( empty( $widget_icon_families[ $family ] ) ||
		 empty( $widget_icon_families[ $family ]['icons'][ $icon ] ) ) {
		return false;
	}
	
	if ( empty( $widget_icons_enqueued[ $family ] ) &&
		 ! empty( $widget_icon_families[ $family ]['style_uri'] ) ) {
		if( ! wp_style_is( 'siteorigin-widget-icon-font-'.$family ) ) {
			wp_enqueue_style( 'siteorigin-widget-icon-font-' . $family, $widget_icon_families[ $family ]['style_uri'] );
		}
		$family_style = 'sow-icon-' . $family . ( empty( $style ) ? '' : ' ' . $style );
		$icon_data = $widget_icon_families[ $family ]['icons'][ $icon ];
		$unicode = '';
		if ( is_array($icon_data) && ! empty( $icon_data['unicode'] ) ) {
			$unicode = $icon_data['unicode'];
		} else if ( is_string( $icon_data ) ) {
			$unicode = $icon_data;
		}
		return '<span class="' . esc_attr( $family_style ) . '" data-sow-icon="' . $unicode . '" ' . ( ! empty( $icon_styles ) ? 'style="' . implode( '; ', $icon_styles ) . '"' : '' ) . '></span>';
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
		'Arial' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
		'Helvetica Neue' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
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
		$font['url'] = 'https://fonts.googleapis.com/css?family=' . $font_url_param;
		$font['css_import'] = '@import url(https://fonts.googleapis.com/css?family=' . $font_url_param . '&display=swap);';
	}
	else {
		$font['family'] = $font_value;
		$font = apply_filters( 'siteorigin_widget_get_custom_font_family', $font );
	}

	return $font;
}

/**
 * Compatibility with Page Builder, add the groups and icons.
 *
 * @param $widgets
 *
 * @return mixed
 */
function siteorigin_widget_add_bundle_groups($widgets){
	foreach( $widgets as $class => &$widget ) {
		if( preg_match('/SiteOrigin_Widgets?_(.*)_Widget/i', $class, $matches) ) {
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
		if( empty($url) ) return '';
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
 * Escape an HTML attribute
 *
 * This is a copy of the WP core `esc_attr` function, but modified to allow specifying arguments to the
 * `_wp_specialchars` function for a bit more control. This was specifically necessary to allow double-encoding for
 * the layout builder field.
 *
 * @param $text
 * @param int $quote_style
 * @param bool $charset
 * @param bool $double_encode
 *
 * @return string
 */
function sow_esc_attr( $text, $quote_style = ENT_QUOTES, $charset = false, $double_encode = false ) {
	$safe_text = wp_check_invalid_utf8( $text );
	$safe_text = _wp_specialchars( $safe_text, $quote_style, $charset, $double_encode );
	return apply_filters( 'attribute_escape', $safe_text, $text );
}

/**
 * Get all the Google Web Fonts.
 *
 * @return mixed|void
 */
function siteorigin_widgets_fonts_google_webfonts( ) {
	$fonts = include plugin_dir_path( __FILE__ ) . 'inc/fonts.php';
	$fonts = apply_filters( 'siteorigin_widgets_google_webfonts', $fonts );
	return !empty( $fonts ) ? $fonts : array();
}

function siteorigin_widgets_is_google_webfont( $font_value ) {
	$google_webfonts = siteorigin_widgets_fonts_google_webfonts();

	$font_family = explode( ':', $font_value );
	$font_family = $font_family[0];

	return isset( $google_webfonts[$font_family] );
}

function siteorigin_widgets_font_families( ){
	// Add the default fonts
	$font_families = array(
		'Arial' => 'Arial',
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

/**
 * Get list of supported measurements
 *
 * @return array
 */
function siteorigin_widgets_get_measurements_list() {
	$measurements = array(
		'px', '%', 'in', 'cm', 'mm', 'em', 'rem', 'pt', 'pc', 'ex', 'ch', 'vw', 'vh', 'vmin', 'vmax',
	);

	// Allow themes and plugins to trim or enhance the list.
	return apply_filters('siteorigin_widgets_get_measurements_list', $measurements);
}


/**
 * Returns the base URL of our widget with `$path` appended.
 *
 * @param string $path Extra path to append to the end of the URL.
 *
 * @return string Base URL of the widget, with $path appended.
 */
function siteorigin_widgets_url( $path = '' ) {
	return plugins_url( 'so-widgets-bundle/' . $path );
}

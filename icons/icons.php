<?php

define( 'SITEORIGIN_WIDGETS_ICONS', true );

function siteorigin_widgets_icon_families_filter( $families ){
	$bundled = array(
		'elegantline' => __( 'Elegant Themes Line Icons', 'so-widgets-bundle' ),
		'fontawesome' => __( 'Font Awesome', 'so-widgets-bundle' ),
		'genericons' => __( 'Genericons', 'so-widgets-bundle' ),
		'icomoon' => __( 'Icomoon Free', 'so-widgets-bundle' ),
		'typicons' => __( 'Typicons', 'so-widgets-bundle' ),
		'ionicons' => __( 'Ionicons', 'so-widgets-bundle' ),
	);

	foreach ( $bundled as $font => $name) {
		include_once plugin_dir_path(__FILE__) . $font . '/filter.php';
		$families[$font] = array(
			'name' => $name,
			'style_uri' => plugin_dir_url(__FILE__) . $font . '/style.css',
			'icons' => apply_filters('siteorigin_widgets_icons_' . $font, array() ),
		);
	}

	return $families;
}
add_filter( 'siteorigin_widgets_icon_families', 'siteorigin_widgets_icon_families_filter' );
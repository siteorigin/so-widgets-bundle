<?php

/**
 * A simple shortcode that just renders a
 *
 * @param string $content
 *
 * @return string
 */
function siteorigin_widget_shortcode( $attr, $content = '' ) {
	$attr = shortcode_atts( array(
		'class' => false,
		'id' => '',
	), $attr, 'panels_widget' );

	$attr[ 'class' ] = html_entity_decode( $attr[ 'class' ] );

	global $wp_widget_factory;

	if ( ! empty( $attr[ 'class' ] ) && isset( $wp_widget_factory->widgets[ $attr[ 'class' ] ] ) ) {
		$the_widget = $wp_widget_factory->widgets[ $attr[ 'class' ] ];

		// Parse the value of the shortcode
		preg_match( '/value="(.*?)"/', trim( $content ), $matches );

		if ( ! empty( $matches[1] ) ) {
			$data = json_decode( html_entity_decode( $matches[1] ), true );
		}

		$widget_args = ! empty( $data[ 'args' ] ) ? $data[ 'args' ] : array();
		$widget_instance = ! empty( $data[ 'instance' ] ) ? $data[ 'instance' ] : array();

		$widget_args = wp_parse_args( array(
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		), $widget_args );

		ob_start();
		$the_widget->widget( $widget_args, $widget_instance );

		return ob_get_clean();
	}
}

function siteorigin_widget_shortcode_register() {
	global $shortcode_tags;

	// Only load this as a fallback if Page Builder hasn't already registered it.
	if ( empty( $shortcode_tags[ 'siteorigin_widget' ] ) ) {
		add_shortcode( 'siteorigin_widget', 'siteorigin_widget_shortcode' );
	}
}
add_action( 'plugins_loaded', 'siteorigin_widget_shortcode_register', 15 );

/**
 * Tell Page Builder we'll be using the custom siteorigin_widget shortcode
 *
 * @return string
 */
function siteorigin_widget_shortcode_panels_cache_shortcode( $shortcode, $widget ) {
	if ( is_a( $widget, 'SiteOrigin_Widget' ) ) {
		$shortcode = 'siteorigin_widget';
	}

	return $shortcode;
}

add_filter( 'siteorigin_panels_cache_shortcode', 'siteorigin_widget_shortcode_panels_cache_shortcode', 10, 2 );

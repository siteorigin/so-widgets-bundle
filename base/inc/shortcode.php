<?php

/**
 * A simple shortcode that just renders a
 *
 * @param $attr
 * @param string $content
 *
 * @return string
 */
function siteorigin_widget_shortcode( $attr, $content = '' ){
	$attr = shortcode_atts( array(
		'class' => false,
		'id' => '',
	), $attr, 'siteorigin_widget' );

	global $wp_widget_factory;
	if( ! empty( $attr[ 'class' ] ) && isset( $wp_widget_factory->widgets[ $attr[ 'class' ] ] ) ) {
		$the_widget = $wp_widget_factory->widgets[ $attr[ 'class' ] ];

		$meta = json_decode( htmlspecialchars_decode( $content ), true );

		$widget_args = ! empty( $meta[ 'args' ] ) ? $meta[ 'args' ] : array();
		$widget_instance = ! empty( $meta[ 'instance' ] ) ? $meta[ 'instance' ] : array();

		$widget_args = wp_parse_args( array(
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		), $widget_args );

		if( is_a( $the_widget, 'SiteOrigin_Widget' ) ) {
			ob_start();
			$the_widget->widget( $widget_args, $widget_instance );
			return ob_get_clean();
		}
	}
}
add_shortcode( 'siteorigin_widget', 'siteorigin_widget_shortcode' );

/**
 * Setup the mode where we're rendering for the Page Builder database.
 */
function siteorigin_widget_shortcode_setup_database_render( $post_id ){
	delete_post_meta( $post_id, 'siteorigin_widget_instances' );
	add_action( 'siteorigin_panels_the_widget_html', 'siteorigin_widget_shortcode_for_database_render', 10, 4 );
}
add_action( 'siteorigin_panels_setup_database_render', 'siteorigin_widget_shortcode_setup_database_render' );

/**
 * Change the widget HTML for a database render
 *
 * @param $html
 * @param $the_widget
 * @param $args
 * @param $instance
 *
 * @return mixed
 */
function siteorigin_widget_shortcode_for_database_render( $html, $the_widget, $args, $instance ) {
	if( empty( $GLOBALS[ 'SITEORIGIN_PANELS_DATABASE_RENDER' ] ) ) return $html;

	if( is_a( $the_widget, 'SiteOrigin_Widget_Editor_Widget' ) ) {
		// The Editor widget handles its own database based rendering
		return $html;
	}
	else if( is_a( $the_widget, 'SiteOrigin_Widget' ) ) {
		unset( $instance[ 'panels_info' ] );

		$meta = get_post_meta( get_the_ID(), 'siteorigin_widget_instances', true );
		if( empty( $meta ) ) {
			$meta = array();
		}
		$i = count( $meta );

		$before_widget = $args['before_widget'];
		$after_widget = $args['after_widget'];
		unset( $args['before_widget'] );
		unset( $args['after_widget'] );

		$meta[$i] = array(
			'args' => $args,
			'instance' => $instance,
		);
		update_post_meta( get_the_ID(), 'siteorigin_widget_instances', $meta );

		$html .= $before_widget;
		$html .= '[siteorigin_widget ';
		$html .= 'class="' . get_class( $the_widget ) . '"]';
		$html .= htmlspecialchars( wp_json_encode( $meta[ $i ] ) );
		$html .= '[/siteorigin_widget]';
		$html .= $after_widget;
	}

	return $html;
}

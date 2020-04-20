<?php

/**
 * Get the attachment src, but also have the option of getting the fallback URL.
 *
 * @param $attachment
 * @param $size
 * @param bool|false $fallback
 *
 * @return array|bool|false
 */
function siteorigin_widgets_get_attachment_image_src( $attachment, $size, $fallback = false ){
	if( empty( $attachment ) && !empty($fallback) ) {
		$url = parse_url( $fallback );

		if( !empty($url['fragment']) && preg_match('/^([0-9]+)x([0-9]+)$/', $url['fragment'], $matches) ) {
			$width = intval($matches[1]);
			$height = intval($matches[2]);
		}
		else {
			$width = 0;
			$height = 0;
		}

		// TODO, try get better values than 0 for width and height
		return array( $fallback, $width, $height, false );
	}
	if( !empty( $attachment ) ) {
		return wp_get_attachment_image_src( $attachment, $size );
	}

	return false;
}

function siteorigin_widgets_get_attachment_image( $attachment, $size, $fallback, $atts = array() ){
	if( !empty( $attachment ) ) {
		return wp_get_attachment_image( $attachment, $size, false, $atts );
	}
	else {
		$src = siteorigin_widgets_get_attachment_image_src( $attachment, $size, $fallback );
		if( empty($src[0]) ) return '';

		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$atts['srcset'] = wp_get_attachment_image_srcset( $attachment, $size );
		}
		if ( function_exists( 'wp_get_attachment_image_sizes' ) ) {
			$atts['sizes'] = wp_get_attachment_image_sizes( $attachment, $size );
		}

		$atts['src'] = $src[0];

		if( !empty($src[1]) ) $atts['width'] = $src[1];
		if( !empty($src[2]) ) $atts['height'] = $src[2];

		$return = '<img ';
		foreach( $atts as $id => $val ) {
			$return .= $id . '="' . esc_attr( $val ) . '" ';
		}
		$return .= '>';
		return $return;
	}
}

/**
 * Get size information for all currently-registered image sizes.
 * From codex example here: https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
 *
 * @global $_wp_additional_image_sizes
 * @uses   get_intermediate_image_sizes()
 * @return array $sizes Data for all currently-registered image sizes.
 */
function siteorigin_widgets_get_image_sizes() {
	global $_wp_additional_image_sizes;

	$sizes = array();

	foreach ( get_intermediate_image_sizes() as $_size ) {
		if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
			$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
			$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
			$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
		} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
			$sizes[ $_size ] = array(
				'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
				'height' => $_wp_additional_image_sizes[ $_size ]['height'],
				'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
			);
		}
	}

	return $sizes;
}


/**
 * @param $size
 *
 * @return mixed
 */
function siteorigin_widgets_get_image_size( $size ) {
	$sizes = siteorigin_widgets_get_image_sizes();
	if ( ! empty( $sizes[ $size ] ) ) {
		return $sizes[ $size ];
	}

	return null;
}

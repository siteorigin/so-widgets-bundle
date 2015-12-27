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

function siteorigin_widgets_get_attachment_image( $attachment, $size, $fallback ){
	if( !empty( $attachment ) ) {
		return wp_get_attachment_image( $attachment, $size );
	}
	else {
		$src = siteorigin_widgets_get_attachment_image_src( $attachment, $size, $fallback );
		if( empty($src[0]) ) return '';

		$atts = array(
			'src' => $src[0],
		);

		if( !empty($src[1]) ) $atts['width'] = $src[1];
		if( !empty($src[2]) ) $atts['height'] = $src[2];

		$return = '<img ';
		foreach( $atts as $id => $val ) {
			$return .= $id . '="' . esc_attr($val) . '" ';
		}
		$return .= '>';
		return $return;
	}
}

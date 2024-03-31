<?php

/**
 * Convert underscore naming convention to camel case. Useful for data to be handled by javascript.
 *
 * @param $array array Input array of which the keys will be transformed.
 *
 * @return array The transformed array with camel case keys.
 */
function siteorigin_widgets_underscores_to_camel_case( $array ) {
	$transformed = array();

	if ( ! empty( $array ) ) {
		foreach ( $array as $key => $val ) {
			if ( is_array( $val ) ) {
				$val = siteorigin_widgets_underscores_to_camel_case( $val );
			}

			$jsKey = preg_replace_callback( '/_(.?)/', 'siteorigin_widgets_match_to_upper', $key );
			$transformed[ $jsKey ] = $val;
		}
	}

	return $transformed;
}

/**
 * Convert a matched string to uppercase. Used as a preg callback.
 *
 * @return string
 */
function siteorigin_widgets_match_to_upper( $matches ) {
	return strtoupper( $matches[1] );
}

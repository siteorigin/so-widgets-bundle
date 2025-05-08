<?php

/**
 * Get the attachment src, but also have the option of getting the fallback URL.
 *
 * @param bool|false $fallback
 *
 * @return array|bool|false
 */
function siteorigin_widgets_get_attachment_image_src( $attachment, $size, $fallback = false, $fallback_size = array() ) {
	if ( empty( $attachment ) && ! empty( $fallback ) ) {
		if ( ! empty( $fallback_size ) ) {
			extract( $fallback_size );
		} else {
			$url = parse_url( $fallback );

			if (
				! empty( $url['fragment'] ) &&
				preg_match(
					'/^([0-9]+)x([0-9]+)$/',
					$url['fragment'],
					$matches
				) ) {
				$width = (int) $matches[1];
				$height = (int) $matches[2];
			} else {
				$width = 0;
				$height = 0;
			}
		}

		// TODO, try get better values than 0 for width and height
		return array( $fallback, $width, $height, false );
	}

	if ( ! empty( $attachment ) ) {
		return wp_get_attachment_image_src( $attachment, $size );
	}

	return false;
}

function siteorigin_widgets_get_attachment_image( $attachment, $size, $fallback, $atts = array() ) {
	if ( ! empty( $attachment ) ) {
		return wp_get_attachment_image( $attachment, $size, false, $atts );
	} else {
		$src = siteorigin_widgets_get_attachment_image_src( $attachment, $size, $fallback );

		if ( empty( $src[0] ) ) {
			return '';
		}

		if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
			$atts['srcset'] = wp_get_attachment_image_srcset( $attachment, $size );
		}

		if ( function_exists( 'wp_get_attachment_image_sizes' ) ) {
			$atts['sizes'] = wp_get_attachment_image_sizes( $attachment, $size );
		}

		$atts['src'] = $src[0];

		if ( ! empty( $src[1] ) ) {
			$atts['width'] = $src[1];
		}

		if ( ! empty( $src[2] ) ) {
			$atts['height'] = $src[2];
		}

		$return = '<img ';

		foreach ( $atts as $id => $val ) {
			$return .= siteorigin_sanitize_attribute_key( $id ) . '="' . esc_attr( $val ) . '" ';
		}
		$return .= '>';

		return $return;
	}
}

$siteorigin_image_sizes = array();
/**
 * Get size information for all currently-registered image sizes.
 *
 * This function retrieves the configuration for all currently-registered image sizes.
 * It includes both the hardcoded sizes (thumbnail, medium, medium_large, large) and
 * any additional image sizes registered by themes or plugins. The resulting sizes
 * array is stored in the $siteorigin_image_sizes global variable for caching
 * purposes.
 *
 * From codex example here: https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
 *
 * @global array $_wp_additional_image_sizes
 * @global array $siteorigin_image_sizes
 * @uses   get_intermediate_image_sizes()
 *
 * @return array $sizes Data for all currently-registered image sizes.
 */
function siteorigin_widgets_get_image_sizes() {
	global $_wp_additional_image_sizes, $siteorigin_image_sizes;

	if ( ! empty( $siteorigin_image_sizes ) ) {
		return $siteorigin_image_sizes;
	}

	$sizes = array();
	$intermediate_sizes = get_intermediate_image_sizes();
	$hardcoded_sizes = array(
		'thumbnail',
		'medium',
		'medium_large',
		'large',
	);

	foreach ( $intermediate_sizes as $_size ) {
		if ( in_array( $_size, $hardcoded_sizes ) ) {
			$sizes[ $_size ] = array(
				'width'  => (int) get_option( $_size . '_size_w' ),
				'height' => (int) get_option( $_size . '_size_h' ),
				'crop'   => (bool) get_option( $_size . '_crop' ),
			);

			continue;
		}

		if ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
			$sizes[ $_size] = $_wp_additional_image_sizes[ $_size ];
		}
	}

	$siteorigin_image_sizes = $sizes;

	return $sizes;
}

/**
 * Get the image size configuration.
 *
 * This function retrieves the configuration for the specified image size.
 * It returns null if the size is not defined, or if the sizes array is empty.
 * If the size identifier is 'thumb', we override it to 'thumbnail' to match the
 * current WordPress core image size.
 *
 * @param string $size - The image size identifier.
 *
 * @return array|null - The configuration array for the image size, or null if not found.
 */
function siteorigin_widgets_get_image_size( $size ) {
	$sizes = siteorigin_widgets_get_image_sizes();

	// Previously, we stored the thumbnail size as 'thumb'. It's now 'thumbnail'.
	if ( $size === 'thumb' ) {
		$size = 'thumbnail';
	}

	if (
		empty( $sizes ) ||
		! is_string( $size ) ||
		! isset( $sizes[ $size ] )
	) {
		return null;
	}

	return $sizes[ $size ];
}

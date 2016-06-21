<?php

/**
 * Class SiteOrigin_Widget_Field_Select
 */
class SiteOrigin_Widget_Field_Image_Size extends SiteOrigin_Widget_Field_Select {

	protected function get_default_options() {
		$image_size_configs = $this->get_image_sizes();
		// Hardcoded 'full' and 'thumb' because they're not registered image sizes.
		// 'full' will result in the original uploaded image size being used.
		// 'thumb' is a small thumbnail image size defined by the current theme.
		$sizes = array(
			'full' => __( 'Full', 'so-widgets-bundle' ),
			'thumb' => __( 'Thumbnail (Theme-defined)', 'so-widgets-bundle' ),
		);
		foreach( $image_size_configs as $name => $size_config) {
			$sizes[$name] = ucwords(preg_replace('/[-_]/', ' ', $name)) . ' (' . $size_config['width'] . 'x' . $size_config['height'] . ')';
		}

		return array(
			'options' => $sizes
		);
	}

	/**
	 * Get size information for all currently-registered image sizes.
	 * From codex example here: https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
	 *
	 * @global $_wp_additional_image_sizes
	 * @uses   get_intermediate_image_sizes()
	 * @return array $sizes Data for all currently-registered image sizes.
	 */
	private function get_image_sizes() {
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

}

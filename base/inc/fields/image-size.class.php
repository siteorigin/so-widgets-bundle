<?php

/**
 * Class SiteOrigin_Widget_Field_Image_Size
 */
class SiteOrigin_Widget_Field_Image_Size extends SiteOrigin_Widget_Field_Select {

	protected function get_default_options() {
		$image_size_configs = siteorigin_widgets_get_image_sizes();
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

}

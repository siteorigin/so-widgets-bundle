<?php

/**
 * Add the image sizes used by the masonry widget.
 */

function sow_masonry_register_image_sizes(){
	add_image_size( 'so-masonry-size-11', 280, 280, true );
	add_image_size( 'so-masonry-size-12', 280, 560, true );
	add_image_size( 'so-masonry-size-21', 560, 280, true );
	add_image_size( 'so-masonry-size-22', 560, 560, true );
}

add_action('init', 'sow_masonry_register_image_sizes');

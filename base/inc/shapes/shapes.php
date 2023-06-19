<?php

function siteorigin_widgets_image_shapes( $shape = false) {
	$shapes = apply_filters(
		'siteorigin_widgets_image_shapes',
		array(
			'circle' => __( 'Circle', 'so-widgets-bundle'  ),
			'oval' => __( 'Oval', 'so-widgets-bundle'  ),
			'triangle' => __( 'Triangle', 'so-widgets-bundle'  ),
			'square' => __( 'Square', 'so-widgets-bundle'  ),
			'diamond' => __( 'Diamond', 'so-widgets-bundle'  ),
			'rhombus' => __( 'Rhombus', 'so-widgets-bundle'  ),
			'parallelogram' => __( 'Parallelogram', 'so-widgets-bundle'  ),
			'pentagon' => __( 'Pentagon', 'so-widgets-bundle'  ),
			'hexagon' => __( 'Hexagon', 'so-widgets-bundle'  ),
		)
	);

	if ( ! $shape ) {
		return $shapes;
	} elseif ( isset( $shapes[ $shape ] ) ) {
		return true;
	} else {
		return false;
	}
}
add_filter( 'siteorigin_widgets_icon_families', 'siteorigin_widgets_icon_families_filter' );


function siteorigin_widgets_image_shape( $shape ) {
	$shapes = siteorigin_widgets_image_shapes( $shape );
	if ( siteorigin_widgets_image_shapes( $shape ) ) {
		$file = wp_normalize_path(
			apply_filters(
				'siteorigin_widgets_image_shape_file_path',
				plugin_dir_path( __FILE__ ) . 'images/' . $shape . '.svg',
				$shape
			)
		); 
		if ( file_exists( $file ) ) {
			return wp_normalize_path(
				apply_filters(
					'siteorigin_widgets_image_shape_file_url',
					plugin_dir_url( __FILE__ ) . 'images/' . $shape . '.svg',
					$shape
				)
			);
		} else {
			return false;
		}
	} else {
		return false;
	}
}

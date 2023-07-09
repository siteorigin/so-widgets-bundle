<?php
if ( ! class_exists( 'SiteOrigin_Widget_Image_Shapes' ) ) {
	class SiteOrigin_Widget_Image_Shapes {
		public $shapes = array();
		
		public function __construct() {
			add_action( 'init', array( $this, 'setup_shapes' ) );
		}

		public static function single() {
			static $single;

			return empty( $single ) ? $single = new self() : $single;
		}

		public function setup_shapes() {
			$this->shapes = apply_filters(
				'siteorigin_widgets_image_shapes',
				array(
					'circle' => __( 'Circle', 'so-widgets-bundle' ),
					'oval' => __( 'Oval', 'so-widgets-bundle' ),
					'triangle' => __( 'Triangle', 'so-widgets-bundle' ),
					'square' => __( 'Square', 'so-widgets-bundle' ),
					'diamond' => __( 'Diamond', 'so-widgets-bundle' ),
					'rhombus' => __( 'Rhombus', 'so-widgets-bundle' ),
					'parallelogram' => __( 'Parallelogram', 'so-widgets-bundle' ),
					'pentagon' => __( 'Pentagon', 'so-widgets-bundle' ),
					'hexagon' => __( 'Hexagon', 'so-widgets-bundle' ),
				)
			);
		}

		function get_shapes() {
			return $this->shapes;
		}

		public function is_valid_shape( $shape ) {
			if (
				! empty( $this->shapes ) &&
				! empty( $shape ) &&
				isset( $this->shapes[ $shape ] )
			) {
				return true;
			}

			return false;
		}

		public function get_image_shape( $shape ) {
			if (
				$this->is_valid_shape( $shape ) &&
				$shape != 'custom'
			) {
				$file = wp_normalize_path(
					apply_filters(
						'siteorigin_widgets_image_shape_file_path',
						plugin_dir_path( __FILE__ ) . 'images/',
						$shape
					)
				) . $shape . '.svg';
				if ( file_exists( $file ) ) {
					return wp_normalize_path(
						apply_filters(
							'siteorigin_widgets_image_shape_file_url',
							plugin_dir_url( __FILE__ ) . 'images/',
							$shape
						)
					) . $shape . '.svg';
				}
			}
			return false;
		}
	}
	SiteOrigin_Widget_Image_Shapes::single();
}

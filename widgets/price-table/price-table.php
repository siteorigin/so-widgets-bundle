<?php

/*
Widget Name: Price Table
Description: A powerful yet simple price table widget for your sidebars or Page Builder pages.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

class SiteOrigin_Widget_PriceTable_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-price-table',
			__( 'SiteOrigin Price Table', 'so-widgets-bundle' ),
			array(
				'description' => __( 'A simple Price Table.', 'so-widgets-bundle' ),
				'help'        => 'https://siteorigin.com/widgets-bundle/price-table-widget/'
			),
			array(),
			false,
			plugin_dir_path( __FILE__ ) . '../'
		);
	}

	function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'siteorigin-pricetable',
					plugin_dir_url( __FILE__ ) . 'js/pricetable' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' )
				)
			)
		);
	}

	function get_widget_form() {
		return array(
			'title' => array(
				'type'  => 'text',
				'label' => __( 'Title', 'so-widgets-bundle' ),
			),

			'columns' => array(
				'type'       => 'repeater',
				'label'      => __( 'Columns', 'so-widgets-bundle' ),
				'item_name'  => __( 'Column', 'so-widgets-bundle' ),
				'item_label' => array(
					'selector'     => "[id*='columns-title']",
					'update_event' => 'change',
					'value_method' => 'val'
				),
				'fields'     => array(
					'featured' => array(
						'type'  => 'checkbox',
						'label' => __( 'Featured', 'so-widgets-bundle' ),
					),
					'title'    => array(
						'type'  => 'text',
						'label' => __( 'Title', 'so-widgets-bundle' ),
					),
					'subtitle' => array(
						'type'  => 'text',
						'label' => __( 'Subtitle', 'so-widgets-bundle' ),
					),

					'image' => array(
						'type'  => 'media',
						'label' => __( 'Image', 'so-widgets-bundle' ),
					),

					'image_title' => array(
						'type'  => 'text',
						'label' => __( 'Image title', 'so-widgets-bundle' ),
					),

					'image_alt' => array(
						'type'  => 'text',
						'label' => __( 'Image alt text', 'so-widgets-bundle' ),
					),

					'price'    => array(
						'type'  => 'text',
						'label' => __( 'Price', 'so-widgets-bundle' ),
					),
					'per'      => array(
						'type'  => 'text',
						'label' => __( 'Per', 'so-widgets-bundle' ),
					),
					'button'   => array(
						'type'  => 'text',
						'label' => __( 'Button text', 'so-widgets-bundle' ),
					),
					'url'      => array(
						'type'  => 'link',
						'label' => __( 'Button URL', 'so-widgets-bundle' ),
					),
					'features' => array(
						'type'       => 'repeater',
						'label'      => __( 'Features', 'so-widgets-bundle' ),
						'item_name'  => __( 'Feature', 'so-widgets-bundle' ),
						'item_label' => array(
							'selector'     => "[id*='columns-features-text']",
							'update_event' => 'change',
							'value_method' => 'val'
						),
						'fields'     => array(
							'text'       => array(
								'type'  => 'text',
								'label' => __( 'Text', 'so-widgets-bundle' ),
							),
							'hover'      => array(
								'type'  => 'text',
								'label' => __( 'Hover text', 'so-widgets-bundle' ),
							),
							'icon_new'   => array(
								'type'  => 'icon',
								'label' => __( 'Icon', 'so-widgets-bundle' ),
							),
							'icon_color' => array(
								'type'  => 'color',
								'label' => __( 'Icon color', 'so-widgets-bundle' ),
							),
						),
					),
				),
			),

			'theme' => array(
				'type'    => 'select',
				'label'   => __( 'Price table theme', 'so-widgets-bundle' ),
				'options' => array(
					'atom' => __( 'Atom', 'so-widgets-bundle' ),
				),
			),

			'header_color' => array(
				'type'  => 'color',
				'label' => __( 'Header color', 'so-widgets-bundle' ),
			),

			'featured_header_color' => array(
				'type'  => 'color',
				'label' => __( 'Featured header color', 'so-widgets-bundle' ),
			),

			'button_color' => array(
				'type'  => 'color',
				'label' => __( 'Button color', 'so-widgets-bundle' ),
			),

			'featured_button_color' => array(
				'type'  => 'color',
				'label' => __( 'Featured button color', 'so-widgets-bundle' ),
			),

			'button_new_window' => array(
				'type'  => 'checkbox',
				'label' => __( 'Open Button URL in a new window', 'so-widgets-bundle' ),
			),

			'equalize_row_heights' => array(
				'type'  => 'checkbox',
				'label' => __( 'Equalize row heights', 'so-widgets-bundle' ),
			),
		);
	}

	function get_column_classes( $column, $i, $columns ) {
		$classes = array();
		if ( $i == 0 ) {
			$classes[] = 'ow-pt-first';
		}
		if ( $i == count( $columns ) - 1 ) {
			$classes[] = 'ow-pt-last';
		}
		if ( ! empty( $column['featured'] ) ) {
			$classes[] = 'ow-pt-featured';
		}

		if ( $i % 2 == 0 ) {
			$classes[] = 'ow-pt-even';
		} else {
			$classes[] = 'ow-pt-odd';
		}

		return implode( ' ', $classes );
	}

	function column_image( $column ) {
		$image = $column['image'];

		if ( ! empty( $image ) ) {
			$size = 'full';
			$src  = wp_get_attachment_image_src( $image, $size );

			$img_attrs = array();
			if ( function_exists( 'wp_get_attachment_image_srcset' ) ) {
				$img_attrs['srcset'] = wp_get_attachment_image_srcset( $image, $size );
			}
			if ( function_exists( 'wp_get_attachment_image_sizes' ) ) {
				$img_attrs['sizes'] = wp_get_attachment_image_sizes( $image, $size );
			}

			if ( ! empty( $column['image_title'] ) ) {
				$img_attrs['title'] = $column['image_title'];
			}
			if ( ! empty( $column['image_alt'] ) ) {
				$img_attrs['alt'] = $column['image_alt'];
			}
			$attr_string = '';
			foreach ( $img_attrs as $attr => $val ) {
				$attr_string .= ' ' . $attr . '="' . esc_attr( $val ) . '"';
			}
			?><img src="<?php echo $src[0] ?>"<?php echo $attr_string ?>/> <?php
		}
	}

	function get_template_name( $instance ) {
		return $this->get_style_name( $instance );
	}

	function get_template_variables( $instance, $args ) {
		$columns              = array();
		$any_column_has_image = false;
		foreach ( $instance['columns'] as $column ) {
			$any_column_has_image = $any_column_has_image || ! empty( $column['image'] );
			if( ! empty( $column['features'] ) ) {
				foreach ( $column['features'] as &$feature ) {
					$feature['text'] = do_shortcode( $feature['text'] );
				}
			}
			$columns[] = $column;
		}

		return array(
			'title'                => $instance['title'],
			'columns'              => $columns,
			'before_title'         => $args['before_title'],
			'after_title'          => $args['after_title'],
			'button_new_window'    => $instance['button_new_window'],
			'equalize_row_heights' => ! empty( $instance['equalize_row_heights'] ),
			'any_column_has_image' => $any_column_has_image,
		);
	}


	function get_style_name( $instance ) {
		if ( empty( $instance['theme'] ) ) {
			return 'atom';
		}

		return $instance['theme'];
	}

	/**
	 * Get the LESS variables for the price table widget.
	 *
	 * @param $instance
	 *
	 * @return array
	 */
	function get_less_variables( $instance ) {
		$instance = wp_parse_args( $instance, array(
			'header_color'          => '',
			'featured_header_color' => '',
			'button_color'          => '',
			'featured_button_color' => '',
		) );

		$colors = array(
			'header_color'          => $instance['header_color'],
			'featured_header_color' => $instance['featured_header_color'],
			'button_color'          => $instance['button_color'],
			'featured_button_color' => $instance['featured_button_color'],
		);

		if ( ! class_exists( 'SiteOrigin_Widgets_Color_Object' ) ) {
			require plugin_dir_path( SOW_BUNDLE_BASE_FILE ) . 'base/inc/color.php';
		}

		if ( ! empty( $instance['button_color'] ) ) {
			$color = new SiteOrigin_Widgets_Color_Object( $instance['button_color'] );
			$color->lum += ( $color->lum > 0.75 ? - 0.5 : 0.8 );
			$colors['button_text_color'] = $color->hex;
		}

		if ( ! empty( $instance['featured_button_color'] ) ) {
			$color = new SiteOrigin_Widgets_Color_Object( $instance['featured_button_color'] );
			$color->lum += ( $color->lum > 0.75 ? - 0.5 : 0.8 );
			$colors['featured_button_text_color'] = $color->hex;
		}

		return $colors;
	}

	/**
	 * Modify the instance to use the new icon.
	 */
	function modify_instance( $instance ) {
		if ( empty( $instance['columns'] ) || ! is_array( $instance['columns'] ) ) {
			return $instance;
		}

		foreach ( $instance['columns'] as &$column ) {
			if ( empty( $column['features'] ) || ! is_array( $column['features'] ) ) {
				continue;
			}

			foreach ( $column['features'] as &$feature ) {

				if ( empty( $feature['icon_new'] ) && ! empty( $feature['icon'] ) ) {
					$feature['icon_new'] = 'fontawesome-' . $feature['icon'];
				}

			}
		}

		return $instance;
	}
}

siteorigin_widget_register( 'sow-price-table', __FILE__, 'SiteOrigin_Widget_PriceTable_Widget' );

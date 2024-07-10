<?php

/*
Widget Name: Price Table
Description: Display pricing plans in a professional table format with custom columns, features, and design.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/price-table-widget/
*/

class SiteOrigin_Widget_PriceTable_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'sow-price-table',
			__( 'SiteOrigin Price Table', 'so-widgets-bundle' ),
			array(
				'description' => __( 'Display pricing plans in a professional table format with custom columns, features, and design.', 'so-widgets-bundle' ),
				'help'        => 'https://siteorigin.com/widgets-bundle/price-table-widget/',
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	public function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'siteorigin-pricetable',
					plugin_dir_url( __FILE__ ) . 'js/pricetable' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' ),
				),
			)
		);
	}

	public function get_widget_form() {
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
					'value_method' => 'val',
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
					'sale_price' => array(
						'type'  => 'text',
						'label' => __( 'Sale price', 'so-widgets-bundle' ),
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
							'value_method' => 'val',
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

			'button_new_window' => array(
				'type'  => 'checkbox',
				'label' => __( 'Open Button URL in a new window', 'so-widgets-bundle' ),
			),

			'equalize_row_heights' => array(
				'type'  => 'checkbox',
				'label' => __( 'Equalize row heights', 'so-widgets-bundle' ),
			),

			'design' => array(
				'type' => 'section',
				'label' => __( 'Design', 'so-widgets-bundle' ),
				'fields' => array(
					'theme' => array(
						'type'    => 'select',
						'label'   => __( 'Price table theme', 'so-widgets-bundle' ),
						'options' => array(
							'atom' => __( 'Atom', 'so-widgets-bundle' ),
						),
					),

					'header' => array(
						'type' => 'section',
						'label' => __( 'Header', 'so-widgets-bundle' ),
						'fields' => array(
							'background_color' => array(
								'type'  => 'color',
								'label' => __( 'Background color', 'so-widgets-bundle' ),
								'default' => '#65707f',
							),

							'featured_background_color' => array(
								'type'  => 'color',
								'label' => __( 'Featured background color', 'so-widgets-bundle' ),
								'default' => '#707d8d',
							),

							'color' => array(
								'type'  => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
								'default' => '#fff',
							),

							'featured_color' => array(
								'type'  => 'color',
								'label' => __( 'Featured color', 'so-widgets-bundle' ),
								'default' => '#fff',
							),
						),
					),

					'feature' => array(
						'type' => 'section',
						'label' => __( 'Feature', 'so-widgets-bundle' ),
						'fields' => array(
							'color' => array(
								'type' => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
								'default' => '#5f6062',
							),
						),
					),

					'button' => array(
						'type' => 'section',
						'label' => __( 'Button', 'so-widgets-bundle' ),
						'fields' => array(
							'container_color' => array(
								'type'  => 'color',
								'label' => __( 'Container background color', 'so-widgets-bundle' ),
								'default' => '#e8e8e8',
							),
							'background_color' => array(
								'type'  => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
								'default' => '#41a9d5',
							),
							'featured_background_color' => array(
								'type'  => 'color',
								'label' => __( 'Background color', 'so-widgets-bundle' ),
								'default' => '#2e9fcf',
							),
						),
					),

				),
			),
		);
	}

	public function get_column_classes( $column, $i, $columns ) {
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

		if ( ! empty( $column['sale_price'] ) ) {
			$classes[] = 'ow-pt-on-sale';
		}

		return implode( ' ', $classes );
	}

	public function column_image( $column ) {
		$image = $column['image'];

		if ( ! empty( $image ) ) {
			$size = 'full';
			$src = wp_get_attachment_image_src( $image, $size );

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
				$attr_string .= ' ' . siteorigin_sanitize_attribute_key( $attr ) . '="' . esc_attr( $val ) . '"';
			}
			?><img src="<?php echo esc_url( $src[0] ); ?>"<?php echo $attr_string; ?>/> <?php
		}
	}

	public function get_template_name( $instance ) {
		return $this->get_style_name( $instance );
	}

	public function get_template_variables( $instance, $args ) {
		$columns = array();
		$any_column_has_image = false;

		if ( ! empty( $instance[ 'columns' ] ) ) {
			foreach ( $instance['columns'] as $column ) {
				$any_column_has_image = $any_column_has_image || ! empty( $column['image'] );

				if ( ! empty( $column['features'] ) ) {
					foreach ( $column['features'] as &$feature ) {
						$feature['text'] = do_shortcode( $feature['text'] );
					}
				}
				$columns[] = $column;
			}
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

	public function get_style_name( $instance ) {
		if ( empty( $instance['design']['theme'] ) ) {
			return 'atom';
		}

		return $instance['design']['theme'];
	}

	/**
	 * Get the LESS variables for the price table widget.
	 *
	 * @return array
	 */
	public function get_less_variables( $instance ) {
		$instance = wp_parse_args( $instance, array(
			'header_color'          => '',
			'header_text_color'          => '',
			'featured_header_color' => '',
			'featured_header_text_color' => '',
			'feature_text_color' => '',
			'background_color'          => '',
			'featured_background_color' => '',
		) );

		$colors = array(
			'header_color'               => $instance['design']['header']['background_color'],
			'featured_header_color'      => $instance['design']['header']['featured_background_color'],
			'header_text_color'         => $instance['design']['header']['color'],
			'featured_header_text_color' => $instance['design']['header']['featured_color'],

			'feature_text_color'          => $instance['design']['feature']['color'],

			'button_container_color'               => $instance['design']['button']['container_color'],
			'button_background_color'      => $instance['design']['button']['background_color'],
			'featured_button_background_color'      => $instance['design']['button']['featured_background_color'],
		);

		if ( ! class_exists( 'SiteOrigin_Widgets_Color_Object' ) ) {
			require plugin_dir_path( SOW_BUNDLE_BASE_FILE ) . 'base/inc/color.php';
		}

		if ( ! empty( $instance['design']['button']['background_color'] ) ) {
			$color = new SiteOrigin_Widgets_Color_Object( $instance['design']['button']['background_color'] );
			$color->lum += ( $color->lum > 0.75 ? - 0.5 : 0.8 );
			$colors['button_text_color'] = $color->hex;
		}

		if ( ! empty( $instance['design']['button']['featured_background_color'] ) ) {
			$color = new SiteOrigin_Widgets_Color_Object( $instance['design']['button']['featured_background_color'] );
			$color->lum += ( $color->lum > 0.75 ? - 0.5 : 0.8 );
			$colors['featured_button_text_color'] = $color->hex;
		}

		return $colors;
	}

	/**
	 * Modify the instance to use the new icon.
	 */
	public function modify_instance( $instance ) {
		if ( empty( $instance ) || ! is_array( $instance ) ) {
			return array();
		}

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

		// Migrate fields to design section.
		if ( isset( $instance['theme'] ) ) {
			$fields = array(
				'header' => array(
					'header_text_color' => 'color',
					'featured_header_text_color' => 'featured_color',
					'header_color' => 'background_color',
					'featured_header_color' => 'featured_background_color',
				),
				'feature' => array(
					'feature_text_color' => 'color',
				),
				'button' => array(
					'button_color' => 'background_color',
					'featured_button_color' => 'featured_background_color',
				),
			);

			// Ensure the design section exists, and is an array.
			if (
				! isset( $instance['design'] ) ||
				! is_array( $instance['design'] )
			) {
				$instance['design'] = array();
			}

			foreach ( $fields as $section => $fields ) {
				foreach ( $fields as $field_id => $field ) {
					if ( ! isset( $instance[ $field_id ] ) ) {
						continue;
					}
					// Ensure the section is valid before processing.
					if (
						! isset( $instance['design'][ $section ] ) ||
						! is_array( $instance['design'][ $section ] )
					) {
						$instance['design'][ $section ] = array();
					}
					$instance['design'][ $section ][ $field ] = $instance[ $field_id ];
					unset( $instance[ $field_id ] );
				}
			}

			$instance['design']['theme'] = $instance['theme'];
			unset( $instance['theme'] );

			$instance['design']['button']['container_color'] = '#e8e8e8';
		}

		return $instance;
	}

	public function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return array(
			sprintf(
				__( 'Add a Price Table feature tooltip with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/tooltip" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
		);
	}
}

siteorigin_widget_register( 'sow-price-table', __FILE__, 'SiteOrigin_Widget_PriceTable_Widget' );

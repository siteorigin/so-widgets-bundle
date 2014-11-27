<?php

/*
Widget Name: Google Maps Widget
Description: A simple Google Map with customisable initial location and zoom level
Author: SiteOrigin
Author URI: http://siteorigin.com
*/

class SiteOrigin_Widget_JsGoogleMap_Widget extends SiteOrigin_Widget {

	function __construct() {

		parent::__construct(
			'sow-js-google-map',
			__( 'SiteOrigin Google Map', 'siteorigin-widgets' ),
			array(
				'description' => __( 'A Google Maps widget.', 'siteorigin-widgets' ),
				'help'        => 'http://siteorigin.com/widgets-bundle/google-maps-widget-documentation/'
			),
			array(),
			array(
				'map_center'  => array(
					'type'        => 'textarea',
					'rows'        => 2,
					'label'       => __( 'Map center', 'siteorigin-widgets' ),
					'description' => __( 'The name of a place, town, city, or even a country. Can be an exact address too.', 'siteorigin-widgets' )
				),
				'settings' => array(
					'type'        => 'section',
					'label'       => __( 'Settings', 'siteorigin-widgets' ),
					'hide'        => false,
					'description' => __( 'Set map display options.', 'siteorigin-widgets' ),
					'fields'      => array(
						'map_type'    => array(
							'type'    => 'radio',
							'default' => 'interactive',
							'label'   => __( 'Google map type', 'siteorigin-widgets' ),
							'options' => array(
								'interactive' => __( 'Interactive', 'siteorigin-widgets' ),
								'static'      => __( 'Static Image', 'siteorigin-widgets' ),
							)
						),
						'width'       => array(
							'type'       => 'text',
							'default'    => 640,
							'hidden'     => true,
							'state_name' => 'static',
							'label'      => __( 'Width', 'siteorigin-widgets' )
						),
						'height'      => array(
							'type'    => 'text',
							'default' => 480,
							'label'   => __( 'Height', 'siteorigin-widgets' )
						),
						'zoom'        => array(
							'type'        => 'slider',
							'label'       => __( 'Zoom Level', 'siteorigin-widgets' ),
							'description' => __( 'A value from 0 (the world) to 21 (street level).', 'siteorigin-widgets' ),
							'min'         => 0,
							'max'         => 21,
							'default'     => 12,
							'integer'     => true,

						),
						'scroll_zoom' => array(
							'type'        => 'checkbox',
							'default'     => true,
							'state_name'  => 'interactive',
							'label'       => __( 'Scroll to zoom', 'siteorigin-widgets' ),
							'description' => __( 'Allow scrolling over the map to zoom in or out.', 'siteorigin-widgets' )
						),
						'draggable'   => array(
							'type'        => 'checkbox',
							'default'     => true,
							'state_name'  => 'interactive',
							'label'       => __( 'Draggable', 'siteorigin-widgets' ),
							'description' => __( 'Allow dragging the map to move it around.', 'siteorigin-widgets' )
						)
					)
				),
				'markers'     => array(
					'type'        => 'section',
					'label'       => __( 'Markers', 'siteorigin-widgets' ),
					'hide'        => true,
					'description' => __( 'Use markers to identify points of interest on the map.', 'siteorigin-widgets' ),
					'fields'      => array(
						'marker_at_center'  => array(
							'type'    => 'checkbox',
							'default' => true,
							'label'   => __( 'Show marker at map center', 'siteorigin-widgets' )
						),
						'marker_icon'       => array(
							'type'        => 'media',
							'default'     => '',
							'label'       => __( 'Marker Icon', 'siteorigin-widgets' ),
							'description' => __( 'Replaces the default map marker with your own image.' )
						),
						'markers_draggable' => array(
							'type'       => 'checkbox',
							'default'    => false,
							'state_name' => 'interactive',
							'label'      => __( 'Draggable markers', 'siteorigin-widgets' )
						),
						'marker_positions'  => array(
							'type'       => 'repeater',
							'label'      => __( 'Marker positions', 'siteorigin-widgets' ),
							'item_name'  => __( 'Marker', 'siteorigin-widgets' ),
							'item_label' => array(
								'selector'     => "[id*='marker_positions-place']",
								'update_event' => 'change',
								'value_method' => 'val'
							),
							'fields'     => array(
								'place' => array(
									'type'  => 'textarea',
									'rows'  => 2,
									'label' => __( 'Place', 'siteorigin-widgets' )
								)
							)
						)
					)
				),
				'styles'      => array(
					'type'        => 'section',
					'label'       => __( 'Styles', 'siteorigin-widgets' ),
					'hide'        => true,
					'description' => __( 'Apply custom colors to map features, or hide them completely.', 'siteorigin-widgets' ),
					'fields'      => array(
							'style_method'        => array(
							'type'    => 'radio',
							'default' => 'normal',
							'label'   => __( 'Map styles', 'siteorigin-widgets' ),
							'options' => array(
								'normal'   => __( 'Normal', 'siteorigin-widgets' ),
								'preset'   => __( 'Preset', 'siteorigin-widgets' ),
								'custom'   => __( 'Custom', 'siteorigin-widgets' ),
								'raw_json' => __( 'Raw JSON', 'siteorigin-widgets' ),
							)
						),
						'styled_map_name'   => array(
							'type'       => 'text',
							'state_name' => 'interactive',
							'label'      => __( 'Styled map name', 'siteorigin-widgets' )
						),
						'preset_map_styles' => array(
							'type'    => 'select',
							'label'   => __( 'Preset map styles', 'siteorigin-widgets' ),
							'options' => array(
								'apocalypse' => __( 'Apocalypse', 'siteorigin-widgets' ),
								'cartoon'    => __( 'Cartoon', 'siteorigin-widgets' ),
								'grayscale'  => __( 'Grayscale', 'siteorigin-widgets' ),
							)
						),
						'raw_json_map_styles'   => array(
							'type'        => 'textarea',
							'rows'        => 5,
							'hidden'      => true,
							'label'       => __( 'Raw JSON Styles', 'siteorigin-widgets' ),
							'description' => __( 'Copy and paste predefined styles here from <a href="http://snazzymaps.com/" target="_blank">Snazzy Maps</a>.', 'siteorigin-widgets' )
						),
						'custom_map_styles' => array(
							'type'       => 'repeater',
							'label'      => __( 'Custom Map Styles', 'siteorigin-widgets' ),
							'item_name'  => __( 'Style', 'siteorigin-widgets' ),
							'item_label' => array(
								'selector'     => "[id*='custom_map_styles-map_feature'] :selected",
								'update_event' => 'change',
								'value_method' => 'text'
							),
							'fields'     => array(
								'map_feature'  => array(
									'type'    => 'select',
									'label'   => __( 'Select map feature to style', 'siteorigin-widgets' ),
									'options' => array(
										'water'                       => __( 'Water', 'siteorigin-widgets' ),
										'road_highway'                => __( 'Highways', 'siteorigin-widgets' ),
										'road_arterial'               => __( 'Arterial roads', 'siteorigin-widgets' ),
										'road_local'                  => __( 'Local roads', 'siteorigin-widgets' ),
										'transit_line'                => __( 'Transit lines', 'siteorigin-widgets' ),
										'transit_station'             => __( 'Transit stations', 'siteorigin-widgets' ),
										'landscape_man-made'          => __( 'Man-made landscape', 'siteorigin-widgets' ),
										'landscape_natural_landcover' => __( 'Natural landscape landcover', 'siteorigin-widgets' ),
										'landscape_natural_terrain'   => __( 'Natural landscape terrain', 'siteorigin-widgets' ),
										'poi_attraction'              => __( 'Point of interest - Attractions', 'siteorigin-widgets' ),
										'poi_business'                => __( 'Point of interest - Business', 'siteorigin-widgets' ),
										'poi_government'              => __( 'Point of interest - Government', 'siteorigin-widgets' ),
										'poi_medical'                 => __( 'Point of interest - Medical', 'siteorigin-widgets' ),
										'poi_park'                    => __( 'Point of interest - Parks', 'siteorigin-widgets' ),
										'poi_place-of-worship'        => __( 'Point of interest - Places of worship', 'siteorigin-widgets' ),
										'poi_school'                  => __( 'Point of interest - Schools', 'siteorigin-widgets' ),
										'poi_sports-complex'          => __( 'Point of interest - Sports complexes', 'siteorigin-widgets' ),
									)
								),
								'element_type' => array(
									'type'    => 'select',
									'label'   => __( 'Select element type to style', 'siteorigin-widgets' ),
									'options' => array(
										'geometry' => __( 'Geometry', 'siteorigin-widgets' ),
										'labels'   => __( 'Labels', 'siteorigin-widgets' ),
										'all'      => __( 'All', 'siteorigin-widgets' ),
									)
								),
								'visibility'   => array(
									'type'    => 'checkbox',
									'default' => true,
									'label'   => __( 'Visible', 'siteorigin-widgets' )
								),
								'color'        => array(
									'type'  => 'color',
									'label' => __( 'Color', 'siteorigin-widgets' )
								)
							)
						)
					)
				),
				'directions'  => array(
					'type'        => 'section',
					'label'       => __( 'Directions', 'siteorigin-widgets' ),
					'state_name'  => 'interactive',
					'hide'        => true,
					'description' => __( 'Display a route on your map, with waypoints between your starting point and destination', 'siteorigin-widgets' ),
					'fields'      => array(
						'origin'             => array(
							'type'        => 'text',
							'label'       => __( 'Starting point', 'siteorigin-widgets' ),
							'description' => __( 'Choose a starting point.', 'siteorigin-widgets' )
						),
						'destination'        => array(
							'type'  => 'text',
							'label' => __( 'Destination', 'siteorigin-widgets' )
						),
						'travel_mode'        => array(
							'type'    => 'select',
							'label'   => __( 'Travel Mode', 'siteorigin-widgets' ),
							'default' => 'driving',
							'options' => array(
								'driving'   => __( 'Driving', 'siteorigin-widgets' ),
								'walking'   => __( 'Walking', 'siteorigin-widgets' ),
								'bicycling' => __( 'Bicycling', 'siteorigin-widgets' ),
								'transit'   => __( 'Transit', 'siteorigin-widgets' )
							)
						),
						'avoid_highways'     => array(
							'type'  => 'checkbox',
							'label' => __( 'Avoid Highways', 'siteorigin-widgets' ),
						),
						'avoid_tolls'        => array(
							'type'  => 'checkbox',
							'label' => __( 'Avoid Tolls', 'siteorigin-widgets' ),
						),
						'waypoints'          => array(
							'type'       => 'repeater',
							'label'      => __( 'Waypoints', 'siteorigin-widgets' ),
							'item_name'  => __( 'Waypoint', 'siteorigin-widgets' ),
							'item_label' => array(
								'selector'     => "[id*='waypoints-location']",
								'update_event' => 'change',
								'value_method' => 'val'
							),
							'fields'     => array(
								'location' => array(
									'type'  => 'textarea',
									'rows'  => 2,
									'label' => __( 'Location', 'siteorigin-widgets' )
								),
								'stopover' => array(
									'type'        => 'checkbox',
									'default'     => true,
									'label'       => __( 'Stopover', 'siteorigin-widgets' ),
									'description' => __( 'Whether or not this is a stop on the route or just a route preference.' )
								)
							)
						),
						'optimize_waypoints' => array(
							'type'        => 'checkbox',
							'label'       => __( 'Optimize waypoints', 'siteorigin-widgets' ),
							'default'     => false,
							'description' => __( 'Allow the service to reorder waypoints for the shortest travelling distance.', 'siteorigin-widgets' )
						)
					)
				),
				'api_key_section' => array(
					'type'        => 'section',
					'label'       => __( 'API key', 'siteorigin-widgets' ),
					'hide'        => true,
					'fields'      => array(
						'api_key'     => array(
							'type'        => 'text',
							'label'       => __( 'API key', 'siteorigin-widgets' ),
							'description' => __( 'Enter your API key if you have one. This enables you to monitor your Maps API usage in the Google APIs Console.', 'siteorigin-widgets' )
						)
					)
				)
			)
		);
	}

	function enqueue_admin_scripts() {
		wp_enqueue_script( 'sow-js-google-map', siteorigin_widget_get_plugin_dir_url( 'js-google-map' ) . 'js/js-map-admin.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
	}

	function enqueue_frontend_scripts() {
		wp_enqueue_script( 'sow-js-google-map', siteorigin_widget_get_plugin_dir_url( 'js-google-map' ) . 'js/js-map.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
	}

	function get_template_name( $instance ) {
		return $instance['settings']['map_type'] == 'static' ? 'static-map' : 'js-map';
	}

	function get_style_name( $instance ) {
		return $instance['settings']['map_type'] == 'static' ? '' : 'js-map';
	}

	function get_template_variables( $instance, $args ) {
		$settings = $instance['settings'];

		$mrkr_src = wp_get_attachment_image_src( $instance['markers']['marker_icon'] );

		$styles = $this->get_styles( $instance );

		if ( $settings['map_type'] == 'static' ) {
			$src_url = $this->get_static_image_src( $instance, $settings['width'], $settings['height'], ! empty( $styles ) ? $styles['styles'] : array() );

			return array(
				'src_url' => esc_url( $src_url )
			);
		} else {
			$markers = $instance['markers'];
			$directions_json = '';
			if ( ! empty( $instance['directions']['origin'] ) && ! empty( $instance['directions']['destination'] ) ) {
				if ( empty( $instance['directions']['waypoints'] )) {
					unset( $instance['directions']['waypoints'] );
				}
				$directions_json = json_encode( $this->underscores_to_camel_case( $instance['directions'] ) );
			}

			return array(
				'map_id'   => md5( $instance['map_center'] ),
				'height'   => $settings['height'],
				'map_data' => array(
					'address'           => $instance['map_center'],
					'zoom'              => $settings['zoom'],
					'scroll-zoom'       => $settings['scroll_zoom'],
					'draggable'         => $settings['draggable'],
					'marker-icon'       => ! empty( $mrkr_src ) ? $mrkr_src[0] : '',
					'markers-draggable' => $markers['markers_draggable'],
					'marker-at-center'  => $markers['marker_at_center'],
					'marker-positions'  => isset( $markers['marker_positions'] ) ? json_encode( $markers['marker_positions'] ) : '',
					'map-name'          => ! empty( $styles ) ? $styles['map_name'] : '',
					'map-styles'        => ! empty( $styles ) ? json_encode( $styles['styles'] ) : '',
					'directions'        => $directions_json,
					'api-key'           => $instance['api_key_section']['api_key']
				)
			);
		}
	}

	private function get_styles( $instance ) {
		$style_config = $instance['styles'];
		switch ( $style_config['style_method'] ) {
			case 'preset':
				$preset_name   = $style_config['preset_map_styles'];
				$map_name      = ! empty( $style_config['styled_map_name'] ) ? $style_config['styled_map_name'] : ucwords( $preset_name );
				$styles_string = file_get_contents( siteorigin_widget_get_plugin_dir_path( 'js-google-map' ) . 'map-styles/' . $preset_name . '.json' );

				return array( 'map_name' => $map_name, 'styles' => json_decode( $styles_string, true ) );
			case 'custom':
				if ( empty( $style_config['custom_map_styles'] ) ) {
					return array();
				} else {
					$map_name   = ! empty( $style_config['styled_map_name'] ) ? $style_config['styled_map_name'] : 'Custom Map';
					$map_styles = $style_config['custom_map_styles'];
					$styles     = array();
					foreach ( $map_styles as $style_item ) {
						$map_feature = $style_item['map_feature'];
						unset( $style_item['map_feature'] );
						$element_type = $style_item['element_type'];
						unset( $style_item['element_type'] );

						$stylers = array();
						foreach ( $style_item as $style_name => $style_value ) {
							if ( $style_value !== '' && ! is_null( $style_value ) ) {
								$style_value = $style_value === false ? 'off' : $style_value;
								array_push( $stylers, array( $style_name => $style_value ) );
							}
						}
						$map_feature = str_replace( '_', '.', $map_feature );
						$map_feature = str_replace( '-', '_', $map_feature );
						array_push( $styles, array(
							'featureType' => $map_feature,
							'elementType' => $element_type,
							'stylers'     => $stylers
						) );
					}

					return array( 'map_name' => $map_name, 'styles' => $styles );
				}
			case 'raw_json':
				if ( empty( $style_config['raw_json_map_styles'] ) ) {
					return array();
				} else {
					$map_name      = ! empty( $style_config['styled_map_name'] ) ? $style_config['styled_map_name'] : 'Custom Map';
					$styles_string = $style_config['raw_json_map_styles'];

					return array( 'map_name' => $map_name, 'styles' => json_decode( $styles_string, true ) );
				}
			case 'normal':
			default:
				return array();
		}
	}

	private function get_static_image_src( $instance, $width, $height, $styles ) {
		$src_url = "https://maps.googleapis.com/maps/api/staticmap?";
		$src_url .= "center=" . $instance['map_center'];
		$src_url .= "&zoom=" . $instance['settings']['zoom'];
		$src_url .= "&size=" . $width . "x" . $height;

		if ( ! empty( $instance['api_key_section']['api_key'] ) ) {
			$src_url .= "&key=" . $instance['api_key_section']['api_key'];
		}

		if ( ! empty( $styles ) ) {
			foreach ( $styles as $st ) {
				if ( empty( $st ) || ! isset( $st['stylers'] ) || empty( $st['stylers'] ) ) {
					continue;
				}
				$st_string = '';
				if ( isset ( $st['featureType'] ) ) {
					$st_string .= 'feature:' . $st['featureType'];
				}
				if ( isset ( $st['elementType'] ) ) {
					if ( ! empty( $st_string ) ) {
						$st_string .= "|";
					}
					$st_string .= 'element:' . $st['elementType'];
				}
				foreach ( $st['stylers'] as $style_prop_arr ) {
					foreach ( $style_prop_arr as $prop_name => $prop_val ) {
						if ( ! empty( $st_string ) ) {
							$st_string .= "|";
						}
						if ( $prop_val[0] == "#" ) {
							$prop_val = "0x" . substr( $prop_val, 1 );
						}
						if ( is_bool( $prop_val ) ) {
							$prop_val = $prop_val ? 'true' : 'false';
						}
						$st_string .= $prop_name . ":" . $prop_val;
					}
				}
				$st_string = '&style=' . $st_string;
				$src_url .= $st_string;
			}
		}

		if ( ! empty( $instance['markers'] ) ) {
			$markers    = $instance['markers'];
			$markers_st = '';

			if ( ! empty( $markers['marker_icon'] ) ) {
				$mrkr_src = wp_get_attachment_image_src( $markers['marker_icon'] );
				if ( ! empty( $mrkr_src ) ) {
					$markers_st .= 'icon:' . $mrkr_src[0];
				}
			}

			if ( $markers['marker_at_center'] ) {
				if ( ! empty( $markers_st ) ) {
					$markers_st .= "|";
				}
				$markers_st .= $instance['map_center'];
			}

			if ( ! empty( $markers['marker_positions'] ) ) {
				foreach ( $markers['marker_positions'] as $marker ) {
					if ( ! empty( $markers_st ) ) {
						$markers_st .= "|";
					}
					$markers_st .= urlencode( $marker['place'] );
				}
			}
			$markers_st = '&markers=' . $markers_st;
			$src_url .= $markers_st;
		}

		return $src_url;
	}
}

siteorigin_widget_register( 'js-google-map', __FILE__ );
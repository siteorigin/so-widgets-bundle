<?php

/*
Widget Name: Google Maps
Description: A highly customisable Google Maps widget. Help your site find its place and give it some direction.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/google-maps-widget/
*/

class SiteOrigin_Widget_GoogleMap_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'sow-google-map',
			__( 'SiteOrigin Google Maps', 'so-widgets-bundle' ),
			array(
				'description' => __( 'A highly customisable Google Maps widget. Help your site find its place and give it some direction.', 'so-widgets-bundle' ),
				'help'        => 'https://siteorigin.com/widgets-bundle/google-maps-widget/',
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);

		add_filter( 'siteorigin_widgets_field_class_paths', array( $this, 'add_location_field_path' ) );
	}

	// Tell the autoloader where to look for the location field class.
	public function add_location_field_path( $class_paths ) {
		$class_paths[] = plugin_dir_path( __FILE__ ) . 'fields/';

		return $class_paths;
	}

	public function initialize() {
		add_action( 'siteorigin_widgets_enqueue_frontend_scripts_sow-google-map', array( $this, 'enqueue_widget_scripts' ) );
	}

	public function get_widget_form() {
		return array(
			'map_center'      => array(
				'type'        => 'location',
				'rows'        => 2,
				'label'       => __( 'Map center', 'so-widgets-bundle' ),
				'description' => sprintf(
					__( 'The name of a place, town, city, or even a country. Can be an exact address too. Please ensure you have enabled the %sPlaces API%s and the %sGeocoding API%s in the %sGoogle APIs Dashboard%s.', 'so-widgets-bundle' ),
					'<strong>',
					'</strong>',
					'<strong>',
					'</strong>',
					'<a href="https://cloud.google.com/maps-platform/#get-started" target="_blank" rel="noopener noreferrer">',
					'</a>'
				),
			),
			'settings'        => array(
				'type'        => 'section',
				'label'       => __( 'Settings', 'so-widgets-bundle' ),
				'hide'        => false,
				'description' => __( 'Set map display options.', 'so-widgets-bundle' ),
				'fields'      => array(
					'map_type'    => array(
						'type'    => 'radio',
						'default' => 'interactive',
						'label'   => __( 'Map type', 'so-widgets-bundle' ),
						'state_emitter' => array(
							'callback' => 'select',
							'args' => array( 'map_type' ),
						),
						'options' => array(
							'interactive' => __( 'Interactive', 'so-widgets-bundle' ),
							'static'      => __( 'Static image', 'so-widgets-bundle' ),
						),
						'description' => sprintf(
							__( 'Please ensure you have enabled the %sJavaScript API%s for Interactive maps or %sStatic API%s for Static maps in the %sGoogle APIs Dashboard%s.', 'so-widgets-bundle' ),
							'<strong>',
							'</strong>',
							'<strong>',
							'</strong>',
							'<a href="https://cloud.google.com/maps-platform/#get-started" target="_blank" rel="noopener noreferrer">',
							'</a>'
						),
					),
					'width'       => array(
						'type'       => 'text',
						'default'    => 640,
						'hidden'     => true,
						'state_handler' => array(
							'map_type[static]' => array( 'show' ),
							'_else[map_type]' => array( 'hide' ),
						),
						'label'      => __( 'Width', 'so-widgets-bundle' ),
					),
					'height'      => array(
						'type'    => 'text',
						'label'   => __( 'Height', 'so-widgets-bundle' ),
						'default' => 480,
					),
					'destination_url' => array(
						'type' => 'link',
						'label' => __( 'Destination URL', 'so-widgets-bundle' ),
						'hidden'     => true,
						'state_handler' => array(
							'map_type[static]' => array( 'show' ),
							'_else[map_type]' => array( 'hide' ),
						),
					),

					'new_window' => array(
						'type' => 'checkbox',
						'default' => false,
						'label' => __( 'Open in a new window', 'so-widgets-bundle' ),
						'hidden'     => true,
						'state_handler' => array(
							'map_type[static]' => array( 'show' ),
							'_else[map_type]' => array( 'hide' ),
						),
					),

					'zoom'        => array(
						'type'        => 'slider',
						'label'       => __( 'Zoom level', 'so-widgets-bundle' ),
						'description' => __( 'A value from 0 (the world) to 21 (street level).', 'so-widgets-bundle' ),
						'min'         => 0,
						'max'         => 21,
						'default'     => 12,
						'integer'     => true,
					),

					'mobile_zoom'        => array(
						'type'        => 'slider',
						'label'       => __( 'Mobile zoom level', 'so-widgets-bundle' ),
						'description' => __( 'A value from 0 (the world) to 21 (street level). This zoom is specific to mobile devices.', 'so-widgets-bundle' ),
						'min'         => 0,
						'max'         => 21,
						'default'     => 12,
						'integer'     => true,
						'state_handler' => array(
							'map_type[interactive]' => array( 'show' ),
							'_else[map_type]' => array( 'hide' ),
						),
					),

					'gesture_handling'   => array(
						'type'        => 'radio',
						'label'       => __( 'Gesture Handling', 'so-widgets-bundle' ),
						'default'     => 'greedy',
						'state_handler' => array(
							'map_type[interactive]' => array( 'show' ),
							'_else[map_type]' => array( 'hide' ),
						),
						'options' => array(
							'greedy'      => __( 'Greedy', 'so-widgets-bundle' ),
							'cooperative' => __( 'Cooperative', 'so-widgets-bundle' ),
							'none'        => __( 'None', 'so-widgets-bundle' ),
							'auto'        => __( 'Auto', 'so-widgets-bundle' ),
						),
						'description' => sprintf(
							__( 'For information on what these settings do, %sclick here%s.', 'so-widgets-bundle' ),
							'<a href="https://developers.google.com/maps/documentation/javascript/interaction#gestureHandling" target="_blank" rel="noopener noreferrer">',
							'</a>'
						),
					),
					'disable_default_ui' => array(
						'type' => 'checkbox',
						'default' => false,
						'state_handler' => array(
							'map_type[interactive]' => array( 'show' ),
							'_else[map_type]' => array( 'hide' ),
						),
						'label'       => __( 'Disable default UI', 'so-widgets-bundle' ),
						'description' => __( 'Hides the default Google Maps controls.', 'so-widgets-bundle' ),
					),
					'keep_centered' => array(
						'type' => 'checkbox',
						'default' => false,
						'state_handler' => array(
							'map_type[interactive]' => array( 'show' ),
							'_else[map_type]' => array( 'hide' ),
						),
						'label'       => __( 'Keep map centered', 'so-widgets-bundle' ),
						'description' => __( 'Keeps the map centered when it\'s container is resized.', 'so-widgets-bundle' ),
					),
					'fallback_image' => array(
						'type' => 'media',
						'label' => __( 'Fallback Image', 'so-widgets-bundle' ),
						'description' => __( 'This image will be displayed if there are any problems with displaying the specified map.', 'so-widgets-bundle' ),
						'library' => 'image',
					),
					'fallback_image_size' => array(
						'type' => 'image-size',
						'label' => __( 'Fallback Image Size', 'so-widgets-bundle' ),
					),
				),
			),
			'markers'         => array(
				'type'        => 'section',
				'label'       => __( 'Markers', 'so-widgets-bundle' ),
				'hide'        => true,
				'description' => __( 'Use markers to identify points of interest on the map.', 'so-widgets-bundle' ),
				'fields'      => array(
					'marker_at_center'  => array(
						'type'    => 'checkbox',
						'default' => true,
						'label'   => __( 'Show marker at map center', 'so-widgets-bundle' ),
					),
					'marker_icon'       => array(
						'type'        => 'media',
						'default'     => '',
						'label'       => __( 'Marker icon', 'so-widgets-bundle' ),
						'description' => __( 'Replaces the default map marker with your own image.', 'so-widgets-bundle' ),
					),
					'marker_icon_size' => array(
						'type' => 'image-size',
						'label' => __( 'Marker icon size', 'so-widgets-bundle' ),
					),
					'markers_draggable' => array(
						'type'       => 'checkbox',
						'default'    => false,
						'state_handler' => array(
							'map_type[interactive]' => array( 'show' ),
							'_else[map_type]' => array( 'hide' ),
						),
						'label'      => __( 'Draggable markers', 'so-widgets-bundle' ),
					),
					'marker_positions'  => array(
						'type'       => 'repeater',
						'label'      => __( 'Marker positions', 'so-widgets-bundle' ),
						'item_name'  => __( 'Marker', 'so-widgets-bundle' ),
						'item_label' => array(
							'selector'     => '.siteorigin-widget-location-input',
							'update_event' => 'change',
							'value_method' => 'val',
						),
						'fields'     => array(
							'place' => array(
								'type'  => 'location',
								'rows'  => 2,
								'label' => __( 'Place', 'so-widgets-bundle' ),
							),
							'info' => array(
								'type' => 'tinymce',
								'rows' => 10,
								'label' => __( 'Info Window Content', 'so-widgets-bundle' ),
							),
							'info_max_width' => array(
								'type' => 'text',
								'label' => __( 'Info Window max width', 'so-widgets-bundle' ),
							),
							'custom_marker_icon'       => array(
								'type'        => 'media',
								'default'     => '',
								'label'       => __( 'Custom Marker icon', 'so-widgets-bundle' ),
								'description' => __( 'Replace the default map marker with your own image for each marker.', 'so-widgets-bundle' ),
							),
							'custom_marker_icon_size' => array(
								'type' => 'image-size',
								'label' => __( 'Custom marker icon size', 'so-widgets-bundle' ),
							),
						),
					),
					'info_display' => array(
						'type' => 'radio',
						'label' => __( 'When should Info Windows be displayed?', 'so-widgets-bundle' ),
						'default' => 'click',
						'options' => array(
							'click'   => __( 'Click', 'so-widgets-bundle' ),
							'mouseover'   => __( 'Mouse over', 'so-widgets-bundle' ),
							'always' => __( 'Always', 'so-widgets-bundle' ),
						),
					),
					'info_multiple' => array(
						'type' => 'checkbox',
						'label' => __( 'Allow multiple simultaneous Info Windows?', 'so-widgets-bundle' ),
						'default' => true,
						'description' => __( 'This setting is ignored when Info Windows are set to always display.' ),
					),
				),
			),
			'styles'          => array(
				'type'        => 'section',
				'label'       => __( 'Styles', 'so-widgets-bundle' ),
				'hide'        => true,
				'description' => __( 'Apply custom colors to map features, or hide them completely.', 'so-widgets-bundle' ),
				'fields'      => array(
					'style_method'        => array(
						'type'    => 'radio',
						'default' => 'normal',
						'label'   => __( 'Map styles', 'so-widgets-bundle' ),
						'state_emitter' => array(
							'callback' => 'select',
							'args' => array( 'style_method' ),
						),
						'options' => array(
							'normal'   => __( 'Default', 'so-widgets-bundle' ),
							'custom'   => __( 'Custom', 'so-widgets-bundle' ),
							'raw_json' => __( 'Predefined Styles', 'so-widgets-bundle' ),
						),
					),
					'styled_map_name'     => array(
						'type'       => 'text',
						'state_handler' => array(
							'style_method[normal]' => array( 'hide' ),
							'_else[style_method]' => array( 'show' ),
						),
						'label'      => __( 'Styled map name', 'so-widgets-bundle' ),
					),
					'raw_json_map_styles' => array(
						'type'        => 'textarea',
						'state_handler' => array(
							'style_method[raw_json]' => array( 'show' ),
							'_else[style_method]' => array( 'hide' ),
						),
						'rows'        => 5,
						'hidden'      => true,
						'label'       => __( 'Raw JSON styles', 'so-widgets-bundle' ),
						'description' => __( 'Copy and paste predefined styles here from <a href="http://snazzymaps.com/" target="_blank" rel="noopener noreferrer">Snazzy Maps</a>.', 'so-widgets-bundle' ),
					),
					'custom_map_styles'   => array(
						'type'       => 'repeater',
						'state_handler' => array(
							'style_method[custom]' => array( 'show' ),
							'_else[style_method]' => array( 'hide' ),
						),
						'label'      => __( 'Custom map styles', 'so-widgets-bundle' ),
						'item_name'  => __( 'Style', 'so-widgets-bundle' ),
						'item_label' => array(
							'selector'     => "[id*='custom_map_styles-map_feature'] :selected",
							'update_event' => 'change',
							'value_method' => 'text',
						),
						'fields'     => array(
							'map_feature'  => array(
								'type'    => 'select',
								'label'   => '',
								'prompt'  => __( 'Select map feature to style', 'so-widgets-bundle' ),
								'options' => array(
									'water'                       => __( 'Water', 'so-widgets-bundle' ),
									'road_highway'                => __( 'Highways', 'so-widgets-bundle' ),
									'road_arterial'               => __( 'Arterial roads', 'so-widgets-bundle' ),
									'road_local'                  => __( 'Local roads', 'so-widgets-bundle' ),
									'transit_line'                => __( 'Transit lines', 'so-widgets-bundle' ),
									'transit_station'             => __( 'Transit stations', 'so-widgets-bundle' ),
									'landscape_man-made'          => __( 'Man-made landscape', 'so-widgets-bundle' ),
									'landscape_natural_landcover' => __( 'Natural landscape landcover', 'so-widgets-bundle' ),
									'landscape_natural_terrain'   => __( 'Natural landscape terrain', 'so-widgets-bundle' ),
									'poi_attraction'              => __( 'Point of interest - Attractions', 'so-widgets-bundle' ),
									'poi_business'                => __( 'Point of interest - Business', 'so-widgets-bundle' ),
									'poi_government'              => __( 'Point of interest - Government', 'so-widgets-bundle' ),
									'poi_medical'                 => __( 'Point of interest - Medical', 'so-widgets-bundle' ),
									'poi_park'                    => __( 'Point of interest - Parks', 'so-widgets-bundle' ),
									'poi_place-of-worship'        => __( 'Point of interest - Places of worship', 'so-widgets-bundle' ),
									'poi_school'                  => __( 'Point of interest - Schools', 'so-widgets-bundle' ),
									'poi_sports-complex'          => __( 'Point of interest - Sports complexes', 'so-widgets-bundle' ),
								),
							),
							'element_type' => array(
								'type'    => 'select',
								'label'   => __( 'Select element type to style', 'so-widgets-bundle' ),
								'options' => array(
									'geometry' => __( 'Geometry', 'so-widgets-bundle' ),
									'labels'   => __( 'Labels', 'so-widgets-bundle' ),
									'all'      => __( 'All', 'so-widgets-bundle' ),
								),
							),
							'visibility'   => array(
								'type'    => 'checkbox',
								'default' => true,
								'label'   => __( 'Visible', 'so-widgets-bundle' ),
							),
							'color'        => array(
								'type'  => 'color',
								'label' => __( 'Color', 'so-widgets-bundle' ),
							),
						),
					),
				),
			),
			'directions'      => array(
				'type'        => 'section',
				'label'       => __( 'Directions', 'so-widgets-bundle' ),
				'state_handler' => array(
					'map_type[interactive]' => array( 'show' ),
					'_else[map_type]' => array( 'hide' ),
				),
				'hide'        => true,
				'description' => sprintf(
					__( 'Display a route on your map, with waypoints between your starting point and destination. Please ensure you have enabled the %sDirections API%s in the %sGoogle APIs Dashboard%s.', 'so-widgets-bundle' ),
					'<strong>',
					'</strong>',
					'<a href="https://cloud.google.com/maps-platform/#get-started" target="_blank" rel="noopener noreferrer">',
					'</a>'
				),
				'fields'      => array(
					'origin'             => array(
						'type'  => 'text',
						'label' => __( 'Starting point', 'so-widgets-bundle' ),
					),
					'destination'        => array(
						'type'  => 'text',
						'label' => __( 'Destination', 'so-widgets-bundle' ),
					),
					'travel_mode'        => array(
						'type'    => 'select',
						'label'   => __( 'Travel mode', 'so-widgets-bundle' ),
						'default' => 'driving',
						'options' => array(
							'driving'   => __( 'Driving', 'so-widgets-bundle' ),
							'walking'   => __( 'Walking', 'so-widgets-bundle' ),
							'bicycling' => __( 'Bicycling', 'so-widgets-bundle' ),
							'transit'   => __( 'Transit', 'so-widgets-bundle' ),
						),
					),
					'avoid_highways'     => array(
						'type'  => 'checkbox',
						'label' => __( 'Avoid highways', 'so-widgets-bundle' ),
					),
					'avoid_tolls'        => array(
						'type'  => 'checkbox',
						'label' => __( 'Avoid tolls', 'so-widgets-bundle' ),
					),
					'preserve_viewport' => array(
						'type'  => 'checkbox',
						'label' => __( 'Preserve viewport', 'so-widgets-bundle' ),
						'description' => __( 'This will prevent the map from centering and zooming around the directions. Use this when you have other markers or features on your map.', 'so-widgets-bundle' ),
					),
					'waypoints'          => array(
						'type'       => 'repeater',
						'label'      => __( 'Waypoints', 'so-widgets-bundle' ),
						'item_name'  => __( 'Waypoint', 'so-widgets-bundle' ),
						'item_label' => array(
							'selector'     => "[id*='waypoints-location']",
							'update_event' => 'change',
							'value_method' => 'val',
						),
						'fields'     => array(
							'location' => array(
								'type'  => 'textarea',
								'rows'  => 2,
								'label' => __( 'Location', 'so-widgets-bundle' ),
							),
							'stopover' => array(
								'type'        => 'checkbox',
								'default'     => true,
								'label'       => __( 'Stopover', 'so-widgets-bundle' ),
								'description' => __( 'Whether or not this is a stop on the route or just a route preference.', 'so-widgets-bundle' ),
							),
						),
					),
					'optimize_waypoints' => array(
						'type'        => 'checkbox',
						'label'       => __( 'Optimize waypoints', 'so-widgets-bundle' ),
						'default'     => false,
						'description' => __( 'Allow the Google Maps service to reorder waypoints for the shortest travelling distance.', 'so-widgets-bundle' ),
					),
				),
			),
		);
	}

	public function get_settings_form() {
		return array(
			'api_key' => array(
				'type'        => 'text',
				'label'       => __( 'API key', 'so-widgets-bundle' ),
				'required'    => true,
				'description' => sprintf(
					__( 'Enter your %sAPI key%s. Your map won\'t function correctly without one.', 'so-widgets-bundle' ),
					'<a href="https://cloud.google.com/maps-platform/#get-started" target="_blank" rel="noopener noreferrer">',
					'</a>'
				),
			),

			'map_consent' => array(
				'type' => 'checkbox',
				'label' => __( 'Require consent before loading Maps API', 'so-widgets-bundle' ),
				'description' => __( 'Consent is required for the Google Maps widget to comply with regulations like DSGVO, or GDPR.', 'so-widgets-bundle' ),
				'default' => false,
			),

			'map_consent_btn_text' => array(
				'type' => 'text',
				'label' => __( 'Consent button text', 'so-widgets-bundle' ),
				'default' => __( 'Load map', 'so-widgets-bundle' ),
			),

			'map_consent_notice' => array(
				'type' => 'tinymce',
				'label' => __( 'Consent prompt text', 'so-widgets-bundle' ),
				'description' => __( 'This is text is displayed when a user is prompted to consent to load the Google Maps API.', 'so-widgets-bundle' ),
				'default' => __( "By loading, you agree to Google's privacy policy.

				<a href='https://policies.google.com/privacy?hl=en&amp;gl=en' target='_blank' rel='noopener noreferrer'>Read more</a>", 'so-widgets-bundle' ),
			),

			'map_consent_design' => array(
				'type' => 'section',
				'label' => __( 'Consent prompt design', 'so-widgets-bundle' ),
				'hide' => true,
				'fields' => array(
					'button' => array(
						'type' => 'section',
						'label' => __( 'Button', 'so-widgets-bundle' ),
						'hide' => true,
						'fields' => array(
							'color' => array(
								'type' => 'color',
								'label' => __( 'Consent prompt button text color', 'so-widgets-bundle' ),
								'default' => '#fff',
							),
							'color_hover' => array(
								'type' => 'color',
								'label' => __( 'Consent prompt button text hover color', 'so-widgets-bundle' ),
							),
							'background' => array(
								'type' => 'color',
								'label' => __( 'Consent prompt button background color', 'so-widgets-bundle' ),
								'default' => '#41a9d5',
							),
							'background_hover' => array(
								'type' => 'color',
								'label' => __( 'Consent prompt button background hover color', 'so-widgets-bundle' ),
								'default' => '#298fba',
							),
						),
					),
				),
			),

			'responsive_breakpoint' => array(
				'type'        => 'number',
				'label'       => __( 'Responsive breakpoint', 'so-widgets-bundle' ),
				'default'     => '780',
				'description' => __( 'This setting controls when the map will use the mobile zoom. This breakpoint will only be used if a mobile zoom is set in the SiteOrigin Google Maps settings. The default value is 780px', 'so-widgets-bundle' ),
			),
		);
	}

	public function get_template_name( $instance ) {
		return $instance['settings']['map_type'] == 'static' ? 'static-map' : 'js-map';
	}

	public function get_style_name( $instance ) {
		if ( $instance['settings']['map_type'] == 'static' ) {
			return false;
		}

		return 'default';
	}

	public function get_template_variables( $instance, $args ) {
		if ( empty( $instance ) ) {
			return array();
		}

		$settings = $instance['settings'];

		$mrkr_src = wp_get_attachment_image_src(
			$instance['markers']['marker_icon'],
			! empty( $instance['markers']['marker_icon_size'] ) ? $instance['markers']['marker_icon_size'] : 'thumbnail'
		);

		$styles = $this->get_styles( $instance );

		$fallback_image = '';

		if ( ! empty( $instance['settings']['fallback_image'] ) ) {
			$fallback_image = siteorigin_widgets_get_attachment_image(
				$instance['settings']['fallback_image'],
				$instance['settings']['fallback_image_size'],
				false
			);
		}
		$global_settings = $this->get_global_settings();
		$breakpoint = ! empty( $global_settings['responsive_breakpoint'] ) ? $global_settings['responsive_breakpoint'] : '780';

		if ( $settings['map_type'] == 'static' ) {
			return array(
				'src_url'             => $this->get_static_image_src( $instance, $settings['width'], $settings['height'], ! empty( $styles['styles'] ) ? $styles['styles'] : array() ),
				'destination_url'     => $instance['settings']['destination_url'],
				'new_window'          => $instance['settings']['new_window'],
				'fallback_image_data' => array( 'img' => $fallback_image ),
				'breakpoint'        => $breakpoint,
			);
		} else {
			$markers = $instance['markers'];
			$directions = '';

			if ( ! empty( $instance['directions']['origin'] ) && ! empty( $instance['directions']['destination'] ) ) {
				if ( empty( $instance['directions']['waypoints'] ) ) {
					unset( $instance['directions']['waypoints'] );
				}
				$directions = siteorigin_widgets_underscores_to_camel_case( $instance['directions'] );
			}

			$markerpos = isset( $markers['marker_positions'] ) ? $markers['marker_positions'] : '';

			if ( ! empty( $markerpos ) ) {
				foreach ( $markerpos as &$pos ) {
					if ( ! empty( $pos['custom_marker_icon'] ) ) {
						$icon_src = wp_get_attachment_image_src(
							$pos['custom_marker_icon'],
							! empty( $pos['custom_marker_icon_size'] ) ? $pos['custom_marker_icon_size'] : 'thumbnail'
						);
						$pos['custom_marker_icon'] = $icon_src[0];
					}

					if ( ! empty( $pos['place'] ) ) {
						$pos['place'] = $this->get_location_string( $pos['place'] );
					}
				}
			}

			$location = $this->get_location_string( $instance['map_center'] );

			$map_data = siteorigin_widgets_underscores_to_camel_case( array(
				'address'           => $location,
				'zoom'              => $settings['zoom'],
				'mobileZoom'        => $settings['mobile_zoom'],
				'gestureHandling'   => isset( $settings['gesture_handling'] ) ? $settings['gesture_handling'] : 'greedy',
				'disable_ui'        => $settings['disable_default_ui'],
				'keep_centered'     => $settings['keep_centered'],
				'marker_icon'       => ! empty( $mrkr_src ) ? $mrkr_src[0] : '',
				'markers_draggable' => isset( $markers['markers_draggable'] ) ? $markers['markers_draggable'] : '',
				'marker_at_center'  => ! empty( $markers['marker_at_center'] ),
				'marker_info_display' => $markers['info_display'],
				'marker_info_multiple' => $markers['info_multiple'],
				'marker_positions'  => ! empty( $markerpos ) ? $markerpos : '',
				'map_name'          => ! empty( $styles['styles'] ) ? $styles['map_name'] : '',
				'map_styles'        => ! empty( $styles['styles'] ) ? $styles['styles'] : '',
				'directions'        => $directions,
				'api_key'           => self::get_api_key( $instance ),
				'breakpoint'        => $breakpoint,
			) );

			return array(
				'map_id'   => md5( json_encode( $instance ) ),
				'map_data' => $map_data,
				'fallback_image_data' => array( 'img' => $fallback_image ),
				'map_consent' => ! empty( $global_settings['map_consent'] ),
				'map_consent_notice' => ! empty( $global_settings['map_consent_notice'] ) ? $global_settings['map_consent_notice'] : '',
				'map_consent_btn_text' => ! empty( $global_settings['map_consent_btn_text'] ) ? $global_settings['map_consent_btn_text'] : '',
				'consent_background_image' => plugin_dir_url( __FILE__ ) . 'assets/map-consent-background.jpg',
			);
		}
	}

	public function get_less_variables( $instance ) {
		$global_settings = $this->get_global_settings();
		$less_variables = array(
			'height' => $instance['settings']['height'] . 'px',
			'map_consent' => ! empty( $global_settings['map_consent'] ),
			'responsive_breakpoint' => ! empty( $global_settings['responsive_breakpoint'] ) ? $global_settings['responsive_breakpoint'] : '780',
		);

		// Map Content Button styling.
		if ( $less_variables['map_consent'] ) {
			foreach ( $global_settings['map_consent_design']['button'] as $style => $value ) {
				if ( ! empty( $value ) ) {
					$less_variables[ 'map_consent_notice_button_' . $style ] = $value;
				}
			}
		}

		return $less_variables;
	}

	private function get_location_string( $location_data ) {
		$location = '';

		if ( ! empty( $location_data['location'] ) ) {
			$location = $location_data['location'];
			$location = preg_replace( '/[\(\)]/', '', $location );
		} elseif ( ! empty( $location_data['address'] ) ) {
			$location = $location_data['address'];
		} elseif ( ! empty( $location_data['name'] ) ) {
			$location = $location_data['name'];
		}

		return $location;
	}

	public function enqueue_widget_scripts( $instance ) {
		if ( ! empty( $instance['settings']['map_type'] ) && $instance['settings']['map_type'] == 'interactive' ||
			 $this->is_preview( $instance ) ) {
			wp_enqueue_script( 'sow-google-map' );

			$global_settings = $this->get_global_settings();

			wp_localize_script(
				'sow-google-map',
				'soWidgetsGoogleMap',
				array(
					'map_consent'  => ! empty( $global_settings['map_consent'] ),
					'geocode' => array(
						'noResults' => __( 'There were no results for the place you entered. Please try another.', 'so-widgets-bundle' ),
					),
				)
			);
		}

		if ( ! empty( $instance['settings']['map_type'] ) && $instance['settings']['map_type'] == 'static' ||
			 $this->is_preview( $instance ) ) {
			wp_enqueue_script(
				'sow-google-map-static',
				plugin_dir_url( __FILE__ ) . 'js/static-map' . SOW_BUNDLE_JS_SUFFIX . '.js',
				array( 'jquery' ),
				SOW_BUNDLE_VERSION
			);
		}
	}

	private function get_styles( $instance ) {
		$style_config = $instance['styles'];
		$styles = array();
		$styles['map_name'] = ! empty( $style_config['styled_map_name'] ) ? $style_config['styled_map_name'] : __( 'Custom Map', 'so-widgets-bundle' );

		switch ( $style_config['style_method'] ) {
			case 'custom':
				if ( ! empty( $style_config['custom_map_styles'] ) ) {
					$map_styles = $style_config['custom_map_styles'];
					$style_values = array();

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
						array_push( $style_values, array(
							'featureType' => $map_feature,
							'elementType' => $element_type,
							'stylers'     => $stylers,
						) );
					}

					$styles['styles'] = $style_values;
				}
				break;

			case 'raw_json':
				if (
					! empty( $style_config['raw_json_map_styles'] ) &&
					is_string( $style_config['raw_json_map_styles'] )
				) {
					$styles['styles'] = json_decode( $style_config['raw_json_map_styles'], true );
				}
				break;

			case 'normal':
			default:
				break;
		}

		return apply_filters( 'siteorigin_widgets_google_maps_widget_styles', $styles, $instance );
	}

	private function get_static_image_src( $instance, $width, $height, $styles ) {
		$location = $this->get_location_string( $instance['map_center'] );
		$src_url = 'https://maps.googleapis.com/maps/api/staticmap?';
		$src_url .= 'center=' . $location;
		$src_url .= '&zoom=' . $instance['settings']['zoom'];
		$src_url .= '&size=' . $width . 'x' . $height;

		$api_key = self::get_api_key( $instance );

		if ( ! empty( $api_key ) ) {
			$src_url .= '&key=' . $api_key;
		}

		if ( ! empty( $styles ) ) {
			foreach ( $styles as $st ) {
				if ( empty( $st ) || ! isset( $st['stylers'] ) || empty( $st['stylers'] ) ) {
					continue;
				}
				$st_string = '';

				if ( isset( $st['featureType'] ) ) {
					$st_string .= 'feature:' . $st['featureType'];
				}

				if ( isset( $st['elementType'] ) ) {
					if ( ! empty( $st_string ) ) {
						$st_string .= '|';
					}
					$st_string .= 'element:' . $st['elementType'];
				}

				foreach ( $st['stylers'] as $style_prop_arr ) {
					foreach ( $style_prop_arr as $prop_name => $prop_val ) {
						if ( ! empty( $st_string ) ) {
							$st_string .= '|';
						}

						if ( is_bool( $prop_val ) ) {
							$prop_val = $prop_val ? 'true' : 'false';
						} elseif ( $prop_val[0] == '#' ) {
							$prop_val = '0x' . substr( $prop_val, 1 );
						}
						$st_string .= $prop_name . ':' . $prop_val;
					}
				}
				$st_string = '&style=' . $st_string;
				$src_url .= $st_string;
			}
		}

		if ( ! empty( $instance['markers'] ) ) {
			$markers = $instance['markers'];
			$markers_st = '';

			if ( ! empty( $markers['marker_icon'] ) ) {
				$mrkr_src = wp_get_attachment_image_src(
					$markers['marker_icon'],
					! empty( $markers['marker_icon_size'] ) ? $markers['marker_icon_size'] : 'thumbnail'
				);

				if ( ! empty( $mrkr_src ) ) {
					$markers_st .= 'icon:' . $mrkr_src[0];
				}
			}

			if ( ! empty( $markers['marker_at_center'] ) ) {
				if ( ! empty( $markers_st ) ) {
					$markers_st .= '|';
				}
				$markers_st .= $location;
			}

			if ( ! empty( $markers['marker_positions'] ) ) {
				foreach ( $markers['marker_positions'] as $marker ) {
					if ( ! empty( $markers_st ) ) {
						$markers_st .= '|';
					}
					$markers_st .= urlencode( $this->get_location_string( $marker['place'] ) );
				}
			}
			$markers_st = '&markers=' . $markers_st;
			$src_url .= $markers_st;
		}

		return $src_url;
	}

	public function modify_instance( $instance ) {
		if ( ! empty( $instance['settings'] ) ) {
			if ( empty( $instance['settings']['mobile_zoom'] ) ) {
				// Check if a zoom is set, and if it is, set the mobile zoom to that
				if ( empty( $instance['settings']['zoom'] ) ) {
					$instance['settings']['mobile_zoom'] = 12;
				} else {
					$instance['settings']['mobile_zoom'] = $instance['settings']['zoom'];
				}
			}

			// Migrate draggable and scroll_zoom to gesture_handling
			if ( isset( $instance['settings']['draggable'] ) || isset( $instance['settings']['scroll_zoom'] ) ) {
				if ( isset( $instance['settings']['draggable'] ) && ! $instance['settings']['draggable'] ) {
					$instance['settings']['gesture_handling'] = 'none';
				} elseif ( isset( $instance['settings']['scroll_zoom'] ) && ! $instance['settings']['scroll_zoom'] ) {
					$instance['settings']['gesture_handling'] = 'cooperative';
				} else {
					$instance['settings']['gesture_handling'] = 'greedy';
				}

				// Remove draggable and scroll_zoom settings due to being deprecated
				unset( $instance['settings']['draggable'] );
				unset( $instance['settings']['scroll_zoom'] );
			}

			if ( empty( $instance['settings']['height'] ) ) {
				$instance['settings']['height'] = 480;
			}
		}

		if ( ! empty( $instance['map_center'] ) && empty( $instance['map_center']['name'] ) ) {
			$instance['map_center'] = $this->migrate_location( $instance['map_center'] );
		}

		if ( ! empty( $instance['markers'] ) && ! empty( $instance['markers']['marker_positions'] ) ) {
			foreach ( $instance['markers']['marker_positions'] as &$marker_position ) {
				if ( ! empty( $marker_position['place'] ) && empty( $marker_position['place']['name'] ) ) {
					$marker_position['place'] = $this->migrate_location( $marker_position['place'] );
				}
			}
		}

		// The API key form field has been removed. Migrate any previously set API keys to the global settings.
		if ( ! empty( $instance['api_key_section'] ) && ! empty( $instance['api_key_section']['api_key'] ) ) {
			$global_settings = $this->get_global_settings();

			if ( empty( $global_settings['api_key'] ) ) {
				$global_settings['api_key'] = $instance['api_key_section']['api_key'];
				$this->save_global_settings( $global_settings );
			}
			unset( $instance['api_key_section'] );
		}

		return $instance;
	}

	private function migrate_location( $location_data ) {
		if ( is_string( $location_data ) ) {
			$raw_location = json_decode( $location_data, true );
		} else {
			$raw_location = $location_data;
		}

		$location = array();
		// If it's not valid JSON
		if ( $raw_location == null ) {
			$location = array( 'address' => $location_data );
		} elseif ( is_array( $raw_location ) ) {
			$location = array();

			if ( ! empty( $raw_location['name'] ) ) {
				$location['name'] = $raw_location['name'];
			}

			if ( ! empty( $raw_location['address'] ) ) {
				$location['address'] = $raw_location['address'];
			}

			if ( ! empty( $raw_location['location'] ) ) {
				$location['location'] = $raw_location['location'];
			}
		}

		return $location;
	}

	public static function get_api_key( $instance ) {
		$widget = new self();
		$global_settings = $widget->get_global_settings();
		$api_key = '';

		if ( ! empty( $global_settings['api_key'] ) ) {
			$api_key = $global_settings['api_key'];
		}

		if ( ! empty( $instance['api_key_section'] ) && ! empty( $instance['api_key_section']['api_key'] ) ) {
			$api_key = $instance['api_key_section']['api_key'];
		}

		return trim( $api_key );
	}

	public function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return array(
			sprintf(
				__( 'Get additional map consent design settings with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/map-styles" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
			sprintf(
				__( 'Get a curated list of predefined map styles with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/map-styles" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
			sprintf(
				__( 'Use Google Fonts right inside the Google Maps Widget with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/web-font-selector" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
		);
	}
}

siteorigin_widget_register( 'sow-google-map', __FILE__, 'SiteOrigin_Widget_GoogleMap_Widget' );

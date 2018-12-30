<?php

/*
Widget Name: Social Media Buttons
Description: Customizable buttons which link to all your social media profiles.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/social-media-buttons-widget/
*/


class SiteOrigin_Widget_SocialMediaButtons_Widget extends SiteOrigin_Widget {

	private $networks;

	function __construct() {
		parent::__construct(
			'sow-social-media-buttons',
			__( 'SiteOrigin Social Media Buttons', 'so-widgets-bundle' ),
			array(
				'description' => __( 'A social media buttons widget.', 'so-widgets-bundle' )
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	function get_settings_form() {
		return array(
			'responsive_breakpoint' => array(
				'type'        => 'measurement',
				'label'       => __( 'Responsive Breakpoint', 'so-widgets-bundle' ),
				'default'     => 780,
				'description' => __( 'This setting controls when the Mobile Align setting will be used. The default value is 780px', 'so-widgets-bundle' ),
			)
		);
	}

	function get_widget_form(){

		if( empty( $this->networks ) ) {
			$this->networks = include plugin_dir_path( __FILE__ ) . 'data/networks.php';
		}

		$network_names = array();
		foreach ( $this->networks as $key => $value ) {
			$network_names[ $key ] = $value['label'];
		}

		return array(
			'title' => array(
				'type' => 'text',
				'label' => __( 'Title', 'so-widgets-bundle' ),
			),
			'networks' => array(
				'type'       => 'repeater',
				'label'      => __( 'Networks', 'so-widgets-bundle' ),
				'item_name'  => __( 'Network', 'so-widgets-bundle' ),
				'item_label' => array(
					'selector'     => "[id*='networks-name'] :selected",
					'update_event' => 'change',
					'value_method' => 'text'
				),
				'fields'     => array(
					'name'         => array(
						'type'    => 'select',
						'label'   => '',
						'prompt'  => __( 'Select network', 'so-widgets-bundle' ),
						'options' => $network_names
					),
					'url'          => array(
						'type'  => 'text',
						'label' => __( 'URL', 'so-widgets-bundle' )
					),
					'icon_title' => array(
						'type' => 'text',
						'label' => __( 'Icon title', 'so-widgets-bundle' ),
					),
					'icon_color'   => array(
						'type'  => 'color',
						'label' => __( 'Icon color', 'so-widgets-bundle' )
					),
					'button_color' => array(
						'type'  => 'color',
						'label' => __( 'Background color', 'so-widgets-bundle' )
					)
				)
			),
			'design'   => array(
				'type'   => 'section',
				'label'  => __( 'Design and layout', 'so-widgets-bundle' ),
				'hide'   => true,
				'fields' => array(
					'new_window' => array(
						'type'    => 'checkbox',
						'label'   => __( 'Open in a new window', 'so-widgets-bundle' ),
						'default' => true
					),
					'theme'      => array(
						'type'    => 'select',
						'label'   => __( 'Button theme', 'so-widgets-bundle' ),
						'default' => 'atom',
						'options' => array(
							'atom' => __( 'Atom', 'so-widgets-bundle' ),
							'flat' => __( 'Flat', 'so-widgets-bundle' ),
							'wire' => __( 'Wire', 'so-widgets-bundle' ),
						),
					),
					'hover'      => array(
						'type'    => 'checkbox',
						'label'   => __( 'Use hover effects', 'so-widgets-bundle' ),
						'default' => true
					),
					'icon_size'  => array(
						'type'    => 'select',
						'label'   => __( 'Icon size', 'so-widgets-bundle' ),
						'options' => array(
							'1'    => __( 'Normal', 'so-widgets-bundle' ),
							'1.33' => __( 'Medium', 'so-widgets-bundle' ),
							'1.66' => __( 'Large', 'so-widgets-bundle' ),
							'2'    => __( 'Extra large', 'so-widgets-bundle' )
						)
					),
					'rounding'   => array(
						'type'    => 'select',
						'label'   => __( 'Rounding', 'so-widgets-bundle' ),
						'default' => '0.25',
						'options' => array(
							'0'    => __( 'None', 'so-widgets-bundle' ),
							'0.25' => __( 'Slightly rounded', 'so-widgets-bundle' ),
							'0.5'  => __( 'Very rounded', 'so-widgets-bundle' ),
							'1.5'  => __( 'Completely rounded', 'so-widgets-bundle' ),
						),
					),
					'padding'    => array(
						'type'    => 'select',
						'label'   => __( 'Padding', 'so-widgets-bundle' ),
						'default' => '1',
						'options' => array(
							'0.5' => __( 'Low', 'so-widgets-bundle' ),
							'1'   => __( 'Medium', 'so-widgets-bundle' ),
							'1.4' => __( 'High', 'so-widgets-bundle' ),
							'1.8' => __( 'Very high', 'so-widgets-bundle' ),
						),
					),
					'align'      => array(
						'type'    => 'select',
						'label'   => __( 'Align', 'so-widgets-bundle' ),
						'default' => 'left',
						'options' => array(
							'left'    => __( 'Left', 'so-widgets-bundle' ),
							'right'   => __( 'Right', 'so-widgets-bundle' ),
							'center'  => __( 'Center', 'so-widgets-bundle' ),
							'justify' => __( 'Justify', 'so-widgets-bundle' ),
						),
					),
					'mobile_align'      => array(
						'type'    => 'select',
						'label'   => __( 'Mobile Align', 'so-widgets-bundle' ),
						'default' => 'left',
						'options' => array(
							'left'    => __( 'Left', 'so-widgets-bundle' ),
							'right'   => __( 'Right', 'so-widgets-bundle' ),
							'center'  => __( 'Center', 'so-widgets-bundle' ),
							'justify' => __( 'Justify', 'so-widgets-bundle' ),
						),
					),
					'margin'     => array(
						'type'    => 'select',
						'label'   => __( 'Margin', 'so-widgets-bundle' ),
						'default' => '0.1',
						'options' => array(
							'0.1' => __( 'Low', 'so-widgets-bundle' ),
							'0.2' => __( 'Medium', 'so-widgets-bundle' ),
							'0.3' => __( 'High', 'so-widgets-bundle' ),
							'0.4' => __( 'Very high', 'so-widgets-bundle' ),
						),
					)
				)
			),
		);
	}

	function modify_form( $form ) {
		return apply_filters( 'sow_social_media_buttons_form_options', $form );
	}

	function modify_instance( $instance ) {
		if ( ! empty( $instance['networks'] ) ) {
			foreach ( $instance['networks'] as $name => $network ) {
				if ( $network['name'] == 'envelope' ) {
					$network['name'] = 'email';
				}
				$network['icon_name'] = 'fontawesome-' . ( $network['name'] == 'email' ? 'envelope' : $network['name'] );
				$instance['networks'][$name] = $network;
			}
		}
		return $instance;
	}

	function get_javascript_variables() {
		if( empty( $this->networks ) ) {
			$this->networks = include plugin_dir_path( __FILE__ ) . 'data/networks.php';
		}

		return array( 'networks' => $this->networks );
	}

	function enqueue_admin_scripts() {
		wp_enqueue_script(
			'sow-social-media-buttons',
			plugin_dir_url( __FILE__ ) . 'js/social-media-buttons-admin' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION
		);
	}

	function get_style_name( $instance ) {
		if ( empty( $instance['design']['theme'] ) ) {
			return 'atom';
		}

		return $instance['design']['theme'];
	}

	function get_less_variables( $instance ) {
		if( empty( $instance ) ) return;

		$design = $instance['design'];
		$m      = $design['margin'];
		$top = $right = $bottom = $left = $m . 'em';
		switch ( $design['align'] ) {
			case 'left':
				$left = '0';
				break;
			case 'right':
				$right = '0';
				break;
			case 'center':
				$left = $right = ( $m * 0.5 ) . 'em';
				break;
		}
		$margin = $top . ' ' . $right . ' ' . $bottom . ' ' . $left;

		$global_settings = $this->get_global_settings();
		return array(
			'icon_size'             => $design['icon_size'] . 'em',
			'rounding'              => $design['rounding'] . 'em',
			'padding'               => $design['padding'] . 'em',
			'align'                 => $design['align'],
			'mobile_align'          => ! empty( $design['mobile_align'] ) ? $design['mobile_align'] : '',
			'responsive_breakpoint' => ! empty( $global_settings['responsive_breakpoint'] ) ? $global_settings['responsive_breakpoint'] : '',
			'margin'                => $margin
		);
	}

	function less_generate_calls_to( $instance, $args ) {
		$networks = $this->get_instance_networks( $instance );
		$calls    = array();
		foreach ( $networks as $network ) {
			if ( ! empty( $network['name'] ) ) {
				$call = $args[0] . '( @name:' . $network['name'];
				$call .= ! empty( $network['icon_color'] ) ? ', @icon_color:' . $network['icon_color'] : '';
				$call .= ! empty( $network['button_color'] ) ? ', @button_color:' . $network['button_color'] : '';
				$call .= ');';
				$calls[] = $call;
			}
		}

		return implode( "\n", $calls );
	}

	function get_template_variables( $instance, $args ) {
		return array(
			'networks' => $this->get_instance_networks( $instance )
		);
	}

	private function get_instance_networks( $instance ) {
		if ( isset( $instance['networks'] ) && ! empty( $instance['networks'] ) ) {
			$networks = $instance['networks'];
		} else {
			$networks = array();
		}
		return apply_filters( 'sow_social_media_buttons_networks', $networks, $instance );
	}

	/**
	 * This is used to generate the hash of the instance.
	 *
	 * @param $instance
	 *
	 * @return array
	 */
	protected function get_style_hash_variables( $instance ){
		$networks = $this->get_instance_networks($instance);

		foreach($networks as $i => $network) {
			// URL is not important for the styling
			unset($networks[$i]['url']);
		}

		return array(
			'less' => $this->get_less_variables($instance),
			'networks' => $networks
		);
	}
}

siteorigin_widget_register( 'sow-social-media-buttons', __FILE__, 'SiteOrigin_Widget_SocialMediaButtons_Widget' );

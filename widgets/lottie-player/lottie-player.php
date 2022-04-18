<?php
/*
Widget Name: Lottie Player
Description: Bring your pages to life with Lottie animations.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/lottie-player-widget/
*/

class SiteOrigin_Widget_Lottie_Player_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-lottie-player',
			__( 'SiteOrigin Lottie Player', 'so-widgets-bundle' ),
			array(
				'description' => __( 'Placeholder.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/lottie-player-widget/'
			),
			array(),
			false,
			plugin_dir_path(__FILE__)
		);
	}

	function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'sow-lottie-player',
					plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/lib/lottie-player' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery', 'dessandro-imagesLoaded', 'dessandro-packery' ),
					'1.3.1'
				),
			)
		);

		add_filter( 'upload_mimes', array( $this, 'add_json_mime' ) );
	}

	function add_json_mime( $types ) {
		$types['json'] = 'text/plain';
		return $types;
	}

	function get_widget_form() {
		$global_settings = $this->get_global_settings();
		return array(
			'file' => array(
				'type' => 'media',
				'label' => __( 'Lottie File', 'so-widgets-bundle' ),
			),

			'autoplay' => array(
				'type' => 'checkbox',
				'default' => false,
				'label' => __( 'Autoplay', 'so-widgets-bundle' ),
			),

			'controls' => array(
				'type' => 'checkbox',
				'default' => true,
				'label' => __( 'Controls', 'so-widgets-bundle' ),
			),

			'loop' => array(
				'type' => 'checkbox',
				'default' => true,
				'label' => __( 'Loop', 'so-widgets-bundle' ),
			),

			'url' => array(
				'type' => 'link',
				'label' => __( 'Destination URL', 'so-widgets-bundle' ),
			),

			'new_window' => array(
				'type' => 'checkbox',
				'default' => false,
				'label' => __( 'Open in new window', 'so-widgets-bundle' ),
			),
		);
	}

	public function get_template_variables( $instance, $args ) {
		return array(
			'file' => ! empty( $instance['file'] ) ? wp_get_attachment_url( $instance['file'] ) : '',
			'attributes' => array(
				'autoplay' => ! empty( $instance['autoplay'] ),
				'controls' => ! empty( $instance['controls'] ),
				'loop' => ! empty( $instance['loop'] ),
			),
			'url' => ! empty( $instance['url'] ) ? $instance['url'] : '',
			'new_window' => ! empty( $instance['new_window'] ) ? $instance['new_window'] : false,
		);
	}

	function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) return false;
		return array(
			sprintf(
				__( 'Lottie player addon placeholder %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/lottie-player" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
		);
	}
}
siteorigin_widget_register( 'sow-lottie-player', __FILE__, 'SiteOrigin_Widget_Lottie_Player_Widget' );

<?php
/*
Widget Name: Icon
Description: An iconic icon.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/icon-widget/
*/

class SiteOrigin_Widget_Icon_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'sow-icon',
			__( 'SiteOrigin Icon', 'so-widgets-bundle' ),
			array(
				'description' => __( 'An iconic icon.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/icon-widget/',
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	public function get_widget_form() {
		return array(
			'icon' => array(
				'type'  => 'icon',
				'label' => __( 'Icon', 'so-widgets-bundle' ),
			),

			'color' => array(
				'type'  => 'color',
				'label' => __( 'Color', 'so-widgets-bundle' ),
			),

			'size' => array(
				'type'  => 'measurement',
				'label' => __( 'Size', 'so-widgets-bundle' ),
			),

			'alignment' => array(
				'type'  => 'select',
				'label' => __( 'Alignment', 'so-widgets-bundle' ),
				'options' => array(
					'center' => __( 'Center', 'so-widgets-bundle' ),
					'left' => __( 'Left', 'so-widgets-bundle' ),
					'right' => __( 'Right', 'so-widgets-bundle' ),
				),
				'default' => 'center',
			),

			'url' => array(
				'type'  => 'link',
				'label' => __( 'Destination URL', 'so-widgets-bundle' ),
			),

			'new_window' => array(
				'type'    => 'checkbox',
				'default' => false,
				'label'   => __( 'Open in a new window', 'so-widgets-bundle' ),
			),

			'title' => array(
				'type'  => 'text',
				'label' => __( 'Title', 'so-widgets-bundle' ),
				'description' => __( 'Tooltip text to be shown when hovering over the icon.', 'so-widgets-bundle' ),
			),
		);
	}

	public function get_less_variables( $instance ) {
		if ( empty( $instance ) ) {
			return array();
		}

		return array(
			'color'     => $instance['color'],
			'alignment' => $instance['alignment'],
			'size'      => $instance['size'],
		);
	}

	/**
	 * Get the template variables for the headline
	 *
	 * @return array
	 */
	public function get_template_variables( $instance, $args ) {
		return array(
			'icon' => $instance['icon'],
			'url' => $instance['url'],
			'new_window' => $instance['new_window'],
			'title' => ! empty( $instance['title'] ) ? $instance['title'] : '',
		);
	}

	public function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return array(
			sprintf(
				__( 'Add an icon title tooltip with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/tooltip" target="_blank">',
				'</a>'
			),
		);
	}
}

siteorigin_widget_register( 'sow-icon', __FILE__, 'SiteOrigin_Widget_Icon_Widget' );

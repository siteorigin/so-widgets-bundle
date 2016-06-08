<?php

/*
Widget Name: Taxonomy
Description: Displays the selected taxonomy for the current post.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

class SiteOrigin_Widget_Taxonomy_Widget extends SiteOrigin_Widget {

	function __construct() {

		parent::__construct(
			'sow-taxonomy',
			__( 'SiteOrigin Taxonomy', 'so-widgets-bundle' ),
			array(
				'description' => __( 'A taxonomy widget.', 'so-widgets-bundle' )
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	function initialize_form() {
		// Gets taxonomy objects and extracts the 'label' field from each one.
		$taxonomies = wp_list_pluck( get_taxonomies( array(), 'objects' ), 'label' );

		return array(
			'taxonomy'       => array(
				'type'    => 'select',
				'label'   => __( 'Taxonomies', 'so-widgets-bundle' ),
				'options' => $taxonomies,
			),
			'show_label'     => array(
				'type'  => 'checkbox',
				'label' => __( 'Show Taxonomy Label', 'so-widgets-bundle' ),
			),
			'display_format' => array(
				'type'    => 'select',
				'label'   => __( 'Display as', 'so-widgets-bundle' ),
				'options' => array(
					'links'   => __( 'Links', 'so-widgets-bundle' ),
					'buttons' => __( 'Buttons', 'so-widgets-bundle' ),
				),
			),
			'color'          => array(
				'type'  => 'color',
				'label' => __( 'Color', 'so-widgets-bundle' ),
			),
			'hover_color'    => array(
				'type'  => 'color',
				'label' => __( 'Hover color', 'so-widgets-bundle' ),
			),
		);
	}

	function get_style_name( $instance ) {
		return 'sow-taxonomy';
	}

	/**
	 * Get the template for the taxonomy widget
	 *
	 * @param $instance
	 *
	 * @return mixed|string
	 */
	function get_template_name( $instance ) {
		return 'taxonomy';
	}

	/**
	 * Get the template variables for the taxonomy
	 *
	 * @param $instance
	 * @param $args
	 *
	 * @return array
	 */
	function get_template_variables( $instance, $args ) {
		if ( empty( $instance ) ) {
			return array();
		}

		return array(
			'taxonomy_name' => $instance['taxonomy'],
			'show_label' => $instance['show_label'],
		);
	}
}

siteorigin_widget_register( 'sow-taxonomy', __FILE__, 'SiteOrigin_Widget_Taxonomy_Widget' );

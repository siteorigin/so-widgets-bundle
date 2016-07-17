<?php

include plugin_dir_path( __FILE__ ) . '/form.php';
include plugin_dir_path( __FILE__ ) . '/widget.php';

class SiteOrigin_Widgets_Builder {

	function __construct(){
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );

		add_action( 'widgets_init', array( $this, 'register_widgets' ), 20 );
	}

	static function single(){
		static $single;
		if( empty( $single ) ) {
			$single = new self();
		}
		return $single;
	}

	function register_post_type() {
		if( current_user_can( 'manage_options' ) ) {
			register_post_type( 'so-custom-widget', array(
				'label' => __( 'Widget Builder', 'so-widgets-bundle' ),
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true,
				'supports' => array( 'title'  ),
				'show_in_menu' => 'tools.php',
			) );
		}
	}

	function add_meta_boxes(){
		add_meta_box(
			'so-widget-builder-settings',
			__( 'Custom Widget Settings', 'so-widgets-bundle' ),
			array( $this, 'meta_box_callback' ),
			'so-custom-widget',
			'normal',
			'default'
		);
	}

	function meta_box_callback( $post ){
		$settings = get_post_meta( $post->ID, 'so_custom_widget', true );
		$form_object = new SiteOrigin_Widgets_Builder_Form();
		$form_object->form( $settings );
		wp_nonce_field( 'save-custom-widget', '_so_nonce' );
	}

	function save_post( $post_id ){
		if( empty( $_POST['_so_nonce'] ) || ! wp_verify_nonce( $_POST['_so_nonce'], 'save-custom-widget' ) ) return;
		if( ! current_user_can( 'edit_post', $post_id ) ) return;
		if( empty( $_POST['so_custom_widget'] ) ) return;

		$custom_widget = get_post_meta( $post_id, 'so_custom_widget', true );
		$form_object = new SiteOrigin_Widgets_Builder_Form();
		$custom_widget = $form_object->update( $_POST['so_custom_widget'], $custom_widget );

		update_post_meta( $post_id, 'so_custom_widget', $custom_widget );
	}

	/**
	 * Register all the widgets created in this process
	 */
	function register_widgets(){
		global $wpdb;

		$results = $wpdb->get_results( "
			SELECT ID, post_title, post_name
			FROM $wpdb->posts
			WHERE post_type = 'so-custom-widget' AND post_status = 'publish'
		" );

		if( empty( $results ) ) return;

		global $wp_widget_factory;

		foreach( $results as $result ) {
			$custom_widget = get_post_meta( $result->ID, 'so_custom_widget', true );
			$widget_obj = new SiteOrigin_Widget_Custom_Built_Widget( 'so-custom-' . $result->post_name, $result->post_title, $custom_widget[ 'description' ], $custom_widget );
			$wp_widget_factory->widgets[ 'SiteOrigin_Widget_' . $result->post_name ] = $widget_obj;
		}
	}

}
SiteOrigin_Widgets_Builder::single();
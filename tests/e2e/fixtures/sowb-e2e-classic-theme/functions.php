<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'widgets_init', function () {
	register_sidebar( array(
		'name' => 'E2E Sidebar',
		'id'   => 'sowb-e2e-sidebar',
	) );
} );

add_action( 'siteorigin_widgets_stylesheet_deleted', function () {
	update_option( 'sowb_e2e_stylesheets_deleted', (int) get_option( 'sowb_e2e_stylesheets_deleted' ) + 1 );
} );

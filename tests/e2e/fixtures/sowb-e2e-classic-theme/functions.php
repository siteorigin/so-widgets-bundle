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

$sowb_e2e_count_stylesheet_write = function () {
	update_option( 'sowb_e2e_stylesheet_writes', (int) get_option( 'sowb_e2e_stylesheet_writes' ) + 1 );
};
add_action( 'siteorigin_widgets_stylesheet_added', $sowb_e2e_count_stylesheet_write );
add_action( 'siteorigin_widgets_stylesheet_deleted', $sowb_e2e_count_stylesheet_write );

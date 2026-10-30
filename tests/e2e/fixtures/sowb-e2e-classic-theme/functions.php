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

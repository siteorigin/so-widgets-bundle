<?php

/**
 * Handles registering Widgets Bundle custom REST endpoints.
 *
 * Class SiteOrigin_Widgets_Rest_Routes
 */
class SiteOrigin_Widgets_Rest_Routes {
	public function __construct() {
		global $wp_version;

		if ( version_compare( $wp_version, '4.7', '>=' ) && class_exists( 'WP_REST_Controller' ) ) {
			include plugin_dir_path( __FILE__ ) . 'siteorigin-widgets-resource.class.php';
			add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		}
	}

	/**
	 * Singleton
	 *
	 * @return SiteOrigin_Widgets_Rest_Routes
	 */
	public static function single() {
		static $single;

		if ( empty( $single ) ) {
			$single = new self();
		}

		return $single;
	}

	/**
	 * Register all our REST resources.
	 */
	public function register_rest_routes() {
		$resources = array(
			new SiteOrigin_Widgets_Resource(),
		);

		foreach ( $resources as $resource ) {
			/* @var WP_REST_Controller $resource */
			$resource->register_routes();
		}
	}
}
SiteOrigin_Widgets_Rest_Routes::single();

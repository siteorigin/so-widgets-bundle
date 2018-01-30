<?php
/**
 * Resource for SiteOrigin widgets.
 */

class SiteOrigin_Widgets_Resource extends WP_REST_Controller {
	
	/**
	 * @var SiteOrigin_Widgets_Widget_Manager
	 */
	private $widgets_manager;
	
	public function __construct() {
		$this->widgets_manager = SiteOrigin_Widgets_Widget_Manager::single();
	}
	
	public function register_routes() {
		$version = '1';
		$namespace = 'sowb/v' . $version;
		$resource = 'widgets';
		
		register_rest_route( $namespace, '/' . $resource, array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_widgets'),
			'permission_callback' => array( $this, 'permissions_check' ),
		) );
		
		$subresource = 'forms';
		register_rest_route( $namespace, '/' . $resource . '/' . $subresource, array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_widget_form'),
			'args' => array(
				'widgetClass' => array(
					'validate_callback' => array( $this, 'validate_widget_class'),
				),
			),
			'permission_callback' => array( $this, 'permissions_check' ),
		) );
		
		$subresource = 'previews';
		register_rest_route( $namespace, '/' . $resource . '/' . $subresource, array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_widget_preview'),
			'args' => array(
				'widgetClass' => array(
					'validate_callback' => array( $this, 'validate_widget_class'),
				),
				'widgetData' => array(
					'validate_callback' => array( $this, 'validate_widget_data'),
				),
			),
			'permission_callback' => array( $this, 'permissions_check' ),
		) );
	}
	
	/**
	 * Get the collection of widgets.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_widgets( $request ) {
		global $wp_widget_factory;
		$so_widgets = array();
		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			if ( ! empty( $widget_obj ) && is_object( $widget_obj ) && is_subclass_of( $widget_obj, 'SiteOrigin_Widget' ) ) {
				$so_widgets[] = array(
					'name' => preg_replace( '/^SiteOrigin /', '', $widget_obj->name ),
					'class' => $class,
				);
			}
		}
		
		return rest_ensure_response( $so_widgets );
	}
	
	/**
	 * TODO: Check that current user has permission to access the requested data.
	 *
	 * @param $request
	 *
	 * @return bool
	 */
	public function permissions_check( $request ) {
		return true;
	}
	
	/**
	 * Validate passed in widgetClass arg only contains alphanumeric and underscores.
	 *
	 * @param $param
	 * @param $request
	 * @param $key
	 *
	 * @return bool
	 */
	function validate_widget_class( $param, $request, $key ) {
		return preg_match( '/\w+/', $param ) == 1;
	}
	
	/**
	 * Get the collection of widgets.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_widget_form( $request ) {
		$widget_class = $request['widgetClass'];
		
		global $wp_widget_factory;
		
		$widget = ! empty( $wp_widget_factory->widgets[ $widget_class ] ) ? $wp_widget_factory->widgets[ $widget_class ] : false;
		
		if ( ! empty( $widget ) && is_object( $widget ) && is_subclass_of( $widget, 'SiteOrigin_Widget' ) ) {
			ob_start();
			/* @var $widget SiteOrigin_Widget */
			$widget->form( array() );
			$widget_form = ob_get_clean();
		} else {
			$widget_form = new WP_Error( '', 'Invalid widget class.' );
		}
		
		return rest_ensure_response( $widget_form );
	}
	
	/**
	 * TODO: Implement.
	 *
	 * @param $param
	 * @param $request
	 * @param $key
	 *
	 * @return bool
	 */
	function validate_widget_data( $param, $request, $key ) {
		return true;
	}
	
	/**
	 * Get the collection of widgets.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_widget_preview( $request ) {
		$widget_class = $request['widgetClass'];
		$widget_data = json_decode( $request['widgetData'], true );
		
		global $wp_widget_factory;
		
		$widget = ! empty( $wp_widget_factory->widgets[ $widget_class ] ) ? $wp_widget_factory->widgets[ $widget_class ] : false;
		// This ensures styles are added inline.
		add_filter( 'siteorigin_widgets_is_preview', '__return_true' );
		
		if ( ! empty( $widget ) && is_object( $widget ) && is_subclass_of( $widget, 'SiteOrigin_Widget' ) ) {
			ob_start();
			/* @var $widget SiteOrigin_Widget */
			$widget->widget( array(), $widget_data );
			siteorigin_widget_print_styles();
			$rendered_widget = ob_get_clean();
		} else {
			$rendered_widget = new WP_Error( '', 'Invalid widget class.' );
		}
		
		return rest_ensure_response( $rendered_widget );
	}
}

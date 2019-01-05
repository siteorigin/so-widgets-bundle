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
		
		$subresource = 'forms';
		register_rest_route( $namespace, '/' . $resource . '/' . $subresource, array(
			'methods' => WP_REST_Server::CREATABLE,
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
			'methods' => WP_REST_Server::CREATABLE,
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
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function permissions_check( $request ) {
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			$status_code = rest_authorization_required_code();
			return new WP_Error(
				$status_code,
				__( 'Insufficient permissions.', 'so-widgets-bundle' ),
				array(
					'status' => $status_code,
				)
			);
		}
		
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
	 * For now widget data is validated in the below `get_widget_preview` function.
	 * Leaving this here for possible later implementation.
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
		$widget_data = $request['widgetData'];
		
		global $wp_widget_factory;
		
		$widget = ! empty( $wp_widget_factory->widgets[ $widget_class ] ) ? $wp_widget_factory->widgets[ $widget_class ] : false;
		// This ensures styles are added inline.
		add_filter( 'siteorigin_widgets_is_preview', '__return_true' );
		
		if ( ! empty( $widget ) && is_object( $widget ) && is_subclass_of( $widget, 'SiteOrigin_Widget' ) ) {
			ob_start();
			/* @var $widget SiteOrigin_Widget */
			$instance = $widget->update( $widget_data, $widget_data );
			$widget->widget( array(), $instance );
			$rendered_widget = ob_get_clean();
		} else {
			$rendered_widget = new WP_Error( '', 'Invalid widget class.' );
		}
		
		return rest_ensure_response( $rendered_widget );
	}
}

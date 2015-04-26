<?php

class SiteOrigin_Widget_Meta_Box_Manager extends SiteOrigin_Widget {

	private $widget_form_fields;

	/**
	 * Get the single instance.
	 *
	 * @return SiteOrigin_Widget_Meta_Box_Manager
	 */
	static function single(){
		static $single = false;
		if( empty($single) ) $single = new SiteOrigin_Widget_Meta_Box_Manager();

		return $single;
	}

	function __construct() {
		parent::__construct(
			'sow-metabox-manager',
			__('SiteOrigin Metabox Manager', 'siteorigin-widgets'),
			array(),
			array(),
			array()
		);
		add_action( 'add_meta_boxes', array( $this, 'sow_add_meta_boxes' ) );
	}

	private function sow_add_meta_boxes() {
		if ( ! is_admin() ) return;
		$screen = get_current_screen();

		$this->form_options = array();
		foreach ( $this->widget_form_fields as $widget_id => $form_fields ) {
			if ( in_array( 'all', $form_fields['post_types'] ) || in_array( $screen->id, $form_fields['post_types'] ) ) {
				foreach ( $form_fields['fields'] as $field_name => $field ) {
					$this->form_options[$widget_id . '_' . $field_name] = $field;
				}
			}
		}

		$this->form( array() );
	}

	/**
	 * @param string $widget_id Base id of the widget adding the fields.
	 * @param array $fields The fields to add.
	 * @param string|array $post_types A post type string, 'all' or an array of post types
	 */
	function append_to_form( $widget_id, $fields, $post_types = 'all' ) {
		if( empty( $fields ) || empty( $post_types ) ) return;

		$this->widget_form_fields[$widget_id] = array(
			'post_types' => $post_types == 'all' ? array( 'all' ) : $post_types,
			'fields' => $fields
		);
	}

	function has_form( $post_type ){
		// return if
	}

	function get_style_name( $instance ) {
		return '';
	}

	function get_template_name( $instance ) {
		return '';
	}
}
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
			'sow-meta-box-manager',
			__('SiteOrigin Meta Box Manager', 'siteorigin-widgets'),
			array(
				'has_preview' => false,
				'help' => 'https://siteorigin.com/docs/widgets-bundle/advanced-concepts/widget-post-meta-box-forms/'
			),
			array(),
			array()
		);
		$this->number = 1;
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_widget_post_meta' ), 10, 3 );
	}

	/**
	 * @param $post_type
	 */
	public function add_meta_boxes( $post_type ) {
		if ( ! is_admin() ) return;

		$this->form_options = array();
		foreach ( $this->widget_form_fields as $widget_id => $form_fields ) {
			if ( in_array( 'all', $form_fields['post_types'] ) || in_array( $post_type, $form_fields['post_types'] ) ) {
				foreach ( $form_fields['fields'] as $field_name => $field ) {
					$this->form_options[$widget_id . '_' . $field_name] = $field;
				}
			}
		}

		if ( !empty( $this->form_options ) ) {
			add_meta_box(
				'siteorigin-widgets-meta-box',
				__( 'Widgets Bundle', 'siteorigin-widgets' ),
				array( $this, 'render_widgets_meta_box' ),
				$post_type,
				'advanced'
			);

		}
	}

	/**
	 * @param $post
	 * @param $meta_box
	 */
	public function render_widgets_meta_box( $post, $meta_box ) {
		wp_enqueue_script(
			'sow-meta-box-manager-js',
			plugin_dir_url(SOW_BUNDLE_BASE_FILE).'base/js/meta-box-manager' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION,
			true
		);
		$widget_post_meta = get_post_meta( $post->ID, 'siteorigin-widgets-post-meta', true );
		$this->form( $widget_post_meta );
		?><input type="hidden" id="widget_post_meta" name="widget_post_meta"> <?php
		wp_nonce_field( 'widget_post_meta_save', '_widget_post_meta_nonce' );
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

	function save_widget_post_meta( $post_id ) {
		$nonce = filter_input( INPUT_POST, '_widget_post_meta_nonce', FILTER_SANITIZE_STRING );
		if ( !wp_verify_nonce( $nonce, 'widget_post_meta_save' ) ) return;
		if ( !current_user_can( 'edit_post', $post_id ) ) return;

		$request = filter_input_array( INPUT_POST, array(
			'widget_post_meta' => FILTER_DEFAULT
		) );
		$widget_post_meta = json_decode( $request['widget_post_meta'], true);

		update_post_meta( $post_id, 'siteorigin-widgets-post-meta', $widget_post_meta );

	}

	function get_style_name( $instance ) {
		return '';
	}

	function get_template_name( $instance ) {
		return '';
	}
}
<?php

class SiteOrigin_Widget_Meta_Box_Manager extends SiteOrigin_Widget {

	const POST_META_KEY = 'siteorigin-widgets-post-meta';

	/**
	 * @var array Fields which have been added for each widget.
	 */
	private $widget_form_fields;

	/**
	 * @var array Post types for which fields have been added.
	 */
	private $post_types;

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

	/**
	 * Private
	 */
	public function __construct() {
		parent::__construct(
			'sow-meta-box-manager',
			__('SiteOrigin Meta Box Manager', 'so-widgets-bundle'),
			array(
				'has_preview' => false,
				'help' => false
			),
			array(),
			array()
		);
	}

	function initialize() {
		// Initialize number for field name attributes.
		$this->number = 1;
		$this->post_types = array();
		$this->widget_form_fields = array();

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_widget_post_meta' ), 10, 3 );
	}

	/**
	 * This handles the 'add_meta_boxes' action. It merges widget fields into the form_options array before adding the
	 * meta box
	 *
	 * @param $post_type
	 */
	public function add_meta_boxes( $post_type ) {

		$this->form_options = array();
		foreach ( $this->widget_form_fields as $widget_id => $post_type_form_fields ) {
			foreach( $post_type_form_fields as $form_fields ) {
				if ( in_array( 'all', $form_fields['post_types'] ) || in_array( $post_type, $form_fields['post_types'] ) ) {
					foreach ( $form_fields['fields'] as $field_name => $field ) {
						$this->form_options[$widget_id . '_' . $field_name] = $field;
					}
				}
			}
		}

		if ( ! empty( $this->form_options ) ) {
			add_meta_box(
				'siteorigin-widgets-meta-box',
				__( 'Widgets Bundle Post Meta Data', 'so-widgets-bundle' ),
				array( $this, 'render_widgets_meta_box' ),
				$post_type,
				'advanced'
			);

		}
	}

	/**
	 * This is the callback used by add_meta_box to render the widgets meta box form and enqueue any necessary scripts.
	 *
	 * @param $post
	 */
	public function render_widgets_meta_box( $post ) {
		wp_enqueue_script(
			'sow-meta-box-manager-js',
			plugin_dir_url(SOW_BUNDLE_BASE_FILE).'base/js/meta-box-manager' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION,
			true
		);
		$widget_post_meta = get_post_meta( $post->ID, self::POST_META_KEY, true );
		$this->form( $widget_post_meta );
		?><input type="hidden" id="widget_post_meta" name="widget_post_meta"> <?php
		wp_nonce_field( 'widget_post_meta_save', '_widget_post_meta_nonce' );
	}

	/**
	 * This method should be called by any widgets that want to be able to store post meta data. It may be called
	 * multiple times by a widget and the additional fields for a widget will be appended and rendered.
	 *
	 * @param string $widget_id Base id of the widget adding the fields.
	 * @param array $fields The fields to add.
	 * @param string|array $post_types A post type string, 'all' or an array of post types
	 */
	public function append_to_form( $widget_id, $fields, $post_types = 'all' ) {
		if( empty( $fields ) || empty( $post_types ) ) return;

		if( $post_types == 'all' ) {
			$post_types = array( 'all' );
		}

		foreach( $post_types as $post_type ) {
			if( !in_array( $post_type, $this->post_types ) ) {
				$this->post_types[] = $post_type;
			}
		}

		if( ! isset( $this->widget_form_fields[$widget_id] ) ) {
			$this->widget_form_fields[$widget_id] = array();
		}

		$this->widget_form_fields[$widget_id][] = array(
			'post_types' =>  $post_types,
			'fields' => $fields
		);
	}

	/**
	 * This handles the 'save_post' action. It checks for a nonce, checks user permissions, and filters the input data
	 * and decodes it from the JSON format before storing it in the post's meta data.
	 *
	 * @param $post_id
	 */
	public function save_widget_post_meta( $post_id ) {
		if ( empty( $_POST['_widget_post_meta_nonce'] ) || !wp_verify_nonce( $_POST['_widget_post_meta_nonce'], 'widget_post_meta_save' ) ) return;
		if ( !current_user_can( 'edit_post', $post_id ) ) return;

		$widget_post_meta = isset( $_POST['widget_post_meta'] ) ? stripslashes_deep( $_POST['widget_post_meta'] ) : '';
		$widget_post_meta = json_decode( $widget_post_meta, true);

		update_post_meta( $post_id, self::POST_META_KEY, $widget_post_meta );

	}

	/**
	 * This function is used to retrieve a widget's post meta data.
	 *
	 * @param $post_id string The id of the post for which the meta data is stored.
	 * @param $widget_id string The id of the widget for which the meta data is stored.
	 * @param $meta_key string The key of the meta data value which is to be retrieved.
	 *
	 * @return mixed An empty string if the meta data is not found, else the meta data in whatever format it was stored.
	 */
	public function get_widget_post_meta( $post_id, $widget_id, $meta_key ) {
		$widget_post_meta = get_post_meta( $post_id, self::POST_META_KEY, true );
		if( empty( $widget_post_meta ) ) return '';
		$widget_post_meta_field = $widget_id . '_' . $meta_key;
		if( ! isset( $widget_post_meta[$widget_post_meta_field] ) ) return '';
		return $widget_post_meta[$widget_post_meta_field];
	}
}
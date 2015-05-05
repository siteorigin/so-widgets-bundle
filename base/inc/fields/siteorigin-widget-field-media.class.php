<?php

/**
 * Use of this field requires at least WordPress 3.5.
 *
 * Class SiteOrigin_Widget_Field_Media
 */
class SiteOrigin_Widget_Field_Media extends SiteOrigin_Widget_Field {
	/**
	 * A label for the title of the media selector dialog.
	 *
	 * @access protected
	 * @var string
	 */
	protected $dialog_title;
	/**
	 * A label for the confirmation button of the media selector dialog.
	 *
	 * @access protected
	 * @var string
	 */
	protected $update_button_label;
	/**
	 * Sets the media library which to browse and from which media can be selected. Allowed values are 'image',
	 * 'audio', 'video', and 'file'. The default is 'file'.
	 *
	 * @access protected
	 * @var string
	 */
	protected $library;
	/**
	 * Whether or not to display a URL input field which allows for specification of a fallback URL to be used in case
	 * the selected media resource isn't available.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $fallback;
	/**
	 * Reference to the containing widget required for creating the fallback subfield.
	 *
	 * @access private
	 * @var SiteOrigin_Widget
	 */
	private $for_widget;
	/**
	 * An array of field names of parent repeaters.
	 *
	 * @var array
	 */
	private $parent_repeater;

	public function __construct( $base_name, $element_id, $element_name, $field_options, $for_widget, $parent_repeater = array()  ) {
		parent::__construct( $base_name, $element_id, $element_name, $field_options );

		if( isset( $field_options['choose'] ) ) {
			$this->dialog_title = $field_options['choose'];
		}
		else {
			$this->dialog_title = __( 'Choose Media', 'siteorigin-widgets' );
		}
		if( isset( $field_options['update'] ) ) {
			$this->update_button_label = $field_options['update'];
		}
		else {
			$this->update_button_label = __( 'Set Media', 'siteorigin-widgets' );
		}
		if( isset( $field_options['library'] ) ) {
			$this->library = $field_options['library'];
		}
		else {
			$this->library = 'image';
		}
		if( isset( $field_options['fallback'] ) ) $this->fallback = $field_options['fallback'];

		$this->for_widget = $for_widget;
		$this->parent_repeater = $parent_repeater;
	}

	protected function render_field( $value, $instance ) {
		if( version_compare( get_bloginfo('version'), '3.5', '<' ) ){
			printf( __('You need to <a href="%s">upgrade</a> to WordPress 3.5 to use media fields', 'siteorigin-widgets'), admin_url('update-core.php') );
			return;
		}

		if( !empty( $value ) ) {
			if( is_array( $value ) ) {
				$src = $value;
			}
			else {
				$post = get_post( $value );
				$src = wp_get_attachment_image_src( $value, 'thumbnail' );
				if( empty( $src ) ) $src = wp_get_attachment_image_src( $value, 'thumbnail', true );
			}
		}
		else{
			$src = array( '', 0, 0 );
		}
		?>
		<div class="media-field-wrapper">
			<div class="current">
				<div class="thumbnail-wrapper">
					<img src="<?php echo sow_esc_url( $src[0] ) ?>" class="thumbnail" <?php if( empty( $src[0] ) ) echo "style='display:none'" ?> />
				</div>
				<div class="title"><?php if( !empty( $post ) ) echo esc_attr( $post->post_title ) ?></div>
			</div>
			<a href="#" class="media-upload-button" data-choose="<?php echo esc_attr( $this->dialog_title ) ?>"
			   data-update="<?php echo esc_attr( $this->update_button_label ) ?>"
			   data-library="<?php echo esc_attr( $this->library ) ?>">
				<?php echo esc_html( $this->dialog_title ) ?>
			</a>
		</div>
		<a href="#" class="media-remove-button <?php if( empty( $value ) ) echo 'remove-hide'; ?>"><?php esc_html_e( 'Remove', 'siteorigin-widgets' ) ?></a>

		<input type="hidden" value="<?php echo esc_attr( is_array( $value ) ? '-1' : $value ) ?>" name="<?php echo $this->element_name ?>" class="siteorigin-widget-input" />

		<?php
	}

	protected function render_after_field( $value, $instance ) {
		if( !empty( $this->fallback ) ) {
			$fallback_name = $this->get_fallback_field_name( $this->base_name );
			$fallback_url = !empty( $instance[ $fallback_name ] ) ? $instance[ $fallback_name ] : '';
			?>
			<input type="text" value="<?php echo esc_url( $fallback_url ) ?>"
			       placeholder="<?php esc_attr_e( 'External URL', 'siteorigin-widgets' ) ?>"
			       name="<?php echo $this->for_widget->so_get_field_name( $this->base_name . '_fallback', $this->parent_repeater ) ?>"
			       class="media-fallback-external siteorigin-widget-input" />
			<div class="clear"></div>
			<?php
		}
		else {
			?>
			<div class="clear"></div>
			<?php
		}
		//Still want the default description, if there is one.
		parent::render_after_field( $value, $instance );
	}

	protected function sanitize_field_input( $value ) {
		// Media values should be integer
		return intval( $value );
	}

	protected function sanitize_instance( $instance ) {
		$fallback_name = $this->get_fallback_field_name( $this->base_name );
		if( !empty( $field['fallback'] ) && !empty( $instance[ $fallback_name ] ) ) {
			$instance[ $fallback_name ] = esc_url_raw( $instance[ $fallback_name ] );
		}
		return $instance;
	}

	private function get_fallback_field_name( $base_name ) {
		$v_name = $base_name;
		if( strpos($v_name, '][') !== false ) {
			// Remove this splitter
			$v_name = substr( $v_name, strpos($v_name, '][') + 2 );
		}
		return $v_name . '_fallback';
	}
}
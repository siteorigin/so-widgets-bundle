<?php

/**
 * Use of this field requires at least WordPress 3.5.
 *
 * Class SiteOrigin_Widget_Field_Media
 */
class SiteOrigin_Widget_Field_Media extends SiteOrigin_Widget_Field_Base {
	/**
	 * A label for the title of the media selector dialog.
	 *
	 * @access protected
	 * @var string
	 */
	protected $choose;
	/**
	 * A label for the confirmation button of the media selector dialog.
	 *
	 * @access protected
	 * @var string
	 */
	protected $update;
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

	protected function get_default_options() {
		return array(
			'choose' => __( 'Choose Media', 'so-widgets-bundle' ),
			'update' => __( 'Set Media', 'so-widgets-bundle' ),
			'library' => 'image'
		);
	}

	protected function render_field( $value, $instance ) {
		if( version_compare( get_bloginfo('version'), '3.5', '<' ) ){
			printf( __('You need to <a href="%s">upgrade</a> to WordPress 3.5 to use media fields', 'so-widgets-bundle'), admin_url('update-core.php') );
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
			<a href="#" class="media-upload-button" data-choose="<?php echo esc_attr( $this->choose ) ?>"
			   data-update="<?php echo esc_attr( $this->update ) ?>"
			   data-library="<?php echo esc_attr( $this->library ) ?>">
				<?php echo esc_html( $this->choose ) ?>
			</a>
		</div>
		<a href="#" class="media-remove-button <?php if( empty( $value ) ) echo 'remove-hide'; ?>"><?php esc_html_e( 'Remove', 'so-widgets-bundle' ) ?></a>

		<input type="hidden" value="<?php echo esc_attr( is_array( $value ) ? '-1' : $value ) ?>" name="<?php echo esc_attr( $this->element_name ) ?>" class="siteorigin-widget-input" />

		<?php
	}

	protected function render_after_field( $value, $instance ) {
		if( !empty( $this->fallback ) ) {
			$fallback_name = $this->get_fallback_field_name( $this->base_name );
			$fallback_url = !empty( $instance[ $fallback_name ] ) ? $instance[ $fallback_name ] : '';
			?>
			<input type="text" value="<?php echo esc_url( $fallback_url ) ?>"
			       placeholder="<?php esc_attr_e( 'External URL', 'so-widgets-bundle' ) ?>"
			       name="<?php echo esc_attr( $this->for_widget->so_get_field_name( $this->base_name . '_fallback', $this->parent_container ) ) ?>"
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

	protected function sanitize_field_input( $value, $instance ) {
		// Media values should be integer
		return intval( $value );
	}

	public function sanitize_instance( $instance ) {
		$fallback_name = $this->get_fallback_field_name( $this->base_name );
		if( !empty( $this->fallback ) && !empty( $instance[ $fallback_name ] ) ) {
			$instance[ $fallback_name ] = sow_esc_url_raw( $instance[ $fallback_name ] );
		}
		return $instance;
	}

	public function get_fallback_field_name( $base_name ) {
		$v_name = $base_name;
		if( strpos($v_name, '][') !== false ) {
			// Remove this splitter
			$v_name = substr( $v_name, strpos($v_name, '][') + 2 );
		}
		return $v_name . '_fallback';
	}
}
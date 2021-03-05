<?php

/**
 * Use of this field requires at least WordPress 3.5.
 *
 * Class SiteOrigin_Widget_Field_Multiple_Media
 */
class SiteOrigin_Widget_Field_Multiple_Media extends SiteOrigin_Widget_Field_Base {
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

	protected function get_default_options() {
		return array(
			'choose' => __( 'Add Media', 'so-widgets-bundle' ),
			'update' => __( 'Set Media', 'so-widgets-bundle' ),
			'library' => 'image'
		);
	}

	protected function render_field( $attachments, $instance ) {
		if ( version_compare( get_bloginfo('version'), '3.5', '<' ) ){
			printf( __( 'You need to <a href="%s">upgrade</a> to WordPress 3.5 to use media fields', 'so-widgets-bundle'), admin_url('update-core.php' ) );
			return;
		}

		// If library is set to all, convert it to a wildcard as all isn't valid
		if ( $this->library == 'all' ) {
			$this->library = '*';
		}
		?>
		<div class="multiple-media-field-wrapper">
			<a href="#" class="button" data-choose="<?php echo esc_attr( $this->choose ); ?>"
			   data-update="<?php echo esc_attr( $this->update ); ?>"
			   data-library="<?php echo esc_attr( $this->library ); ?>">
				<?php echo esc_html( $this->choose ); ?>
			</a>


			<div class="multiple-media-field-items">
				<?php
				if ( is_array( $attachments ) ) {
					foreach ( $attachments as $attachment ) {
						$post = get_post( $attachment );
						$src = wp_get_attachment_image_src( $attachment, 'thumbnail' );

						if ( empty( $src ) ) {
							continue;
						}
						?>
						<div class="multiple-media-field-item" data-id="<?php echo esc_attr( $attachment ); ?>">
							<img src="<?php echo sow_esc_url( $src[0] ); ?>" class="thumbnail" title="<?php echo esc_attr( $post->post_title ); ?>"/>
							<a href="#" class="media-remove-button"><?php esc_html_e( 'Remove', 'so-widgets-bundle' ); ?></a>
							<div class="title">
								<?php
								if ( ! empty( $post ) ) {
									echo esc_attr( $post->post_title );
								}
								?>		
							</div>
						</div>
					<?php
					}
				}
				?>
			</div>
			
			<div class="multiple-media-field-template" style="display:none">
				<div class="multiple-media-field-item">
					<img class="thumbnail" />
					<a href="#" class="media-remove-button"><?php esc_html_e( 'Remove', 'so-widgets-bundle' ); ?></a>
					<div class="title"></div>
				</div>

			</div>

			<input type="hidden" value="<?php echo is_array( $attachments ) ? esc_attr( implode( ',', $attachments ) ) : ''; ?>" data-element="<?php echo esc_attr( $this->element_name ); ?>" name="<?php echo esc_attr( $this->element_name ); ?>" class="siteorigin-widget-input" />
		</div>

		<?php
	}

	protected function sanitize_field_input( $value, $instance ) {
		if ( empty( $value ) ) {
			return array();
		}

		$value = explode( ',', $value );
		$media = array();

		foreach ( $value as $item ) {
			$media[] = (int) $item;
		}
		return $media;
	}

	function enqueue_scripts() {
		wp_enqueue_script( 'so-multiple-media-field', plugin_dir_url( __FILE__ ) . 'js/multiple-media-field' . SOW_BUNDLE_JS_SUFFIX .  '.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
		wp_enqueue_style( 'so-multiple-media-field', plugin_dir_url( __FILE__ ) . 'css/multiple-media-field.css', array( ), SOW_BUNDLE_VERSION );
	}
}

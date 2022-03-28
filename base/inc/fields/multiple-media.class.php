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

	/**
	 * Whether to display the item title or not. Default is `true`.
	 *
	 * @access protected
	 * @var boolean
	 */
	protected $title;

	/**
	 * The dimensions of each thumbnail item. Only used when editing widgets. The default is 75x75.
	 *
	 * @access protected
	 * @var array
	 */
	protected $thumbnail_dimensions;

	/**
	 * An optional array containing information about a repeater field. This will
	 * allow for the multiple media field to add items to the repeater.
	 *
	 * @access protected
	 * @var array
	 */
	protected $repeater;

	static $default_thumbnail_dimensions = array( 64, 64 ); 

	protected function get_default_options() {
		return array(
			'choose' => __( 'Add Media', 'so-widgets-bundle' ),
			'update' => __( 'Set Media', 'so-widgets-bundle' ),
			'library' => 'image',
			'title' => true,
			'thumbnail_dimensions' => self::$default_thumbnail_dimensions,
		);
	}

	protected function render_field( $attachments, $instance ) {
		if ( version_compare( get_bloginfo('version'), '3.5', '<' ) ){
			printf( __( 'You need to <a href="%s">upgrade</a> to WordPress 3.5 to use media fields', 'so-widgets-bundle'), admin_url('update-core.php' ) );
			return;
		}

		// Ensure thumbnail_dimensions are valid. 
		if (
			empty( $this->thumbnail_dimensions ) ||
			empty( $this->thumbnail_dimensions[0] ) ||
			empty( $this->thumbnail_dimensions[1] ) ||
			! is_numeric( $this->thumbnail_dimensions[0] ) ||
			! is_numeric( $this->thumbnail_dimensions[1] )
		) {
			$this->thumbnail_dimensions = self::$default_thumbnail_dimensions;
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

			<?php if ( empty( $this->repeater ) ) : ?>
				<div class="multiple-media-field-items">
					<?php
					if ( is_array( $attachments ) ) {
						foreach ( $attachments as $attachment ) {
							$item_title = get_the_title( $attachment );
							$src = wp_get_attachment_image_src( $attachment, 'thumbnail' );

							if ( empty( $src ) ) {
								// If item doesn't have an image src, use the WP icon for its media type.
								$src = wp_mime_type_icon( $attachment );
							} else {
								$src = $src[0];
							}
							?>
							<div class="multiple-media-field-item current" data-id="<?php echo esc_attr( $attachment ); ?>">
								<?php if ( ! empty( $src ) ) : ?>
									<img src="<?php echo sow_esc_url( $src ); ?>" class="thumbnail" title="<?php echo esc_attr( $item_title ); ?>" width="<?php echo $this->thumbnail_dimensions[0]; ?>" height="<?php echo $this->thumbnail_dimensions[1]; ?>"/>
								<?php endif; ?>
								<a href="#" class="media-remove-button"><?php esc_html_e( 'Remove', 'so-widgets-bundle' ); ?></a>
								<div class="title <?php echo (bool) $this->title ? 'title-enabled" style="width: ' . $this->thumbnail_dimensions[0] . 'px' : ''; ?>">
									<?php
									if ( ! empty( $item_title ) ) {
										echo esc_attr( $item_title );
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
					<div class="multiple-media-field-item current">
						<img class="thumbnail"  width="<?php echo $this->thumbnail_dimensions[0]; ?>" height="<?php echo $this->thumbnail_dimensions[1]; ?>"/>
						<a href="#" class="media-remove-button"><?php esc_html_e( 'Remove', 'so-widgets-bundle' ); ?></a>
						<div class="title <?php echo (bool) $this->title ? 'title-enabled" style="width: ' . $this->thumbnail_dimensions[0] . 'px' : ''; ?>"></div>
					</div>

				</div>
			<?php endif; ?>

			<input
				type="hidden"
				value="<?php echo is_array( $attachments ) ? esc_attr( implode( ',', $attachments ) ) : ''; ?>"
				data-element="<?php echo esc_attr( $this->element_name ); ?>"
				name="<?php echo esc_attr( $this->element_name ); ?>"
				<?php if ( ! empty( $this->repeater ) ) : ?>
					data-repeater="<?php echo esc_attr( json_encode( $this->repeater ) ); ?>"
				<?php endif; ?>
				class="siteorigin-widget-input"
			/>
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

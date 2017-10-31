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
	 * A label to search for external images.
	 *
	 * @var
	 */
	protected $image_search;

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

	protected function initialize(){
		static $once;
		if( empty( $once ) ) {
			add_action( 'siteorigin_widgets_footer_admin_templates', array( $this, 'image_search_dialog' ) );
		}
		$once = true;
	}

	protected function get_default_options() {
		return array(
			'choose' => __( 'Choose Media', 'so-widgets-bundle' ),
			'update' => __( 'Set Media', 'so-widgets-bundle' ),
			'image_search' => __( 'Image Search', 'so-widgets-bundle' ),
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
		
		// If library is set to all, convert it to a wildcard as all isn't valid
		if( $this->library == 'all' ){
			$this->library = '*';
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
			<?php if( $this->library == 'image' ) : ?>
				<a href="#" class="find-image-button">
					<?php echo esc_html( $this->image_search ) ?>
				</a>
			<?php endif; ?>
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

	function enqueue_scripts(){
		wp_enqueue_script( 'so-media-field', plugin_dir_url( __FILE__ ) . 'js/media-field' . SOW_BUNDLE_JS_SUFFIX .  '.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
		wp_enqueue_style( 'so-media-field', plugin_dir_url( __FILE__ ) . 'css/media-field.css', array( ), SOW_BUNDLE_VERSION );
	}

	function image_search_dialog(){
		?>
		<script type="text/template" id="so-widgets-bundle-tpl-image-search-dialog">
			<div class="so-widgets-dialog" id="so-widgets-image-search" data-confirm-import="<?php esc_attr_e( 'Would you like to import this image into your media library?', 'so-widgets-bundle' ) ?>">
				<div class="so-widgets-dialog-overlay"></div>

				<div class="so-widgets-toolbar">
					<h3><?php _e( 'Search For Images', 'so-widgets-bundle' ) ?></h3>
					<div class="close"><span class="dashicons dashicons-no-alt"></span></div>
				</div>

				<div class="so-widgets-dialog-frame">
					<div id="so-widgets-image-search-frame">

						<form id="so-widgets-image-search-form">
							<input type="text" value="" name="s" class="widefat so-widgets-search-input" placeholder="<?php echo esc_attr_e( 'Search For Images', 'so-widgets-bundle' ) ?>" />
							<?php wp_nonce_field( 'so-image', '_sononce', false ) ?>
							<button type="submit" class="button-primary so-widgets-search-button">
								<span class="dashicons dashicons-search"></span>
							</button>

							<div id="so-widgets-image-search-suggestions">
								<strong><?php esc_html_e( 'Related Searches: ', 'so-widgets-bundle' ) ?></strong>
								<ul class="so-keywords-list"></ul>
							</div>
						</form>
						<div id="so-widgets-image-search-powered">
							<?php
							printf(
								__( 'Powered by %s', 'so-widgets-bundle' ),
								'<a href="https://pixabay.com/" target="_blank" rel="noopener noreferrer">Pixabay</a>'
							);
							?>
						</div>

						<div class="so-widgets-image-results"></div>

						<div class="so-widgets-results-loading">
							<div class="so-widgets-loading-icon"></div>
							<strong
								data-loading="<?php esc_attr_e( 'Loading Images', 'so-widgets-bundle' ) ?>"
								data-importing="<?php esc_attr_e( 'Downloading Image - Please Wait', 'so-widgets-bundle' ) ?>"></strong>
						</div>
						<div class="so-widgets-results-more">
							<button class="button-secondary"><?php esc_html_e( 'Load More', 'so-widgets-bundle' ) ?></button>
						</div>

						<div class="so-widgets-preview-window">
							<div class="so-widgets-preview-window-inside">
							</div>
						</div>
					</div>
				</div>
			</div>
		</script>

		<script type="text/template" id="so-widgets-bundle-tpl-image-search-result">
			<div class="so-widgets-result">
				<a class="so-widgets-result-image"></a>
			</div>
		</script>

		<script type="text/template" id="so-widgets-bundle-tpl-image-search-result-sponsored">
			<span class="so-widgets-result-sponsored"><?php esc_html_e( 'Sponsored', 'so-widgets-bundle' ) ?></span>
		</script>
		<?php
	}
}

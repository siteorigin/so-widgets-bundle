<?php

/**
 * Class SiteOrigin_Widget_Field_Autocomplete
 */
class SiteOrigin_Widget_Field_Autocomplete extends SiteOrigin_Widget_Field_Text_Input_Base {
	/**
	 * An array of post types to use in the autocomplete query. Only used for posts.
	 *
	 * @var array
	 */
	protected $post_types;

	/**
	 * Indicates which database table will be used to retrieve autocomplete suggestions.
	 * Currently only `posts` and `terms` are allowed.
	 *
	 * @var string
	 */
	protected $source;

	/**
	 * Whether to allow multiple items to be selected.
	 *
	 * @access protected
	 * @var string
	 */
	protected $multiple;

	/**
	 * The CSS classes to be applied to the rendered text input.
	 */
	protected function get_input_classes() {
		return array( 'widefat', 'siteorigin-widget-input', 'siteorigin-widget-autocomplete-input' );
	}

	protected function get_default_options() {
		$defaults = parent::get_default_options();
		$defaults['source'] = 'posts';
		$defaults['multiple'] = true;
		return $defaults;
	}

	protected function render_after_field( $value, $instance ) {
		$post_types = ! empty( $this->post_types ) && is_array( $this->post_types ) ? implode( ',', $this->post_types ) : '';
		?>
		<div class="existing-content-selector" data-multiple="<?php echo esc_attr( $this->multiple ); ?>">

			<input
				type="text"
				class="content-text-search"
				data-post-types="<?php echo esc_attr( $post_types ); ?>"
				data-source="<?php echo esc_attr( $this->source ); ?>"
				placeholder="<?php esc_attr_e( 'Search', 'so-widgets-bundle' ); ?>"
				tabindex="0"
			/>

			<ul class="items"></ul>

			<div class="buttons">
				<a href="#" class="button-close button"><?php esc_html_e( 'Close', 'so-widgets-bundle' ); ?></a>
			</div>
		</div>
		<?php
		parent::render_after_field( $value, $instance );
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			'so-autocomplete-field',
			plugin_dir_url( __FILE__ ) . 'js/autocomplete-field' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION
		);
	}
}

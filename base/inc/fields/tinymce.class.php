<?php

class SiteOrigin_Widget_Field_TinyMCE extends SiteOrigin_Widget_Field_Text_Input_Base {
	/**
	 * The number of visible rows in the textarea.
	 *
	 * @access protected
	 * @var int
	 */
	protected $rows = 10;

	protected function render_field( $value, $instance ) {
		$settings = array(
			'textarea_name' => esc_attr( $this->element_name ),
			'editor_class' => 'siteorigin-widget-input-tinymce',
			'default_editor' => 'tinymce',
			'textarea_rows' => $this->rows,
		);
		$this->javascript_variables['mceSettings'] = $settings;
		$this->javascript_variables['qtSettings'] = array();
		?>
		<div class="siteorigin-widget-tinymce-container" data-element-id="<?php echo esc_attr( $this->element_id ) ?>"
		     data-element-name="<?php echo esc_attr( $this->element_name ) ?>">
			<?php
			wp_editor( $value, 'siteorigin-widget-input-tinymce-field', $settings )
			?>
		</div>
		<?php
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'so-tinymce-field', plugin_dir_url(__FILE__) . 'js/so-tinymce-field' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
	}
}
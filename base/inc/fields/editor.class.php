<?php

class SiteOrigin_Widget_Field_Editor extends SiteOrigin_Widget_Field_Text_Input_Base {

	protected function initialize() {
		if ( ! is_admin() ) {
			return;
		}
	}


	protected function render_field( $value, $instance ) {
		?>
		<textarea id="<?php echo esc_attr( $this->element_id ) ?>"
		          name="<?php echo esc_attr( $this->element_name ) ?>"
			<?php $this->render_data_attributes( $this->get_input_data_attributes() ) ?>
			<?php $this->render_CSS_classes( $this->get_input_classes() ) ?>
			<?php if ( ! empty( $this->placeholder ) ) echo 'placeholder="' . esc_attr( $this->placeholder ) . '"' ?>
			<?php if( ! empty( $this->readonly ) ) echo 'readonly' ?>></textarea>
		<?php

	}

	public function enqueue_scripts() {
		wp_enqueue_editor();
		wp_enqueue_script( 'so-editor-field', plugin_dir_url(__FILE__) . 'js/editor-field' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
	}

	protected function sanitize_field_input( $value, $instance ) {
		return $value;
	}

	public function sanitize_instance( $instance ) {
		return $instance;
	}
}

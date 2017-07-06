<?php

class SiteOrigin_Widget_Field_Editor extends SiteOrigin_Widget_Field_Text_Input_Base {

	protected function initialize() {
		if ( ! is_admin() ) {
			return;
		}
	}


	protected function render_field( $value, $instance ) {
		$settings = array(
			'tinymce' => array(
				'wp_skip_init' => strpos( $this->element_id, '__i__' ) != false ||
				                  strpos( $this->element_id, '_id_' ) != false,
				'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,wp_adv',
				'toolbar2' => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
				'wpautop' => true,
			),
			'quicktags' => array(
				'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close',
			),
		);
		
		?><div class="siteorigin-widget-tinymce-container"
		       data-editor-settings="<?php echo esc_attr( json_encode( $settings ) ) ?>">
			<textarea id="<?php echo esc_attr( $this->element_id ) ?>"
			          name="<?php echo esc_attr( $this->element_name ) ?>"
				<?php $this->render_data_attributes( $this->get_input_data_attributes() ) ?>
				<?php $this->render_CSS_classes( $this->get_input_classes() ) ?>
				<?php if ( ! empty( $this->placeholder ) ) echo 'placeholder="' . esc_attr( $this->placeholder ) . '"' ?>
				<?php if( ! empty( $this->readonly ) ) echo 'readonly' ?>></textarea>
		</div>
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

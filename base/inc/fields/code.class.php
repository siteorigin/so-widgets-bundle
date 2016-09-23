<?php

/**
 * Class SiteOrigin_Widget_Field_Textarea
 */
class SiteOrigin_Widget_Field_Code extends SiteOrigin_Widget_Field_Text_Input_Base {
	/**
	 * The number of visible rows in the textarea.
	 *
	 * @access protected
	 * @var int
	 */
	protected $rows;

	protected function render_field( $value, $instance ) {
		?>
		<textarea type="text" name="<?php echo esc_attr( $this->element_name ) ?>" id="<?php echo esc_attr( $this->element_id ) ?>"
			autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
			<?php if ( ! empty( $this->placeholder ) ) echo 'placeholder="' . esc_attr( $this->placeholder ) . '"' ?>
            <?php $this->render_CSS_classes( $this->get_input_classes() ) ?>
                  rows="<?php echo ! empty( $this->rows ) ? intval( $this->rows ) : 4 ?>"
			<?php if( ! empty( $this->readonly ) ) echo 'readonly' ?>><?php echo esc_textarea( $value ) ?></textarea>
		<?php
	}

	/**
	 * The CSS classes to be applied to the rendered text input.
	 */
	protected function get_input_classes() {
		return array( 'widefat', 'siteorigin-widget-input', 'siteorigin-widget-code-input' );
	}

	function enqueue_scripts(){
		wp_enqueue_script( 'so-code-field', plugin_dir_url( __FILE__ ) . 'js/code-field' . SOW_BUNDLE_JS_SUFFIX .  '.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
	}
}

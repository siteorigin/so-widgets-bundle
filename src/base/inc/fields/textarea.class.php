<?php

/**
 * Class SiteOrigin_Widget_Field_Textarea
 */
class SiteOrigin_Widget_Field_Textarea extends SiteOrigin_Widget_Field_Text_Input_Base {
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
			<?php if ( ! empty( $this->placeholder ) ) echo 'placeholder="' . esc_attr( $this->placeholder ) . '"' ?>
            <?php $this->render_CSS_classes( $this->get_input_classes() ) ?>
                  rows="<?php echo ! empty( $this->rows ) ? intval( $this->rows ) : 4 ?>"
			<?php if( ! empty( $this->readonly ) ) echo 'readonly' ?>><?php echo esc_textarea( $value ) ?></textarea>
		<?php
	}
}
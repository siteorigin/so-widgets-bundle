<?php

/**
 * Class SiteOrigin_Widget_Field_Textarea
 */
class SiteOrigin_Widget_Field_Textarea extends SiteOrigin_Widget_Field_Text_Input_Base {
/**
	 * The number of visible rows in the textarea.
	 *
	 * @var int
	 */
	protected $rows;

	protected function render_field( $value, $instance ) {
		?>
		<textarea
			type="text"
			name="<?php echo esc_attr( $this->element_name ); ?>"
			id="<?php echo esc_attr( $this->element_id ); ?>"
			rows="<?php echo ! empty( $this->rows ) ? (int) $this->rows : 4; ?>"
			<?php
			$this->render_CSS_classes( $this->get_input_classes() );

			if ( ! empty( $this->placeholder ) ) {
				echo 'placeholder="' . esc_attr( $this->placeholder ) . '"';
			}
			if ( ! empty( $this->readonly ) ) {
				echo 'readonly';
			} ?>><?php echo ! empty( $value ) ? esc_textarea( $value ) : ''; ?></textarea>
		<?php
	}
}

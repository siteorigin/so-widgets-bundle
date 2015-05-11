<?php

/**
 * This is a placeholder class until we have a TinyMCE editor field.
 *
 * Class SiteOrigin_Widget_Field_Editor
 */
class SiteOrigin_Widget_Field_Editor extends SiteOrigin_Widget_Field_Text_Input_Base {
	/**
	 * The number of visible rows in the textarea.
	 *
	 * @access protected
	 * @var int
	 */
	protected $rows;

	public function __construct( $base_name, $element_id, $element_name, $field_options ) {
		parent::__construct( $base_name, $element_id, $element_name, $field_options );

		if( isset( $field_options['rows'] ) ) $this->rows = $field_options['rows'];

		$this->input_classes[] = 'siteorigin-widget-input-editor';
	}

	protected function render_field( $value, $instance ) {
		?>
		<textarea type="text" name="<?php echo $this->element_name ?>"
		          id="<?php echo $this->element_id ?>"
			<?php if ( ! empty( $this->placeholder ) ) echo 'placeholder="' . esc_attr( $this->placeholder ) . '"' ?>
			<?php $this->render_input_classes() ?>
			      rows="<?php echo ! empty( $this->rows ) ? intval( $this->rows ) : 4 ?>"
			<?php if( ! empty( $this->readonly ) ) echo 'readonly' ?>><?php echo esc_textarea( $value ) ?></textarea>
		<?php
	}
}
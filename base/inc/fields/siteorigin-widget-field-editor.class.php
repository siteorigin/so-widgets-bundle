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

	public function __construct( $base_name, $element_id, $element_name, $options ) {
		parent::__construct( $base_name, $element_id, $element_name, $options );

		if( isset( $options['rows'] ) ) $this->rows = $options['rows'];
	}

	protected function render_field( $value ) {
		?>
		<textarea type="text" name="<?php echo $this->element_name ?>"
		          id="<?php echo $this->element_id ?>"
			<?php if ( ! empty( $this->placeholder ) ) echo 'placeholder="' . $this->placeholder . '"' ?>
			      class="widefat siteorigin-widget-input siteorigin-widget-input-editor"
			      rows="<?php echo !empty( $this->rows ) ? intval( $this->rows ) : 4 ?>"
			<?php if( ! empty( $this->readonly ) ) echo 'readonly' ?>><?php echo esc_textarea( $value ) ?></textarea>
		<?php
	}
}
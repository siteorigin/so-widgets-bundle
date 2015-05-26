<?php

/**
 *
 * The common base class for text input type fields.
 *
 * Class SiteOrigin_Widget_Field_Text
 */
abstract class SiteOrigin_Widget_Field_Text_Input_Base extends SiteOrigin_Widget_Field_Base {

	/**
	 * A string to display before any text has been input.
	 *
	 * @access protected
	 * @var string
	 */
	protected $placeholder;
	/**
	 * If true, this field will not be editable.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $readonly;

	/**
	 * The CSS classes to be applied to the rendered text input.
	 */
	protected function get_input_classes() {
		return array( 'widefat', 'siteorigin-widget-input' );
	}

	protected function render_field( $value, $instance ) {
		?>
		<input type="text" name="<?php echo esc_attr( $this->element_name ) ?>" id="<?php echo esc_attr( $this->element_id ) ?>"
		         value="<?php echo esc_attr( $value ) ?>"
		         <?php $this->render_CSS_classes( $this->get_input_classes() ) ?>
			<?php if ( ! empty( $this->placeholder ) ) echo 'placeholder="' . esc_attr( $this->placeholder ) . '"' ?>
			<?php if( ! empty( $this->readonly ) ) echo 'readonly' ?> />
		<?php
	}

	protected function sanitize_field_input( $value ) {
		$sanitized_value = wp_kses_post( $value );
		$sanitized_value = balanceTags( $sanitized_value , true );
		return $sanitized_value;
	}
}
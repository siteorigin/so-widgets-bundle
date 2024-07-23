<?php

/**
 * The common base class for text input type fields.
 *
 * Class SiteOrigin_Widget_Field_Text
 */
abstract class SiteOrigin_Widget_Field_Text_Input_Base extends SiteOrigin_Widget_Field_Base {
	/**
	 * A string to display before any text has been input.
	 *
	 * @var string
	 */
	protected $placeholder;

	/**
	 * If true, this field will not be editable.
	 *
	 * @var bool
	 */
	protected $readonly;

	/**
	 * The type of this input.
	 *
	 * @var string
	 */
	protected $input_type;

	/**
	 * Whether to apply onclick sanitization to this field when saving.
	 *
	 * @var string
	 */
	protected $onclick;

	/**
	 * Whether to allow HTML or not.
	 *
	 * @var bool
	 */
	protected $allow_html = true;

	/**
	 * The width of the input field.
	 *
	 * @var int
	 */
	protected $width;

	/**
	 * The CSS classes to be applied to the rendered text input.
	 */
	protected function get_input_classes() {
		return array( 'widefat', 'siteorigin-widget-input' );
	}

	/**
	 * The data attributes to be added to the input element.
	 */
	protected function get_input_data_attributes() {
		return array();
	}

	/**
	 * The attributes to be added to the input element.
	 */
	protected function get_input_attributes() {
		return array();
	}

	protected function get_default_options() {
		return array(
			'input_type' => 'text',
		);
	}

	protected function render_data_attributes( $data_attributes ) {
		$attr_string = '';

		foreach ( $data_attributes as $name => $value ) {
			$attr_string .= ' data-' . siteorigin_sanitize_attribute_key( $name ) . '="' . esc_attr( $value ) . '"';
		}
		echo $attr_string;
	}

	protected function render_attributes( $attributes ) {
		$attr_string = '';

		foreach ( $attributes as $name => $value ) {
			$attr_string .= ' ' . siteorigin_sanitize_attribute_key( $name ) . '="' . esc_attr( $value ) . '"';
		}
		echo $attr_string;
	}

	protected function render_field( $value, $instance ) {
		?>
		<input
			type="<?php echo esc_attr( $this->input_type ); ?>"
			name="<?php echo esc_attr( $this->element_name ); ?>"
			id="<?php echo esc_attr( $this->element_id ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			<?php $this->render_data_attributes( $this->get_input_data_attributes() ); ?>
			<?php $this->render_attributes( $this->get_input_attributes() ); ?>
			<?php $this->render_CSS_classes( $this->get_input_classes() ); ?>
			<?php if ( ! empty( $this->width ) ) { ?>
				style="width: <?php echo (int) $this->width; ?>px"
				<?php
			}

			if ( ! empty( $this->placeholder ) ) {
				echo 'placeholder="' . esc_attr( $this->placeholder ) . '"';
			}

			if ( ! empty( $this->readonly ) ) {
				echo 'readonly';
			}
			?>
		/>
		<?php
	}

	protected function sanitize_field_input( $value, $instance ) {
		if ( $this->allow_html ) {
			$value = wp_kses_post( $value );
		} else {
			$value = sanitize_text_field( $value );
		}

		$value = balanceTags( $value, true );

		// Remove escape sequences.
		$value = siteorigin_widgets_strip_escape_sequences( $value );

		if ( ! empty( $this->onclick ) ) {
			return siteorigin_widget_onclick( $value );
		}

		return $value;
	}
}

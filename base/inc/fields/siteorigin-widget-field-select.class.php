<?php

/**
 * Class SiteOrigin_Widget_Field_Select
 */
class SiteOrigin_Widget_Field_Select extends SiteOrigin_Widget_Field {
	/**
	 * The list of options which may be selected.
	 *
	 * @access protected
	 * @var array
	 */
	protected $options;
	/**
	 * If present, this string is included as a disabled (not selectable) value at the top of the list of options. If
	 * there is no default value, it is selected by default. You might even want to leave the label value blank when
	 * you use this.
	 *
	 * @access protected
	 * @var string
	 */
	protected $prompt;

	public function __construct( $base_name, $element_id, $element_name, $field_options ) {
		parent::__construct( $base_name, $element_id, $element_name, $field_options );

		if( isset( $field_options['options'] ) ) $this->options = $field_options['options'];
		if( isset( $field_options['prompt'] ) ) $this->prompt = $field_options['prompt'];
	}

	protected function render_field( $value, $instance ) {
		?>
		<select name="<?php echo $this->element_name ?>" id="<?php echo $this->element_id ?>"
		        class="siteorigin-widget-input">
			<?php if ( isset( $this->prompt ) ) : ?>
				<option value="default" disabled="disabled" selected="selected"><?php echo esc_html( $this->prompt ) ?></option>
			<?php endif; ?>

			<?php if( isset( $this->options ) && !empty( $this->options ) ) : ?>
				<?php foreach( $this->options as $key => $val ) : ?>
					<option value="<?php echo esc_attr( $key ) ?>" <?php selected( $key, $value ) ?>><?php echo esc_html( $val ) ?></option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
		<?php
	}

	protected function sanitize_field_input( $value ) {
		$sanitized_value = $value;
		$keys = array_keys( $this->options );
		if( ! in_array( $sanitized_value, $keys ) ) $sanitized_value = isset( $this->default ) ? $this->default : false;
		return $sanitized_value;
	}

}
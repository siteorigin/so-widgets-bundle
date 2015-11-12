<?php

/**
 * Class SiteOrigin_Widget_Field_Radio
 */
class SiteOrigin_Widget_Field_Radio extends SiteOrigin_Widget_Field_Base {
	/**
	 * The list of options which may be selected.
	 *
	 * @access protected
	 * @var array
	 */
	protected $options;

	protected function render_field( $value, $instance ) {
		if ( ! isset( $this->options ) || empty( $this->options ) ) return;

		foreach( $this->options as $k => $v ) {
			?>
			<label for="<?php echo esc_attr( $this->element_id . '-' . $k ) ?>">
				<input type="radio" name="<?php echo esc_attr( $this->element_name ) ?>"
			       id="<?php echo esc_attr( $this->element_id . '-' . $k ) ?>" class="siteorigin-widget-input"
			       value="<?php echo esc_attr( $k ) ?>" <?php checked( $k, $value ) ?>> <?php echo esc_html( $v ) ?>
			</label>
			<?php
		}
	}

	protected function sanitize_field_input( $value, $instance ) {
		$sanitized_value = $value;
		$keys = array_keys( $this->options );
		if( ! in_array( $sanitized_value, $keys ) ) $sanitized_value = isset( $this->default ) ? $this->default : false;
		return $sanitized_value;
	}

}
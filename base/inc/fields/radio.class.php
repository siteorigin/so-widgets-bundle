<?php

/**
 * Class SiteOrigin_Widget_Field_Radio
 */
class SiteOrigin_Widget_Field_Radio extends SiteOrigin_Widget_Field_Base {
/**
	 * The list of options which may be selected.
	 *
	 * @var array
	 */
	protected $options;

	protected function render_field( $value, $instance ) {
		if ( ! isset( $this->options ) || empty( $this->options ) ) {
			return;
		}
		$i = 0;

		foreach ( $this->options as $k => $v ) {
			?>
			<label for="<?php echo esc_attr( $this->element_id . '-' . $i ); ?>">
				<input
					type="radio"
					name="<?php echo esc_attr( $this->element_name ); ?>"
					id="<?php echo esc_attr( $this->element_id . '-' . $i ); ?>"
					class="siteorigin-widget-input"
					value="<?php echo esc_attr( $k ); ?>"
					<?php checked( $k, $value ); ?>
				>
				<?php echo esc_html( $v ); ?>
			</label>
			<?php
			++$i;
		}
	}

	protected function sanitize_field_input( $value, $instance ) {
		// When the options registry didn't populate this request, return the
		// stored value unchanged instead of resetting it to default.
		if ( empty( $this->options ) ) {
			return $value;
		}

		$sanitized_value = $value;
		$keys = array_keys( $this->options );

		if ( ! in_array( $sanitized_value, $keys ) ) {
			$sanitized_value = isset( $this->default ) ? $this->default : false;
		}

		return $sanitized_value;
	}
}

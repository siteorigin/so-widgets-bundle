<?php

/**
 * Class SiteOrigin_Widget_Field_Checkbox
 */
class SiteOrigin_Widget_Field_Checkboxes extends SiteOrigin_Widget_Field_Base {
	protected $options;

	protected function render_field( $value, $instance ) {
		if ( empty( $value ) ) {
			$value = array();
		}

		if ( !is_array( $value ) ) {
			$value = array( $value );
		}

		$i = 0;

		foreach ( $this->options as $id => $label ) {
			?>
			<label for="<?php echo esc_attr( $this->element_id ); ?>-<?php echo esc_attr( $id ); ?>" class="so-checkbox-label">
				<input
					type="checkbox"
					class="siteorigin-widget-input"
					name="<?php echo esc_attr( $this->element_name ); ?>[<?php echo esc_attr( $i++ ); ?>]"
					value="<?php echo esc_attr( $id ); ?>"
					id="<?php echo esc_attr( $this->element_id ); ?>-<?php echo esc_attr( $id ); ?>"
					<?php checked( in_array( $id, $value ) ); ?>
					/>
				<?php echo esc_html( $label ); ?>
			</label>
			<?php
		}
	}

	protected function sanitize_field_input( $value, $instance ) {
		if ( empty( $value ) ) {
			$value = array();
		}

		// When the options registry didn't populate this request, don't reset
		// valid stored values to default.
		if ( empty( $this->options ) ) {
			// See select.class.php::sanitize_field_input() for full reasoning
			// (same registry-empty gap, same fix). Checkboxes values are
			// always an array (checkbox group) — preserve the existing
			// scalar-to-array coercion for the untouched-resave path, and
			// sanitize each element for the changed-value path.
			$normalized_value = is_array( $value ) ? $value : array( $value );

			if ( $normalized_value === $this->old_value ) {
				return $normalized_value;
			}

			return array_map( 'sanitize_text_field', $normalized_value );
		}

		$values = is_array( $value ) ? $value : array( $value );
		$keys = array_keys( $this->options );
		$sanitized_value = array();

		foreach ( $values as $value ) {
			if ( ! in_array( $value, $keys ) ) {
				$sanitized_value[] = isset( $this->default ) ? $this->default : false;
			} else {
				$sanitized_value[] = $value;
			}
		}

		return $sanitized_value;
	}
}

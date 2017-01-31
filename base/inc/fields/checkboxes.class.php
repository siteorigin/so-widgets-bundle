<?php

/**
 * Class SiteOrigin_Widget_Field_Checkbox
 */
class SiteOrigin_Widget_Field_Checkboxes extends SiteOrigin_Widget_Field_Base {

	protected $options;

	protected function render_field( $value, $instance ) {
		if( empty($value) ) {
			$value = array();
		}

		if( !is_array( $value ) ) {
			$value = array( $value );
		}

		foreach( $this->options as $id => $label ) {
			?>
			<label for="<?php echo esc_attr( $this->element_id ) ?>-<?php echo esc_attr( $id ) ?>" class="so-checkbox-label">
				<input
					type="checkbox"
					class="siteorigin-widget-input"
					name="<?php echo esc_attr( $this->element_name ) ?>[]"
					value="<?php echo esc_attr( $id ) ?>"
					id="<?php echo esc_attr( $this->element_id ) ?>-<?php echo esc_attr( $id ) ?>"
				    <?php checked( in_array( $id, $value ) ) ?>
					/>
				<?php echo( $label ) ?>
			</label>
			<?php
		}
	}

	protected function sanitize_field_input( $value, $instance ) {
		if( empty( $value ) ) {
			$value = array();
		}

		$values = is_array( $value ) ? $value : array( $value );
		$keys = array_keys( $this->options );
		$sanitized_value = array();
		foreach( $values as $value ) {
			if ( !in_array( $value, $keys ) ) {
				$sanitized_value[] = isset( $this->default ) ? $this->default : false;
			}
			else {
				$sanitized_value[] = $value;
			}
		}

		return $sanitized_value;
	}

}

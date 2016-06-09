<?php

/**
 * Class SiteOrigin_Widget_Field_Measurement
 */
class SiteOrigin_Widget_Field_Measurement extends SiteOrigin_Widget_Field_Text_Input_Base {

	protected function get_input_classes() {
		$input_classes = parent::get_input_classes();
		$input_classes[] = 'siteorigin-widget-input-measurement';
		return $input_classes;
	}

	/**
	 * Parse a value into a unit and value.
	 *
	 * @param $value
	 *
	 * @return array
	 */
	protected function get_render_values( $value ) {
		preg_match('/(\d+\.?\d*)([a-z%]+)*/', $value, $matches);
		$num_matches = count( $matches );
		$val = array();
		$val['value'] = $num_matches > 1 ? $matches[1] : null;
		$val['unit'] = $num_matches > 2 ? $matches[2] : null;
		return $val;
	}

	protected function render_field( $value, $instance ) {
		$value_parts = $this->get_render_values($value);
		parent::render_field( $value_parts['value'], $instance );
	}

	protected function render_after_field( $value, $instance ) {
		$value_parts = $this->get_render_values($value);
		$unit = $value_parts['unit'];
		if ( is_null( $unit ) ) {
			$unit_name = $this->get_unit_field_name( $this->base_name );

			if( !empty( $instance[ $unit_name ] ) ) {
				$unit = $instance[ $unit_name ];
			}
			else if ( isset( $this->default ) ) {
				$default_parts = $this->get_render_values($this->default);
				$unit = $default_parts['unit'];
			}
		}
		?>
		<select class="sow-measurement-select-unit siteorigin-widget-input"
				name="<?php echo esc_attr( $this->for_widget->so_get_field_name( $this->base_name . '_unit', $this->parent_container ) ) ?>">
			<?php foreach ( siteorigin_widgets_get_measurements_list() as $measurement ):?>
				<option value="<?php echo esc_attr( $measurement ) ?>" <?php selected( $measurement, $unit, true ); ?>><?php echo esc_html( $measurement ) ?></option>
			<?php endforeach?>
		</select>
		<div class="clear"></div>
		<?php
		//Still want the default description, if there is one.
		parent::render_after_field( $value, $instance );
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'so-measurement-field', plugin_dir_url(__FILE__) . 'css/measurement-field.css', array(), SOW_BUNDLE_VERSION );
	}

	// This is doing sanitization and is being used to concatenate the numeric measurement value and the selected
	// measurement unit.
	protected function sanitize_field_input( $value, $instance ) {
		//Get the property name of the unit field
		$unit_name = $this->get_unit_field_name( $this->base_name );

		//Initialize with default value, if any.
		$default_parts = $this->get_render_values($this->default);
		$unit = $default_parts['unit'];
		if( isset( $instance[ $unit_name ] ) ) {
			$units = siteorigin_widgets_get_measurements_list();
			if ( in_array( $instance[ $unit_name ], $units ) ) {
				$unit = $instance[ $unit_name ];
			}
			unset( $instance[ $unit_name ] );
		}

		$value = ( $value === '' ) ? false : ( (float) $value ) . $unit;
		return $value;
	}

	// Get the name of the dropdown field rendered to allow the user to select a measurement unit.
	public function get_unit_field_name( $base_name ) {
		$v_name = $base_name;
		if( strpos($v_name, '][') !== false ) {
			// Remove this splitter
			$v_name = substr( $v_name, strrpos($v_name, '][') + 2 );
		}
		return $v_name . '_unit';
	}
}

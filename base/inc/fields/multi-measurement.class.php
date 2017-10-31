<?php

/**
 * Class SiteOrigin_Widget_Field_Multi_Measurement
 */
class SiteOrigin_Widget_Field_Multi_Measurement extends SiteOrigin_Widget_Field_Text_Input_Base {
	
	/**
	 * Configuration of the measurements to be taken. Should be in the form:
	 * `array(
	 *      'padding_left' => array(
	 * 			'label' => __( 'Padding left', 'so-widgets-bundle' ),
	 * 			'units' => array( 'px', 'rem', 'em', '%' ),
	 * 		),
	 *      'padding_right' => __( 'Padding right', 'so-widgets-bundle' ),
	 *      'padding_bottom' => __( 'Padding bottom', 'so-widgets-bundle' ),
	 *      'padding_top' => __( 'Padding top', 'so-widgets-bundle' ),
	 * )`
	 *
	 * The 'units' property is optional and defaults to the list returned by `siteorigin_widgets_get_measurements_list`.
	 *
	 * @access protected
	 * @var array
	 */
	protected $measurements;
	
	/**
	 * String separator for the measurements. Default is an empty space.
	 *
	 * @access protected
	 * @var string
	 */
	protected $separator;
	
	/**
	 * Whether to automatically fill the rest of the inputs when the first value is entered.
	 * Default is false.
	 *
	 * @access protected
	 * @var boolean
	 */
	protected $autofill;
	
	protected function get_default_options() {
		return array(
			'separator' => ' ',
			'autofill' => false,
		);
	}
	
	protected function render_field( $value, $instance ) {
		?>
		<div class="sow-multi-measurement-container">
		<?php
		foreach ( $this->measurements as $name => $measurement_config ) {
			
			if ( is_array( $measurement_config ) ) {
				$label = empty( $measurement_config['label'] ) ? '' : $measurement_config['label'];
			} else {
				$label = $measurement_config;
			}
			$units = empty( $measurement_config['units'] ) ?
				siteorigin_widgets_get_measurements_list() :
				$measurement_config['units'];
			$input_id = $this->element_id . '-' . $name;
			?>
			<div class="sow-multi-measurement-input-container">
				<label for="<?php echo esc_attr( $input_id ) ?>"><?php echo esc_html( $label ) ?></label>
				<input id="<?php echo esc_attr( $input_id ) ?>" type="text" class="sow-multi-measurement-input">
				<select class="sow-multi-measurement-select-unit">
					<?php foreach ( $units as $unit ):?>
						<option value="<?php echo esc_attr( $unit ) ?>"><?php echo esc_html( $unit ) ?></option>
					<?php endforeach?>
				</select>
				<div class="clear"></div>
			</div>
			<?php
		}
		?>
		</div>
		<input type="hidden"
			   class="siteorigin-widget-input"
			   value="<?php echo esc_attr( $value ) ?>"
			   name="<?php echo esc_attr( $this->element_name ) ?>"
			   data-autofill="<?php echo empty( $this->autofill ) ? 'false' : 'true'; ?>"
			   data-separator="<?php echo esc_attr( $this->separator ) ?>"/><?php
	}
	
	public function enqueue_scripts() {
		wp_enqueue_style( 'so-multi-measurement-field', plugin_dir_url( __FILE__ ) . 'css/multi-measurement-field.css', array(),
			SOW_BUNDLE_VERSION );
		wp_enqueue_script( 'so-multi-measurement-field',
			plugin_dir_url( __FILE__ ) . 'js/multi-measurement-field' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery' ),
			SOW_BUNDLE_VERSION );
	}
	
	protected function sanitize_field_input( $value, $instance ) {
		if ( empty( $value ) ) {
			return $value;
		}
		
		$values = explode( $this->separator, $value );
		$any_values = false;
		foreach ( $values as $index => $measurement ) {
			$any_values = $any_values || ! empty( $measurement );
			preg_match('/(\d+\.?\d*)([a-z%]+)*/', $measurement, $matches);
			
			$num_matches = count( $matches );
			
			$val = $num_matches > 1 ? $matches[1] : '0';
			$unit = $num_matches > 2 ? $matches[2] : 'px';
			$measurement = $val . $unit;
			$values[ $index ] = $measurement;
		}
		// If all values are empty, just return an empty string.
		return $any_values ? implode( $this->separator, $values ) : '';
	}
}

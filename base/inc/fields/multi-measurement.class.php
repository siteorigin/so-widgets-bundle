<?php

/**
 * Class SiteOrigin_Widget_Field_Multi_Measurement
 */
class SiteOrigin_Widget_Field_Multi_Measurement extends SiteOrigin_Widget_Field_Text_Input_Base {
	
	/**
	 * Configuration of the measurements to be taken. Should be in the form:
	 * `array(
	 *      'padding_left' => __( 'Padding left', 'so-widgets-bundle' ),
	 *      'padding_right' => __( 'Padding right', 'so-widgets-bundle' ),
	 *      'padding_bottom' => __( 'Padding bottom', 'so-widgets-bundle' ),
	 *      'padding_top' => __( 'Padding top', 'so-widgets-bundle' ),
	 * )`
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
	
	protected function get_default_options() {
		return array(
			'separator' => ' ',
		);
	}
	
	protected function render_field( $value, $instance ) {
		foreach ( $this->measurements as $name => $label ) {
			?>
			<div class="sow-multi-measurement-input-container">
				<input class="sow-multi-measurement-input" placeholder="<?php echo esc_attr( $label ) ?>">
				<select class="sow-multi-measurement-select-unit">
					<?php foreach ( siteorigin_widgets_get_measurements_list() as $measurement_unit ):?>
						<option value="<?php echo esc_attr( $measurement_unit ) ?>" <?php selected( $measurement_unit, $value[$name], true ); ?>><?php echo esc_html( $measurement_unit ) ?></option>
					<?php endforeach?>
				</select>
				<div class="clear"></div>
			</div>
			<?php
		}
		?><input type="hidden"
				 class="siteorigin-widget-input"
				 value="<?php echo esc_attr( $value ) ?>"
				 name="<?php echo esc_attr( $this->element_name ) ?>"
				 data-separator="<?php echo esc_attr( $this->separator ) ?>"/><?php
	}
	
	public function enqueue_scripts() {
		wp_enqueue_script( 'so-multi-measurement-field',
			plugin_dir_url( __FILE__ ) . 'js/multi-measurement-field' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery' ),
			SOW_BUNDLE_VERSION );
	}
	
	protected function sanitize_field_input( $value, $instance ) {
		return $value;
	}
}

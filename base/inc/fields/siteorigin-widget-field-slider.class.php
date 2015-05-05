<?php

/**
 * Class SiteOrigin_Widget_Field_Slider
 */
class SiteOrigin_Widget_Field_Slider extends SiteOrigin_Widget_Field {

	/**
	 * The minimum value of the allowed range.
	 *
	 * @access protected
	 * @var int
	 */
	protected $min;
	/**
	 * The maximum value of the allowed range.
	 *
	 * @access protected
	 * @var int
	 */
	protected $max;

	public function __construct( $base_name, $element_id, $element_name, $field_options ) {
		parent::__construct( $base_name, $element_id, $element_name, $field_options );

		if( isset( $field_options['min'] ) ) $this->min = $field_options['min'];
		if( isset( $field_options['max'] ) ) $this->max = $field_options['max'];
	}


	protected function render_field( $value, $instance ) {
		?>
		<div class="siteorigin-widget-slider-value"><?php echo ! empty( $value ) ? $value : 0 ?></div>
		<div class="siteorigin-widget-slider-wrapper">
			<div class="siteorigin-widget-value-slider"></div>
		</div>
		<input
			type="number"
			class="siteorigin-widget-input"
			name="<?php echo $this->element_name ?>"
			id="<?php echo $this->element_id ?>"
			value="<?php echo !empty( $value ) ? esc_attr( $value ) : 0 ?>"
			min="<?php echo isset( $this->min ) ? intval( $this->min ) : 0 ?>"
			max="<?php echo isset( $this->max ) ? intval( $this->max ) : 100 ?>" />
		<?php
	}

	protected function sanitize_field_input( $value ) {
		return (float) $value;
	}


}
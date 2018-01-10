<?php

/**
 * Class SiteOrigin_Widget_Field_Slider
 */
class SiteOrigin_Widget_Field_Slider extends SiteOrigin_Widget_Field_Base {

	/**
	 * The minimum value of the allowed range.
	 *
	 * @access protected
	 * @var float
	 */
	protected $min;

	/**
	 * The maximum value of the allowed range.
	 *
	 * @access protected
	 * @var float
	 */
	protected $max;

    /**
     * The step size when moving in the range.
     *
     * @access protected
     * @var float
     */
    protected $step;

	protected function render_field( $value, $instance ) {
		?>
		<div class="siteorigin-widget-slider-value"><?php echo ! empty( $value ) ? esc_html( $value ) : 0 ?></div>
		<div class="siteorigin-widget-slider-wrapper">
			<div class="siteorigin-widget-value-slider"></div>
		</div>
		<input type="number" class="siteorigin-widget-input" name="<?php echo esc_attr(  $this->element_name ) ?>" id="<?php echo esc_attr( $this->element_id ) ?>"
			value="<?php echo !empty( $value ) ? esc_attr( $value ) : 0 ?>"
			min="<?php echo isset( $this->min ) ? floatval( $this->min ) : 0 ?>"
			max="<?php echo isset( $this->max ) ? floatval( $this->max ) : 100 ?>"
			step="<?php echo isset( $this->step ) ? floatval( $this->step ) : 1 ?>"/>
		<?php
	}

	protected function sanitize_field_input( $value, $instance ) {
		return (float) $value;
	}

}
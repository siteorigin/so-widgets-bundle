<?php

/**
 * Class SiteOrigin_Widget_Field_Slider
 */
class SiteOrigin_Widget_Field_Slider extends SiteOrigin_Widget_Field_Base {

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

	protected function render_field( $value, $instance ) {
		?>
		<div class="siteorigin-widget-slider-value"><?php echo ! empty( $value ) ? esc_html( $value ) : 0 ?></div>
		<div class="siteorigin-widget-slider-wrapper">
			<div class="siteorigin-widget-value-slider"></div>
		</div>
		<input type="number" class="siteorigin-widget-input" name="<?php echo esc_attr(  $this->element_name ) ?>" id="<?php echo esc_attr( $this->element_id ) ?>"
			value="<?php echo !empty( $value ) ? esc_attr( $value ) : 0 ?>"
			min="<?php echo isset( $this->min ) ? intval( $this->min ) : 0 ?>"
			max="<?php echo isset( $this->max ) ? intval( $this->max ) : 100 ?>" />
		<?php
	}

	protected function sanitize_field_input( $value ) {
		return (float) $value;
	}

}
<?php

/**
 * Class SiteOrigin_Widget_Field_Number
 */
class SiteOrigin_Widget_Field_Number extends SiteOrigin_Widget_Field_Text_Input_Base {
	/**
	 * The minimum value of the allowed range.
	 *
	 * @var float
	 */
	protected $min;

	/**
	 * The maximum value of the allowed range.
	 *
	 * @var float
	 */
	protected $max;

	/**
	 * The step size when moving in the range.
	 *
	 * @var float
	 */
	protected $step;

	/**
	 * Whether to apply abs() when saving to ensure only positive numbers are possible.
	 *
	 * @var bool
	 */
	protected $abs;

	protected function get_default_options() {
		return array(
			'input_type' => 'number',
		);
	}

	protected function get_input_attributes() {
		$input_attributes = array(
			'step' => $this->step,
			'min'  => $this->min,
			'max'  => $this->max,
		);

		return array_filter( $input_attributes );
	}

	protected function get_input_classes() {
		$input_classes = parent::get_input_classes();
		$input_classes[] = 'siteorigin-widget-input-number';

		return $input_classes;
	}

	protected function sanitize_field_input( $value, $instance ) {
		if ( empty( $value ) ) {
			return false;
		}

		if ( ! empty( $this->min ) ) {
			$value = max( $value, $this->min );
		}

		if ( ! empty( $this->max ) ) {
			$value = min( $value, $this->max );
		}

		if ( ! empty( $this->abs ) ) {
			$value = abs( $value );
		}

		return (float) $value;
	}
}

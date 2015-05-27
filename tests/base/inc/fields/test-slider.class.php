<?php

class SiteOrigin_Widget_Field_SliderTest extends WP_UnitTestCase {

	/* @var $field SiteOrigin_Widget_Field_Base */
	private $field;

	private $field_output;

	function setUp() {
		$field_options = array(
			'type' => 'slider',
			'label' => 'A slider field',
			'min' => 2,
			'max' => 22,
		);

		$this->field = new SiteOrigin_Widget_Field_Slider( 'slider_field', 'slider_field_id', 'slider_field_name', $field_options );

		$this->field_output = get_field_render_output( $this->field );
	}


	/**
	 * @test
	 */
	public function render_outputs_correctly() {

		$this->assertContains( '<input type="number" class="siteorigin-widget-input" name="slider_field_name" id="slider_field_id"', $this->field_output );
	}

	/**
	 * @test
	 */
	public function render_outputs_min_value() {

		$this->assertContains( 'min="2"', $this->field_output );
	}
	/**
	 * @test
	 */
	public function render_outputs_max_value() {

		$this->assertContains( 'max="22"', $this->field_output );
	}

	/**
	 * @test
	 */
	public function sanitize_converts_to_float() {

		$value = 9.23;
		$sanitized_value = $this->field->sanitize( $value );
		$this->assertEquals( $value, $sanitized_value );


		$value = 'not a float';
		$sanitized_value = $this->field->sanitize( $value );
		$this->assertEquals( 0, $sanitized_value );
	}
}

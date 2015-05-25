<?php

class SiteOrigin_Widget_Field_RadioTest extends WP_UnitTestCase {
	/**
	 * @test
	 */
	public function render_outputs_correctly() {
		$field_options = array(
			'type' => 'radio',
			'label' => 'A radio input',
			'options' => array(
				'option_one' => 'Option One',
				'option_two' => 'Option Two',
				'option_three' => 'Option Three',
			)
		);

		$field = new SiteOrigin_Widget_Field_Radio( 'radio_field', 'radio_field_id', 'radio_field_name', $field_options );

		$actual = get_field_render_output( $field );

		$this->assertContains( '<input type="radio" name="radio_field_name"', $actual );
	}

	/**
	 * @test
	 */
	public function sanitize_ensures_the_value_exists_in_options() {
		$field_options = array(
			'type' => 'radio',
			'label' => 'A radio input',
			'options' => array(
				'option_one' => 'Option One',
				'option_two' => 'Option Two',
				'option_three' => 'Option Three',
			)
		);

		$field = new SiteOrigin_Widget_Field_Radio( 'radio_field', 'radio_field_id', 'radio_field_name', $field_options );

		$value = 'option_one';
		$sanitized_value = $field->sanitize( $value );

		$this->assertEquals( $value, $sanitized_value );
	}

	/**
	 * @test
	 */
	public function sanitize_set_value_to_false_if_not_in_options() {
		$field_options = array(
			'type' => 'radio',
			'label' => 'A radio input',
			'options' => array(
				'option_one' => 'Option One',
				'option_two' => 'Option Two',
				'option_three' => 'Option Three',
			)
		);

		$field = new SiteOrigin_Widget_Field_Radio( 'radio_field', 'radio_field_id', 'radio_field_name', $field_options );

		$value = 'option_five';
		$sanitized_value = $field->sanitize( $value );

		$this->assertFalse( $sanitized_value );
	}
}

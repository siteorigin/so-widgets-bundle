<?php

class SiteOrigin_Widget_Field_SelectTest extends WP_UnitTestCase {

	function setUp() {

	}


	/**
	 * @test
	 */
	public function render_outputs_correctly() {
		$field_options = array(
			'type' => 'select',
			'label' => 'A select field',
			'options' => array(
				'option_one' => 'Option One',
				'option_two' => 'Option Two',
				'option_three' => 'Option Three',
			)
		);

		$field = new SiteOrigin_Widget_Field_Select( 'select_field', 'select_field_id', 'select_field_name', $field_options );

		$actual = get_field_render_output( $field );
		$this->assertContains( '<select name="select_field_name" id="select_field_id"', $actual );
		$this->assertNotContains( 'multiple', $actual );
	}

	/**
	 * @test
	 */
	public function render_outputs_multiple_attribute() {
		$field_options = array(
			'type' => 'select',
			'label' => 'A select field',
			'multiple' => true,
			'options' => array(
				'option_one' => 'Option One',
				'option_two' => 'Option Two',
				'option_three' => 'Option Three',
			)
		);

		$field = new SiteOrigin_Widget_Field_Select( 'select_field', 'select_field_id', 'select_field_name', $field_options );

		$actual = get_field_render_output( $field );
		$this->assertContains( 'multiple', $actual );
	}

	/**
	 * @test
	 */
	public function render_outputs_prompt_option_when_present() {
		$prompt = 'Select something';
		$field_options = array(
			'type' => 'select',
			'label' => 'A select field',
			'prompt' => $prompt,
			'options' => array(
				'option_one' => 'Option One',
				'option_two' => 'Option Two',
				'option_three' => 'Option Three',
			)
		);

		$field = new SiteOrigin_Widget_Field_Select( 'select_field', 'select_field_id', 'select_field_name', $field_options );

		$actual = get_field_render_output( $field );

		$this->assertContains( '<option value="default" disabled="disabled" selected="selected">' . $prompt . '</option>', $actual );
	}

	/**
	 * @test
	 */
	public function sanitize_ensures_the_value_exists_in_options() {
		$field_options = array(
			'type' => 'select',
			'label' => 'A select input',
			'options' => array(
				'option_one' => 'Option One',
				'option_two' => 'Option Two',
				'option_three' => 'Option Three',
			)
		);

		$field = new SiteOrigin_Widget_Field_Select( 'select_field', 'select_field_id', 'select_field_name', $field_options );

		$value = 'option_one';
		$sanitized_value = $field->sanitize( $value );

		$this->assertEquals( $value, $sanitized_value );
	}

	/**
	 * @test
	 */
	public function sanitize_set_value_to_false_if_not_in_options() {
		$field_options = array(
			'type' => 'select',
			'label' => 'A select input',
			'options' => array(
				'option_one' => 'Option One',
				'option_two' => 'Option Two',
				'option_three' => 'Option Three',
			)
		);

		$field = new SiteOrigin_Widget_Field_Select( 'select_field', 'select_field_id', 'select_field_name', $field_options );

		$value = 'option_five';
		$sanitized_value = $field->sanitize( $value );

		$this->assertFalse( $sanitized_value );
	}

	/**
	 * @test
	 */
	public function sanitize_sanitizes_multiple_values_if_multiple_select() {
		$field_options = array(
			'type' => 'select',
			'label' => 'A select input',
			'multiple' => true,
			'options' => array(
				'option_one' => 'Option One',
				'option_two' => 'Option Two',
				'option_three' => 'Option Three',
			)
		);

		$field = new SiteOrigin_Widget_Field_Select( 'select_field', 'select_field_id', 'select_field_name', $field_options );

		$value = array( 'option_one', 'option_three' );
		$sanitized_value = $field->sanitize( $value );

		$this->assertEquals( 2, count( $sanitized_value ) );

	}
}

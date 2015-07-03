<?php

class SiteOrigin_Widget_Field_CheckboxTest extends WP_UnitTestCase {

	/**
	 * @test
	 */
	public function render_outputs_correctly() {

		$field_options = array(
			'type' => 'checkbox',
			'label' => 'A checkbox',
		);

		/* @var $field SiteOrigin_Widget_Field_Base */
		$field = new SiteOrigin_Widget_Field_Checkbox( 'a_checkbox', 'a_checkbox_id', 'a_checkbox_name', $field_options );

		$actual = get_field_render_output( $field );

		$this->assertContains( '<input type="checkbox" name="a_checkbox_name" id="a_checkbox_id"', $actual);
		$this->assertNotContains( 'checked=\'checked\'', $actual );
	}

	/**
	 * @test
	 */
	public function render_outputs_checked_correctly() {

		$field_options = array(
			'type' => 'checkbox',
			'label' => 'A checkbox',
		);

		/* @var $field SiteOrigin_Widget_Field_Base */
		$field = new SiteOrigin_Widget_Field_Checkbox( 'a_checkbox', 'a_checkbox_id', 'a_checkbox_name', $field_options );

		$actual = get_field_render_output( $field, true );

		$this->assertContains( 'checked=\'checked\'', $actual);
	}

	/**
	 * @test
	 */
	public function sanitize_converts_to_boolean() {

		$field_options = array(
			'type' => 'checkbox',
			'label' => 'A checkbox',
		);

		/* @var $field SiteOrigin_Widget_Field_Base */
		$field = new SiteOrigin_Widget_Field_Checkbox( 'a_checkbox', 'a_checkbox_id', 'a_checkbox_name', $field_options );
		$sanitized_value = $field->sanitize( 'something' );
		$this->assertTrue( $sanitized_value === true );
		$sanitized_value = $field->sanitize( '' );
		$this->assertTrue( $sanitized_value === false );
	}
}
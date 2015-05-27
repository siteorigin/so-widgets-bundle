<?php

class SiteOrigin_Widget_Field_FontTest extends WP_UnitTestCase {
	/**
	 * @tset
	 */
	public function render_outputs_correctly() {

		$field_options = array(
			'type' => 'font',
			'label' => 'A font input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Font */
		$field = new SiteOrigin_Widget_Field_Font( 'a_font_input', 'a_font_input_id', 'a_font_input_name', $field_options );

		$actual = get_field_render_output( $field );

		$this->assertContains( '<select name="a_font_input_name" id="a_font_input_id"', $actual);
		$this->assertContains( '<option value="default" selected="selected">Use theme font</option>', $actual);
	}

	/**
	 * @test
	 */
	public function sanitize_allows_values_without_spaces() {

		$field_options = array(
			'type' => 'font',
			'label' => 'A font input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Font */
		$field = new SiteOrigin_Widget_Field_Font( 'a_font_input', 'a_font_input_id', 'a_font_input_name', $field_options );

		$value = 'Georgia';
		$sanitized_value = $field->sanitize( $value );

		$this->assertEquals( $value, $sanitized_value );
	}

	/**
	 * @test
	 */
	public function sanitize_allows_values_with_spaces() {

		$field_options = array(
			'type' => 'font',
			'label' => 'A font input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Font */
		$field = new SiteOrigin_Widget_Field_Font( 'a_font_input', 'a_font_input_id', 'a_font_input_name', $field_options );

		$value = 'Helvetica Neue';
		$sanitized_value = $field->sanitize( $value );

		$this->assertEquals( $value, $sanitized_value );
	}

	/**
	 * @test
	 */
	public function sanitize_allows_values_with_font_size() {

		$field_options = array(
			'type' => 'font',
			'label' => 'A font input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Font */
		$field = new SiteOrigin_Widget_Field_Font( 'a_font_input', 'a_font_input_id', 'a_font_input_name', $field_options );

		$value = 'Advent Pro:200';
		$sanitized_value = $field->sanitize( $value );

		$this->assertEquals( $value, $sanitized_value );
	}

	/**
	 * @test
	 */
	public function sanitize_trims_whitespace() {

		$field_options = array(
			'type' => 'font',
			'label' => 'A font input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Font */
		$field = new SiteOrigin_Widget_Field_Font( 'a_font_input', 'a_font_input_id', 'a_font_input_name', $field_options );

		$value = '   Advent Pro:200   ';
		$sanitized_value = $field->sanitize( $value );

		$this->assertEquals( 'Advent Pro:200', $sanitized_value );
	}

	/**
	 * @test
	 */
	public function sanitize_extracts_valid_values() {

		$field_options = array(
			'type' => 'font',
			'label' => 'A font input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Font */
		$field = new SiteOrigin_Widget_Field_Font( 'a_font_input', 'a_font_input_id', 'a_font_input_name', $field_options );

		$value = '@#!@!Advent Pro:200';
		$sanitized_value = $field->sanitize( $value );

		$this->assertEquals( 'Advent Pro:200', $sanitized_value );
	}

	/**
	 * @test
	 */
	public function sanitize_changes_invalid_values_to_default() {

		$field_options = array(
			'type' => 'font',
			'label' => 'A font input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Font */
		$field = new SiteOrigin_Widget_Field_Font( 'a_font_input', 'a_font_input_id', 'a_font_input_name', $field_options );

		$value = '@#!@!';
		$sanitized_value = $field->sanitize( $value );

		$this->assertEquals( 'default', $sanitized_value );
	}

	/**
	 * @test
	 */
	public function sanitize_changes_values_not_in_font_collection_to_default() {

		$field_options = array(
			'type' => 'font',
			'label' => 'A font input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Font */
		$field = new SiteOrigin_Widget_Field_Font( 'a_font_input', 'a_font_input_id', 'a_font_input_name', $field_options );

		$value = 'Some Font:233';
		$sanitized_value = $field->sanitize( $value );

		$this->assertEquals( 'default', $sanitized_value );
	}
}

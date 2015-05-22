<?php

class SiteOrigin_Widget_Field_EditorTest extends WP_UnitTestCase {
	/**
	 * @test
	 */
	public function render_outputs_correctly() {

		$field_options = array(
			'type' => 'editor',
			'label' => 'An editor input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Editor */
		$field = new SiteOrigin_Widget_Field_Editor( 'an_editor_input', 'an_editor_input_id', 'an_editor_input_name', $field_options );

		$actual = get_field_render_output( $field );

		$this->assertContains( '<textarea type="text" name="an_editor_input_name" id="an_editor_input_id"', $actual);
	}

	/**
	 * @test
	 */
	public function render_outputs_additional_input_class() {

		$field_options = array(
			'type' => 'editor',
			'label' => 'An editor input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Editor */
		$field = new SiteOrigin_Widget_Field_Editor( 'an_editor_input', 'an_editor_input_id', 'an_editor_input_name', $field_options );

		$actual = get_field_render_output( $field );

		$this->assertContains( 'siteorigin-widget-input-editor', $actual);
	}

	/**
	 * @test
	 */
	public function render_outputs_rows_attribute() {

		$field_options = array(
			'type' => 'editor',
			'label' => 'An editor input',
			'rows' => 10
		);

		/* @var $base_field SiteOrigin_Widget_Field_Editor */
		$field = new SiteOrigin_Widget_Field_Editor( 'an_editor_input', 'an_editor_input_id', 'an_editor_input_name', $field_options );

		$actual = get_field_render_output( $field );

		$this->assertContains( 'rows="10"', $actual);
	}
}
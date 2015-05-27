<?php

class SiteOrigin_Widget_Field_EditorTest extends WP_UnitTestCase {

	private $field_output;

	function setUp() {

		$field_options = array(
			'type' => 'editor',
			'label' => 'A editor input',
			'placeholder' => 'Some placeholder text',
			'rows' => 10
		);

		/* @var $base_field SiteOrigin_Widget_Field_Editor */
		$field = new SiteOrigin_Widget_Field_Editor( 'an_editor_input', 'an_editor_input_id', 'an_editor_input_name', $field_options );

		$this->field_output = get_field_render_output( $field );
	}

	/**
	 * @test
	 */
	public function render_outputs_correctly() {

		$this->assertContains( '<textarea type="text" name="an_editor_input_name" id="an_editor_input_id"', $this->field_output);
	}

	/**
	 * @test
	 */
	public function render_outputs_additional_input_class() {

		$this->assertContains( 'siteorigin-widget-input-editor', $this->field_output);
	}

	/**
	 * @test
	 */
	public function render_outputs_placeholder_if_present() {

		$this->assertContains( 'placeholder="Some placeholder text"', $this->field_output);
	}

	/**
	 * @test
	 */
	public function render_outputs_rows_attribute() {

		$this->assertContains( 'rows="10"', $this->field_output);
	}
}
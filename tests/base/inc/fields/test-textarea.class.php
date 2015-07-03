<?php

class SiteOrigin_Widget_Field_TextareaTest extends WP_UnitTestCase {

	private $field_output;

	function setUp() {

		$field_options = array(
			'type' => 'textarea',
			'label' => 'A textarea input',
			'placeholder' => 'Some placeholder text',
			'rows' => 10
		);

		/* @var $base_field SiteOrigin_Widget_Field_Textarea */
		$field = new SiteOrigin_Widget_Field_Textarea( 'textarea_input', 'textarea_input_id', 'textarea_input_name', $field_options );

		$this->field_output = get_field_render_output( $field );
	}

	/**
	 * @test
	 */
	public function render_outputs_correctly() {

		$this->assertContains( '<textarea type="text" name="textarea_input_name" id="textarea_input_id"', $this->field_output);
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
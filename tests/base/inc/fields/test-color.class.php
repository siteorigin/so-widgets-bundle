<?php

class SiteOrigin_Widget_Field_ColorTest extends WP_UnitTestCase {

	/**
	 * @test
	 */
	public function render_outputs_correctly() {

		$field_options = array(
			'type' => 'color',
			'label' => 'A color input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Color */
		$field = new SiteOrigin_Widget_Field_Color( 'a_color_input', 'a_color_input_id', 'a_color_input_name', $field_options );

		$actual = get_field_render_output( $field );

		$this->assertContains( '<input type="text" name="a_color_input_name" id="a_color_input_id"', $actual);
	}

	/**
	 * @test
	 */
	public function render_outputs_additional_input_class() {

		$field_options = array(
			'type' => 'color',
			'label' => 'A color input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Color */
		$field = new SiteOrigin_Widget_Field_Color( 'a_color_input', 'a_color_input_id', 'a_color_input_name', $field_options );

		$actual = get_field_render_output( $field );

		$this->assertContains( 'siteorigin-widget-input-color', $actual);
	}
}
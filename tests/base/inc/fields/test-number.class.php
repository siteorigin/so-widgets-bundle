<?php

class SiteOrigin_Widget_Field_NumberTest extends WP_UnitTestCase {
	/**
	 * @test
	 */
	public function render_outputs_correctly() {
		$field_options = array(
			'type' => 'number',
			'label' => 'A number field'
		);

		$field = new SiteOrigin_Widget_Field_Number( 'number_field', 'number_field_id', 'number_field_name', $field_options );

		$actual = get_field_render_output( $field );

		$this->assertContains( 'siteorigin-widget-input-number', $actual );
	}

	/**
	 * @test
	 */
	public function sanitize_converts_to_float() {
		$field_options = array(
			'type' => 'number',
			'label' => 'A number field'
		);

		$field = new SiteOrigin_Widget_Field_Number( 'number_field', 'number_field_id', 'number_field_name', $field_options );

		$value = 9.23;
		$sanitized_value = $field->sanitize( $value );
		$this->assertEquals( $value, $sanitized_value );


		$value = 'not a float';
		$sanitized_value = $field->sanitize( $value );
		$this->assertEquals( 0, $sanitized_value );
	}
}

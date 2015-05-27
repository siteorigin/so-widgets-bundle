<?php

class SiteOrigin_Widget_Field_IconTest extends WP_UnitTestCase {
	/**
	 * @test
	 */
	public function render_outputs_correctly() {

		$field_options = array(
			'type' => 'icon',
			'label' => 'A icon input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Icon */
		$field = new SiteOrigin_Widget_Field_Icon( 'icon_input', 'icon_input_id', 'icon_input_name', $field_options );

		$actual = get_field_render_output( $field );

		$this->assertContains( '<select class="siteorigin-widget-icon-family" >', $actual);
		$this->assertContains( '<input type="hidden" name="icon_input_name"', $actual);
	}

	/**
	 * @test
	 */
	public function sanitize_allows_alphanumeric_characters_and_hyphens() {

		$field_options = array(
			'type' => 'icon',
			'label' => 'A font input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Icon */
		$field = new SiteOrigin_Widget_Field_Icon( 'icon_input', 'icon_input_id', 'icon_input_name', $field_options );

		$value = 'icomoon-thumbs-up2';
		$sanitized_value = $field->sanitize( $value );

		$this->assertEquals( $value, $sanitized_value );

	}

	/**
	 * @test
	 */
	public function sanitize_changes_values_not_in_icon_collection_to_empty_string() {

		$field_options = array(
			'type' => 'icon',
			'label' => 'A icon input',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Icon */
		$field = new SiteOrigin_Widget_Field_Icon( 'icon_input', 'icon_input_id', 'icon_input_name', $field_options );

		$value = 'an-icon-123';
		$sanitized_value = $field->sanitize( $value );

		$this->assertEquals( '', $sanitized_value );
	}
}

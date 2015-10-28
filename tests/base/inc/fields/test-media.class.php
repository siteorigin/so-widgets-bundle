<?php

class SiteOrigin_Widget_Field_MediaTest extends WP_UnitTestCase {
	/**
	 * @test
	 */
	public function render_outputs_correctly() {

		$field_options = array(
			'type' => 'media',
			'label' => 'A media field'
		);

		$stub_widget = $this->getMockBuilder( 'SiteOrigin_Widget' )
                            ->disableOriginalConstructor()
                            ->getMockForAbstractClass();

		$field = new SiteOrigin_Widget_Field_Media( 'media_field_test', 'media_field_id', 'media_field_name', $field_options, $stub_widget );

		$actual = get_field_render_output( $field );

		$this->assertContains( '<div class="media-field-wrapper">', $actual );
	}
	/**
	 * @test
	 */
	public function render_outputs_fallback() {

		$field_options = array(
			'type' => 'media',
			'label' => 'A media field',
			'fallback' => true
		);

		/* @var $stub_widget SiteOrigin_Widget */
		$stub_widget = $this->getMockBuilder( 'SiteOrigin_Widget' )
		                    ->disableOriginalConstructor()
		                    ->getMockForAbstractClass();

		$field = new SiteOrigin_Widget_Field_Media( 'media_field_test', 'media_field_id', 'media_field_name', $field_options, $stub_widget );

		$actual = get_field_render_output( $field );

		$this->assertContains( 'name="widget-[][media_field_test_fallback]"', $actual );
	}

	/**
	 * @test
	 */
	public function sanitize_converts_to_integer() {

		$field_options = array(
			'type' => 'media',
			'label' => 'A media input',
		);

		/* @var $stub_widget SiteOrigin_Widget */
		$stub_widget = $this->getMockBuilder( 'SiteOrigin_Widget' )
		                    ->disableOriginalConstructor()
		                    ->getMockForAbstractClass();

		/* @var $base_field SiteOrigin_Widget_Field_Media */
		$field = new SiteOrigin_Widget_Field_Media( 'media_field_test', 'media_field_id', 'media_field_name', $field_options, $stub_widget );

		$value = '123';
		$sanitized_value = $field->sanitize( $value );

		$this->assertEquals( '123', $sanitized_value );

		$value = 'apples';
		$sanitized_value = $field->sanitize( $value );

		$this->assertEquals( 0, $sanitized_value );
	}

	/**
	 * @test
	 */
	public function sanitize_instance_sanitizes_fallback_if_present() {

		$field_options = array(
			'type' => 'media',
			'label' => 'A media input',
			'fallback' => true
		);

		/* @var $stub_widget SiteOrigin_Widget */
		$stub_widget = $this->getMockBuilder( 'SiteOrigin_Widget' )
		                    ->disableOriginalConstructor()
		                    ->getMockForAbstractClass();

		/* @var $base_field SiteOrigin_Widget_Field_Media */
		$field = new SiteOrigin_Widget_Field_Media( 'media_field_test', 'media_field_id', 'media_field_name', $field_options, $stub_widget );

		$fallback_field_name = $field->get_fallback_field_name( 'media_field_test' );
		$value = array( $fallback_field_name => 'http://www.exam[ple.o]rg' );
		$sanitized_value = $field->sanitize_instance( $value );

		$this->assertEquals( 'http://www.exam[ple.o]rg' , $sanitized_value[$fallback_field_name] );
	}
}

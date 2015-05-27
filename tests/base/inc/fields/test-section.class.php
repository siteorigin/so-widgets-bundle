<?php

class SiteOrigin_Widget_Field_SectionTest extends WP_UnitTestCase {

	/**
	 * @test
	 */
	public function render_outputs_correctly() {
		$field_options = array(
			'type' => 'section',
			'label' => 'A section field',
			'fields' => array(
				'field_one' => array(
					'type' => 'text',
				)
			)
		);

		/* @var $stub_widget SiteOrigin_Widget */
		$stub_widget = $this->getMockBuilder( 'SiteOrigin_Widget' )
		                          ->disableOriginalConstructor()
		                          ->getMockForAbstractClass();

		$field = new SiteOrigin_Widget_Field_Section( '', '', '', $field_options, $stub_widget );

		$actual = get_field_render_output( $field );

		$this->assertContains( '<div class="siteorigin-widget-section', $actual );
	}

	/**
	 * @test
	 */
	public function render_outputs_hide_class_when_required() {
		$field_options = array(
			'type' => 'section',
			'label' => 'A section field',
			'fields' => array(
				'field_one' => array(
					'type' => 'text',
				)
			),
			'hide' => true
		);

		/* @var $stub_widget SiteOrigin_Widget */
		$stub_widget = $this->getMockBuilder( 'SiteOrigin_Widget' )
		                          ->disableOriginalConstructor()
		                          ->getMockForAbstractClass();

		$field = new SiteOrigin_Widget_Field_Section( '', '', '', $field_options, $stub_widget );

		$actual = get_field_render_output( $field );

		$this->assertContains( 'siteorigin-widget-section-hide', $actual );
	}
}

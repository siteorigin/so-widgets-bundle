<?php

class SiteOrigin_Widget_Field_Container_BaseTest extends WP_UnitTestCase {

	/**
	 * @var SiteOrigin_Widget
	 */
	private $stub_widget;

	public function setUp() {
		$this->stub_widget = $this->getMockBuilder( 'SiteOrigin_Widget' )
		                          ->disableOriginalConstructor()
		                          ->getMockForAbstractClass();
	}

	/**
	 * @test
	 */
	public function render_outputs_additional_label_class() {
		$field_options = array(
			'type' => 'meh',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Container_Base */
		$base_field = $this->getMockBuilder( 'SiteOrigin_Widget_Field_Container_Base' )
		                   ->setConstructorArgs( array( 'test_base', '', '', $field_options, $this->stub_widget ) )
		                   ->getMockForAbstractClass();

		$class = 'siteorigin-widget-section-visible';
		$html = get_field_render_output( $base_field );
		$this->assertContains( $class, $html );
	}
}
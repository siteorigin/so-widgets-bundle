<?php

class SiteOrigin_Widget_Field_WidgetTest extends WP_UnitTestCase {
	/**
	 * @test
	 */
	public function render_outputs_correctly() {
		$field_options = array(
			'type' => 'widget',
			'class' => 'SiteOrigin_Widget_Button_Widget'
		);

		/* @var $stub_widget SiteOrigin_Widget*/
		$stub_widget = $this->getMockBuilder( 'SiteOrigin_Widget' )
                            ->disableOriginalConstructor()
                            ->getMockForAbstractClass();

		$field = new SiteOrigin_Widget_Field_Widget( 'widget_field', 'widget_field_id', 'widget_field_name', $field_options, $stub_widget );

		$actual = get_field_render_output( $field );
		$this->assertContains( '<div class="siteorigin-widget-section', $actual );
	}
}

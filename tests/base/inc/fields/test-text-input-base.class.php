<?php

class SiteOrigin_Widget_Field_Text_Input_BaseTest extends WP_UnitTestCase {

	/**
	 * @test
	 */
	public function render_outputs_input_classes() {

		$field_options = array(
			'type' => 'text',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Text_Input_Base */
		$base_field = $this->getMockBuilder( 'SiteOrigin_Widget_Field_Text_Input_Base' )
		                   ->setConstructorArgs( array( 'test_base', '', '', $field_options ) )
		                   ->getMockForAbstractClass();

		$classes = 'widefat siteorigin-widget-input';
		$html = get_field_render_output( $base_field );
		$this->assertContains( $classes, $html );
	}

	/**
	 * @test
	 */
	public function render_does_not_output_placeholder_or_readonly_if_not_specified() {

		$field_options = array(
			'type' => 'text',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Text_Input_Base */
		$base_field = $this->getMockBuilder( 'SiteOrigin_Widget_Field_Text_Input_Base' )
		                   ->setConstructorArgs( array( 'test_base', '', '', $field_options ) )
		                   ->getMockForAbstractClass();

		$html = get_field_render_output( $base_field );
		$this->assertNotContains( 'placeholder=', $html );
		$this->assertNotContains( 'readonly', $html );
	}

	/**
	 * @test
	 */
	public function render_outputs_placeholder() {
		$placeholder = 'Some placeholder text';
		$field_options = array(
			'type' => 'text',
			'placeholder' => $placeholder,
		);

		/* @var $base_field SiteOrigin_Widget_Field_Text_Input_Base */
		$base_field = $this->getMockBuilder( 'SiteOrigin_Widget_Field_Text_Input_Base' )
		                   ->setConstructorArgs( array( 'test_base', '', '', $field_options ) )
		                   ->getMockForAbstractClass();

		$html = get_field_render_output( $base_field );
		$this->assertContains( 'placeholder="' . $placeholder . '"', $html );
	}

	/**
	 * @test
	 */
	public function render_outputs_readonly() {
		$field_options = array(
			'type' => 'text',
			'readonly' => true,
		);

		/* @var $base_field SiteOrigin_Widget_Field_Text_Input_Base */
		$base_field = $this->getMockBuilder( 'SiteOrigin_Widget_Field_Text_Input_Base' )
		                   ->setConstructorArgs( array( 'test_base', '', '', $field_options ) )
		                   ->getMockForAbstractClass();

		$html = get_field_render_output( $base_field );
		$this->assertContains( 'readonly', $html );
	}
}
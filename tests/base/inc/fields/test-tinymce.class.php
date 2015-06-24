<?php

class SiteOrigin_Widget_Field_TinyMCETest extends WP_UnitTestCase {
	function setUp() {
		parent::setUp();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
	}


	/**
	 * @test
	 */
	public function render_outputs_correctly() {

		$field_options = array(
			'type' => 'tinymce',
			'label' => 'A TinyMCE input',
			'default_editor' => 'tinymce',
			'rows' => 12,
		);

		/* @var $base_field SiteOrigin_Widget_Field_Textarea */
		$field = new SiteOrigin_Widget_Field_TinyMCE( 'tinymce_input', 'tinymce_input_id', 'tinymce_input_name', $field_options );

		$field_output = get_field_render_output( $field );

		$this->assertContains( '<div class="siteorigin-widget-tinymce-container"', $field_output);
		$this->assertContains( '<div id="wp-tinymce_input_id-wrap"', $field_output);
	}

	/**
	 * @test
	 */
	public function render_outputs_rows_attribute() {

		$field_options = array(
			'type' => 'tinymce',
			'label' => 'A TinyMCE input',
			'default_editor' => 'tinymce',
			'rows' => 12,
		);

		/* @var $base_field SiteOrigin_Widget_Field_Textarea */
		$field = new SiteOrigin_Widget_Field_TinyMCE( 'tinymce_input', 'tinymce_input_id', 'tinymce_input_name', $field_options );

		$field_output = get_field_render_output( $field );

		$this->assertContains( 'rows="12"', $field_output);
	}

	/**
	 * @test
	 */
	public function render_ignores_rows_if_editor_height_specified() {

		$field_options = array(
			'type' => 'tinymce',
			'label' => 'A TinyMCE input',
			'default_editor' => 'tinymce',
			'rows' => 12,
			'editor_height' => 250,
		);

		/* @var $base_field SiteOrigin_Widget_Field_Textarea */
		$field = new SiteOrigin_Widget_Field_TinyMCE( 'tinymce_input', 'tinymce_input_id', 'tinymce_input_name', $field_options );

		$field_output = get_field_render_output( $field );

		$this->assertContains( 'height: 250px', $field_output);
		$this->assertNotContains( 'rows="12"', $field_output);
	}

	/**
	 * @test
	 */
	public function render_set_correct_default_editor() {

		$field_options = array(
			'type' => 'tinymce',
			'label' => 'A TinyMCE input',
			'default_editor' => 'tmce',
		);

		/* @var $base_field SiteOrigin_Widget_Field_Textarea */
		$field = new SiteOrigin_Widget_Field_TinyMCE( 'tinymce_input', 'tinymce_input_id', 'tinymce_input_name', $field_options );

		$field_output = get_field_render_output( $field );

		$this->markTestIncomplete('user_can_richedit returns nothing in testing which causes incorrect default editor to be set.');
		$this->assertTrue( user_can_richedit() );
		$this->assertContains( 'tmce-active', $field_output);
	}
}
<?php

class SiteOrigin_Widget_Field_LinkTest extends WP_UnitTestCase {
	/**
	 * @test
	 */
	public function render_outputs_correctly() {
		$field_options = array(
			'type' => 'link',
			'label' => 'A link field'
		);

		$field = new SiteOrigin_Widget_Field_Link( 'link_field', 'link_field_id', 'link_field_name', $field_options );

		$actual = get_field_render_output( $field );

		$this->assertContains( '<input type="text" name="link_field_name" id="link_field_id"', $actual);
	}
	/**
	 * @test
	 */
	public function render_outputs_content_selector_correctly() {
		$field_options = array(
			'type' => 'link',
			'label' => 'A link field'
		);

		$field = new SiteOrigin_Widget_Field_Link( 'link_field', 'link_field_id', 'link_field_name', $field_options );

		$actual = get_field_render_output( $field );

		$this->assertContains( '<a href="#" class="select-content-button button-secondary">Select Content</a>', $actual);
	}

	/**
	 * @test
	 */
	public function sanitize_trims_whitespace() {
		$field_options = array(
			'type' => 'link',
			'label' => 'A link field'
		);

		$field = new SiteOrigin_Widget_Field_Link( 'link_field', 'link_field_id', 'link_field_name', $field_options );

		$value = '  http://www.example.org/things       ';
		$sanitized_value = $field->sanitize( $value );
		$this->assertEquals( 'http://www.example.org/things', $sanitized_value );
	}

	/**
	 * @test
	 */
	public function sanitize_allows_post_ids() {

		$post_id = $this->factory->post->create();

		$field_options = array(
			'type' => 'link',
			'label' => 'A link field'
		);

		$field = new SiteOrigin_Widget_Field_Link( 'link_field', 'link_field_id', 'link_field_name', $field_options );

		$value = 'post: '.$post_id;
		$sanitized_value = $field->sanitize( $value );
		$this->assertEquals( $value, $sanitized_value );
	}

	/**
	 * @test
	 */
	public function sanitize_escapes_normal_urls() {
		$field_options = array(
			'type' => 'link',
			'label' => 'A link field'
		);

		$field = new SiteOrigin_Widget_Field_Link( 'link_field', 'link_field_id', 'link_field_name', $field_options );

		$value = 'http://www.example.org/t[hin]gs';
		$sanitized_value = $field->sanitize( $value );
		$this->assertEquals( 'http://www.example.org/t%5Bhin%5Dgs', $sanitized_value );
	}
}

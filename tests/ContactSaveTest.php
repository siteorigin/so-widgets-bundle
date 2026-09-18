<?php

use SiteOrigin\Tests\SiteOriginTests;

if ( ! class_exists( 'SiteOrigin_Widgets_ContactForm_Widget' ) ) {
	require __DIR__ . '/../widgets/contact/contact.php';
}

/**
 * The Contact Form widget through the real SiteOrigin_Widget::update(): a
 * design section absent from the submission, a stored instance whose
 * design.success is an empty string (the shape taken from a real site), a
 * stored instance whose settings section is an empty string, and a re-save
 * of already sanitised data.
 */
class ContactSaveTest extends SiteOriginTests {
	protected function setUp(): void {
		parent::setUp();

		// default_from_address() reads the server name.
		$_SERVER['SERVER_NAME'] = 'localhost';
	}

	private function widget() {
		return new SiteOrigin_Widgets_ContactForm_Widget();
	}

	private static function stored_instance() {
		return json_decode( file_get_contents( __DIR__ . '/fixtures/instances/contact-empty-success.json' ), true );
	}

	private function success_keys() {
		$form = $this->widget()->form_options();

		return array_keys( $form['design']['fields']['success']['fields'] );
	}

	private function update_silently( $new, $old ) {
		list( $stored, $errors ) = $this->run_capturing_errors(
			fn() => $this->widget()->update( $new, $old )
		);

		$this->assertSame( array(), $errors );

		return $stored;
	}

	public function test_a_submission_without_the_success_section_stores_it_keyed() {
		$new = self::stored_instance();
		unset( $new['design']['success'] );

		$stored = $this->update_silently( $new, self::stored_instance() );

		$this->assertIsArray( $stored['design']['success'] );
		$this->assertSame( $this->success_keys(), array_keys( $stored['design']['success'] ) );
	}

	public function test_a_stored_empty_success_section_saves_as_an_array() {
		$instance = self::stored_instance();
		$this->assertSame( '', $instance['design']['success'] );

		$stored = $this->update_silently( $instance, $instance );

		$this->assertIsArray( $stored['design']['success'] );
		$this->assertSame( $this->success_keys(), array_keys( $stored['design']['success'] ) );
	}

	public function test_a_second_save_of_sanitised_output_is_byte_identical() {
		$first = $this->update_silently( self::stored_instance(), self::stored_instance() );
		unset( $first['_sow_form_timestamp'] );

		$second = $this->update_silently( $first, $first );
		unset( $second['_sow_form_timestamp'] );

		$this->assertSame( $first, $second );
	}

	/**
	 * modify_instance() writes the default recipient and sender into the
	 * settings section before anything else runs; a settings section stored
	 * as an empty string used to make that first write a fatal.
	 */
	public function test_a_stored_empty_settings_section_saves_as_an_array() {
		$instance = self::stored_instance();
		$instance['settings'] = '';

		$stored = $this->update_silently( $instance, $instance );

		$this->assertIsArray( $stored['settings'] );
		$this->assertSame( 'wordpress@localhost', $stored['settings']['from'] );
		$this->assertArrayHasKey( 'to', $stored['settings'] );
	}

	/**
	 * The instance the save path stores for this fixture, and so the style
	 * hash it generates CSS under, is what it was before container fields
	 * stored arrays: tests/fixtures/update-outputs.json holds that earlier
	 * output, and the hash of the two must agree.
	 */
	public function test_saving_a_stored_design_keeps_the_style_hash_it_had_before() {
		$baseline = json_decode( file_get_contents( __DIR__ . '/fixtures/update-outputs.json' ), true );
		$before   = $this->widget()->get_style_hash( $baseline['contact-empty-success'] );

		$stored = $this->update_silently( self::stored_instance(), self::stored_instance() );

		$this->assertSame( $before, $this->widget()->get_style_hash( $stored ) );
	}
}

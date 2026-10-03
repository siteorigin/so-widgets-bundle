<?php

use Brain\Monkey\Functions;
use SiteOrigin\Tests\SiteOriginTests;

if ( ! class_exists( 'SiteOrigin_Widgets_ContactForm_Widget' ) ) {
	require __DIR__ . '/../widgets/contact/contact.php';
}

// The plugin autoloads field classes; load the ones these fixtures render.
foreach ( array( 'base', 'name', 'text', 'checkboxes', 'radio', 'select' ) as $contact_field_type ) {
	require_once __DIR__ . '/../widgets/contact/fields/' . $contact_field_type . '.class.php';
}

/**
 * A required Checkboxes, Radio or multiple Select field left empty submits no
 * key, so validation reports it under the bare label while the form renders
 * the field under its numbered name. Each such field must still show its own
 * missing message when the form is rendered again.
 */
class ContactRequiredChoiceFieldTest extends SiteOriginTests {
	protected function setUp(): void {
		parent::setUp();

		// default_from_address() reads the server name.
		$_SERVER['SERVER_NAME'] = 'localhost';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'stripslashes_deep' )->returnArg();
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( '_x' )->returnArg();
		Functions\when( 'wp_mail' )->justReturn( true );

		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'sanitize_title' )->returnArg();
		Functions\when( 'sanitize_html_class' )->returnArg();
		Functions\when( 'siteorigin_sanitize_attribute_key' )->returnArg();
		Functions\when( 'checked' )->justReturn( '' );
		Functions\when( 'selected' )->justReturn( '' );
		Functions\when( 'esc_textarea' )->returnArg();
		Functions\when( 'wp_enqueue_script' )->justReturn( null );
		Functions\when( 'wp_enqueue_style' )->justReturn( null );

		// name_from_label() builds render names against a global register; reset
		// it so every test renders its fields as `-1`.
		$GLOBALS['field_ids'] = array();
	}

	protected function tearDown(): void {
		$_POST = array();
		unset( $GLOBALS['field_ids'] );

		parent::tearDown();
	}

	private static function required( $message ) {
		return array(
			'required'        => true,
			'missing_message' => $message,
		);
	}

	private static function options( ...$values ) {
		return array_map(
			function ( $value ) {
				return array( 'value' => $value );
			},
			$values
		);
	}

	private static function choice_fields() {
		return array(
			array(
				'type'     => 'name',
				'label'    => 'Your Name',
				'required' => self::required( 'NAME MISSING' ),
			),
			array(
				'type'     => 'checkboxes',
				'label'    => 'Promo Methods',
				'options'  => self::options( 'Blog', 'Social' ),
				'required' => self::required( 'CHECKBOXES MISSING' ),
			),
			array(
				'type'     => 'radio',
				'label'    => 'Pick One',
				'options'  => self::options( 'A', 'B' ),
				'required' => self::required( '' ),
			),
			array(
				'type'            => 'select',
				'label'           => 'Channels',
				'multiple_select' => true,
				'options'         => self::options( 'Email', 'Phone' ),
				'required'        => self::required( 'SELECT MISSING' ),
			),
			array(
				'type'     => 'checkboxes',
				'label'    => 'Extras',
				'options'  => self::options( 'X' ),
				'required' => array( 'required' => false ),
			),
		);
	}

	private static function instance( $fields ) {
		return array(
			'form_id'  => 7,
			'fields'   => $fields,
			'settings' => array(
				'to'                       => 'owner@example.com',
				'from'                     => 'site@example.com',
				'default_subject'          => 'Enquiry',
				'subject_prefix'           => '',
				'log_ip_address'           => false,
				'required_field_indicator' => false,
			),
			'spam'     => array(
				'recaptcha' => array(),
				'akismet'   => array( 'use_akismet' => false ),
			),
		);
	}

	/**
	 * Validates a submission with the real contact_form_action(), then renders
	 * the form with its result, as the widget template does.
	 *
	 * @return array The validation result and one chunk of rendered HTML per field.
	 */
	private function submit_and_render( $instance, $post ) {
		$_POST = array_merge(
			array(
				'_wpnonce'        => 'nonce',
				// A unique hash per submission so the widget's per-request
				// send cache never returns an earlier test's result.
				'instance_hash-7' => uniqid( 'hash', true ),
			),
			$post
		);

		$widget = new SiteOrigin_Widgets_ContactForm_Widget();
		$result = $widget->contact_form_action( $instance );

		ob_start();
		$widget->render_form_fields( $instance['fields'], $result, $instance );
		$html = ob_get_clean();

		$chunks = array_slice( explode( '<div class="sow-form-field ', $html ), 1 );
		$this->assertCount( count( $instance['fields'] ), $chunks );

		return array( $result, $chunks );
	}

	public function test_empty_required_choice_fields_show_their_own_message() {
		list( $result, $chunks ) = $this->submit_and_render(
			self::instance( self::choice_fields() ),
			array( 'your-name-1' => 'Ada' )
		);

		$this->assertSame( 'CHECKBOXES MISSING', $result['errors']['promo-methods'] );
		$this->assertSame( 'Required field', $result['errors']['pick-one'] );
		$this->assertSame( 'SELECT MISSING', $result['errors']['channels'] );

		// The real field renderers ran, not the text input fallback.
		$this->assertStringContainsString( 'type="checkbox"', $chunks[1] );
		$this->assertStringContainsString( 'type="radio"', $chunks[2] );
		$this->assertStringContainsString( 'multiple', $chunks[3] );

		$expected = array(
			1 => 'CHECKBOXES MISSING',
			2 => 'Required field',
			3 => 'SELECT MISSING',
		);

		foreach ( $chunks as $index => $chunk ) {
			if ( isset( $expected[ $index ] ) ) {
				$this->assertStringContainsString( 'sow-error', $chunk );
				$this->assertStringContainsString( $expected[ $index ], $chunk );
			} else {
				$this->assertStringNotContainsString( 'sow-error', $chunk );
			}

			foreach ( $expected as $other_index => $message ) {
				if ( $other_index !== $index ) {
					$this->assertStringNotContainsString( $message, $chunk );
				}
			}
		}
	}

	public function test_an_empty_required_text_field_still_shows_its_message() {
		list( , $chunks ) = $this->submit_and_render(
			self::instance( self::choice_fields() ),
			array( 'your-name-1' => '' )
		);

		$this->assertStringContainsString( 'NAME MISSING', $chunks[0] );
	}

	public function test_answered_choice_fields_show_no_message() {
		list( $result, $chunks ) = $this->submit_and_render(
			self::instance( self::choice_fields() ),
			array(
				'your-name-1'     => 'Ada',
				'promo-methods-1' => array( 'Blog' ),
				'pick-one-1'      => 'A',
				'channels-1'      => array( 'Email', 'Phone' ),
			)
		);

		$errors = ! empty( $result['errors'] ) ? $result['errors'] : array();
		$this->assertArrayNotHasKey( 'promo-methods', $errors );
		$this->assertArrayNotHasKey( 'pick-one', $errors );
		$this->assertArrayNotHasKey( 'channels', $errors );

		foreach ( array( 1, 2, 3 ) as $index ) {
			$this->assertStringNotContainsString( 'sow-error', $chunks[ $index ] );
		}
	}

	public function test_a_text_field_without_a_submitted_key_shows_no_bare_label_message() {
		list( $result, $chunks ) = $this->submit_and_render(
			self::instance(
				array(
					array(
						'type'     => 'text',
						'label'    => 'Notes',
						'required' => self::required( 'NOTES MISSING' ),
					),
				)
			),
			array()
		);

		$this->assertSame( 'NOTES MISSING', $result['errors']['notes'] );
		$this->assertStringNotContainsString( 'sow-error', $chunks[0] );
	}

	public function test_a_single_select_without_a_submitted_key_shows_no_bare_label_message() {
		list( $result, $chunks ) = $this->submit_and_render(
			self::instance(
				array(
					array(
						'type'     => 'select',
						'label'    => 'Size',
						'options'  => self::options( 'S', 'M' ),
						'required' => self::required( 'SIZE MISSING' ),
					),
				)
			),
			array()
		);

		$this->assertSame( 'SIZE MISSING', $result['errors']['size'] );
		$this->assertStringNotContainsString( 'sow-error', $chunks[0] );
	}

	public function test_the_numbered_name_error_wins_over_the_bare_label_error() {
		$fields = self::choice_fields();

		ob_start();
		( new SiteOrigin_Widgets_ContactForm_Widget() )->render_form_field(
			$fields[1],
			array(
				'promo-methods-1' => 'NUMBERED',
				'promo-methods'   => 'BARE',
			),
			'above',
			self::instance( $fields ),
			false,
			1
		);
		$html = ob_get_clean();

		$this->assertStringContainsString( 'NUMBERED', $html );
		$this->assertStringNotContainsString( 'BARE', $html );
	}

	public function test_an_unlabelled_empty_choice_field_shows_its_message() {
		list( $result, $chunks ) = $this->submit_and_render(
			self::instance(
				array(
					array(
						'type'     => 'name',
						'label'    => 'Your Name',
						'required' => self::required( 'NAME MISSING' ),
					),
					array(
						'type'     => 'checkboxes',
						'label'    => '',
						'options'  => self::options( 'Yes' ),
						'required' => self::required( 'UNLABELLED MISSING' ),
					),
				)
			),
			array( 'your-name-1' => 'Ada' )
		);

		$this->assertSame( 'UNLABELLED MISSING', $result['errors']['1'] );
		$this->assertStringContainsString( 'UNLABELLED MISSING', $chunks[1] );
	}
}

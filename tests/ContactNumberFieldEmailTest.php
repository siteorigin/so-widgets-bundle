<?php

use Brain\Monkey\Functions;
use SiteOrigin\Tests\SiteOriginTests;

if ( ! class_exists( 'SiteOrigin_Widgets_ContactForm_Widget' ) ) {
	require __DIR__ . '/../widgets/contact/contact.php';
}

/**
 * A Number field submitted through the Contact Form widget's real
 * contact_form_action() must reach the email body wp_mail() is given,
 * listed under its own label like every other message field.
 */
class ContactNumberFieldEmailTest extends SiteOriginTests {
	private $sent = array();

	protected function setUp(): void {
		parent::setUp();

		// default_from_address() reads the server name.
		$_SERVER['SERVER_NAME'] = 'localhost';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'stripslashes_deep' )->returnArg();
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( '_x' )->returnArg();

		$this->sent = array();
		Functions\when( 'wp_mail' )->alias(
			function ( $to, $subject, $body, $headers ) {
				$this->sent[] = compact( 'to', 'subject', 'body', 'headers' );

				return true;
			}
		);
	}

	protected function tearDown(): void {
		$_POST = array();

		parent::tearDown();
	}

	private static function field( $type, $label ) {
		return array(
			'type'     => $type,
			'label'    => $label,
			'required' => array( 'required' => false ),
		);
	}

	private static function instance( $fields ) {
		return array(
			'form_id'  => 7,
			'fields'   => $fields,
			'settings' => array(
				'to'              => 'owner@example.com',
				'from'            => 'site@example.com',
				'default_subject' => 'Enquiry',
				'subject_prefix'  => '',
				'log_ip_address'  => false,
			),
			'spam'     => array(
				'recaptcha' => array(),
				'akismet'   => array( 'use_akismet' => false ),
			),
		);
	}

	private function submit( $instance, $post ) {
		$_POST = array_merge(
			array(
				'_wpnonce'        => 'nonce',
				// A unique hash per submission so the widget's per-request
				// send cache never returns an earlier test's result.
				'instance_hash-7' => uniqid( 'hash', true ),
			),
			$post
		);

		return ( new SiteOrigin_Widgets_ContactForm_Widget() )->contact_form_action( $instance );
	}

	public function test_number_field_values_reach_the_email_body_under_their_labels() {
		$result = $this->submit(
			self::instance(
				array(
					self::field( 'name', 'Your Name' ),
					self::field( 'email', 'Your Email' ),
					self::field( 'number', 'Guests' ),
					self::field( 'number', 'Nights' ),
				)
			),
			array(
				'your-name'  => 'Ada',
				'your-email' => 'ada@example.com',
				'guests'     => '42',
				'nights'     => '3',
			)
		);

		$this->assertSame( 'success', $result['status'] );
		$this->assertCount( 1, $this->sent );

		$body = $this->sent[0]['body'];
		$this->assertStringContainsString( "<strong>Guests:</strong>\n42", $body );
		$this->assertStringContainsString( "<strong>Nights:</strong>\n3", $body );
	}

	public function test_a_non_numeric_number_value_is_rejected_and_not_sent() {
		$result = $this->submit(
			self::instance(
				array(
					self::field( 'email', 'Your Email' ),
					self::field( 'number', 'Guests' ),
				)
			),
			array(
				'your-email' => 'ada@example.com',
				'guests'     => 'many',
			)
		);

		$this->assertSame( 'fail', $result['status'] );
		$this->assertSame( 'Invalid number.', $result['errors']['guests'] );
		$this->assertCount( 0, $this->sent );
	}
}

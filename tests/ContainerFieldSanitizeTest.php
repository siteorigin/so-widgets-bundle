<?php

use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

require_once __DIR__ . '/fixtures/TestWidget.php';

/**
 * A container field (section, toggle, widget, repeater) that is absent from
 * the submitted instance, or submitted as '' or null, is stored as an array:
 * the same shape a submission with nothing filled in produces. Scalar fields
 * keep storing '' for an empty value, and the posts field, a container that
 * stores a query string, keeps storing ''.
 */
class ContainerFieldSanitizeTest extends SiteOriginTests {
	private static $sanitize_args = array();

	protected function setUp(): void {
		parent::setUp();

		self::$sanitize_args = array();
		SiteOrigin_Test_Widget::$modify_instance_callback = null;
	}

	private function widget() {
		return new SiteOrigin_Test_Widget();
	}

	/**
	 * A complete instance, so update() has an old instance whose raw read in
	 * get_less_variables() succeeds; what is under test is the new instance.
	 */
	private function complete_instance() {
		return array(
			'title' => 'Hello',
			'design' => array(
				'text' => 'body',
				'box' => array( 'color' => '#123456' ),
			),
			'shadow' => array( 'color' => '#000000' ),
			'items' => array(
				array( 'text' => 'one', 'meta' => array( 'note' => 'a' ) ),
			),
			'button' => array( 'icon' => array( 'name' => 'star' ) ),
			'query' => 'post_type=post',
		);
	}

	private function keyed_empty_design() {
		return array(
			'text' => '',
			'box' => array( 'color' => '' ),
		);
	}

	public static function empty_values() {
		return array(
			'empty string' => array( '' ),
			'null' => array( null ),
			'absent' => array( "\0absent" ),
		);
	}

	private function submit_with( $key, $value ) {
		$new = $this->complete_instance();

		if ( $value === "\0absent" ) {
			unset( $new[ $key ] );
		} else {
			$new[ $key ] = $value;
		}

		return $this->widget()->update( $new, $this->complete_instance() );
	}

	#[DataProvider( 'empty_values' )]
	public function test_empty_section_is_stored_as_the_keyed_empty_shape( $value ) {
		$stored = $this->submit_with( 'design', $value );

		$this->assertSame( $this->keyed_empty_design(), $stored['design'] );
	}

	public function test_empty_section_matches_a_submission_with_nothing_filled_in() {
		$blank = $this->complete_instance();
		$blank['design'] = array( 'text' => '', 'box' => array( 'color' => '' ) );

		$from_blank  = $this->widget()->update( $blank, $this->complete_instance() );
		$from_absent = $this->submit_with( 'design', "\0absent" );

		$this->assertSame( $from_blank['design'], $from_absent['design'] );
	}

	#[DataProvider( 'empty_values' )]
	public function test_empty_nested_section_is_stored_keyed( $value ) {
		$new = $this->complete_instance();

		if ( $value === "\0absent" ) {
			unset( $new['design']['box'] );
		} else {
			$new['design']['box'] = $value;
		}

		$stored = $this->widget()->update( $new, $this->complete_instance() );

		$this->assertSame( array( 'color' => '' ), $stored['design']['box'] );
	}

	#[DataProvider( 'empty_values' )]
	public function test_empty_toggle_is_stored_keyed( $value ) {
		$stored = $this->submit_with( 'shadow', $value );

		$this->assertSame( array( 'color' => '' ), $stored['shadow'] );
	}

	#[DataProvider( 'empty_values' )]
	public function test_empty_repeater_is_stored_as_an_empty_array( $value ) {
		$stored = $this->submit_with( 'items', $value );

		$this->assertSame( array(), $stored['items'] );
	}

	#[DataProvider( 'empty_values' )]
	public function test_empty_widget_field_is_stored_keyed( $value ) {
		$stored = $this->submit_with( 'button', $value );

		// The sub-widget form filter removes 'label', leaving the icon section.
		$this->assertSame( array( 'icon' => array( 'name' => '' ) ), $stored['button'] );
	}

	#[DataProvider( 'empty_values' )]
	public function test_empty_posts_field_keeps_storing_a_string( $value ) {
		$stored = $this->submit_with( 'query', $value );

		$this->assertSame( '', $stored['query'] );
	}

	#[DataProvider( 'empty_values' )]
	public function test_empty_text_field_keeps_storing_an_empty_string( $value ) {
		$stored = $this->submit_with( 'title', $value );

		$this->assertSame( '', $stored['title'] );
	}

	public function test_a_filled_section_is_stored_unchanged() {
		$stored = $this->widget()->update( $this->complete_instance(), $this->complete_instance() );

		$this->assertSame( $this->complete_instance()['design'], $stored['design'] );
		$this->assertSame( $this->complete_instance()['items'], $stored['items'] );
	}

	public static function record_sanitize_args() {
		self::$sanitize_args[] = func_get_args();

		return func_get_arg( 0 );
	}

	/**
	 * A container declared with its own callable sanitizer is handed
	 * ( $value, $old_value ) through the override exactly as the base
	 * sanitize() hands them to any field. A user-defined callable on a
	 * sub-field inside the container is handed ( $value, null ), because
	 * container sanitization passes no old value to its children and the
	 * base always hands a user-defined callable both arguments.
	 */
	public function test_callable_sanitizers_receive_the_same_arguments_as_before() {
		$factory = SiteOrigin_Widget_Field_Factory::single();
		$widget  = $this->widget();

		$section = $factory->create_field(
			'design',
			array(
				'type' => 'section',
				'sanitize' => array( __CLASS__, 'record_sanitize_args' ),
				'fields' => array(
					'text' => array(
						'type' => 'text',
						'sanitize' => array( __CLASS__, 'record_sanitize_args' ),
					),
				),
			),
			$widget
		);

		$section->sanitize( array( 'text' => 'new' ), array(), array( 'text' => 'old' ) );

		$this->assertSame(
			array(
				array( 'new', null ),                                      // sub-field: no old value to pass
				array( array( 'text' => 'new' ), array( 'text' => 'old' ) ), // container: value and old value
			),
			self::$sanitize_args
		);
	}
}

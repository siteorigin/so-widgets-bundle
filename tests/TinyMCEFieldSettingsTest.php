<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use SiteOrigin\Tests\SiteOriginTests;

if ( ! function_exists( 'siteorigin_widget_register' ) ) {
	function siteorigin_widget_register() {
		return true;
	}
}

if ( ! class_exists( 'SiteOrigin_Widget_Editor_Widget' ) ) {
	require __DIR__ . '/../widgets/editor/editor.php';
}

/**
 * The editor settings the TinyMCE field emits in its data-editor-settings
 * attribute, after other plugins change them through tiny_mce_before_init.
 *
 * Empty values a filter returns are skipped so TinyMCE keeps its defaults. The
 * one exception is a boolean false wordpress_adv_hidden: Advanced Editor Tools
 * sets it to keep the second and later toolbar rows visible, and WordPress
 * hides those rows when the key is absent.
 */
class TinyMCEFieldSettingsTest extends SiteOriginTests {
	/**
	 * What tiny_mce_before_init returns in the current test. Every other
	 * filter returns its value unchanged.
	 */
	private $filtered;

	protected function setUp(): void {
		parent::setUp();

		if ( ! defined( 'SCRIPT_DEBUG' ) ) {
			define( 'SCRIPT_DEBUG', false );
		}

		$GLOBALS['wp_version'] = '7.0';

		Functions\when( 'user_can_richedit' )->justReturn( true );
		Functions\when( 'includes_url' )->alias( fn( $path = '' ) => 'https://example.test/wp-includes/' . $path );
		Functions\when( 'get_bloginfo' )->justReturn( '7.0' );
		Functions\when( 'get_editor_stylesheets' )->justReturn( array() );
		Functions\when( 'esc_attr' )->alias( fn( $text ) => htmlspecialchars( (string) $text, ENT_QUOTES ) );
		Functions\when( 'esc_html' )->alias( fn( $text ) => htmlspecialchars( (string) $text, ENT_QUOTES ) );
		Functions\when( 'esc_textarea' )->alias( fn( $text ) => htmlspecialchars( (string) $text, ENT_QUOTES ) );
		Functions\when( 'siteorigin_sanitize_attribute_key' )->returnArg();
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value = null ) => 'tiny_mce_before_init' === $tag ? $this->filtered : $value
		);
	}

	/**
	 * Render a SiteOrigin Editor text field with tiny_mce_before_init returning
	 * the given value, and return its decoded editor settings.
	 */
	private function render_settings( $filtered ) {
		$this->filtered = $filtered;

		$widget = ( new ReflectionClass( SiteOrigin_Widget_Editor_Widget::class ) )->newInstanceWithoutConstructor();
		$widget->id_base = 'sow-editor';
		$widget->number = 1;

		$field = new SiteOrigin_Widget_Field_TinyMCE(
			'text',
			'widget-sow-editor-1-text',
			'widget-sow-editor[1][text]',
			array(
				'type' => 'tinymce',
				'media_buttons' => false,
			),
			$widget
		);

		ob_start();
		try {
			$field->render( '' );
		} finally {
			$html = ob_get_clean();
		}

		$this->assertMatchesRegularExpression( '/data-editor-settings="([^"]*)"/', $html );
		preg_match( '/data-editor-settings="([^"]*)"/', $html, $matches );

		return json_decode( html_entity_decode( $matches[1], ENT_QUOTES ), true );
	}

	public function test_truthy_filtered_values_are_copied() {
		$settings = $this->render_settings( array(
			'toolbar1' => 'bold,italic',
			'menubar' => true,
			'wpautop' => true,
		) );

		$this->assertSame( 'bold,italic', $settings['tinymce']['toolbar1'] );
		$this->assertTrue( $settings['tinymce']['menubar'] );
		$this->assertTrue( $settings['tinymce']['wpautop'] );
	}

	public function test_a_truthy_filtered_value_overwrites_the_fields_own_setting() {
		$settings = $this->render_settings( array( 'wp_skip_init' => 'yes' ) );

		$this->assertSame( 'yes', $settings['tinymce']['wp_skip_init'] );
	}

	public static function dropped_values() {
		return array(
			'false' => array( false ),
			'zero' => array( 0 ),
			'zero string' => array( '0' ),
			'empty string' => array( '' ),
			'null' => array( null ),
			'empty array' => array( array() ),
		);
	}

	#[DataProvider( 'dropped_values' )]
	public function test_empty_filtered_values_are_dropped( $value ) {
		$settings = $this->render_settings( array(
			'forced_root_block' => $value,
			'verify_html' => $value,
			'toolbar3' => $value,
		) );

		$this->assertArrayNotHasKey( 'forced_root_block', $settings['tinymce'] );
		$this->assertArrayNotHasKey( 'verify_html', $settings['tinymce'] );
		$this->assertArrayNotHasKey( 'toolbar3', $settings['tinymce'] );
	}

	#[DataProvider( 'dropped_values' )]
	public function test_an_empty_filtered_wpautop_keeps_the_fields_own_value( $value ) {
		$settings = $this->render_settings( array( 'wpautop' => $value ) );

		$this->assertTrue( $settings['tinymce']['wpautop'] );
	}

	public function test_json_strings_are_decoded() {
		$settings = $this->render_settings( array(
			'style_formats' => '[{"title":"Bold","inline":"b"}]',
			'toolbar1' => 'bold',
		) );

		$this->assertSame( array( array( 'title' => 'Bold', 'inline' => 'b' ) ), $settings['tinymce']['style_formats'] );
		$this->assertSame( 'bold', $settings['tinymce']['toolbar1'] );
	}

	public function test_a_false_wordpress_adv_hidden_reaches_the_editor() {
		$settings = $this->render_settings( array(
			'toolbar1' => 'bold,italic',
			'wordpress_adv_hidden' => false,
		) );

		$this->assertArrayHasKey( 'wordpress_adv_hidden', $settings['tinymce'] );
		$this->assertFalse( $settings['tinymce']['wordpress_adv_hidden'] );
	}

	public static function other_empty_wordpress_adv_hidden_values() {
		return array(
			'zero' => array( 0 ),
			'zero string' => array( '0' ),
			'empty string' => array( '' ),
			'null' => array( null ),
		);
	}

	#[DataProvider( 'other_empty_wordpress_adv_hidden_values' )]
	public function test_other_empty_wordpress_adv_hidden_values_are_dropped( $value ) {
		$settings = $this->render_settings( array( 'wordpress_adv_hidden' => $value ) );

		$this->assertArrayNotHasKey( 'wordpress_adv_hidden', $settings['tinymce'] );
	}

	public static function truthy_wordpress_adv_hidden_values() {
		return array(
			'true' => array( true, true ),
			'string false' => array( 'false', 'false' ),
			// The JSON decoding the loop applies turns 'true' into true, and
			// leaves 'false' as a string because it decodes to an empty value.
			'string true' => array( 'true', true ),
		);
	}

	#[DataProvider( 'truthy_wordpress_adv_hidden_values' )]
	public function test_truthy_wordpress_adv_hidden_values_are_copied( $value, $expected ) {
		$settings = $this->render_settings( array( 'wordpress_adv_hidden' => $value ) );

		$this->assertSame( $expected, $settings['tinymce']['wordpress_adv_hidden'] );
	}

	public function test_no_editor_settings_without_rich_editing() {
		Functions\when( 'user_can_richedit' )->justReturn( false );

		$widget = ( new ReflectionClass( SiteOrigin_Widget_Editor_Widget::class ) )->newInstanceWithoutConstructor();
		$widget->id_base = 'sow-editor';
		$widget->number = 1;
		$field = new SiteOrigin_Widget_Field_TinyMCE(
			'text',
			'widget-sow-editor-1-text',
			'widget-sow-editor[1][text]',
			array(
				'type' => 'tinymce',
				'media_buttons' => false,
			),
			$widget
		);

		ob_start();
		$field->render( '' );
		$html = ob_get_clean();

		preg_match( '/data-editor-settings="([^"]*)"/', $html, $matches );
		$settings = json_decode( html_entity_decode( $matches[1], ENT_QUOTES ), true );

		$this->assertArrayNotHasKey( 'tinymce', $settings );
	}

	/**
	 * A filter that breaks the array contract gets the same outcome as before:
	 * a warning for scalars, an object's truthy offsets copied, nothing for an
	 * empty object, and an Error for an object used as an array.
	 */
	#[RunInSeparateProcess]
	public function test_a_string_filter_result_warns_and_copies_nothing() {
		$this->assert_scalar_result_warns( 'not an array' );
	}

	#[RunInSeparateProcess]
	public function test_an_int_filter_result_warns_and_copies_nothing() {
		$this->assert_scalar_result_warns( 5 );
	}

	#[RunInSeparateProcess]
	public function test_a_null_filter_result_warns_and_copies_nothing() {
		$this->assert_scalar_result_warns( null );
	}

	#[RunInSeparateProcess]
	public function test_a_false_filter_result_warns_and_copies_nothing() {
		$this->assert_scalar_result_warns( false );
	}

	private function assert_scalar_result_warns( $filtered ) {
		$warnings = array();
		set_error_handler( function ( $errno, $message ) use ( &$warnings ) {
			$warnings[] = $message;

			return true;
		}, E_WARNING );

		$settings = $this->render_settings( $filtered );
		restore_error_handler();

		$this->assertCount( 1, $warnings );
		$this->assertStringContainsString( 'foreach() argument must be of type array|object', $warnings[0] );
		$this->assertSame( array( 'wp_skip_init', 'wpautop' ), array_keys( $settings['tinymce'] ) );
	}

	#[RunInSeparateProcess]
	public function test_an_array_object_filter_result_copies_truthy_offsets_only() {
		$settings = $this->render_settings( new ArrayObject( array(
			'toolbar1' => 'bold',
			'menubar' => false,
			'wordpress_adv_hidden' => false,
		) ) );

		$this->assertSame( 'bold', $settings['tinymce']['toolbar1'] );
		$this->assertArrayNotHasKey( 'menubar', $settings['tinymce'] );
		$this->assertArrayNotHasKey( 'wordpress_adv_hidden', $settings['tinymce'] );
	}

	#[RunInSeparateProcess]
	public function test_an_empty_object_filter_result_copies_nothing() {
		$settings = $this->render_settings( new stdClass() );

		$this->assertSame( array( 'wp_skip_init', 'wpautop' ), array_keys( $settings['tinymce'] ) );
	}

	#[RunInSeparateProcess]
	public function test_an_object_with_properties_throws_as_an_array() {
		$filtered = new stdClass();
		$filtered->toolbar1 = 'bold';
		$filtered->wordpress_adv_hidden = false;

		$this->expectException( Error::class );
		$this->expectExceptionMessage( 'Cannot use object of type stdClass as array' );

		$this->render_settings( $filtered );
	}
}

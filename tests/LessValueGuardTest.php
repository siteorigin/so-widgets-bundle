<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'SiteOrigin_Widgets_Less_Value_Guard' ) ) {
	require __DIR__ . '/../base/inc/less-value-guard.php';
}

/**
 * Unit tests for SiteOrigin_Widgets_Less_Value_Guard.
 *
 * Each refusal case asserts the exact reason, which ties every check to the
 * values it refuses. No case reads a file: the guard only parses, and the
 * paths named here do not exist.
 */
class LessValueGuardTest extends TestCase {
	/**
	 * Ordinary setting values: icon glyphs, ampersands, units, fonts,
	 * gradients, calc(), var(), colors and url()s.
	 */
	public static function ordinary_values() {
		return array(
			'icon glyph escape'           => array( '"\f123"' ),
			'icon glyph hex entity'       => array( '"&#xf123;"' ),
			'icon glyph decimal entity'   => array( '"&#61441;"' ),
			'backreference-like text'     => array( '"$1\foo"' ),
			'bare ampersand'              => array( 'a&b' ),
			'quoted ampersand'            => array( '"Tom & Jerry"' ),
			'unit'                        => array( '5em' ),
			'two units'                   => array( '2em 2.5em' ),
			'integer'                     => array( 12 ),
			'float'                       => array( 1.5 ),
			'zero string'                 => array( '0' ),
			'quoted multi-word font list' => array( '"Helvetica Neue", Helvetica, Arial, sans-serif' ),
			'single-quoted font list'     => array( "'Open Sans', sans-serif" ),
			'unquoted multi-word font'    => array( 'Open Sans' ),
			'linear gradient'             => array( 'linear-gradient(to right, #fff 0%, rgba(0,0,0,.5) 100%)' ),
			'calc'                        => array( 'calc(100% - 20px)' ),
			'escaped calc'                => array( '~"calc(100% - 20px)"' ),
			'custom property'             => array( 'var(--x, 10px)' ),
			'rgba'                        => array( 'rgba(1,2,3,0.5)' ),
			'fade'                        => array( 'fade(#000, 50%)' ),
			'url with query string'       => array( 'url("https://example.com/a.png?x=1&y=2")' ),
			'url with encoded ampersand'  => array( 'url("https://example.com/a.png?x=1&#038;y=2")' ),
			'unquoted url with query'     => array( 'url(https://example.com/a.png?x=1&y=2)' ),
			'data url'                    => array( 'url(data:text/plain;base64,QUJD)' ),
			'quoted punctuation'          => array( '"a;b {x} @import data-uri("' ),
			'quoted function name'        => array( '"datauri(probe.txt)"' ),
			'box shadow'                  => array( '0 2px 4px rgba(0,0,0,.2)' ),
		);
	}

	#[DataProvider( 'ordinary_values' )]
	public function test_accepts_ordinary_variable_values( $value ) {
		$this->assertTrue( SiteOrigin_Widgets_Less_Value_Guard::check_variable( $value ) );
	}

	/**
	 * Values refused because of what their parsed tree holds, with the reason.
	 */
	public static function denied_values() {
		return array(
			'variable'                   => array( '@payload', 'variable reference' ),
			'variable variable'          => array( '@@payload', 'variable reference' ),
			'variable in a function'     => array( 'fade(@payload, 50%)', 'variable reference' ),
			'variable in a url'          => array( 'url(@payload)', 'variable reference' ),
			'quoted interpolation'       => array( '"@{payload}"', 'variable interpolation' ),
			'escaped interpolation'      => array( '~"@{payload}"', 'variable interpolation' ),
			'url interpolation'          => array( 'url("@{payload}")', 'variable interpolation' ),
			'e() interpolation'          => array( 'e("@{payload}")', 'variable interpolation' ),
			'data-uri'                   => array( 'data-uri("probe.txt")', 'resource read' ),
			'datauri'                    => array( 'datauri("probe.txt")', 'resource read' ),
			'datauri upper case'         => array( 'DATAURI("probe.txt")', 'resource read' ),
			'data-uri mixed case'        => array( 'DaTa-Uri("probe.txt")', 'resource read' ),
			'datauri with a mime type'   => array( 'datauri("text/plain", "probe.txt")', 'resource read' ),
			'datauri in a function'      => array( 'fade(datauri("probe.txt"), 50%)', 'resource read' ),
			'block holding an import'    => array( '{ @import "probe.less"; }', 'not one value' ),
			'block holding declarations' => array( '{ color: red; }', 'not one value' ),
		);
	}

	#[DataProvider( 'denied_values' )]
	public function test_refuses_denied_variable_values( $value, $reason ) {
		$this->assertSame( $reason, SiteOrigin_Widgets_Less_Value_Guard::check_variable( $value ) );
	}

	#[DataProvider( 'denied_values' )]
	public function test_refuses_denied_mixin_arguments( $value, $reason ) {
		$this->assertSame( $reason, SiteOrigin_Widgets_Less_Value_Guard::check_mixin_argument( 'icon_color', $value ) );
	}

	/**
	 * Values refused because they are not exactly one declaration with one value.
	 */
	public static function malformed_variable_values() {
		return array(
			'second declaration'           => array( 'red; @extra: blue', 'extra statement' ),
			'entity before a declaration'  => array( 'red&#59; @extra:blue', 'extra statement' ),
			'named entity before a rule'   => array( 'red&semi; @extra:blue', 'extra statement' ),
			'entity before a directive'    => array( 'red&semi; @charset "UTF-8"', 'extra statement' ),
			'import after the value'       => array( 'red; @import "probe.less"', 'extra statement' ),
			'import spelling'              => array( 'red; @impor "probe.less"', 'extra statement' ),
			'closed ruleset'               => array( 'red;} .marker{a:b} .x{b:c', 'extra statement' ),
			'important'                    => array( 'red !important', 'not one declaration' ),
			'unclosed comment'             => array( 'red /*', 'parse error' ),
			'unclosed string'              => array( '"red', 'parse error' ),
			'negated nothing'              => array( '1 * -@', 'parse error' ),
			'negated open parenthesis'     => array( '1 -(', 'parse error' ),
			'array'                        => array( array( 'red' ), 'not scalar' ),
			'null'                         => array( null, 'not scalar' ),
		);
	}

	#[DataProvider( 'malformed_variable_values' )]
	public function test_refuses_malformed_variable_values( $value, $reason ) {
		$this->assertSame( $reason, SiteOrigin_Widgets_Less_Value_Guard::check_variable( $value ) );
	}

	public static function ordinary_colors() {
		return array(
			'hex'         => array( '#fff' ),
			'long hex'    => array( '#3b5998' ),
			'rgba'        => array( 'rgba(1,2,3,0.5)' ),
			'rgb spaced'  => array( 'rgb(10, 20, 30)' ),
			'hsl'         => array( 'hsl(0, 100%, 50%)' ),
			'keyword'     => array( 'transparent' ),
			'empty quote' => array( "''" ),
			'class name'  => array( 'x-twitter-0' ),
		);
	}

	#[DataProvider( 'ordinary_colors' )]
	public function test_accepts_ordinary_mixin_arguments( $value ) {
		$this->assertTrue( SiteOrigin_Widgets_Less_Value_Guard::check_mixin_argument( 'icon_color', $value ) );
	}

	/**
	 * Values that would add arguments or statements to a mixin call.
	 */
	public static function malformed_mixin_arguments() {
		return array(
			'second argument'  => array( 'red, @icon_color:blue' ),
			'comma list'       => array( 'Arial, sans-serif' ),
			'semicolon'        => array( 'a, b;' ),
			'closed call'      => array( 'red); @extra:blue; .m(@name:foo, @icon_color:red' ),
			'rule and charset' => array( 'red); .extra { marker:yes; } @charset "UTF-8"; .m(@x: red' ),
			'second call'      => array( 'q); .marker { a: b; } .create_social_media_button_style( @name: z' ),
			'important'        => array( 'red !important' ),
			'array'            => array( array( 'red' ) ),
		);
	}

	#[DataProvider( 'malformed_mixin_arguments' )]
	public function test_refuses_malformed_mixin_arguments( $value ) {
		$this->assertNotTrue( SiteOrigin_Widgets_Less_Value_Guard::check_mixin_argument( 'icon_color', $value ) );
	}

	public function test_accepts_a_mixin_call_with_the_expected_arguments() {
		$this->assertTrue(
			SiteOrigin_Widgets_Less_Value_Guard::check_mixin_call(
				'( @name:facebook-0, @icon_color:#fff, @border_hover_color:\'\');',
				array( 'name', 'icon_color', 'border_hover_color' )
			)
		);
	}

	public function test_refuses_a_mixin_call_with_other_arguments() {
		$this->assertSame(
			'unexpected argument',
			SiteOrigin_Widgets_Less_Value_Guard::check_mixin_call( '( @name:facebook-0, @button_color:#fff);', array( 'name', 'icon_color' ) )
		);
		$this->assertSame(
			'wrong argument count',
			SiteOrigin_Widgets_Less_Value_Guard::check_mixin_call( '( @name:facebook-0);', array( 'name', 'icon_color' ) )
		);
		$this->assertSame(
			'not one mixin call',
			SiteOrigin_Widgets_Less_Value_Guard::check_mixin_call( '( @name:facebook-0) !important;', array( 'name' ) )
		);
	}

	/**
	 * Report a refusal, and return the notices raised.
	 */
	private function refusal_notices() {
		$notices = array();

		set_error_handler(
			function ( $errno, $errstr ) use ( &$notices ) {
				$notices[] = $errstr;

				return true;
			},
			E_USER_NOTICE
		);

		try {
			SiteOrigin_Widgets_Less_Value_Guard::refused( (object) array( 'id_base' => 'sow-test' ), 'icon_color', 'resource read' );
		} finally {
			restore_error_handler();
		}

		return $notices;
	}

	/**
	 * The debug constant can't be undefined once set, so these run in their own processes.
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_refusal_is_silent_without_debug() {
		$this->assertFalse( defined( 'SITEORIGIN_WIDGETS_DEBUG' ), 'The test bootstrap must not define SITEORIGIN_WIDGETS_DEBUG.' );
		$this->assertSame( array(), $this->refusal_notices() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_refusal_raises_a_notice_when_debugging() {
		$this->assertFalse( defined( 'SITEORIGIN_WIDGETS_DEBUG' ), 'The test bootstrap must not define SITEORIGIN_WIDGETS_DEBUG.' );
		define( 'SITEORIGIN_WIDGETS_DEBUG', true );

		$this->assertSame(
			array( 'SiteOrigin Widgets: LESS value "icon_color" in sow-test was skipped (resource read).' ),
			$this->refusal_notices()
		);
	}
}

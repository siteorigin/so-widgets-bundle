<?php

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

/**
 * Another plugin may load its own less.php before the bundle, and
 * lessc.inc.php then keeps it. These tests load a stand-in Less_Parser whose
 * tree has another shape, and check the guard refuses every value instead of
 * raising a TypeError. Each runs in its own process, before the bundled
 * parser is loaded.
 */
class LessForeignParserShapeTest extends TestCase {
	/**
	 * Load a stand-in parser whose parse() leaves the given rules, then the guard.
	 */
	private function load_guard_with_rules( $rules_source ) {
		$this->assertFalse( class_exists( 'Less_Parser', false ), 'The bundled Less_Parser must not be loaded yet.' );

		eval(
			'class Less_Version { const less_version = "0.0.0"; }
			class Less_Tree_Ruleset { public $rules; }
			class Less_Parser {
				protected $rules;
				public function __construct( $options = null ) {}
				public function parse( $str ) { $this->rules = ' . $rules_source . '; return $this; }
			}'
		);

		require __DIR__ . '/../base/inc/less-value-guard.php';
	}

	private function assert_every_check_refuses() {
		$this->assertSame( 'parse error', SiteOrigin_Widgets_Less_Value_Guard::check_variable( '5em' ) );
		$this->assertSame( 'parse error', SiteOrigin_Widgets_Less_Value_Guard::check_mixin_argument( 'icon_color', '#fff' ) );
		$this->assertSame( 'parse error', SiteOrigin_Widgets_Less_Value_Guard::check_mixin_call( '( @name:facebook-0);', array( 'name' ) ) );
		$this->assertSame( 'parse error', SiteOrigin_Widgets_Less_Value_Guard::check_stylesheet( '.so-widget-test { p: 1; }' ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_refuses_when_the_parser_gives_no_rules_array() {
		$this->load_guard_with_rules( 'null' );
		$this->assert_every_check_refuses();
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_refuses_when_a_ruleset_holds_no_rules_array() {
		$this->load_guard_with_rules( 'array( ( function () { $r = new Less_Tree_Ruleset(); $r->rules = "x"; return $r; } )() )' );
		$this->assert_every_check_refuses();
	}
}

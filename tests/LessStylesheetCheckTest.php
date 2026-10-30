<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use SiteOrigin\Tests\SiteOriginTests;

require_once __DIR__ . '/fixtures/less-canary-stream.php';

if ( ! class_exists( 'SiteOrigin_Widgets_Less_Value_Guard' ) ) {
	require __DIR__ . '/../base/inc/less-value-guard.php';
}

if ( ! class_exists( 'SiteOrigin_Less_Stylesheet_Test_Widget' ) ) {
	/**
	 * A widget whose LESS and setting values a test sets.
	 */
	class SiteOrigin_Less_Stylesheet_Test_Widget extends SiteOrigin_Widget {
		public $less = '';
		public $values = array();

		public function __construct( $id_base = 'so-less-stylesheet-test' ) {
			parent::__construct( $id_base, 'LESS Stylesheet Test', array( 'has_preview' => false ), array(), false, __DIR__ );
		}

		public function get_widget_form() {
			return array();
		}

		public function get_less_content( $instance ) {
			return $this->less;
		}

		public function get_less_variables( $instance ) {
			return $this->values;
		}

		public function get_style_name( $instance ) {
			return 'default';
		}
	}
}

if ( ! class_exists( 'SiteOrigin_Reduced_Less_Value_Guard' ) ) {
	/**
	 * Stands in for another less.php that has a built-in the bundled one lacks.
	 */
	class SiteOrigin_Reduced_Less_Value_Guard extends SiteOrigin_Widgets_Less_Value_Guard {
		const BUNDLED_FUNCTIONS = array( 'fade', 'rgba' );
	}
}

if ( ! class_exists( 'SiteOrigin_Recording_LessC' ) ) {
	/**
	 * A compiler that keeps the LESS it is given, compiles it, and keeps any
	 * error, which get_instance_css() would otherwise turn into empty CSS.
	 */
	class SiteOrigin_Recording_LessC extends SiteOrigin_LessC {
		public static $less = null;
		public static $error = null;

		public function compile( $string, $name = null ) {
			self::$less = $string;

			try {
				return parent::compile( $string, $name );
			} catch ( Throwable $e ) {
				self::$error = $e;
				throw $e;
			}
		}
	}
}

if ( ! class_exists( 'SiteOrigin_Counting_Compiler' ) ) {
	/**
	 * A compiler from another source, which only counts its calls.
	 */
	class SiteOrigin_Counting_Compiler {
		public static $calls = 0;

		public function compile( $less ) {
			self::$calls++;

			return '.counted { p: 1; }';
		}
	}
}

/**
 * Unit tests for SiteOrigin_Widgets_Less_Value_Guard::check_stylesheet(), and
 * for the check in SiteOrigin_Widget::get_instance_css().
 *
 * Every refused case through a widget asserts empty CSS and that the canary
 * was neither opened nor probed; the canary wrapper's own test proves the
 * counters move.
 */
class LessStylesheetCheckTest extends SiteOriginTests {
	private $filters = array();

	protected function setUp(): void {
		parent::setUp();

		$this->filters = array();
		$filters = &$this->filters;

		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value, ...$args ) use ( &$filters ) {
				return isset( $filters[ $hook ] ) ? $filters[ $hook ]( $value, ...$args ) : $value;
			}
		);

		SiteOrigin_Less_Canary_Stream::register();
		SiteOrigin_Recording_LessC::$less = null;
		SiteOrigin_Recording_LessC::$error = null;
		SiteOrigin_Counting_Compiler::$calls = 0;
	}

	protected function tearDown(): void {
		SiteOrigin_Less_Canary_Stream::unregister();

		parent::tearDown();
	}

	/**
	 * Wrap LESS the way get_instance_css() does.
	 */
	private static function stylesheet( $less, $imports = '' ) {
		return $imports . "\n\n.so-widget-test { \n" . $less . "\n } ";
	}

	private function compile( $less, $values = array(), $id_base = 'so-less-stylesheet-test' ) {
		$widget = new SiteOrigin_Less_Stylesheet_Test_Widget( $id_base );
		$widget->less = $less;
		$widget->values = $values;
		SiteOrigin_Less_Canary_Stream::reset();

		return $widget->get_instance_css( array() );
	}

	private function assert_refused_without_reading( $css ) {
		$this->assertSame( '', $css );
		$this->assertSame( 0, SiteOrigin_Less_Canary_Stream::$opens );
		$this->assertSame( 0, SiteOrigin_Less_Canary_Stream::$stats );
	}

	public function test_bundled_function_list_matches_the_bundled_parser() {
		$methods = array();

		foreach ( ( new ReflectionClass( 'Less_Functions' ) )->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ) {
			if ( $method->class === 'Less_Functions' && ! $method->isConstructor() ) {
				$methods[] = strtolower( $method->name );
			}
		}

		$listed = SiteOrigin_Widgets_Less_Value_Guard::BUNDLED_FUNCTIONS;
		sort( $methods );
		sort( $listed );

		$this->assertSame( $methods, $listed );
	}

	public function test_bundled_node_list_matches_the_bundled_parser() {
		$classes = array();
		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( __DIR__ . '/../base/inc/lib/Less/Tree', FilesystemIterator::SKIP_DOTS ) );

		foreach ( $files as $file ) {
			preg_match_all( '/^\s*class\s+(\w+)/m', file_get_contents( $file ), $declared );
			$classes = array_merge( $classes, $declared[1] );
		}

		$listed = SiteOrigin_Widgets_Less_Value_Guard::BUNDLED_NODES;
		sort( $classes );
		sort( $listed );

		$this->assertSame( $classes, $listed );
	}

	public static function accepted_stylesheets() {
		return array(
			'remote css import'              => array( '', '@import url(https://fonts.googleapis.com/css?family=Roboto);' ),
			'protocol-relative with media'   => array( '', '@import url(//fonts.googleapis.com/css?family=Roboto) screen;' ),
			'quoted remote import'           => array( '', '@import "https://example.com/x.css";' ),
			'quoted remote import and media' => array( '', '@import "https://example.com/x.css" screen;' ),
			'explicit css import'            => array( '', '@import (css) url(//fonts.googleapis.com/css2?family=Roboto:wght@400);' ),
			'root comment'                   => array( '', '/* x */' ),
			'bundled built-ins'              => array( '.a { p: darken(#fff, 10%) percentage(0.5) %("%d", 1) fade(#000, 50%); }' ),
			'css functions'                  => array( '.a { p: rgba(0,0,0,.5) calc(100% - 1px) var(--x) linear-gradient(red, blue); }' ),
			'plain url'                      => array( '.a { background: url(https://example.com/a.png); }' ),
		);
	}

	#[DataProvider( 'accepted_stylesheets' )]
	public function test_accepts( $less, $imports = '' ) {
		$this->assertTrue( SiteOrigin_Widgets_Less_Value_Guard::check_stylesheet( self::stylesheet( $less, $imports ) ) );
	}

	public static function refused_stylesheets() {
		$imports = array(
			'local url import'              => '@import url("sowcanary://style.css");',
			'root-relative import'          => '@import url(/x.css);',
			'relative import with media'    => '@import "x.css" screen;',
			'local protocol-relative path'  => '@import url(//Users/x/style.css);',
			'host the parser treats as local' => '@import url(https://my-cdn.example.com/x.css);',
			'parent segment'                => '@import url(https://a.b/c/../../x.css);',
			'inline import'                 => '@import (inline) url(https://example.com/x.css);',
			'optional import'               => '@import (optional) "https://example.com/x.css";',
			'less import'                   => '@import "p";',
			'forced less import'            => '@import (less) "https://example.com/x.css";',
			'bare css2 url'                 => '@import url(https://fonts.googleapis.com/css2?family=Roboto:wght@400);',
			'quoted interpolation'          => '@import "@{x}css";',
			'quoted url interpolation'      => '@import url("https://@{x}.css");',
			'unquoted url interpolation'    => '@import url(https://@{x}.css);',
			'comment in a url'              => '@import url(https://example.com/a/*x*/.css);',
			'backtick url'                  => '@import url(`1`);',
		);

		$cases = array();

		foreach ( $imports as $name => $import ) {
			$cases[ $name ] = array( '', 'import', $import );
		}

		$reads = array(
			'data-uri'       => 'data-uri(p)',
			'datauri'        => 'datauri(p)',
			'DATA-URI'       => 'DATA-URI(p)',
			'image-size'     => 'image-size(p)',
			'image-width'    => 'image-width(p)',
			'image-height'   => 'image-height(p)',
		);

		foreach ( $reads as $name => $call ) {
			$cases[ "$name in a rule" ] = array( ".a { p: $call; }", 'resource read' );
		}

		return $cases + array(
			'read in a nested rule'        => array( '.a { .b { p: data-uri(p); } }', 'resource read' ),
			'read in a mixin body'         => array( '.m() { p: data-uri(p); } .a { .m(); }', 'resource read' ),
			'read in a mixin argument'     => array( '.m(@x) { p: @x; } .a { .m(data-uri(p)); }', 'resource read' ),
			'read in a parameter default'  => array( '.m(@x: data-uri(p)) { p: @x; }', 'resource read' ),
			'read in a guard'              => array( '.m(@x) when (data-uri(p) = 1) { p: @x; }', 'resource read' ),
			'variable import path'         => array( '', 'parse error', '@import @x;' ),
			'unparseable'                  => array( '.a {', 'parse error' ),
			'second root ruleset'          => array( '} .b {', 'unexpected structure' ),
			'root declaration'             => array( '', 'unexpected structure', '@x: 1;' ),
			'nesting too deep'             => array( str_repeat( '.a { ', 150 ) . str_repeat( '} ', 150 ), 'too deep' ),
		);
	}

	#[DataProvider( 'refused_stylesheets' )]
	public function test_refuses( $less, $reason, $imports = '' ) {
		$this->assertSame( $reason, SiteOrigin_Widgets_Less_Value_Guard::check_stylesheet( self::stylesheet( $less, $imports ) ) );
	}

	public function test_refuses_a_built_in_outside_the_bundled_list() {
		$less = self::stylesheet( '.a { p: darken(#fff, 10%); }' );

		$this->assertTrue( SiteOrigin_Widgets_Less_Value_Guard::check_stylesheet( $less ) );
		$this->assertSame( 'unknown function', SiteOrigin_Reduced_Less_Value_Guard::check_stylesheet( $less ) );
	}

	public function test_refuses_an_unknown_node() {
		$parser = new SiteOrigin_Widgets_Less_Value_Parser();
		$parser->parse( self::stylesheet( '.a { p: 1; }' ) );
		$rules = $parser->get_rules();
		$rules[0]->rules[0]->rules[0]->value = new ArrayObject();

		$walk = new ReflectionMethod( 'SiteOrigin_Widgets_Less_Value_Guard', 'find_resource_read' );
		$walk->setAccessible( true );

		$this->assertSame( 'unknown node', $walk->invoke( null, $rules ) );
	}

	public static function allowed_imports() {
		return array(
			'remote'            => array( '@import url(https://fonts.googleapis.com/css?family=Roboto);' ),
			'protocol-relative' => array( '@import url(//fonts.googleapis.com/css?family=Roboto);' ),
			'quoted with media' => array( '@import "https://example.com/x.css" screen;' ),
		);
	}

	#[DataProvider( 'allowed_imports' )]
	public function test_allowed_import_compiles_unchanged( $import ) {
		$css = $this->compile( $import . "\n.out { p: 1; }\n" );

		$this->assertMatchesRegularExpression(
			'/^' . preg_quote( $import, '/' ) . '\n\.so-widget-so-less-stylesheet-test-default-[0-9a-f]{12} \.out \{\n  p: 1;\n\}$/',
			$css
		);
		$this->assertSame( 0, SiteOrigin_Less_Canary_Stream::$opens );
		$this->assertSame( 0, SiteOrigin_Less_Canary_Stream::$stats );
	}

	/**
	 * Templates that would read the canary, with the canary's path.
	 */
	public static function file_reading_templates() {
		$canary = SiteOrigin_Less_Canary_Stream::url();

		return array(
			'data-uri'             => array( ".out { p: data-uri('$canary'); }\n" ),
			'image-size'           => array( ".out { p: image-size('$canary'); }\n" ),
			'local css import'     => array( "@import url(\"$canary\");\n.out { p: 1; }\n" ),
			'inline import'        => array( "@import (inline) url(\"$canary\");\n.out { p: 1; }\n" ),
			'optional import'      => array( "@import (optional) \"$canary\";\n.out { p: 1; }\n" ),
			'less import'          => array( "@import (less) url(\"$canary\");\n.out { p: 1; }\n" ),
			'interpolated import'  => array( "@x: \"sowcanary://canary\";\n@import url(\"@{x}\");\n.out { p: 1; }\n" ),
		);
	}

	#[DataProvider( 'file_reading_templates' )]
	public function test_file_reading_template_is_refused( $less ) {
		$this->assert_refused_without_reading( $this->compile( $less ) );
	}

	#[DataProvider( 'file_reading_templates' )]
	public function test_file_reading_template_never_reaches_a_filtered_compiler( $less ) {
		$this->filters['siteorigin_widgets_less_compiler'] = function () {
			return new SiteOrigin_Counting_Compiler();
		};

		$this->assert_refused_without_reading( $this->compile( $less ) );
		$this->assertSame( 0, SiteOrigin_Counting_Compiler::$calls );
	}

	public function test_accepted_stylesheet_reaches_a_filtered_compiler() {
		$this->filters['siteorigin_widgets_less_compiler'] = function () {
			return new SiteOrigin_Counting_Compiler();
		};

		$this->assertSame( '.counted { p: 1; }', $this->compile( ".out { p: 1; }\n" ) );
		$this->assertSame( 1, SiteOrigin_Counting_Compiler::$calls );
	}

	public function test_local_import_added_by_a_styles_filter_is_refused() {
		$this->filters['siteorigin_widgets_styles'] = function ( $less, $widget_class, $instance ) {
			return $less . "\n@import url(\"" . SiteOrigin_Less_Canary_Stream::url() . "\");";
		};

		$this->assert_refused_without_reading( $this->compile( ".out { p: 1; }\n" ) );
	}

	public function test_remote_import_added_by_a_styles_filter_compiles() {
		$this->filters['siteorigin_widgets_styles'] = function ( $less ) {
			return $less . "\n@import url(https://example.com/x.css);";
		};

		$this->assertStringStartsWith( "@import url(https://example.com/x.css);\n", $this->compile( ".out { p: 1; }\n" ) );
	}

	public function test_registered_function_cannot_replace_a_refused_built_in() {
		$this->filters['siteorigin_widgets_less_compiler'] = function ( $compiler ) {
			$compiler->registerFunction(
				'data-uri',
				function () {
					return new Less_Tree_Keyword( 'registered' );
				}
			);

			return $compiler;
		};

		$this->assert_refused_without_reading( $this->compile( '.out { p: data-uri("' . SiteOrigin_Less_Canary_Stream::url() . '"); }' ) );
	}

	/**
	 * In an import's media list the parser reads a call as a keyword and a
	 * parenthesis, which are emitted as text.
	 */
	public function test_call_in_an_import_media_list_reads_nothing() {
		$css = $this->compile( "@import url(https://example.com/x.css) data-uri('" . SiteOrigin_Less_Canary_Stream::url() . "');\n.out { p: 1; }\n" );

		$this->assertStringStartsWith( "@import url(https://example.com/x.css) data-uri ('sowcanary://canary');\n", $css );
		$this->assertStringNotContainsString( SiteOrigin_Less_Canary_Stream::CANARY, $css );
		$this->assertSame( 0, SiteOrigin_Less_Canary_Stream::$opens );
		$this->assertSame( 0, SiteOrigin_Less_Canary_Stream::$stats );
	}

	public function test_type_error_from_the_compiler_gives_empty_css() {
		$this->filters['siteorigin_widgets_less_compiler'] = function () {
			return new class() {
				public function compile( $less ) {
					throw new TypeError( 'compile' );
				}
			};
		};

		$this->assertSame( '', $this->compile( ".out { p: 1; }\n" ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_refused_stylesheet_throws_when_debugging() {
		define( 'SITEORIGIN_WIDGETS_DEBUG', true );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'SiteOrigin Widgets: the LESS for so-less-stylesheet-test was not compiled (resource read).' );

		$this->compile( '.out { p: data-uri("' . SiteOrigin_Less_Canary_Stream::url() . '"); }' );
	}

	/**
	 * Every widget stylesheet, except partials that another stylesheet in the
	 * same folder imports; those are checked through the stylesheets that
	 * import them.
	 */
	public static function bundled_stylesheets() {
		$cases = array();

		foreach ( glob( __DIR__ . '/../widgets/*/styles' ) as $dir ) {
			$files = glob( $dir . '/*.less' );
			$imported = array();

			foreach ( $files as $file ) {
				preg_match_all( '/^@import\s+"([\w\-]+)";/m', file_get_contents( $file ), $imports );
				$imported = array_merge( $imported, $imports[1] );
			}

			foreach ( $files as $file ) {
				if ( ! in_array( basename( $file, '.less' ), $imported, true ) ) {
					$cases[ basename( dirname( $dir ) ) . '/' . basename( $file ) ] = array( $file );
				}
			}
		}

		return $cases;
	}

	/**
	 * Each bundled stylesheet passes the check after the widget's own import
	 * expansion, compiles without an error, and gives widget CSS.
	 */
	#[DataProvider( 'bundled_stylesheets' )]
	public function test_bundled_stylesheet_passes( $file ) {
		Functions\when( 'siteorigin_widget_get_plugin_dir_path' )->justReturn( dirname( $file, 2 ) . '/' );
		$this->filters['siteorigin_widgets_less_compiler'] = function () {
			return new SiteOrigin_Recording_LessC();
		};

		$css = $this->compile( file_get_contents( $file ) );

		$this->assertNotNull( SiteOrigin_Recording_LessC::$less, 'The stylesheet did not reach the compiler.' );
		$this->assertNull( SiteOrigin_Recording_LessC::$error, 'The stylesheet did not compile.' );
		$this->assertTrue( SiteOrigin_Widgets_Less_Value_Guard::check_stylesheet( SiteOrigin_Recording_LessC::$less ) );

		// The Video widget's rules all depend on settings, which are empty here.
		if ( basename( dirname( $file, 2 ) ) . '/' . basename( $file ) === 'video/default.less' ) {
			$this->assertSame( '', $css );
		} else {
			$this->assertMatchesRegularExpression( '/^\.so-widget-so-less-stylesheet-test-default-[0-9a-f]{12} .*\{/m', $css );
		}
	}
}

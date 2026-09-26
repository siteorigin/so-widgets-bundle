<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

if ( ! class_exists( 'SiteOrigin_Less_Value_Test_Widget' ) ) {
	/**
	 * A widget whose LESS writes one setting value into one property.
	 */
	class SiteOrigin_Less_Value_Test_Widget extends SiteOrigin_Widget {
		public $value = '';

		public function __construct() {
			parent::__construct( 'so-less-value-test', 'LESS Value Test', array( 'has_preview' => false ), array(), false, __DIR__ );
		}

		public function get_widget_form() {
			return array();
		}

		public function get_less_content( $instance ) {
			return "@value: template-default;\n.out { p: @value; }\n";
		}

		public function get_less_variables( $instance ) {
			return array( 'value' => $this->value );
		}

		public function get_style_name( $instance ) {
			return 'default';
		}
	}
}

/**
 * Unit tests for the setting value check in SiteOrigin_Widget::get_instance_css().
 *
 * Every case asserts the whole compiled CSS, so a value can neither change
 * the property it is written into nor add a rule, import or directive.
 */
class LessValueSubstitutionTest extends SiteOriginTests {
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'apply_filters' )->returnArg( 2 );
	}

	/**
	 * Compile a value, and return the CSS without the widget's instance selector.
	 */
	private function compile( $value ) {
		$widget = new SiteOrigin_Less_Value_Test_Widget();
		$widget->value = $value;

		return preg_replace( '/^\.so-widget-so-less-value-test-default-[0-9a-f]{12} /m', '', $widget->get_instance_css( array() ) );
	}

	private static function css( $property_value ) {
		return ".out {\n  p: $property_value;\n}";
	}

	/**
	 * Ordinary values: icon glyphs, ampersands, units, fonts, gradients,
	 * calc(), var(), colors and url()s, with the CSS each compiles to.
	 */
	public static function ordinary_values() {
		return array(
			'icon glyph: "\\f123"' => array( '"\\f123"', '"\\f123"' ),
			'icon glyph: "\\e900"' => array( '"\\e900"', '"\\e900"' ),
			'icon glyph: "&#xf123;"' => array( '"&#xf123;"', '"&#xf123;"' ),
			'icon glyph: "&#61441;"' => array( '"&#61441;"', '"&#61441;"' ),
			'bare &: a&b' => array( 'a&b', 'a&b' ),
			'bare &: "a & b"' => array( '"a & b"', '"a & b"' ),
			'bare &: "Tom & Jerry"' => array( '"Tom & Jerry"', '"Tom & Jerry"' ),
			'bare &: url("https://example.com/?a=1&b=2")' => array( 'url("https://example.com/?a=1&b=2")', 'url("https://example.com/?a=1&b=2")' ),
			'units: 5em' => array( '5em', '5em' ),
			'units: 10px' => array( '10px', '10px' ),
			'units: 1.5rem' => array( '1.5rem', '1.5rem' ),
			'units: 50%' => array( '50%', '50%' ),
			'units: 100vh' => array( '100vh', '100vh' ),
			'units: 0' => array( '0', '0' ),
			'units: 0 (integer)' => array( 0, '0' ),
			'units: 12 (integer)' => array( 12, '12' ),
			'units: 1.5 (double)' => array( 1.5, '1.5' ),
			'units: -2px' => array( '-2px', '-2px' ),
			'units: 2em 2.5em' => array( '2em 2.5em', '2em 2.5em' ),
			'units: 10px 20px 30px 40px' => array( '10px 20px 30px 40px', '10px 20px 30px 40px' ),
			'units: .5em' => array( '.5em', '0.5em' ),
			'units: 768px' => array( '768px', '768px' ),
			'units: auto' => array( 'auto', 'auto' ),
			'units: 5em 0 0' => array( '5em 0 0', '5em 0 0' ),
			'fonts: Arial' => array( 'Arial', 'Arial' ),
			'fonts: "Open Sans"' => array( '"Open Sans"', '"Open Sans"' ),
			'fonts: \'Open Sans\', sans-serif' => array( '\'Open Sans\', sans-serif', '\'Open Sans\', sans-serif' ),
			'fonts: "Helvetica Neue", Helvetica, Arial, sans-serif' => array( '"Helvetica Neue", Helvetica, Arial, sans-serif', '"Helvetica Neue", Helvetica, Arial, sans-serif' ),
			'fonts: Georgia, serif' => array( 'Georgia, serif', 'Georgia, serif' ),
			'fonts: "Font Awesome 5 Free"' => array( '"Font Awesome 5 Free"', '"Font Awesome 5 Free"' ),
			'fonts: Open Sans' => array( 'Open Sans', 'Open Sans' ),
			'fonts: inherit' => array( 'inherit', 'inherit' ),
			'fonts: "Playfair Display", serif' => array( '"Playfair Display", serif', '"Playfair Display", serif' ),
			'gradients: linear-gradient(to right, #fff 0%, rgba(0,0,0,.5) 100%)' => array( 'linear-gradient(to right, #fff 0%, rgba(0,0,0,.5) 100%)', 'linear-gradient(to right, #ffffff 0%, rgba(0, 0, 0, 0.5) 100%)' ),
			'gradients: radial-gradient(circle at center, red, blue)' => array( 'radial-gradient(circle at center, red, blue)', 'radial-gradient(circle at center, #ff0000, #0000ff)' ),
			'gradients: repeating-linear-gradient(45deg, #000 0 10px, #fff 10px 20px)' => array( 'repeating-linear-gradient(45deg, #000 0 10px, #fff 10px 20px)', 'repeating-linear-gradient(45deg, #000000 0 10px, #ffffff 10px 20px)' ),
			'gradients: linear-gradient(180deg, transparent, #000)' => array( 'linear-gradient(180deg, transparent, #000)', 'linear-gradient(180deg, transparent, #000000)' ),
			'calc/var/clamp: calc(100% - 20px)' => array( 'calc(100% - 20px)', 'calc(80%)' ),
			'calc/var/clamp: ~"calc(100% - 20px)"' => array( '~"calc(100% - 20px)"', 'calc(100% - 20px)' ),
			'calc/var/clamp: var(--x)' => array( 'var(--x)', 'var(--x)' ),
			'calc/var/clamp: var(--x, 10px)' => array( 'var(--x, 10px)', 'var(--x, 10px)' ),
			'calc/var/clamp: clamp(1rem, 2vw, 3rem)' => array( 'clamp(1rem, 2vw, 3rem)', '1rem' ),
			'calc/var/clamp: env(safe-area-inset-top)' => array( 'env(safe-area-inset-top)', 'env(safe-area-inset-top)' ),
			'calc/var/clamp: color-mix(in srgb, red 50%, blue)' => array( 'color-mix(in srgb, red 50%, blue)', 'color-mix(in srgb, #ff0000 50%, #0000ff)' ),
			'colors: rgba(1,2,3,0.5)' => array( 'rgba(1,2,3,0.5)', 'rgba(1, 2, 3, 0.5)' ),
			'colors: rgba(0, 0, 0, .5)' => array( 'rgba(0, 0, 0, .5)', 'rgba(0, 0, 0, 0.5)' ),
			'colors: rgb(255, 0, 0)' => array( 'rgb(255, 0, 0)', '#ff0000' ),
			'colors: hsl(120, 50%, 50%)' => array( 'hsl(120, 50%, 50%)', '#40bf40' ),
			'colors: hsla(120, 50%, 50%, 0.3)' => array( 'hsla(120, 50%, 50%, 0.3)', 'rgba(64, 191, 64, 0.3)' ),
			'colors: #fff' => array( '#fff', '#ffffff' ),
			'colors: #AbCdEf' => array( '#AbCdEf', '#abcdef' ),
			'colors: #ffffff80' => array( '#ffffff80', '#ffffff 80' ),
			'colors: transparent' => array( 'transparent', 'transparent' ),
			'colors: currentColor' => array( 'currentColor', 'currentColor' ),
			'colors: fade(#000, 50%)' => array( 'fade(#000, 50%)', 'rgba(0, 0, 0, 0.5)' ),
			'colors: darken(#fff, 10%)' => array( 'darken(#fff, 10%)', '#e6e6e6' ),
			'url: url("https://example.com/a.png?x=1&y=2")' => array( 'url("https://example.com/a.png?x=1&y=2")', 'url("https://example.com/a.png?x=1&y=2")' ),
			'url: url("https://example.com/a.png?x=1&#038;y=2")' => array( 'url("https://example.com/a.png?x=1&#038;y=2")', 'url("https://example.com/a.png?x=1&#038;y=2")' ),
			'url: url(https://example.com/a.png?x=1&y=2)' => array( 'url(https://example.com/a.png?x=1&y=2)', 'url(https://example.com/a.png?x=1&y=2)' ),
			'url: url("https://example.com/a%20b.png#frag")' => array( 'url("https://example.com/a%20b.png#frag")', 'url("https://example.com/a%20b.png#frag")' ),
			'url: url(data:text/plain;base64,QUJD)' => array( 'url(data:text/plain;base64,QUJD)', 'url(data:text/plain;base64,QUJD)' ),
			'url: url("data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\'></svg>")' => array( 'url("data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\'></svg>")', 'url("data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\'></svg>")' ),
			'url: url(\'https://example.com/a.png\')' => array( 'url(\'https://example.com/a.png\')', 'url(\'https://example.com/a.png\')' ),
			'url: url("//cdn.example.com/a.webp?v=1.2&amp;w=300")' => array( 'url("//cdn.example.com/a.webp?v=1.2&amp;w=300")', 'url("//cdn.example.com/a.webp?v=1.2&amp;w=300")' ),
			'misc: "a;b {x} @import data-uri("' => array( '"a;b {x} @import data-uri("', '"a;b {x} @import data-uri("' ),
			'misc: ~"foo"' => array( '~"foo"', 'foo' ),
			'misc: e("bar")' => array( 'e("bar")', 'bar' ),
			'misc: percentage(0.5)' => array( 'percentage(0.5)', '50%' ),
			'misc: unit(5, px)' => array( 'unit(5, px)', '5px' ),
			'misc: center' => array( 'center', 'center' ),
			'misc: none' => array( 'none', 'none' ),
			'misc: 1px solid #ccc' => array( '1px solid #ccc', '1px solid #cccccc' ),
			'misc: 0 2px 4px rgba(0,0,0,.2)' => array( '0 2px 4px rgba(0,0,0,.2)', '0 2px 4px rgba(0, 0, 0, 0.2)' ),
			'misc: "datauri(probe.txt)"' => array( '"datauri(probe.txt)"', '"datauri(probe.txt)"' ),
		);
	}

	#[DataProvider( 'ordinary_values' )]
	public function test_ordinary_value_compiles_unchanged( $value, $expected ) {
		$this->assertSame( self::css( $expected ), $this->compile( $value ) );
	}

	/**
	 * Values that a preg_replace() replacement string would read as backreferences.
	 */
	public static function literal_values() {
		return array(
			'dollar backreference'       => array( '"$1\foo"' ),
			'escaped backreference'      => array( '"\1"' ),
			'escape that looks like one' => array( '"\31"' ),
			'double backslash'           => array( '"\\\\f101"' ),
		);
	}

	#[DataProvider( 'literal_values' )]
	public function test_dollar_and_backslash_stay_literal( $value ) {
		$this->assertSame( self::css( $value ), $this->compile( $value ) );
	}

	/**
	 * Refused values, one per kind of refusal.
	 */
	public static function refused_values() {
		return array(
			'variable reference'     => array( '@payload' ),
			'variable interpolation' => array( '"@{payload}"' ),
			'resource read'          => array( 'datauri("probe.txt")' ),
			'block'                  => array( '{ @import "probe.less"; }' ),
			'extra statement'        => array( 'red; @extra: blue' ),
			'important'              => array( 'red !important' ),
		);
	}

	#[DataProvider( 'refused_values' )]
	public function test_refused_value_keeps_the_template_default( $value ) {
		$this->assertSame( self::css( 'template-default' ), $this->compile( $value ) );
	}
}

<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

if ( ! function_exists( 'siteorigin_widget_register' ) ) {
	function siteorigin_widget_register() {
		return true;
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return rtrim( dirname( $file ), '/\\' ) . '/';
	}
}

if ( ! defined( 'SOW_BUNDLE_BASE_FILE' ) ) {
	define( 'SOW_BUNDLE_BASE_FILE', __DIR__ . '/../so-widgets-bundle.php' );
}

if ( ! class_exists( 'SiteOrigin_Widget_PriceTable_Widget' ) ) {
	require __DIR__ . '/../widgets/price-table/price-table.php';
}

require_once __DIR__ . '/../base/inc/widget-describer.php';

/**
 * Pins the CSS and LESS variables of the Price Table widget.
 *
 * tests/fixtures/price-table-css.json holds the output captured before the
 * font settings existed. A saved widget that never sets them must keep the
 * same variables, and so the same style hash and CSS.
 *
 * Re-capture (only on a branch whose output is the intended baseline):
 *   SOW_CAPTURE_FIXTURES=1 vendor/bin/phpunit --filter PriceTableFontsTest
 */
class PriceTableFontsTest extends SiteOriginTests {
	private const FIXTURE_FILE = __DIR__ . '/fixtures/price-table-css.json';

	private const PARTS = array( 'title', 'subtitle', 'price', 'sale_price', 'original_price', 'period', 'features', 'button' );

	private static $captured = array();

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'siteorigin_widget_get_plugin_dir_path' )->justReturn( __DIR__ . '/../widgets/price-table/' );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'get_option' )->alias(
			function ( $option, $default = false ) {
				return $default;
			}
		);
		Functions\when( 'wp_parse_args' )->alias(
			function ( $args, $defaults ) {
				return array_merge( $defaults, (array) $args );
			}
		);
	}

	public static function tearDownAfterClass(): void {
		if ( getenv( 'SOW_CAPTURE_FIXTURES' ) && ! empty( self::$captured ) ) {
			ksort( self::$captured );
			file_put_contents(
				self::FIXTURE_FILE,
				json_encode( array( 'instances' => self::$captured ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n"
			);
		}

		parent::tearDownAfterClass();
	}

	private function widget() {
		return new SiteOrigin_Widget_PriceTable_Widget();
	}

	private static function default_design() {
		return array(
			'theme' => 'atom',
			'header' => array(
				'background_color' => '#65707f',
				'featured_background_color' => '#707d8d',
				'color' => '#fff',
				'featured_color' => '#fff',
			),
			'feature' => array(
				'color' => '#5f6062',
			),
			'button' => array(
				'container_color' => '#e8e8e8',
				'background_color' => '#41a9d5',
				'featured_background_color' => '#2e9fcf',
			),
		);
	}

	private static function default_fonts() {
		return array_fill_keys(
			self::PARTS,
			array(
				'font' => 'default',
				'size' => '',
			)
		);
	}

	private function css( $instance ) {
		$css = $this->widget()->get_instance_css( $instance );
		$css = preg_replace( '/\.so-widget-sow-price-table-atom-[0-9a-f]{12} /', '', $css );

		return trim( preg_replace( '/\s+/', ' ', $css ) );
	}

	public static function saved_instances() {
		$custom = self::default_design();
		$custom['header']['background_color'] = '#203040';
		$custom['feature']['color'] = '#101010';
		$custom['button']['background_color'] = '#aa3300';

		return array(
			'legacy' => array( 'legacy', array( 'title' => 'Pricing' ) ),
			'default colours' => array( 'default colours', array( 'design' => self::default_design() ) ),
			'default colours and fonts' => array(
				'default colours and fonts',
				array( 'design' => self::default_design() + array( 'fonts' => self::default_fonts() ) ),
			),
			'custom colours' => array( 'custom colours', array( 'design' => $custom ) ),
		);
	}

	#[DataProvider( 'saved_instances' )]
	public function test_saved_widget_keeps_its_variables_and_css( $key, $instance ) {
		$actual = array(
			'less_variables' => $this->widget()->get_less_variables( $instance ),
			'css' => $this->css( $instance ),
		);

		if ( getenv( 'SOW_CAPTURE_FIXTURES' ) ) {
			self::$captured[ $key ] = $actual;
			$this->assertNotEmpty( $actual['css'] );

			return;
		}

		$fixture = json_decode( file_get_contents( self::FIXTURE_FILE ), true );

		$this->assertArrayHasKey( $key, $fixture['instances'] );
		$this->assertSame( $fixture['instances'][ $key ]['less_variables'], $actual['less_variables'] );
		$this->assertSame( $fixture['instances'][ $key ]['css'], $actual['css'] );
	}

	public function test_form_has_a_closed_fonts_section_after_button() {
		$design = $this->widget()->form_options()['design']['fields'];

		$this->assertSame( 'button', array_slice( array_keys( $design ), -2, 1 )[0] );
		$this->assertSame( 'fonts', array_slice( array_keys( $design ), -1 )[0] );
		$this->assertSame( 'section', $design['fonts']['type'] );
		$this->assertTrue( $design['fonts']['hide'] );
		$this->assertSame( self::PARTS, array_keys( $design['fonts']['fields'] ) );

		foreach ( $design['fonts']['fields'] as $part => $section ) {
			$this->assertTrue( $section['hide'], "{$part} is closed" );
			$this->assertSame( array( 'font', 'size' ), array_keys( $section['fields'] ), $part );
			$this->assertSame( 'font', $section['fields']['font']['type'], $part );
			$this->assertSame( 'default', $section['fields']['font']['default'], $part );
			$this->assertSame( 'measurement', $section['fields']['size']['type'], $part );
			$this->assertArrayNotHasKey( 'default', $section['fields']['size'], $part );
		}
	}

	public function test_schema_offers_font_and_size_for_each_part() {
		$schema = SiteOrigin_Widgets_Widget_Describer::single()->get_schema( $this->widget() );
		$fonts = $schema['properties']['design']['properties']['fonts']['properties'];

		$this->assertSame( self::PARTS, array_keys( $fonts ) );

		foreach ( $fonts as $part => $section ) {
			$this->assertSame( 'string', $section['properties']['font']['type'], $part );
			$this->assertSame( 1, preg_match( '/' . $section['properties']['size']['pattern'] . '/', '24px' ), $part );
		}
	}
}

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

if ( ! class_exists( 'SiteOrigin_Widgets_Testimonials_Widget' ) ) {
	require __DIR__ . '/../widgets/testimonial/testimonial.php';
}

require_once __DIR__ . '/../base/inc/widget-describer.php';

/**
 * Pins the CSS and LESS variables of the Testimonials widget.
 *
 * tests/fixtures/testimonial-css.json holds the output of saved widgets that
 * set no Name or Location color. The CSS is compared exactly as
 * get_instance_css() returns it, widget class and whitespace included, so a
 * saved widget keeps its style hash and its stylesheet byte for byte.
 *
 * Re-capture (only when the output is meant to change):
 *   SOW_CAPTURE_FIXTURES=1 vendor/bin/phpunit --filter TestimonialColorsTest
 */
class TestimonialColorsTest extends SiteOriginTests {
	private const FIXTURE_FILE = __DIR__ . '/fixtures/testimonial-css.json';

	private static $captured = array();

	/**
	 * Font variables a filter adds, as the Premium Testimonials addon does.
	 */
	private $filtered_variables = array();

	protected function setUp(): void {
		parent::setUp();

		$this->filtered_variables = array();

		Functions\when( 'siteorigin_widget_get_plugin_dir_path' )->justReturn( __DIR__ . '/../widgets/testimonial/' );
		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value ) {
				if ( $hook === 'siteorigin_widgets_less_variables_sow-testimonials' && is_array( $value ) ) {
					return array_merge( $value, $this->filtered_variables );
				}

				return $value;
			}
		);
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
		return new SiteOrigin_Widgets_Testimonials_Widget();
	}

	private static function testimonial( $url = '' ) {
		return array(
			'name' => 'Sarah Mitchell',
			'location' => 'Northfield Studio',
			'image' => 0,
			'text' => 'Quote.',
			'url' => $url,
			'new_window' => false,
		);
	}

	/**
	 * The design as the form stores it with its defaults.
	 */
	private static function default_design() {
		return array(
			'image' => array(
				'image_shape' => 'square',
				'image_size' => 50,
			),
			'colors' => array(
				'testimonial_background' => false,
				'text_background' => '#f0f0f0',
				'text_color' => '#444444',
			),
			'padding' => 10,
			'border_radius' => 4,
			'user_position' => 'left',
			'layout' => 'side',
			'equalize_testimonial_height' => false,
		);
	}

	private static function default_settings() {
		return array(
			'per_line' => 3,
			'responsive' => array(
				'tablet' => array(
					'per_line' => 2,
					'image_size' => 50,
					'width' => 800,
				),
				'mobile' => array(
					'per_line' => 1,
					'image_size' => 50,
					'width' => 480,
				),
			),
		);
	}

	private static function instance( $design ) {
		return array(
			'title' => 'What clients say',
			'testimonials' => array( self::testimonial( 'https://example.com' ), self::testimonial() ),
			'settings' => self::default_settings(),
			'design' => $design,
		);
	}

	/**
	 * Font variables the Premium Testimonials addon passes for a saved widget
	 * with name and location fonts, so those rules hold declarations of their own.
	 */
	private const PREMIUM_FONTS = array(
		'name_font_family' => 'Georgia, serif',
		'name_font_size' => '18px',
		'location_font_family' => 'Georgia, serif',
		'location_font_size' => '14px',
	);

	public static function saved_instances() {
		$custom = self::default_design();
		$custom['colors'] = array(
			'testimonial_background' => '#203040',
			'text_background' => '#101820',
			'text_color' => '#f6f6f6',
		);

		$string_colors = self::default_design();
		$string_colors['colors'] = '';

		return array(
			'legacy' => array( 'legacy', array( 'title' => 'What clients say' ), array() ),
			'default colors' => array( 'default colors', self::instance( self::default_design() ), array() ),
			'custom colors' => array( 'custom colors', self::instance( $custom ), array() ),
			'custom colors and fonts' => array( 'custom colors and fonts', self::instance( $custom ), self::PREMIUM_FONTS ),
			'string colors section' => array( 'string colors section', self::instance( $string_colors ), array() ),
		);
	}

	#[DataProvider( 'saved_instances' )]
	public function test_saved_widget_keeps_its_variables_and_css( $key, $instance, $filtered_variables ) {
		$this->filtered_variables = $filtered_variables;

		$actual = array(
			'less_variables' => $this->widget()->get_less_variables( $instance ),
			'css' => $this->widget()->get_instance_css( $instance ),
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
}

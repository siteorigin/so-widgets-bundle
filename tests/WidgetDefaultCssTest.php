<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * Pins the compiled CSS of every widget's default instance, byte for byte.
 *
 * tests/fixtures/widget-default-css.json holds what get_instance_css()
 * returns for each widget with every declared field at its default, widget
 * class and whitespace included. A change to a widget's form, variables or
 * stylesheet that alters the CSS of a saved widget fails here, including a
 * new `prop: @var;` whose stripped `default` value leaves a blank line in a
 * rule that has other declarations.
 *
 * Re-capture (only when the output is meant to change, with the cause named
 * in the commit):
 *   SOW_CAPTURE_FIXTURES=1 vendor/bin/phpunit --filter WidgetDefaultCssTest
 */
class WidgetDefaultCssTest extends SiteOriginTests {
	private const FIXTURE_FILE = __DIR__ . '/fixtures/widget-default-css.json';

	private static $captured = array();

	protected function setUp(): void {
		parent::setUp();

		$_SERVER['SERVER_NAME'] = 'localhost';
	}

	public static function tearDownAfterClass(): void {
		if ( getenv( 'SOW_CAPTURE_FIXTURES' ) && ! empty( self::$captured ) ) {
			ksort( self::$captured );
			file_put_contents(
				self::FIXTURE_FILE,
				json_encode( self::$captured, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n"
			);
		}

		parent::tearDownAfterClass();
	}

	/**
	 * Every widget folder with a main file of the same name, and its class.
	 */
	public static function widgets() {
		$widgets = array();

		foreach ( glob( __DIR__ . '/../widgets/*/' ) as $dir ) {
			$name = basename( $dir );
			$file = $dir . $name . '.php';

			if ( ! file_exists( $file ) ) {
				continue;
			}

			if ( preg_match( '/^class\s+(\w+)\s+extends\s+\w+/m', file_get_contents( $file ), $match ) ) {
				$widgets[ $name ] = array( $name, $match[1], $file );
			}
		}

		ksort( $widgets );

		return $widgets;
	}

	public function test_every_widget_folder_is_pinned() {
		$this->assertSame(
			count( glob( __DIR__ . '/../widgets/*', GLOB_ONLYDIR ) ),
			count( self::widgets() )
		);
	}

	#[DataProvider( 'widgets' )]
	public function test_default_instance_css_is_unchanged( $name, $class, $file ) {
		Functions\when( 'siteorigin_widget_get_plugin_dir_path' )->justReturn( dirname( $file ) . '/' );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'sanitize_key' )->alias( 'strtolower' );
		Functions\when( 'sanitize_option' )->returnArg( 2 );
		Functions\when( 'wp_enqueue_style' )->justReturn( null );
		Functions\when( 'wp_enqueue_script' )->justReturn( null );
		Functions\when( 'get_taxonomies' )->justReturn( array() );
		// No registered size: the carousel falls back to its own default.
		Functions\when( 'siteorigin_widgets_get_image_size' )->justReturn( array() );
		Functions\when( 'siteorigin_widget_get_plugin_dir_url' )->justReturn( 'https://example.com/widgets/' );
		Functions\when( 'wp_list_pluck' )->alias(
			function ( $list, $field ) {
				return array_map(
					function ( $item ) use ( $field ) {
						return is_object( $item ) ? $item->$field : $item[ $field ];
					},
					(array) $list
				);
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

		if ( ! class_exists( $class ) ) {
			require_once $file;
		}

		$widget = new $class();
		$instance = $widget->add_defaults( $widget->form_options(), array() );
		$css = $widget->get_instance_css( $instance );

		if ( getenv( 'SOW_CAPTURE_FIXTURES' ) ) {
			self::$captured[ $name ] = $css;
			$this->assertIsString( $css );

			return;
		}

		$fixture = json_decode( file_get_contents( self::FIXTURE_FILE ), true );

		$this->assertArrayHasKey( $name, $fixture, 'Widget missing from widget-default-css.json; capture it.' );
		$this->assertSame( $fixture[ $name ], $css );
	}
}

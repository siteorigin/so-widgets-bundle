<?php

namespace SiteOrigin\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as FrameworkTestCase;
use Brain\Monkey\Functions;

/**
 * Class SiteOriginTests
 *
 * Base test class for SiteOrigin functionality.
 * Provides common setup and teardown methods for mocking WordPress functions.
 * This class uses Brain Monkey to mock WordPress functions and PHPUnit
 * for testing.
 */
class SiteOriginTests extends FrameworkTestCase {
	use MockeryPHPUnitIntegration;

	/**
	 * Set up the test environment.
	 *
	 * Initializes Brain Monkey and mocks common WordPress functions.
	 * This method is called before each test is executed.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->mock_general_wp_functions();
	}

	/**
	 * Mock general WordPress functions.
	 */
	private function mock_general_wp_functions() {
		// Mock the '__' function to return its argument.
		Functions\when( '__' )->returnArg();

		// Mock the 'shortcode_atts' function to merge attributes.
		Functions\when( 'shortcode_atts' )
			->alias(
				function ( $pairs, $atts, $shortcode ) {
					return array_merge( $pairs, $atts );
				}
			);

		// Add basic escaping functions. These aren't equivalent to
		// WordPress's functions, but they will suffice for testing
		// purposes.
		Functions\when( 'esc_attr' )
			->alias(
				function ( $value ) {
					return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
				}
			);

		Functions\when( 'esc_html' )
			->alias(
				function ( $value ) {
					return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
				}
			);

		$this->mock_widget_base_functions();
	}

	/**
	 * Mock the WordPress functions the real SiteOrigin_Widget base class calls
	 * while constructing a widget and running update(): the object cache the
	 * form is read through, the options table global settings come from, the
	 * upload directory and post id the CSS file name is built from, and the
	 * post type list the posts field enumerates in its constructor.
	 *
	 * A test that needs different behaviour redefines the function itself;
	 * Brain Monkey's last definition wins.
	 */
	private function mock_widget_base_functions() {
		Functions\when( 'wp_parse_args' )->alias(
			function ( $args, $defaults = array() ) {
				if ( is_object( $args ) ) {
					$args = get_object_vars( $args );
				} elseif ( ! is_array( $args ) ) {
					parse_str( (string) $args, $args );
				}

				return array_merge( $defaults, $args );
			}
		);
		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\when( 'wp_cache_set' )->justReturn( true );
		Functions\when( 'wp_cache_add' )->justReturn( true );
		Functions\when( 'get_option' )->alias(
			function ( $option, $default = false ) {
				return $default;
			}
		);
		Functions\when( 'wp_upload_dir' )->justReturn( array( 'basedir' => sys_get_temp_dir() ) );
		Functions\when( 'get_the_id' )->justReturn( 0 );
		Functions\when( 'is_preview' )->justReturn( false );
		Functions\when( 'is_customize_preview' )->justReturn( false );
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'wp_get_current_user' )->justReturn(
			(object) array( 'user_email' => '', 'roles' => array() )
		);
		Functions\when( 'get_post_types' )->justReturn(
			array(
				'post' => (object) array( 'labels' => (object) array( 'name' => 'Posts' ) ),
				'page' => (object) array( 'labels' => (object) array( 'name' => 'Pages' ) ),
			)
		);
		$this->mock_font_helper();
		Functions\when( 'apply_filters' )->returnArg( 2 );

		// Sanitizers the field classes run on save. WordPress's own are
		// pass-throughs (the tests assert shape, not escaping); the plugin
		// helpers from base/base.php, which cannot be loaded without the rest
		// of the plugin, mirror the real implementation where it is pure.
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'sanitize_text_field' )->alias(
			function ( $value ) {
				return is_scalar( $value ) ? trim( strip_tags( (string) $value ) ) : '';
			}
		);
		Functions\when( 'balanceTags' )->returnArg();
		Functions\when( 'sanitize_email' )->returnArg();
		Functions\when( 'sanitize_html_class' )->returnArg();
		Functions\when( 'sanitize_title' )->returnArg();
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'sow_esc_url_raw' )->returnArg();
		Functions\when( 'siteorigin_sanitize_json' )->returnArg();
		Functions\when( 'is_admin' )->justReturn( false );
		// An empty font family list makes the font field keep the regex-cleaned
		// value instead of validating it against Google Fonts.
		Functions\when( 'siteorigin_widgets_font_families' )->justReturn( array() );
		Functions\when( 'siteorigin_widgets_get_image_sizes' )->justReturn( array() );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wpautop' )->returnArg();
		Functions\when( 'siteorigin_widgets_get_measurements_list' )->justReturn(
			array( 'px', '%', 'in', 'cm', 'mm', 'em', 'rem', 'pt', 'pc', 'ex', 'ch', 'vw', 'vh', 'vmin', 'vmax' )
		);
		Functions\when( 'siteorigin_widgets_strip_escape_sequences' )->alias(
			function ( $value, $html = false ) {
				$value = preg_replace( '/\\\\u[0-9a-fA-F]{4}|\\\\x[0-9a-fA-F]{2}|\\\\[0-7]{3}|[\p{C}&&[^\r\n]]+/u', '', $value );

				if ( $html ) {
					$value = preg_replace( '/&[^;]+;/', '', $value );
				}

				return $value;
			}
		);
		Functions\when( 'esc_attr_e' )->alias(
			function ( $value ) {
				echo htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
			}
		);
	}

	/**
	 * Mock siteorigin_widget_get_font(), which widgets call while building
	 * their LESS variables and so while computing their style hash.
	 *
	 * The real helper looks the value up as an array key, so handing it an
	 * array or object is a fatal on PHP 8. Mirror that here: a permissive stub
	 * would hide exactly the bug the widget suites are meant to catch. A null
	 * is a valid array key and the real helper returns an empty family for it
	 * (with a deprecation notice from explode()), so null is treated as ''.
	 *
	 * The values it returns mirror siteorigin_widget_get_font() in base/base.php
	 * without the enqueue side effects: a web-safe name maps to its stack,
	 * 'default' to 'default', 'Family:weight' splits into family, weight,
	 * weight_raw and style, and anything else is a custom family. The style
	 * hash assertions depend on this being faithful, because the font family
	 * is part of the hashed variables.
	 */
	private function mock_font_helper() {
		Functions\when( 'siteorigin_widget_get_font' )->alias(
			function ( $font_value = '' ) {
				if ( is_array( $font_value ) || is_object( $font_value ) ) {
					throw new \TypeError( 'Cannot access offset of type ' . gettype( $font_value ) . ' in isset or empty' );
				}

				if ( is_null( $font_value ) ) {
					trigger_error( 'explode(): Passing null to parameter #2 ($string) of type string is deprecated', E_USER_DEPRECATED );
					$font_value = '';
				}

				$web_safe = array(
					'Arial'   => 'Arial, Helvetica Neue, Helvetica, sans-serif',
					'default' => 'default',
				);

				if ( isset( $web_safe[ $font_value ] ) ) {
					return array( 'family' => $web_safe[ $font_value ] );
				}

				$font_parts = explode( ':', $font_value );
				$font       = array( 'family' => $font_parts[0] );

				if ( count( $font_parts ) > 1 ) {
					$font['weight']     = $font_parts[1];
					$font['weight_raw'] = filter_var( $font['weight'], FILTER_SANITIZE_NUMBER_INT );
					$font['style']      = ! is_numeric( $font['weight'] ) || $font['weight'] == 'italic' ? 'italic' : '';
				}

				return $font;
			}
		);
	}

	/**
	 * Run a callable while recording warnings, notices and deprecations, so a
	 * test can assert that a code path is silent as well as non-fatal.
	 *
	 * @return array [ result, errors ] where errors is a list of messages.
	 */
	protected function run_capturing_errors( callable $callback ) {
		$errors = array();

		set_error_handler(
			function ( $errno, $errstr ) use ( &$errors ) {
				$errors[] = $errstr;

				return true;
			},
			E_WARNING | E_NOTICE | E_DEPRECATED | E_USER_WARNING | E_USER_NOTICE | E_USER_DEPRECATED
		);

		try {
			$result = $callback();
		} finally {
			restore_error_handler();
		}

		return array( $result, $errors );
	}

	/**
	 * Mock the logged-in state of the user.
	 *
	 * @param bool $logged_in Whether the user is logged in.
	 */
	public function mock_logged_in( $logged_in = false ) {
		Functions\expect( 'is_user_logged_in' )
			->once()
			->andReturn( $logged_in );
	}

	/**
	 * Mock the roles of the current user.
	 *
	 * @param array $roles Array of roles assigned to the user.
	 * @param bool  $logged_in Whether the user is logged in.
	 */
	public function mock_user_roles( $roles = array(), $logged_in = true ) {
		$this->mock_logged_in( $logged_in );

		Functions\expect( 'wp_get_current_user' )
			->once()
			->andReturn( (object) array( 'roles' => $roles ) );
	}

	/**
	 * Tear down the test environment.
	 *
	 * Cleans up Brain Monkey and calls the parent teardown method.
	 * This method is called after each test is executed.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}

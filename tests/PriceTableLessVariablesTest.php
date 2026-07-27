<?php

use Brain\Monkey\Functions;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * Unit tests for SiteOrigin_Widget_PriceTable_Widget::get_less_variables().
 *
 * price-table.php registers itself by calling siteorigin_widget_register()
 * while the file is being required, and the widget constructor resolves its
 * plugin directory, so both have to exist as real functions at that moment —
 * the same pattern CacheCompatTest uses for add_action().
 */
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

/**
 * Minimal stand-in for the widget base class. get_less_variables() reads no
 * base-class state, so an empty parent keeps the test free of WordPress.
 */
if ( ! class_exists( 'SiteOrigin_Widget' ) ) {
	class SiteOrigin_Widget {
		public function __construct() {
		}
	}
}

if ( ! class_exists( 'SiteOrigin_Widget_PriceTable_Widget' ) ) {
	require __DIR__ . '/../widgets/price-table/price-table.php';
}

class PriceTableLessVariablesTest extends SiteOriginTests {
	/**
	 * PHP errors captured while the code under test runs.
	 */
	private $php_errors = array();

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_parse_args' )->alias(
			function ( $args, $defaults ) {
				return array_merge( $defaults, (array) $args );
			}
		);
	}

	private function widget() {
		return new SiteOrigin_Widget_PriceTable_Widget();
	}

	/**
	 * Runs get_less_variables() while recording warnings, notices, and
	 * deprecations so tests can assert the guards keep it silent.
	 */
	private function get_less_variables_capturing_errors( $instance ) {
		$this->php_errors = array();

		set_error_handler(
			function ( $errno, $errstr ) {
				$this->php_errors[] = $errstr;

				return true;
			},
			E_WARNING | E_NOTICE | E_DEPRECATED
		);

		try {
			return $this->widget()->get_less_variables( $instance );
		} finally {
			restore_error_handler();
		}
	}

	public function test_instance_without_design_returns_empty_strings_without_warnings() {
		$colors = $this->get_less_variables_capturing_errors(
			array(
				'title'   => 'Pricing',
				'columns' => array(),
			)
		);

		$this->assertSame( array(), $this->php_errors );

		$expected_empty = array(
			'header_color',
			'featured_header_color',
			'header_text_color',
			'featured_header_text_color',
			'feature_text_color',
			'button_container_color',
			'button_background_color',
			'featured_button_background_color',
		);

		foreach ( $expected_empty as $key ) {
			$this->assertSame( '', $colors[ $key ], "Expected '' for {$key}" );
		}

		$this->assertArrayNotHasKey( 'button_text_color', $colors );
		$this->assertArrayNotHasKey( 'featured_button_text_color', $colors );
	}

	public function test_design_sanitized_to_empty_string_is_treated_as_absent() {
		$colors = $this->get_less_variables_capturing_errors(
			array(
				'design' => '',
			)
		);

		$this->assertSame( array(), $this->php_errors );
		$this->assertSame( '', $colors['header_color'] );
		$this->assertSame( '', $colors['button_background_color'] );
	}

	public function test_partial_design_resolves_present_keys_only() {
		$colors = $this->get_less_variables_capturing_errors(
			array(
				'design' => array(
					'header' => array(
						'background_color' => '#65707f',
					),
				),
			)
		);

		$this->assertSame( array(), $this->php_errors );
		$this->assertSame( '#65707f', $colors['header_color'] );
		$this->assertSame( '', $colors['featured_header_color'] );
		$this->assertSame( '', $colors['feature_text_color'] );
		$this->assertArrayNotHasKey( 'button_text_color', $colors );
	}

	public function test_full_design_returns_expected_color_map() {
		$colors = $this->get_less_variables_capturing_errors(
			array(
				'design' => array(
					'theme'  => 'atom',
					'header' => array(
						'background_color'          => '#65707f',
						'featured_background_color' => '#707d8d',
						'color'                     => '#fff',
						'featured_color'            => '#fff',
					),
					'feature' => array(
						'color' => '#5f6062',
					),
					'button' => array(
						'container_color'           => '#e8e8e8',
						'background_color'          => '#41a9d5',
						'featured_background_color' => '#2e9fcf',
					),
				),
			)
		);

		$this->assertSame( array(), $this->php_errors );
		$this->assertSame( '#65707f', $colors['header_color'] );
		$this->assertSame( '#707d8d', $colors['featured_header_color'] );
		$this->assertSame( '#fff', $colors['header_text_color'] );
		$this->assertSame( '#fff', $colors['featured_header_text_color'] );
		$this->assertSame( '#5f6062', $colors['feature_text_color'] );
		$this->assertSame( '#e8e8e8', $colors['button_container_color'] );
		$this->assertSame( '#41a9d5', $colors['button_background_color'] );
		$this->assertSame( '#2e9fcf', $colors['featured_button_background_color'] );

		// Derived, readable-on-button text colors.
		$this->assertSame( '#FFFFFF', $colors['button_text_color'] );
		$this->assertSame( '#FFFFFF', $colors['featured_button_text_color'] );
	}
}

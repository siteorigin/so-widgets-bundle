<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * Unit tests for SiteOrigin_Widgets_ContactForm_Widget::get_less_variables().
 *
 * contact.php registers itself by calling siteorigin_widget_register() while
 * the file is being required, and the widget constructor resolves its plugin
 * directory, so both have to exist as real functions at that moment — the same
 * pattern CacheCompatTest uses for add_action().
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
 * contact.php registers a filter hook at the top level (line 2143) while the
 * file is being required, before any test has a chance to set Brain Monkey up.
 * add_filter has to exist as a real function by then — the same pattern
 * CacheCompatTest uses for add_action().
 */
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter() {
		return true;
	}
}

/**
 * Minimal stand-in for the widget base class. get_less_variables() reads no
 * base-class state beyond get_global_settings(), so a small parent keeps the
 * test free of WordPress.
 */
if ( ! class_exists( 'SiteOrigin_Widget' ) ) {
	class SiteOrigin_Widget {
		public function __construct() {
		}

		public function get_global_settings() {
			return array();
		}
	}
}

if ( ! class_exists( 'SiteOrigin_Widgets_ContactForm_Widget' ) ) {
	require __DIR__ . '/../widgets/contact/contact.php';
}

class ContactLessVariablesTest extends SiteOriginTests {
	/**
	 * PHP errors captured while the code under test runs.
	 */
	private $php_errors = array();

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'siteorigin_widget_get_font' )->alias(
			function () {
				return array(
					'family'     => '',
					'weight'     => '',
					'weight_raw' => '',
					'style'      => '',
				);
			}
		);
	}

	private function widget() {
		return new SiteOrigin_Widgets_ContactForm_Widget();
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

	public function test_instance_without_design_returns_without_errors() {
		$result = $this->get_less_variables_capturing_errors(
			array(
				'title' => 'Contact',
			)
		);

		$this->assertNull( $result );
		$this->assertSame( array(), $this->php_errors );
	}

	public function test_valid_design_returns_expected_vars() {
		$vars = $this->get_less_variables_capturing_errors(
			array(
				'design' => array(
					'container' => array(
						'background' => '#f2f2f2',
					),
					'labels' => array(
						'font'    => 'Arial',
						'size'    => '16px',
						'color'   => '#333333',
						'position' => 'above',
					),
					'fields' => array(
						'font'          => 'Georgia',
						'font_size'     => '14px',
						'color'         => '#000000',
						'border_radius' => 3,
					),
					'submit' => array(
						'border_radius' => 3,
					),
				),
			)
		);

		$this->assertSame( '#f2f2f2', $vars['container_background'] );
		$this->assertSame( '16px', $vars['label_font_size'] );
		$this->assertSame( '14px', $vars['field_font_size'] );
		$this->assertSame( '3px', $vars['field_border_radius'] );
		$this->assertSame( '3px', $vars['submit_border_radius'] );
		$this->assertSame( 'default', $vars['label_position'] );
	}

	public function test_missing_design_sections_yield_empty_without_errors() {
		$vars = $this->get_less_variables_capturing_errors(
			array(
				'design' => array(
					'labels' => array(
						'font' => '',
					),
				),
			)
		);

		// Normalising missing sections to empty arrays means the reads resolve
		// to null (rather than throwing an offset-of-string TypeError).
		$this->assertSame( null, $vars['field_font_size'] );
		$this->assertSame( null, $vars['container_background'] );
	}

	/**
	 * A design section saved as a string (from a corrupt or legacy instance)
	 * must not throw a "Cannot access offset of type string on string"
	 * TypeError. Note: under this fix such sections resolve to empty, and reads
	 * of missing sub-keys may still emit "Undefined array key" warnings — those
	 * are out of scope here. The goal is only to prevent the fatal.
	 */
	#[DataProvider( 'corrupt_sections' )]
	public function test_corrupt_section_as_string_does_not_fatal( $section ) {
		$instance = array(
			'design' => array(
				'labels' => array(
					'font' => '',
				),
				'fields' => array(
					'font' => '',
				),
			),
		);

		// Set only the section under test to a corrupt string value.
		$instance['design'][ $section ] = 'corrupt-string';

		$exception = null;
		try {
			$this->get_less_variables_capturing_errors( $instance );
		} catch ( \Throwable $e ) {
			$exception = $e;
		}

		$this->assertNull( $exception, 'get_less_variables() threw: ' . ( $exception ? $exception->getMessage() : '' ) );
	}

	public static function corrupt_sections() {
		$cases = array();

		foreach ( array(
			'container',
			'labels',
			'fields',
			'descriptions',
			'errors',
			'submit',
			'focus',
			'success',
		) as $section ) {
			$cases[ $section ] = array( $section );
		}

		return $cases;
	}
}

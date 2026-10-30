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

	private static function font_parts() {
		return array( 'title', 'subtitle', 'price', 'sale_price', 'original_price', 'period', 'features', 'button' );
	}

	private static function with_fonts( $fonts ) {
		return array( 'design' => array( 'fonts' => $fonts ) );
	}

	/**
	 * The keys of an instance that uses no font setting.
	 */
	public function test_instance_without_fonts_adds_no_keys() {
		$variables = $this->get_less_variables_capturing_errors( array( 'title' => 'Pricing' ) );

		$this->assertSame( array(), $this->php_errors );
		$this->assertSame(
			array(
				'header_color',
				'featured_header_color',
				'header_text_color',
				'featured_header_text_color',
				'feature_text_color',
				'button_container_color',
				'button_background_color',
				'featured_button_background_color',
			),
			array_keys( $variables )
		);
	}

	public function test_default_fonts_and_empty_sizes_keep_the_variables_and_style_hash() {
		$widget = $this->widget();
		$legacy = array( 'title' => 'Pricing' );
		$fonts = array_fill_keys(
			self::font_parts(),
			array(
				'font' => 'default',
				'size' => '',
			)
		);

		$instances = array(
			'default and empty' => self::with_fonts( $fonts ),
			'empty font' => self::with_fonts( array( 'title' => array( 'font' => '', 'size' => '' ) ) ),
			'through add_defaults()' => $widget->add_defaults( $widget->form_options(), $legacy ),
		);

		$this->assertSame( 'default', $instances['through add_defaults()']['design']['fonts']['price']['font'] );

		foreach ( $instances as $name => $instance ) {
			$this->assertSame( $this->font_keys( $widget->get_less_variables( $legacy ) ), $this->font_keys( $widget->get_less_variables( $instance ) ), $name );
		}

		$this->assertSame(
			$widget->get_style_hash( $legacy ),
			$widget->get_style_hash( self::with_fonts( $fonts ) )
		);
	}

	/**
	 * The keys of a variables array that aren't colors.
	 */
	private function font_keys( $variables ) {
		return array_values( array_filter( array_keys( $variables ), fn( $key ) => strpos( $key, 'color' ) === false ) );
	}

	public function test_each_part_passes_its_own_size() {
		foreach ( self::font_parts() as $part ) {
			$variables = $this->widget()->get_less_variables(
				self::with_fonts( array( $part => array( 'font' => 'default', 'size' => '24px' ) ) )
			);

			$this->assertSame( array( $part . '_size' ), $this->font_keys( $variables ) );
			$this->assertSame( '24px', $variables[ $part . '_size' ] );
		}
	}

	public function test_web_safe_font_passes_its_stack_and_a_normal_style() {
		$variables = $this->widget()->get_less_variables(
			self::with_fonts( array( 'sale_price' => array( 'font' => 'Georgia', 'size' => '' ) ) )
		);

		$this->assertSame( 'Georgia, Times, Times New Roman, serif', $variables['sale_price_font'] );
		$this->assertSame( 'normal', $variables['sale_price_font_style'] );
		$this->assertArrayNotHasKey( 'sale_price_font_weight', $variables );
	}

	public function test_google_font_passes_its_family_weight_and_style() {
		$variables = $this->widget()->get_less_variables(
			self::with_fonts(
				array(
					'price' => array( 'font' => 'Open Sans:700' ),
					'original_price' => array( 'font' => 'Open Sans:700italic' ),
				)
			)
		);

		$this->assertSame( 'Open Sans', $variables['price_font'] );
		$this->assertSame( '700', $variables['price_font_weight'] );
		$this->assertSame( 'normal', $variables['price_font_style'] );

		$this->assertSame( 'Open Sans', $variables['original_price_font'] );
		$this->assertSame( '700', $variables['original_price_font_weight'] );
		$this->assertSame( 'italic', $variables['original_price_font_style'] );
	}

	public function test_font_variant_without_a_weight_passes_no_weight() {
		$variables = $this->widget()->get_less_variables(
			self::with_fonts( array( 'title' => array( 'font' => 'Open Sans:italic' ) ) )
		);

		$this->assertSame( 'italic', $variables['title_font_style'] );
		$this->assertArrayNotHasKey( 'title_font_weight', $variables );
	}

	public function test_fonts_of_the_wrong_type_add_no_keys_and_no_warnings() {
		$instances = array(
			self::with_fonts( 'fonts' ),
			self::with_fonts( array( 'title' => 'font' ) ),
			self::with_fonts( array( 'title' => array( 'font' => array( 'Georgia' ) ) ) ),
		);

		foreach ( $instances as $instance ) {
			$variables = $this->get_less_variables_capturing_errors( $instance );

			$this->assertSame( array(), $this->php_errors );
			$this->assertSame( array(), $this->font_keys( $variables ) );
		}
	}
}

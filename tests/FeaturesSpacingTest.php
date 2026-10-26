<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

if ( ! class_exists( 'SiteOrigin_Widget_Features_Widget' ) ) {
	require __DIR__ . '/../widgets/features/features.php';
}

/**
 * Pins the CSS and LESS variables of the Features widget, and tests the
 * title margin and icon spacing settings.
 *
 * tests/fixtures/features-css.json holds the output captured before the
 * spacing settings existed. A saved widget that never sets them must keep
 * the same variables, and so the same style hash and CSS.
 *
 * Re-capture (only on a branch whose output is the intended baseline):
 *   SOW_CAPTURE_FIXTURES=1 vendor/bin/phpunit --filter FeaturesSpacingTest
 */
class FeaturesSpacingTest extends SiteOriginTests {
	private const FIXTURE_FILE = __DIR__ . '/fixtures/features-css.json';

	private static $captured = array();

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'siteorigin_widget_get_plugin_dir_path' )->justReturn( __DIR__ . '/../widgets/features/' );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'get_option' )->alias(
			function ( $option, $default = false ) {
				return $default;
			}
		);
	}

	public static function tearDownAfterClass(): void {
		if ( getenv( 'SOW_CAPTURE_FIXTURES' ) && ! empty( self::$captured ) ) {
			$fixture = file_exists( self::FIXTURE_FILE ) ? json_decode( file_get_contents( self::FIXTURE_FILE ), true ) : array();
			$fixture['layouts'] = self::$captured;
			ksort( $fixture['layouts'] );
			file_put_contents(
				self::FIXTURE_FILE,
				json_encode( $fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n"
			);
		}

		parent::tearDownAfterClass();
	}

	private static function fixture() {
		return json_decode( file_get_contents( self::FIXTURE_FILE ), true );
	}

	private function widget() {
		return new SiteOrigin_Widget_Features_Widget();
	}

	private static function instance( $overrides = array() ) {
		$features = array();

		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $position ) {
			$features[] = array(
				'icon' => 'fontawesome-star',
				'container_color' => '#404040',
				'container_position' => $position,
				'title' => 'Feature ' . $position,
				'text' => 'Text',
			);
		}

		$instance = array(
			'features' => $features,
			'fonts' => array(
				'title_options' => array(
					'tag' => 'h5',
					'font' => 'default',
					'size' => '28px',
					'color' => '#111111',
				),
				'text_options' => array(
					'font' => 'default',
					'size' => '16px',
					'color' => '#222222',
				),
				'more_text_options' => array(
					'font' => 'default',
					'size' => '',
					'color' => '',
				),
			),
			'container_size' => '84px',
			'icon_size' => '24px',
			'icon_size_custom' => false,
			'per_row' => 3,
			'responsive' => true,
			'more_text_bottom_align' => false,
			'feature_spacing' => '25px 25px',
			'feature_spacing_mobile' => '25px 25px',
		);

		return array_replace_recursive( $instance, $overrides );
	}

	private function css( $instance ) {
		$css = $this->widget()->get_instance_css( $instance );
		$css = preg_replace( '/\.so-widget-sow-features-default-[0-9a-f]{12} /', '', $css );

		return trim( preg_replace( '/\s+/', ' ', $css ) );
	}

	public static function layouts() {
		return array(
			'per row 3' => array( 'per row 3', array( 'per_row' => 3 ) ),
			'per row 1' => array( 'per row 1', array( 'per_row' => 1 ) ),
			'per row 3, bottom align' => array( 'per row 3, bottom align', array( 'per_row' => 3, 'more_text_bottom_align' => true ) ),
			'per row 1, bottom align' => array( 'per row 1, bottom align', array( 'per_row' => 1, 'more_text_bottom_align' => true ) ),
			'per row 3, custom icon size' => array( 'per row 3, custom icon size', array( 'per_row' => 3, 'icon_size_custom' => true ) ),
			'per row 3, h2 title' => array( 'per row 3, h2 title', array( 'fonts' => array( 'title_options' => array( 'tag' => 'h2' ) ) ) ),
		);
	}

	#[DataProvider( 'layouts' )]
	public function test_saved_widget_keeps_its_variables_and_css( $key, $overrides ) {
		$instance = self::instance( $overrides );
		$widget = $this->widget();

		$actual = array(
			'less_variables' => $widget->get_less_variables( $instance ),
			'css' => $this->css( $instance ),
		);

		if ( getenv( 'SOW_CAPTURE_FIXTURES' ) ) {
			self::$captured[ $key ] = $actual;
			$this->assertNotEmpty( $actual['css'] );

			return;
		}

		$fixture = self::fixture();

		$this->assertArrayHasKey( $key, $fixture['layouts'] );
		$this->assertSame( $fixture['layouts'][ $key ]['less_variables'], $actual['less_variables'] );
		$this->assertSame( $fixture['layouts'][ $key ]['css'], $actual['css'] );
	}

	private static function with_title_margins( $top, $bottom ) {
		return self::instance(
			array(
				'fonts' => array(
					'title_options' => array(
						'margin_top' => $top,
						'margin_bottom' => $bottom,
					),
				),
			)
		);
	}

	/**
	 * The declarations of the first rule whose selector ends with $selector.
	 */
	private function rule( $css, $selector ) {
		$this->assertSame( 1, preg_match( '/' . preg_quote( $selector, '/' ) . ' \{([^{}]*)\}/', $css, $match ), $selector . ' in ' . $css );

		return trim( $match[1] );
	}

	public function test_title_bottom_margin_only_sets_the_bottom_of_the_title() {
		$css = $this->css( self::with_title_margins( false, '8px' ) );
		$title = $this->rule( $css, '.sow-features-feature .sow-features-feature-title' );

		$this->assertStringContainsString( 'margin-bottom: 8px;', $title );
		$this->assertStringNotContainsString( 'margin-top', $title );
		$this->assertSame( 'margin: 1em 0;', $this->rule( $css, '.sow-features-feature h5' ) );
	}

	public function test_title_top_margin_only_sets_the_top_of_the_title() {
		$css = $this->css( self::with_title_margins( '0.5em', false ) );
		$title = $this->rule( $css, '.sow-features-feature .sow-features-feature-title' );

		$this->assertStringContainsString( 'margin-top: 0.5em;', $title );
		$this->assertStringNotContainsString( 'margin-bottom', $title );
	}

	public function test_zero_title_margin_is_kept() {
		$instance = self::with_title_margins( '0px', '0px' );
		$vars = $this->widget()->get_less_variables( $instance );
		$title = $this->rule( $this->css( $instance ), '.sow-features-feature .sow-features-feature-title' );

		$this->assertSame( '0px', $vars['title_margin_top'] );
		$this->assertSame( '0px', $vars['title_margin_bottom'] );
		$this->assertStringContainsString( 'margin-top: 0px;', $title );
		$this->assertStringContainsString( 'margin-bottom: 0px;', $title );
	}

	public function test_empty_title_margins_add_no_variable_and_no_declaration() {
		foreach ( array( false, '', null ) as $empty ) {
			$instance = self::with_title_margins( $empty, $empty );
			$vars = $this->widget()->get_less_variables( $instance );
			$title = $this->rule( $this->css( $instance ), '.sow-features-feature .sow-features-feature-title' );

			$this->assertArrayNotHasKey( 'title_margin_top', $vars );
			$this->assertArrayNotHasKey( 'title_margin_bottom', $vars );
			$this->assertStringNotContainsString( 'margin', $title );
		}
	}

	public function test_title_margin_fields_are_measurements_in_the_title_group() {
		$form = $this->widget()->get_widget_form();
		$fields = $form['fonts']['fields']['title_options']['fields'];

		$this->assertSame( array( 'tag', 'font', 'size', 'color', 'margin_top', 'margin_bottom' ), array_keys( $fields ) );

		foreach ( array( 'margin_top', 'margin_bottom' ) as $name ) {
			$this->assertSame( 'measurement', $fields[ $name ]['type'] );
			$this->assertSame( array( 'px', 'em', 'rem' ), $fields[ $name ]['units'] );
			$this->assertArrayNotHasKey( 'default', $fields[ $name ] );
		}

		$this->assertNotEmpty( $fields['margin_bottom']['description'] );
	}
}

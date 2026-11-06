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

	public function test_colors_section_names_each_part() {
		$design = $this->widget()->get_widget_form()['design']['fields'];
		$colors = $design['colors']['fields'];

		$this->assertSame(
			array(
				'testimonial_background' => 'Testimonial background',
				'text_background' => 'Text background',
				'text_color' => 'Text',
				'name_color' => 'Name',
				'location_color' => 'Location',
			),
			array_map(
				function ( $field ) {
					return $field['label'];
				},
				$colors
			)
		);

		foreach ( array( 'name_color', 'location_color' ) as $key ) {
			$this->assertSame( 'color', $colors[ $key ]['type'], $key );
			$this->assertArrayNotHasKey( 'default', $colors[ $key ], $key );
		}

		$this->assertSame( 'Text background radius', $design['border_radius']['label'] );
	}

	public function test_schema_offers_name_and_location_colors() {
		$schema = SiteOrigin_Widgets_Widget_Describer::single()->get_schema( $this->widget() );
		$colors = $schema['properties']['design']['properties']['colors']['properties'];

		foreach ( array( 'name_color' => 'Name', 'location_color' => 'Location' ) as $key => $title ) {
			$this->assertSame( 'string', $colors[ $key ]['type'], $key );
			$this->assertSame( 'color', $colors[ $key ]['format'], $key );
			$this->assertSame( $title, $colors[ $key ]['title'], $key );
		}
	}

	public static function part_colors() {
		return array(
			'name' => array( 'name_color' ),
			'location' => array( 'location_color' ),
		);
	}

	#[DataProvider( 'part_colors' )]
	public function test_a_set_part_color_is_passed_to_less( $key ) {
		$design = self::default_design();
		$design['colors'][ $key ] = '#a1b2c3';

		$vars = $this->widget()->get_less_variables( self::instance( $design ) );

		$this->assertSame( '#a1b2c3', $vars[ $key ] );
	}

	/**
	 * Selector => declarations of a widget's compiled CSS, without the widget class.
	 */
	private function rules( $instance ) {
		$css = $this->widget()->get_instance_css( $instance );
		$css = preg_replace( '/\.so-widget-sow-testimonials-default-[0-9a-f]{12} ?/', '', $css );

		preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $css, $matches, PREG_SET_ORDER );

		$rules = array();

		foreach ( $matches as $match ) {
			$selector = trim( preg_replace( '/\s+/', ' ', $match[1] ) );
			$rules[ $selector ][] = trim( preg_replace( '/\s+/', ' ', $match[2] ) );
		}

		return $rules;
	}

	public static function part_rules() {
		return array(
			'name' => array(
				'name_color',
				array(
					'.sow-testimonials .sow-testimonial-name',
					'.sow-testimonials .sow-text a:has(> .sow-testimonial-name)',
				),
			),
			'location' => array(
				'location_color',
				array(
					'.sow-testimonials .sow-testimonial-location',
					'.sow-testimonials .sow-text a:has(> .sow-testimonial-location)',
				),
			),
		);
	}

	#[DataProvider( 'part_rules' )]
	public function test_a_part_color_colors_its_text_and_its_link_only( $key, $selectors ) {
		$before = $this->rules( self::instance( self::default_design() ) );

		$design = self::default_design();
		$design['colors'][ $key ] = '#a1b2c3';
		$after = $this->rules( self::instance( $design ) );

		$added = array();

		foreach ( $after as $selector => $declarations ) {
			$existing = $before[ $selector ] ?? array();
			$new = array_values( array_diff( $declarations, $existing ) );

			if ( ! empty( $new ) ) {
				$added[ $selector ] = $new;
			}
		}

		$this->assertSame( array_fill_keys( $selectors, array( 'color: #a1b2c3;' ) ), $added );

		foreach ( $before as $selector => $declarations ) {
			$this->assertSame( $declarations, array_slice( $after[ $selector ], 0, count( $declarations ) ), $selector );
		}
	}

	public function test_link_and_text_rules_are_separate() {
		$design = self::default_design();
		$design['colors']['name_color'] = '#a1b2c3';

		$css = $this->widget()->get_instance_css( self::instance( $design ) );

		$this->assertDoesNotMatchRegularExpression( '/,[^{}]*:has\(|:has\([^{}]*,/', $css );
	}

	/**
	 * A re-saved widget stores an unset Name and Location as empty strings,
	 * and keeps the variables and CSS of the same widget without those keys.
	 */
	public function test_a_resaved_widget_keeps_its_variables_and_css() {
		$fixture = json_decode( file_get_contents( self::FIXTURE_FILE ), true );

		$design = self::default_design();
		$design['colors'] = array(
			'testimonial_background' => '#203040',
			'text_background' => '#101820',
			'text_color' => '#f6f6f6',
			'name_color' => '',
			'location_color' => '',
		);
		$instance = self::instance( $design );

		$this->assertSame( $fixture['instances']['custom colors']['less_variables'], $this->widget()->get_less_variables( $instance ) );
		$this->assertSame( $fixture['instances']['custom colors']['css'], $this->widget()->get_instance_css( $instance ) );
	}

	/**
	 * The template's markup for a testimonial with an image and a URL: the name
	 * and location spans are direct children of their links inside .sow-text,
	 * which is what the a:has(> ...) rules select.
	 */
	public function test_linked_name_and_location_match_the_link_rules() {
		foreach ( array( 'esc_url', 'wp_kses_post', 'sow_esc_url' ) as $passthrough ) {
			Functions\when( $passthrough )->returnArg();
		}

		Functions\when( 'siteorigin_widgets_get_attachment_image_src' )->justReturn( array( 'https://example.com/face.png', 50, 50 ) );

		$linked = self::testimonial( 'https://example.com' );
		$linked['image'] = 12;
		$instance = self::instance( self::default_design() );
		$instance['testimonials'] = array( $linked );

		$widget = $this->widget();
		$render = function ( $instance ) {
			$args = array( 'before_title' => '<h3>', 'after_title' => '</h3>' );
			extract( $this->get_template_variables( $instance, $args ) );
			ob_start();

			try {
				include __DIR__ . '/../widgets/testimonial/tpl/default.php';
			} finally {
				$html = ob_get_clean();
			}

			return $html;
		};
		$html = Closure::bind( $render, $widget, get_class( $widget ) )( $instance );

		$dom = new DOMDocument();
		$previous = libxml_use_internal_errors( true );

		try {
			$dom->loadHTML( '<?xml encoding="utf-8"?><div>' . $html . '</div>' );
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
		}

		$xpath = new DOMXPath( $dom );

		foreach ( array( 'sow-testimonial-name' => 'Sarah Mitchell', 'sow-testimonial-location' => 'Northfield Studio' ) as $part => $text ) {
			$links = $xpath->query( "//*[contains(concat(' ', @class, ' '), ' sow-text ')]//a[*[1][contains(concat(' ', @class, ' '), ' {$part} ')]]" );
			$this->assertSame( 1, $links->length, $part );
			$link = $links->item( 0 );
			$this->assertSame( 'https://example.com', $link->getAttribute( 'href' ), $part );

			// All of the link's visible text sits inside the colored span.
			$span = $xpath->query( '*[1]', $link )->item( 0 );
			$this->assertSame( $text, trim( $span->textContent ), $part );
			$this->assertSame( trim( $link->textContent ), trim( $span->textContent ), $part );
		}
	}

	public function test_an_empty_part_color_is_not_passed_to_less() {
		$design = self::default_design();
		$design['colors']['name_color'] = '';
		$design['colors']['location_color'] = false;

		$vars = $this->widget()->get_less_variables( self::instance( $design ) );

		$this->assertArrayNotHasKey( 'name_color', $vars );
		$this->assertArrayNotHasKey( 'location_color', $vars );
	}
}

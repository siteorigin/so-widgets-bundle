<?php

use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

require_once __DIR__ . '/fixtures/TestWidget.php';

if ( ! class_exists( 'SiteOrigin_Widgets_ContactForm_Widget' ) ) {
	require __DIR__ . '/../widgets/contact/contact.php';
}

if ( ! class_exists( 'SiteOrigin_Widget_Hero_Widget' ) ) {
	require __DIR__ . '/../widgets/hero/hero.php';
}

foreach ( array(
	'SiteOrigin_Widget_Anything_Carousel_Widget' => 'anything-carousel/anything-carousel.php',
	'SiteOrigin_Widgets_Testimonials_Widget' => 'testimonial/testimonial.php',
	'SiteOrigin_Widget_LayoutSlider_Widget' => 'layout-slider/layout-slider.php',
	'SiteOrigin_Widget_GoogleMap_Widget' => 'google-map/google-map.php',
	'SiteOrigin_Widget_SocialMediaButtons_Widget' => 'social-media-buttons/social-media-buttons.php',
) as $class => $file ) {
	if ( ! class_exists( $class ) ) {
		require __DIR__ . '/../widgets/' . $file;
	}
}

/**
 * Pins what SiteOrigin_Widget::update() stores for a fixed set of instances.
 *
 * tests/fixtures/update-outputs.json holds the output of the real update()
 * for every fixture below, captured on the develop branch before the
 * container-field changes. A re-save of a widget whose data is already
 * correct must store byte-identical data, so every fixture without an empty
 * string in a container slot is asserted equal to its capture. Fixtures that
 * do carry an empty-string container are allowed to differ only at those
 * paths. Throwables and PHP warnings are recorded as `__throwable` and
 * `__errors` entries so the pin covers them as well.
 *
 * Re-capture (only on a branch whose output is the intended baseline):
 *   SOW_CAPTURE_FIXTURES=1 vendor/bin/phpunit --filter UpdateOutputFixturesTest
 */
class UpdateOutputFixturesTest extends SiteOriginTests {
	private const FIXTURE_FILE = __DIR__ . '/fixtures/update-outputs.json';

	private static $captured = array();

	protected function setUp(): void {
		parent::setUp();

		$_SERVER['SERVER_NAME'] = 'localhost';
		SiteOrigin_Test_Widget::$modify_instance_callback = null;
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

	private static function test_widget_complete() {
		return array(
			'title' => 'Hello',
			'design' => array(
				'text' => 'body',
				'box' => array( 'color' => '#123456' ),
			),
			'shadow' => array( 'color' => '#000000', 'so_field_container_state' => 'open' ),
			'items' => array(
				array( 'text' => 'one', 'meta' => array( 'note' => 'a' ) ),
				array( 'text' => 'two', 'meta' => array( 'note' => 'b' ) ),
			),
			'button' => array( 'icon' => array( 'name' => 'star' ) ),
			'query' => 'post_type=post&posts_limit=3',
		);
	}

	private static function test_widget_with_containers( $value ) {
		$instance = self::test_widget_complete();

		foreach ( array( 'design', 'shadow', 'items', 'button' ) as $key ) {
			$instance[ $key ] = $value;
		}

		return $instance;
	}

	private static function load( $file ) {
		return json_decode( file_get_contents( __DIR__ . '/fixtures/instances/' . $file ), true );
	}

	private static function contact_complete() {
		$instance = self::load( 'contact-empty-success.json' );
		$instance['design']['success'] = array(
			'font'             => 'default',
			'font_size'        => '',
			'color'            => '#ffffff',
			'background_color' => '#4caf50',
			'padding'          => '15px',
			'border_color'     => '',
			'border_width'     => '',
			'border_style'     => 'solid',
		);

		return $instance;
	}

	/**
	 * Each row: fixture name, widget class, instance, and the container key
	 * paths (dot separated) that hold an empty string. A null instance means
	 * the widget's complete instance (every declared field at its default),
	 * built inside the test because data providers run before the WordPress
	 * stubs the widget constructors need are in place.
	 */
	public static function fixtures() {
		$rows = array();

		foreach ( array(
			'hero' => 'SiteOrigin_Widget_Hero_Widget',
			'anything-carousel' => 'SiteOrigin_Widget_Anything_Carousel_Widget',
			'testimonial' => 'SiteOrigin_Widgets_Testimonials_Widget',
			'layout-slider' => 'SiteOrigin_Widget_LayoutSlider_Widget',
			'google-map' => 'SiteOrigin_Widget_GoogleMap_Widget',
			'social-media-buttons' => 'SiteOrigin_Widget_SocialMediaButtons_Widget',
		) as $name => $class ) {
			$rows[ "$name, complete" ] = array( "$name-complete", $class, null, array() );
		}

		return $rows + array(
			'test widget, complete' => array(
				'test-widget-complete', 'SiteOrigin_Test_Widget', self::test_widget_complete(), array(),
			),
			'test widget, non-empty strings in container slots' => array(
				'test-widget-string-containers', 'SiteOrigin_Test_Widget', self::test_widget_with_containers( 'not-an-array' ), array(),
			),
			'test widget, zero in container slots' => array(
				'test-widget-zero-containers', 'SiteOrigin_Test_Widget', self::test_widget_with_containers( 0 ), array(),
			),
			'test widget, false in container slots' => array(
				'test-widget-false-containers', 'SiteOrigin_Test_Widget', self::test_widget_with_containers( false ), array(),
			),
			'test widget, empty strings in container slots' => array(
				'test-widget-empty-containers', 'SiteOrigin_Test_Widget', self::test_widget_with_containers( '' ),
				array( 'design', 'shadow', 'items', 'button' ),
			),
			'contact form, complete' => array(
				'contact-complete', 'SiteOrigin_Widgets_ContactForm_Widget', self::contact_complete(), array(),
			),
			'contact form, stored with an empty success section' => array(
				'contact-empty-success', 'SiteOrigin_Widgets_ContactForm_Widget', self::load( 'contact-empty-success.json' ),
				array( 'design.success' ),
			),
			'hero, stored with empty design and layout sections' => array(
				'hero-empty-design', 'SiteOrigin_Widget_Hero_Widget', self::load( 'hero-empty-design.json' ),
				array( 'design', 'layout', 'frames.0.buttons.0.button.button_icon' ),
			),
		);
	}

	#[DataProvider( 'fixtures' )]
	public function test_update_output_matches_the_captured_baseline( $name, $class, $instance, $empty_paths ) {
		$widget = new $class();

		if ( $instance === null ) {
			$instance = self::complete_instance( $widget );
		}

		list( $output, $errors ) = $this->run_capturing_errors(
			function () use ( $widget, $instance ) {
				try {
					return $widget->update( $instance, $instance );
				} catch ( \TypeError $e ) {
					// Recorded so the baseline pins which fixtures fatal.
					return array( '__throwable' => get_class( $e ) . ': ' . $e->getMessage() );
				}
			}
		);

		unset( $output['_sow_form_timestamp'] );

		if ( ! empty( $errors ) ) {
			// Warnings are part of the pinned behaviour too.
			$output['__errors'] = array_values( array_unique( $errors ) );
		}
		// json_decode() of the capture yields nested arrays, so compare through
		// the same encoding the file holds.
		$output = json_decode( json_encode( $output ), true );

		if ( getenv( 'SOW_CAPTURE_FIXTURES' ) ) {
			self::$captured[ $name ] = $output;
			$this->assertIsArray( $output );

			return;
		}

		$this->assertFileExists( self::FIXTURE_FILE );
		$baseline = json_decode( file_get_contents( self::FIXTURE_FILE ), true );
		$this->assertArrayHasKey( $name, $baseline, 'Fixture missing from update-outputs.json; re-capture on the baseline branch.' );

		if ( empty( $empty_paths ) ) {
			$this->assertSame( $baseline[ $name ], $output );

			return;
		}

		if ( isset( $baseline[ $name ]['__throwable'] ) ) {
			// The baseline fataled on this shape, so there is no stored output
			// to compare against: the requirement is that it now stores, with
			// every empty-string container repaired to an array.
			$this->assertArrayNotHasKey( '__throwable', $output );

			foreach ( $empty_paths as $path ) {
				$value = $output;

				foreach ( explode( '.', $path ) as $key ) {
					$this->assertArrayHasKey( $key, $value, "expected '$path' in the stored output" );
					$value = $value[ $key ];
				}

				$this->assertIsArray( $value, "'$path' must be stored as an array" );
			}

			return;
		}

		foreach ( $empty_paths as $path ) {
			$value = $output;

			foreach ( explode( '.', $path ) as $key ) {
				$this->assertArrayHasKey( $key, $value, "expected '$path' in the stored output" );
				$value = $value[ $key ];
			}

			$this->assertIsArray( $value, "'$path' must be stored as an array" );
		}

		$diff = array();
		self::diff_paths( $baseline[ $name ], $output, '', $diff );

		foreach ( $diff as $path ) {
			$inside = $path === '__errors';

			foreach ( $empty_paths as $allowed ) {
				if ( $path === $allowed || strpos( $path, $allowed . '.' ) === 0 ) {
					$inside = true;
				}
			}

			$this->assertTrue( $inside, "update() output differs from the baseline at '$path', outside the empty-string containers." );
		}
	}

	/**
	 * Every declared field at its default, with one defaulted row in every
	 * repeater at any depth: add_defaults() alone never creates a repeater,
	 * and an absent repeater is exactly the shape whose stored value the
	 * change under test moves.
	 */
	private static function complete_instance( $widget ) {
		$form = $widget->form_options();

		return self::fill_repeaters( $widget, $form, $widget->add_defaults( $form, array() ) );
	}

	private static function fill_repeaters( $widget, $form, $instance ) {
		foreach ( $form as $id => $field ) {
			if ( ! is_array( $field ) || empty( $field['type'] ) ) {
				continue;
			}

			if ( $field['type'] === 'repeater' ) {
				$fields = $field['fields'] ?? array();
				$row    = self::fill_repeaters( $widget, $fields, $widget->add_defaults( $fields, array() ) );

				$instance[ $id ] = array( $row );
			} elseif ( in_array( $field['type'], array( 'section', 'toggle' ), true ) && isset( $instance[ $id ] ) && is_array( $instance[ $id ] ) ) {
				$instance[ $id ] = self::fill_repeaters( $widget, $field['fields'] ?? array(), $instance[ $id ] );
			}
		}

		return $instance;
	}

	private static function diff_paths( $a, $b, $prefix, &$acc ) {
		$keys = array_unique( array_merge( array_keys( (array) $a ), array_keys( (array) $b ) ) );

		foreach ( $keys as $key ) {
			$path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
			$x = is_array( $a ) && array_key_exists( $key, $a ) ? $a[ $key ] : "\0absent";
			$y = is_array( $b ) && array_key_exists( $key, $b ) ? $b[ $key ] : "\0absent";

			if ( is_array( $x ) && is_array( $y ) ) {
				self::diff_paths( $x, $y, $path, $acc );
			} elseif ( $x !== $y ) {
				$acc[] = $path;
			}
		}
	}
}

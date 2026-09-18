<?php

use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * Every widget whose modify_instance() writes through a section, or whose
 * get_less_variables() is reached from the save path with raw section
 * reads, through the real SiteOrigin_Widget::update() with a container
 * stored as an empty string. Each save must be non-fatal and silent and
 * store the container as an array. A widget whose modify_instance()
 * fills defaults into a section it finds non-empty does that on the save
 * after the one that repaired the section, so the saved data is asserted
 * to reach a fixed point by the second save: the third save is
 * byte-identical to the second.
 *
 * For the widgets whose reads were coalesced, get_less_variables() of a
 * complete instance is pinned against tests/fixtures/less-variables.json,
 * captured before the coalescing, so the change cannot move a CSS hash for
 * an instance whose values are present.
 *
 * Re-capture (only on a branch whose output is the intended baseline):
 *   SOW_CAPTURE_FIXTURES=1 vendor/bin/phpunit --filter WidgetStringContainerTest
 */
class WidgetStringContainerTest extends SiteOriginTests {
	private const FIXTURE_FILE = __DIR__ . '/fixtures/less-variables.json';

	private static $captured = array();

	/**
	 * Widget class, file, and the dot paths of containers to store as ''.
	 */
	private const WIDGETS = array(
		'hero' => array(
			'SiteOrigin_Widget_Hero_Widget', 'hero/hero.php',
			array( 'design', 'layout', 'controls', 'frames', 'frames.0' ),
		),
		'anything-carousel' => array(
			'SiteOrigin_Widget_Anything_Carousel_Widget', 'anything-carousel/anything-carousel.php',
			array( 'design', 'design.item_title', 'design.navigation', 'responsive', 'responsive.tablet', 'carousel_settings', 'items' ),
		),
		'testimonial' => array(
			'SiteOrigin_Widgets_Testimonials_Widget', 'testimonial/testimonial.php',
			array( 'design', 'design.colors', 'settings', 'settings.responsive', 'settings.responsive.tablet', 'testimonials' ),
		),
		'layout-slider' => array(
			'SiteOrigin_Widget_LayoutSlider_Widget', 'layout-slider/layout-slider.php',
			array( 'controls', 'layout', 'design', 'frames' ),
		),
		'google-map' => array(
			'SiteOrigin_Widget_GoogleMap_Widget', 'google-map/google-map.php',
			array( 'settings', 'markers' ),
		),
		'social-media-buttons' => array(
			'SiteOrigin_Widget_SocialMediaButtons_Widget', 'social-media-buttons/social-media-buttons.php',
			array( 'design', 'networks', 'networks.0' ),
		),
		'features' => array(
			'SiteOrigin_Widget_Features_Widget', 'features/features.php',
			array( 'fonts', 'features' ),
		),
		'button' => array(
			'SiteOrigin_Widget_Button_Widget', 'button/button.php',
			array( 'design', 'button_icon', 'attributes' ),
		),
		'slider' => array(
			'SiteOrigin_Widget_Slider_Widget', 'slider/slider.php',
			array( 'controls', 'design', 'frames' ),
		),
		'video' => array(
			'SiteOrigin_Widget_Video_Widget', 'video/video.php',
			array( 'playback', 'video' ),
		),
		'headline' => array(
			'SiteOrigin_Widget_Headline_Widget', 'headline/headline.php',
			array( 'headline', 'sub_headline', 'divider' ),
		),
	);

	/**
	 * Widgets whose get_less_variables() reads were coalesced.
	 */
	private const COALESCED = array(
		'hero', 'anything-carousel', 'testimonial', 'layout-slider', 'google-map', 'social-media-buttons',
	);

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

	private static function widget( $name ) {
		list( $class, $file ) = self::WIDGETS[ $name ];

		if ( ! class_exists( $class ) ) {
			require_once __DIR__ . '/../widgets/' . $file;
		}

		return new $class();
	}

	/**
	 * A complete instance: every declared field at its default. A legacy
	 * top-level key is planted for the Slider so its controls migration in
	 * modify_instance() has something to move.
	 */
	private static function complete_instance( $name, $widget ) {
		$instance = $widget->add_defaults( $widget->form_options(), array() );

		if ( $name === 'slider' ) {
			$instance['speed'] = 800;
		}

		return $instance;
	}

	private static function with_empty( $instance, $path ) {
		$ref = &$instance;

		foreach ( explode( '.', $path ) as $key ) {
			if ( ! is_array( $ref ) ) {
				$ref = array();
			}

			$ref = &$ref[ $key ];
		}
		$ref = '';

		return $instance;
	}

	private static function read_path( $instance, $path ) {
		foreach ( explode( '.', $path ) as $key ) {
			$instance = $instance[ $key ];
		}

		return $instance;
	}

	public static function empty_container_cases() {
		$cases = array();

		foreach ( self::WIDGETS as $name => $widget ) {
			foreach ( $widget[2] as $path ) {
				$cases[ "$name: $path" ] = array( $name, $path );
			}
		}

		return $cases;
	}

	#[DataProvider( 'empty_container_cases' )]
	public function test_saving_a_stored_empty_container_is_silent_and_repairs_it( $name, $path ) {
		$widget   = self::widget( $name );
		$instance = self::with_empty( self::complete_instance( $name, $widget ), $path );

		list( $stored, $errors ) = $this->run_capturing_errors( fn() => $widget->update( $instance, $instance ) );
		unset( $stored['_sow_form_timestamp'] );

		$this->assertSame( array(), $errors, "$name with $path stored as '' must save silently" );
		$this->assertIsArray( self::read_path( $stored, $path ), "$path must be stored as an array" );

		list( $second, $errors ) = $this->run_capturing_errors( fn() => $widget->update( $stored, $stored ) );
		unset( $second['_sow_form_timestamp'] );
		$this->assertSame( array(), $errors );

		list( $third, $errors ) = $this->run_capturing_errors( fn() => $widget->update( $second, $second ) );
		unset( $third['_sow_form_timestamp'] );

		$this->assertSame( $second, $third, 'saved data must reach a fixed point by the second save' );
		$this->assertSame( array(), $errors );
	}

	public static function coalesced_widgets() {
		$cases = array();

		foreach ( self::COALESCED as $name ) {
			$cases[ $name ] = array( $name );
		}

		return $cases;
	}

	#[DataProvider( 'coalesced_widgets' )]
	public function test_less_variables_of_a_complete_instance_are_unchanged( $name ) {
		$widget   = self::widget( $name );
		$instance = self::complete_instance( $name, $widget );

		list( $vars, $errors ) = $this->run_capturing_errors( fn() => $widget->get_less_variables( $instance ) );
		$vars = json_decode( json_encode( $vars ), true );

		$this->assertSame( array(), $errors );

		if ( getenv( 'SOW_CAPTURE_FIXTURES' ) ) {
			self::$captured[ $name ] = $vars;
			$this->assertIsArray( $vars );

			return;
		}

		$this->assertFileExists( self::FIXTURE_FILE );
		$baseline = json_decode( file_get_contents( self::FIXTURE_FILE ), true );
		$this->assertArrayHasKey( $name, $baseline );
		$this->assertSame( $baseline[ $name ], $vars );
	}
}

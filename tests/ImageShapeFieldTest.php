<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * Tests for the Image widget's shape handling (#2370).
 *
 * The shape picker's hidden input is only populated when a shape is clicked,
 * so enabling the section on its default preview submits ''. The field must
 * default that to 'circle' on save, and the widget must render a circle for
 * instances stored with '' before that default existed.
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

if ( ! class_exists( 'SiteOrigin_Widget_Image_Shapes' ) ) {
	require __DIR__ . '/../base/inc/shapes/shapes.php';
}

if ( ! class_exists( 'SiteOrigin_Widget_Field_Image_Shape' ) ) {
	require __DIR__ . '/../base/inc/fields/image-shape.class.php';
}

if ( ! class_exists( 'SiteOrigin_Widget_Image_Widget' ) ) {
	require __DIR__ . '/../widgets/image/image.php';
}

class ImageShapeFieldTest extends SiteOriginTests {
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_parse_args' )->alias(
			function ( $args, $defaults ) {
				return array_merge( $defaults, (array) $args );
			}
		);
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'plugin_dir_url' )->justReturn( 'https://example.com/shapes/' );
		Functions\when( 'wp_normalize_path' )->returnArg();

		// shapes.php registers setup_shapes() on init; there is no init here.
		SiteOrigin_Widget_Image_Shapes::single()->setup_shapes();
	}

	private function field() {
		return new SiteOrigin_Widget_Field_Image_Shape( 'shape', 'shape', 'shape', array( 'type' => 'image_shape' ) );
	}

	private function widget() {
		return new SiteOrigin_Widget_Image_Widget();
	}

	private function instance( $image_shape ) {
		return array(
			'align' => 'default',
			'size' => 'full',
			'image_shape' => $image_shape,
		);
	}

	public static function empty_values() {
		return array(
			'empty string' => array( '' ),
			'null' => array( null ),
		);
	}

	#[DataProvider( 'empty_values' )]
	public function test_field_defaults_an_empty_value_to_circle( $value ) {
		$this->assertSame( 'circle', $this->field()->sanitize( $value ) );
	}

	public function test_field_defaults_an_unknown_shape_to_circle() {
		$this->assertSame( 'circle', $this->field()->sanitize( 'bogus' ) );
	}

	public function test_field_keeps_a_registered_shape() {
		$this->assertSame( 'hexagon', $this->field()->sanitize( 'hexagon' ) );
	}

	public function test_widget_renders_a_circle_for_a_stored_empty_shape() {
		$vars = $this->widget()->get_less_variables(
			$this->instance( array( 'enable' => true, 'shape' => '' ) )
		);

		$this->assertSame( 'url( "https://example.com/shapes/images/circle.svg" )', $vars['image_shape'] );
		$this->assertSame( 'contain', $vars['image_shape_size'] );
	}

	public function test_widget_renders_a_circle_when_shape_key_is_missing() {
		$vars = $this->widget()->get_less_variables(
			$this->instance( array( 'enable' => true ) )
		);

		$this->assertSame( 'url( "https://example.com/shapes/images/circle.svg" )', $vars['image_shape'] );
	}

	public function test_widget_renders_the_stored_shape() {
		$vars = $this->widget()->get_less_variables(
			$this->instance( array( 'enable' => true, 'shape' => 'hexagon', 'size' => '50px' ) )
		);

		$this->assertSame( 'url( "https://example.com/shapes/images/hexagon.svg" )', $vars['image_shape'] );
		$this->assertSame( '50px', $vars['image_shape_size'] );
	}

	public static function disabled_sections() {
		return array(
			'section missing' => array( null ),
			'section stored as string' => array( '' ),
			'enable stored as empty string' => array( array( 'enable' => '', 'shape' => 'circle' ) ),
			'enable false' => array( array( 'enable' => false, 'shape' => 'circle' ) ),
		);
	}

	#[DataProvider( 'disabled_sections' )]
	public function test_widget_emits_no_mask_when_the_section_is_off( $image_shape ) {
		$vars = $this->widget()->get_less_variables( $this->instance( $image_shape ) );

		$this->assertArrayNotHasKey( 'image_shape', $vars );
	}

	public static function unregistered_shapes() {
		return array(
			'unknown name' => array( 'bogus' ),
			'falsey string' => array( '0' ),
		);
	}

	#[DataProvider( 'unregistered_shapes' )]
	public function test_widget_emits_no_mask_for_an_unregistered_shape( $shape ) {
		$vars = $this->widget()->get_less_variables(
			$this->instance( array( 'enable' => true, 'shape' => $shape ) )
		);

		$this->assertArrayNotHasKey( 'image_shape', $vars );
	}

	public function test_field_passes_the_defaulted_value_through_the_base_sanitizer() {
		$field = new SiteOrigin_Widget_Field_Image_Shape(
			'shape',
			'shape',
			'shape',
			array( 'type' => 'image_shape', 'sanitize' => 'strtoupper' )
		);

		$this->assertSame( 'CIRCLE', $field->sanitize( '' ) );
	}
}

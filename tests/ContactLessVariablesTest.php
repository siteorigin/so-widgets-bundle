<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * Unit tests for the Contact Form widget's handling of malformed design data.
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
 * contact.php registers a filter hook at the top level while the file is being
 * required, before any test has a chance to set Brain Monkey up. add_filter has
 * to exist as a real function by then — the same pattern CacheCompatTest uses
 * for add_action().
 */
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter() {
		return true;
	}
}

if ( ! class_exists( 'SiteOrigin_Widgets_ContactForm_Widget' ) ) {
	require __DIR__ . '/../widgets/contact/contact.php';
}

class ContactLessVariablesTest extends SiteOriginTests {
	/**
	 * The design sections the widget owns.
	 */
	private const SECTIONS = array(
		'container',
		'labels',
		'fields',
		'descriptions',
		'errors',
		'submit',
		'focus',
		'success',
	);

	/**
	 * PHP errors captured while the code under test runs.
	 */
	private $php_errors = array();

	protected function setUp(): void {
		parent::setUp();

		// The real helper looks the value up as an array key, so handing it a
		// non-scalar is a fatal on PHP 8. Mirror that here: a permissive stub would
		// hide exactly the bug this suite is meant to catch.
		Functions\when( 'siteorigin_widget_get_font' )->alias(
			function ( $font_value = '' ) {
				if ( ! is_scalar( $font_value ) ) {
					throw new \TypeError( 'Cannot access offset of type ' . gettype( $font_value ) . ' in isset or empty' );
				}

				return array(
					'family'     => '',
					'weight'     => '',
					'weight_raw' => '',
					'style'      => '',
				);
			}
		);

		Functions\when( 'wp_get_current_user' )->alias(
			function () {
				return (object) array( 'user_email' => 'current@example.com' );
			}
		);

		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'sanitize_html_class' )->returnArg();
		Functions\when( 'siteorigin_sanitize_attribute_key' )->returnArg();
		Functions\when( 'sanitize_title' )->returnArg();
		Functions\when( 'wp_nonce_field' )->justReturn( '' );
		Functions\when( 'checked' )->justReturn( '' );
		Functions\when( 'selected' )->justReturn( '' );
		Functions\when( 'add_query_arg' )->returnArg();
		Functions\when( 'esc_textarea' )->returnArg();

		// name_from_label() builds ids against a global register; reset it so the
		// ids a test sees do not depend on the tests that ran before it.
		$GLOBALS['field_ids'] = array();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['field_ids'] );

		parent::tearDown();
	}

	private function widget() {
		return new SiteOrigin_Widgets_ContactForm_Widget();
	}

	/**
	 * A complete instance, so a case can only fail on the design shape under
	 * test rather than on an unrelated missing key. settings.to and settings.from
	 * are populated so modify_instance() does not reach for the current user or
	 * the server name.
	 */
	private function base_instance() {
		return array(
			'title'    => 'Contact',
			'settings' => array(
				'to'                       => 'to@example.com',
				'from'                     => 'from@example.com',
				'required_field_indicator' => false,
				'on_click'                 => '',
			),
			'fields'   => array(
				array( 'type' => 'text', 'label' => 'Your Name' ),
			),
		);
	}

	/**
	 * Runs a callable while recording warnings, notices and deprecations, so a
	 * test can assert the guards keep the whole call silent.
	 */
	private function capturing_errors( callable $fn ) {
		$this->php_errors = array();

		set_error_handler(
			function ( $errno, $errstr ) {
				$this->php_errors[] = $errstr;

				return true;
			},
			E_WARNING | E_NOTICE | E_DEPRECATED
		);

		try {
			return $fn();
		} finally {
			restore_error_handler();
		}
	}

	private function get_less_variables_capturing_errors( $instance ) {
		return $this->capturing_errors(
			function () use ( $instance ) {
				return $this->widget()->get_less_variables( $instance );
			}
		);
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
		$vars = $this->get_less_variables_capturing_errors( $this->healthy_instance() );

		$this->assertSame( '#f2f2f2', $vars['container_background'] );
		$this->assertSame( '16px', $vars['label_font_size'] );
		$this->assertSame( '14px', $vars['field_font_size'] );
		$this->assertSame( '3px', $vars['field_border_radius'] );
		$this->assertSame( '3px', $vars['submit_border_radius'] );
		$this->assertSame( 'default', $vars['label_position'] );
		$this->assertSame( array(), $this->php_errors );
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

		// Every design lookup coalesces, so a missing section resolves to an
		// empty string rather than throwing an offset-of-string TypeError.
		$this->assertSame( '', $vars['field_font_size'] );
		$this->assertSame( '', $vars['container_background'] );
		$this->assertSame( array(), $this->php_errors );
	}

	/**
	 * Every malformed shape a stored design can take, through every method that
	 * reads it. A section absent from the submitted form is stored as an empty
	 * string, and the design itself arrives that way too, so both forms are
	 * covered for the whole design and for each section individually.
	 */
	#[DataProvider( 'corrupt_shapes' )]
	public function test_corrupt_design_never_fatals_in_get_less_variables( $design ) {
		$instance = $this->instance_with_design( $design );

		$throwable = null;
		$vars      = null;

		try {
			$vars = $this->get_less_variables_capturing_errors( $instance );
		} catch ( \Throwable $e ) {
			$throwable = $e;
		}

		$this->assertNull( $throwable, 'get_less_variables() threw: ' . ( $throwable ? $throwable->getMessage() : '' ) );
		$this->assertSame( array(), $this->php_errors );

		// Silence alone would also be satisfied by a method that bailed out and
		// returned nothing, so assert the variables it is supposed to produce.
		if ( empty( $instance['design'] ) ) {
			// The existing early return: nothing to build variables from.
			$this->assertNull( $vars );

			return;
		}

		$this->assertIsArray( $vars );

		foreach ( array( 'container_background', 'field_font_size', 'label_font_size', 'outline_style' ) as $key ) {
			$this->assertArrayHasKey( $key, $vars );
			$this->assertSame( '', $vars[ $key ], "$key should fall back to an empty string" );
		}

		// Values built by concatenation keep their unit rather than becoming empty.
		$this->assertSame( 'px', $vars['field_border_radius'] );
	}

	#[DataProvider( 'corrupt_shapes' )]
	public function test_corrupt_design_never_fatals_in_modify_instance( $design ) {
		$instance = $this->instance_with_design( $design );

		$throwable = null;
		$modified  = null;

		try {
			$modified = $this->capturing_errors(
				function () use ( $instance ) {
					return $this->widget()->modify_instance( $instance );
				}
			);
		} catch ( \Throwable $e ) {
			$throwable = $e;
		}

		$this->assertNull( $throwable, 'modify_instance() threw: ' . ( $throwable ? $throwable->getMessage() : '' ) );
		$this->assertSame( array(), $this->php_errors );

		// A design that was set is repaired; absent and null are left as found,
		// because every read of them coalesces.
		if ( isset( $instance['design'] ) ) {
			$this->assertIsArray( $modified['design'] );

			foreach ( self::SECTIONS as $section ) {
				$this->assertIsArray(
					$modified['design'][ $section ],
					"design.$section should have been normalised to an array"
				);
			}
		} elseif ( array_key_exists( 'design', $instance ) ) {
			$this->assertNull( $modified['design'] );
		} else {
			$this->assertArrayNotHasKey( 'design', $modified );
		}
	}

	#[DataProvider( 'corrupt_shapes' )]
	public function test_corrupt_design_never_fatals_in_render_form_fields( $design ) {
		$instance = $this->instance_with_design( $design );

		$throwable = null;
		$html      = null;

		try {
			$html = $this->capturing_errors(
				function () use ( $instance ) {
					ob_start();

					try {
						$this->widget()->render_form_fields( $instance['fields'], array(), $instance );

						return ob_get_contents();
					} finally {
						ob_end_clean();
					}
				}
			);
		} catch ( \Throwable $e ) {
			$throwable = $e;
		}

		$this->assertNull( $throwable, 'render_form_fields() threw: ' . ( $throwable ? $throwable->getMessage() : '' ) );
		$this->assertSame( array(), $this->php_errors );

		// A method that rendered nothing would also be silent, so assert the field
		// reached the markup and carried a label position class.
		$this->assertIsString( $html );
		$this->assertStringContainsString( 'sow-form-field', $html );
		$this->assertStringContainsString( 'Your Name', $html );

		// No malformed shape carries a usable label position, so every one of them
		// must fall back to the documented default rather than emit an empty class.
		$this->assertStringContainsString( 'sow-form-field-label-above', $html );
	}

	/**
	 * The section container state is written into design by the form itself and
	 * is legitimately a string. Normalisation must leave keys it does not own.
	 */
	public function test_normalisation_preserves_keys_the_widget_does_not_own() {
		$instance                                       = $this->base_instance();
		$instance['design']                             = array( 'labels' => '' );
		$instance['design']['so_field_container_state'] = 'closed';

		$modified = $this->widget()->modify_instance( $instance );

		$this->assertSame( 'closed', $modified['design']['so_field_container_state'] );
		$this->assertIsArray( $modified['design']['labels'] );
	}

	/**
	 * A fully populated design must be returned untouched, so normalisation cannot
	 * disturb a widget that was already well formed.
	 *
	 * The style hash this protects is built by the widget base class, which these
	 * unit tests stand in for rather than load, so hash equality is proven outside
	 * the suite against a real install.
	 */
	public function test_healthy_design_is_returned_unchanged() {
		$instance = $this->healthy_instance();

		$modified = $this->widget()->modify_instance( $instance );

		$this->assertSame( $instance['design'], $modified['design'] );
	}

	public static function corrupt_shapes() {
		$shapes = array(
			'design absent'       => array( '__ABSENT__' ),
			'design null'         => array( null ),
			'design empty string' => array( '' ),
			'design empty array'  => array( array() ),
			'design zero string'  => array( '0' ),
			'design string'       => array( 'corrupt-string' ),
			'design bool'         => array( true ),
			'design object'       => array( new \stdClass() ),
		);

		foreach ( self::SECTIONS as $section ) {
			$shapes[ "section $section empty string" ] = array( array( $section => '' ) );
			$shapes[ "section $section string" ]       = array( array( $section => 'corrupt-string' ) );
		}

		// A leaf value can be malformed even when its section is a proper array.
		// The design form offers only scalars, so an array or object here is
		// meaningless, and both are fatal downstream: fonts are used as an array
		// key, and measurements are concatenated into CSS.
		foreach ( array( 'labels', 'fields', 'success' ) as $section ) {
			$shapes[ "section $section font empty array" ] = array( array( $section => array( 'font' => array() ) ) );
			$shapes[ "section $section font array" ]       = array( array( $section => array( 'font' => array( 'Arial' ) ) ) );
		}

		// A boolean position used to pass the renderer's non-strict validation,
		// because true loosely equals every non-empty string.
		$shapes['labels position bool']  = array( array( 'labels' => array( 'position' => true ) ) );
		$shapes['submit styled object']  = array( array( 'submit' => array( 'styled' => new \stdClass() ) ) );

		foreach ( array(
			'fields border_radius' => array( 'fields' => array( 'border_radius' => null ) ),
			'submit border_radius' => array( 'submit' => array( 'border_radius' => null ) ),
			'submit gradient'      => array( 'submit' => array( 'background_gradient' => null ) ),
			'submit highlight'     => array( 'submit' => array( 'inset_highlight' => null ) ),
			'labels position'      => array( 'labels' => array( 'position' => null ) ),
			'container background' => array( 'container' => array( 'background' => null ) ),
		) as $name => $template ) {
			$section = key( $template );
			$setting = key( $template[ $section ] );

			$shapes[ "leaf $name array" ]  = array( array( $section => array( $setting => array( 'x' ) ) ) );
			$shapes[ "leaf $name object" ] = array( array( $section => array( $setting => new \stdClass() ) ) );
		}

		return $shapes;
	}

	private function instance_with_design( $design ) {
		$instance = $this->base_instance();

		if ( $design === '__ABSENT__' ) {
			unset( $instance['design'] );

			return $instance;
		}

		$instance['design'] = $design;

		return $instance;
	}

	/**
	 * An instance whose design carries a value for every key the widget reads.
	 */
	private function healthy_instance() {
		$instance = $this->base_instance();

		$instance['design'] = array(
			'container'    => array(
				'background'   => '#f2f2f2',
				'padding'      => '10px',
				'border_color' => '#c0c0c0',
				'border_width' => '1px',
				'border_style' => 'solid',
			),
			'labels'       => array(
				'font'     => 'Arial',
				'size'     => '16px',
				'color'    => '#000000',
				'position' => 'above',
				'width'    => '120px',
				'align'    => 'left',
			),
			'fields'       => array(
				'font'            => '',
				'font_size'       => '14px',
				'color'           => '#333333',
				'multi_margin'    => '0px 0px 15px 0px',
				'padding'         => '10px',
				'height'          => '40px',
				'background'      => '#ffffff',
				'border_radius'   => '3',
				'max_width'       => '',
				'height_textarea' => '',
			),
			'descriptions' => array(
				'size'  => '12px',
				'color' => '#666666',
				'style' => 'italic',
			),
			'errors'       => array(
				'background'   => '#fce4e4',
				'border_color' => '#cc0000',
				'text_color'   => '#cc0000',
				'padding'      => '10px',
				'margin'       => '10px',
			),
			'submit'       => array(
				'background_color'   => '#eeeeee',
				'background_gradient' => '10',
				'border_color'       => '#cccccc',
				'border_style'       => 'solid',
				'border_width'       => '1px',
				'border_radius'      => '3',
				'text_color'         => '#000000',
				'font_size'          => '14px',
				'weight'             => 'normal',
				'padding'            => '10px',
				'inset_highlight'    => '50',
				'styled'             => true,
			),
			'focus'        => array(
				'style' => 'solid',
				'color' => '#3498db',
				'width' => '2px',
			),
			'success'      => array(
				'font_size'        => '14px',
				'color'            => '#000000',
				'background_color' => '#eafae4',
				'padding'          => '10px',
				'border_width'     => '1px',
				'border_color'     => '#5cb85c',
				'border_style'     => 'solid',
				'font'             => '',
			),
		);

		return $instance;
	}
}

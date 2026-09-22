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
	private static function base_instance() {
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

	/**
	 * A design as the form actually stores it: blank measurement and colour fields
	 * are false, sliders are floats and the checkbox is a bool. The LESS variables
	 * must come out exactly as the branch produced them before the guards were
	 * added, type for type, because the style hash is built from this array and
	 * any change to it makes every site regenerate its CSS.
	 */
	public function test_stored_design_keeps_its_less_variables() {
		$vars = $this->get_less_variables_capturing_errors( self::stored_instance() );

		$this->assertSame( array(), $this->php_errors );
		$this->assertSame( $this->stored_instance_less_variables(), $vars );
	}

	/**
	 * The hash itself, not only its inputs. Both literals are what
	 * get_style_hash() returned at 19820b78 for the same fixtures, measured
	 * against the real install with the five contact filters removed. A widget
	 * whose hash moves regenerates its CSS on every site that stores that shape.
	 */
	#[DataProvider( 'stored_design_hashes' )]
	public function test_stored_design_keeps_its_style_hash( $instance, $hash ) {
		$this->assertSame(
			$hash,
			$this->capturing_errors(
				function () use ( $instance ) {
					return $this->widget()->get_style_hash( $instance );
				}
			)
		);
		$this->assertSame( array(), $this->php_errors );
	}

	public static function stored_design_hashes() {
		$left = self::stored_instance();

		$left['design']['labels']['position']     = 'left';
		$left['design']['labels']['font']         = 'Roboto:700';
		$left['design']['fields']['border_width'] = false;

		return array(
			'labels above, default fonts, field border' => array( self::stored_instance(), '2cebac97cd02' ),
			'labels left, Roboto:700, no field border'  => array( $left, '96deef3b8c7f' ),
		);
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
		$instance                                       = self::base_instance();
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
		$instance = self::base_instance();

		if ( $design === '__ABSENT__' ) {
			unset( $instance['design'] );

			return $instance;
		}

		$instance['design'] = $design;

		return $instance;
	}

	/**
	 * An instance shaped the way a real save stores it. Measurement and colour
	 * fields left blank are stored as false, sliders as floats, the checkbox as a
	 * bool, and every section carries its container state.
	 */
	private static function stored_instance() {
		$instance = self::base_instance();

		$instance['design'] = array(
			'container'                => array(
				'background'               => '#f2f2f2',
				'padding'                  => '10px',
				'border_color'             => '#c0c0c0',
				'border_width'             => '1px',
				'border_style'             => 'solid',
				'so_field_container_state' => 'closed',
			),
			'labels'                   => array(
				'font'                     => 'default',
				'size'                     => false,
				'color'                    => false,
				'position'                 => 'above',
				'width'                    => false,
				'align'                    => 'left',
				'so_field_container_state' => 'closed',
			),
			'fields'                   => array(
				'font'                     => 'default',
				'font_size'                => false,
				'color'                    => false,
				'multi_margin'             => '0px 0px 15px 0px',
				'padding'                  => false,
				'max_width'                => false,
				'height'                   => false,
				'height_textarea'          => false,
				'background'               => false,
				'border_color'             => '#c0c0c0',
				'border_width'             => '1px',
				'border_style'             => 'solid',
				'border_radius'            => 0.0,
				'so_field_container_state' => 'closed',
			),
			'descriptions'             => array(
				'size'                     => '0.9em',
				'color'                    => '#999999',
				'style'                    => 'italic',
				'top_margin'               => '0.2em',
				'so_field_container_state' => 'closed',
			),
			'errors'                   => array(
				'background'               => '#fce4e5',
				'border_color'             => '#ec666a',
				'text_color'               => '#ec666a',
				'padding'                  => '5px',
				'margin'                   => '10px',
				'so_field_container_state' => 'closed',
			),
			'submit'                   => array(
				'styled'                   => true,
				'background_color'         => '#eeeeee',
				'background_color_hover'   => false,
				'background_gradient'      => 10.0,
				'border_color'             => '#989a9c',
				'border_color_hover'       => false,
				'border_style'             => 'solid',
				'border_width'             => '1px',
				'border_radius'            => 3.0,
				'text_color'               => '#5a5a5a',
				'text_color_hover'         => false,
				'font_size'                => false,
				'weight'                   => '500',
				'padding'                  => '10px',
				'width'                    => false,
				'align'                    => 'left',
				'inset_highlight'          => 50.0,
				'so_field_container_state' => 'closed',
			),
			'focus'                    => array(
				'style'                    => 'solid',
				'color'                    => false,
				'width'                    => '1px',
				'so_field_container_state' => 'closed',
			),
			'success'                  => array(
				'font'                     => 'default',
				'font_size'                => false,
				'color'                    => false,
				'background_color'         => false,
				'padding'                  => false,
				'border_width'             => false,
				'border_color'             => false,
				'border_style'             => 'solid',
				'so_field_container_state' => 'closed',
			),
			'so_field_container_state' => 'open',
		);

		return $instance;
	}

	/**
	 * What get_less_variables() produced for stored_instance() at 19820b78, the
	 * branch head before the design guards were added, measured against the real
	 * install with the five contact filters removed. The font stub reproduces
	 * the real helper's values, so this array and its hash are the same in both.
	 *
	 * Raw reads keep the stored false; the values that carried an ! empty() guard
	 * yield ''; concatenated values carry their unit even when the source is 0.
	 */
	private function stored_instance_less_variables() {
		return array(
			'container_background'          => '#f2f2f2',
			'container_padding'             => '10px',
			'container_border_color'        => '#c0c0c0',
			'container_border_width'        => '1px',
			'container_border_style'        => 'solid',
			'label_font_family'             => 'default',
			'label_font_size'               => false,
			'label_font_color'              => false,
			'label_position'                => 'default',
			'label_width'                   => false,
			'label_align'                   => 'left',
			'field_font_family'             => 'default',
			'field_font_size'               => false,
			'field_font_color'              => false,
			'field_margin'                  => '0px 0px 15px 0px',
			'field_padding'                 => false,
			'field_max_width'               => '',
			'field_height'                  => false,
			'field_height_textarea'         => '',
			'field_background'              => false,
			'field_border_radius'           => '0px',
			'description_font_size'         => '0.9em',
			'description_font_color'        => '#999999',
			'description_font_style'        => 'italic',
			'description_top_margin'        => '0.2em',
			'error_background'              => '#fce4e5',
			'error_border'                  => '#ec666a',
			'error_text'                    => '#ec666a',
			'error_padding'                 => '5px',
			'error_margin'                  => '10px',
			'submit_background_color'       => '#eeeeee',
			'submit_background_color_hover' => '',
			'submit_background_gradient'    => '10%',
			'submit_border_color'           => '#989a9c',
			'submit_border_color_hover'     => '',
			'submit_border_style'           => 'solid',
			'submit_border_width'           => '1px',
			'submit_border_radius'          => '3px',
			'submit_text_color'             => '#5a5a5a',
			'submit_text_color_hover'       => '',
			'submit_font_size'              => false,
			'submit_weight'                 => '500',
			'submit_padding'                => '10px',
			'submit_width'                  => '',
			'submit_align'                  => 'left',
			'submit_inset_highlight'        => '50%',
			'outline_style'                 => 'solid',
			'outline_color'                 => false,
			'outline_width'                 => '1px',
			'success_font_size'             => '',
			'success_color'                 => '',
			'success_background_color'      => '',
			'success_padding'               => '',
			'success_border_width'          => '',
			'success_border_color'          => '',
			'success_border_style'          => 'solid',
			'field_border'                  => '1px #c0c0c0 solid',
			'success_font_family'           => 'default',
			'success_font_weight'           => '',
			'success_font_style'            => '',
		);
	}

	/**
	 * An instance whose design carries a value for every key the widget reads.
	 */
	private function healthy_instance() {
		$instance = self::base_instance();

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

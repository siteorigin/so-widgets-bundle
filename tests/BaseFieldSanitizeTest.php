<?php

use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * Unit tests for SiteOrigin_Widget_Field_Base::sanitize().
 *
 * base.class.php is a pure class definition and makes no top-level WordPress
 * calls, so it can be required directly without the function stubs the
 * widget-level tests need.
 *
 * The rule under test: a callable sanitizer receives ( $value, $old_value )
 * unless it is an internal PHP function with fewer than two required
 * parameters, in which case it receives $value only. User-defined callables
 * always get both arguments, whatever their declared arity, because PHP
 * ignores surplus arguments to user-land functions.
 */
if ( ! class_exists( 'SiteOrigin_Widget_Field_Base' ) ) {
	require __DIR__ . '/../base/inc/fields/base.class.php';
}

/**
 * Minimal concrete field so the abstract base class can be instantiated.
 * sanitize_field_input passes the value through untouched, isolating the
 * test to the custom-sanitize handling in the base sanitize() method.
 */
if ( ! class_exists( 'SiteOrigin_Test_Field_Stub' ) ) {
	class SiteOrigin_Test_Field_Stub extends SiteOrigin_Widget_Field_Base {
		protected function render_field( $value, $instance ) {
		}

		protected function sanitize_field_input( $value, $instance ) {
			return $value;
		}
	}
}

/**
 * User-defined callables used by the data providers below. Every one of them
 * returns func_get_args() so the test can assert exactly which arguments
 * sanitize() passed, independent of the declared signature.
 */
if ( ! function_exists( 'siteorigin_test_field_sanitize_named' ) ) {
	function siteorigin_test_field_sanitize_named( $value ) {
		return func_get_args();
	}
}

/**
 * siteorigin-premium's lightbox addon uses 'sanitize_title_with_dashes' as
 * a callable sanitizer. WordPress is not loaded in this suite, so mirror its
 * signature: ( $title, $raw_title = '', $context = 'display' ).
 */
if ( ! function_exists( 'sanitize_title_with_dashes' ) ) {
	function sanitize_title_with_dashes( $title, $raw_title = '', $context = 'display' ) {
		return func_get_args();
	}
}

if ( ! class_exists( 'SiteOrigin_Test_Field_Sanitize_Recorder' ) ) {
	class SiteOrigin_Test_Field_Sanitize_Recorder {
		public function record( $value, $old_value ) {
			return func_get_args();
		}

		public static function record_static( $value, $old_value ) {
			return func_get_args();
		}

		public function __invoke( $value ) {
			return func_get_args();
		}

		/**
		 * Mirrors siteorigin-premium's CPT Builder
		 * sanitize_reserved_post_types( $post_type, $old_value ), registered
		 * as array( $this, 'sanitize_reserved_post_types' ).
		 */
		public function sanitize_reserved_post_types( $post_type, $old_value ) {
			return func_get_args();
		}
	}
}

class BaseFieldSanitizeTest extends SiteOriginTests {
	/**
	 * PHP errors captured while the code under test runs.
	 */
	private $php_errors = array();

	/**
	 * Build a field with the given callable sanitizer.
	 */
	private function field_with_sanitizer( $sanitize ) {
		return new SiteOrigin_Test_Field_Stub(
			'test_field',
			'test_field',
			'test_field',
			array( 'type' => 'text', 'sanitize' => $sanitize )
		);
	}

	/**
	 * Runs sanitize() while recording warnings, notices, and deprecations so
	 * tests can assert the custom-sanitize guards keep it silent.
	 *
	 * @return array [ result, errors ]
	 */
	private function sanitize_capturing_errors( $field, $value, $old_value = null ) {
		$this->php_errors = array();

		set_error_handler(
			function ( $errno, $errstr ) {
				$this->php_errors[] = $errstr;

				return true;
			},
			E_WARNING | E_NOTICE | E_DEPRECATED
		);

		try {
			$result = $field->sanitize( $value, array(), $old_value );

			return array( $result, $this->php_errors );
		} finally {
			restore_error_handler();
		}
	}

	/**
	 * A field using 'intval' as a callable sanitize must not pass null as its
	 * optional $base parameter — the PHP 8.1+ deprecation recorded here used
	 * to fire from sanitize()'s call_user_func().
	 */
	public function test_intval_with_null_old_value_is_silent() {
		list( $result, $errors ) = $this->sanitize_capturing_errors(
			$this->field_with_sanitizer( 'intval' ),
			'42abc'
		);

		$this->assertSame( array(), $errors );
		$this->assertSame( 42, $result );
	}

	/**
	 * A non-null old value must not become intval()'s $base: with base 8,
	 * '42abc' would come back as 34.
	 */
	public function test_intval_old_value_does_not_become_the_base() {
		list( $result, $errors ) = $this->sanitize_capturing_errors(
			$this->field_with_sanitizer( 'intval' ),
			'42abc',
			'8'
		);

		$this->assertSame( array(), $errors );
		$this->assertSame( 42, $result );
	}

	/**
	 * Internal functions whose second parameter is optional (or absent) get
	 * the value only. Each row's expected result is what the one-argument
	 * call returns; the two-argument call would either throw (strlen, round,
	 * htmlspecialchars) or change the result (trim).
	 */
	#[DataProvider( 'internal_single_argument_sanitizers' )]
	public function test_internal_function_with_optional_second_param_gets_value_only( $sanitize, $value, $expected ) {
		list( $result, $errors ) = $this->sanitize_capturing_errors(
			$this->field_with_sanitizer( $sanitize ),
			$value,
			'old'
		);

		$this->assertSame( array(), $errors );
		$this->assertSame( $expected, $result );
	}

	public static function internal_single_argument_sanitizers() {
		return array(
			'strlen' => array( 'strlen', 'abcd', 4 ),
			'round' => array( 'round', 3.7, 4.0 ),
			'trim' => array( 'trim', '  old  ', 'old' ),
			'htmlspecialchars' => array( 'htmlspecialchars', '<a>', '&lt;a&gt;' ),
		);
	}

	/**
	 * json_decode()'s optional second parameter is $associative. A truthy
	 * $old_value there would turn the object into an array.
	 */
	public function test_json_decode_old_value_does_not_become_associative_flag() {
		list( $result, $errors ) = $this->sanitize_capturing_errors(
			$this->field_with_sanitizer( 'json_decode' ),
			'{"a":1}',
			'old'
		);

		$this->assertSame( array(), $errors );
		$this->assertIsObject( $result );
		$this->assertSame( 1, $result->a );
	}

	/**
	 * Internal functions wrapped in a Closure still reflect as internal and
	 * still get the value only. Closure::fromCallable() and first-class
	 * callable syntax are the two ways to build such a wrapper.
	 */
	#[DataProvider( 'internal_closure_sanitizers' )]
	public function test_closure_over_internal_function_gets_value_only( $sanitize ) {
		list( $result, $errors ) = $this->sanitize_capturing_errors(
			$this->field_with_sanitizer( $sanitize ),
			'42abc',
			'8'
		);

		$this->assertSame( array(), $errors );
		$this->assertSame( 42, $result );
	}

	public static function internal_closure_sanitizers() {
		return array(
			'Closure::fromCallable( intval )' => array( Closure::fromCallable( 'intval' ) ),
			'first-class callable intval(...)' => array( intval( ... ) ),
		);
	}

	/**
	 * An array callable pointing at an internal method with one required
	 * parameter gets the value only. ArrayObject::append( $value ) throws
	 * ArgumentCountError if handed a second argument.
	 */
	public function test_internal_method_array_callable_gets_value_only() {
		$store = new ArrayObject();

		list( $result, $errors ) = $this->sanitize_capturing_errors(
			$this->field_with_sanitizer( array( $store, 'append' ) ),
			'abc',
			'old'
		);

		$this->assertSame( array(), $errors );
		$this->assertNull( $result );
		$this->assertSame( array( 'abc' ), $store->getArrayCopy() );
	}

	/**
	 * An internal function that requires two parameters keeps receiving
	 * $old_value as its second argument.
	 */
	public function test_internal_function_requiring_two_params_receives_old_value() {
		list( $result, $errors ) = $this->sanitize_capturing_errors(
			$this->field_with_sanitizer( 'array_slice' ),
			array( 'a', 'b', 'c' ),
			1
		);

		$this->assertSame( array(), $errors );
		$this->assertSame( array( 'b', 'c' ), $result );
	}

	/**
	 * Every user-defined callable receives ( $value, $old_value ), whatever
	 * its declared arity. Each row's callable returns func_get_args(), so the
	 * assertion is on the exact argument list sanitize() passed.
	 */
	#[DataProvider( 'user_defined_sanitizers' )]
	public function test_user_defined_callable_receives_old_value( $sanitize ) {
		list( $result, $errors ) = $this->sanitize_capturing_errors(
			$this->field_with_sanitizer( $sanitize ),
			'abc',
			'old-value'
		);

		$this->assertSame( array(), $errors );
		$this->assertSame( array( 'abc', 'old-value' ), $result );
	}

	public static function user_defined_sanitizers() {
		$recorder = new SiteOrigin_Test_Field_Sanitize_Recorder();

		return array(
			'closure with one param' => array(
				function ( $value ) {
					return func_get_args();
				},
			),
			'closure with optional second param' => array(
				function ( $value, $old_value = null ) {
					return func_get_args();
				},
			),
			'closure with two required params' => array(
				function ( $value, $old_value ) {
					return func_get_args();
				},
			),
			'variadic closure' => array(
				function ( $value, ...$rest ) {
					return func_get_args();
				},
			),
			'named function with one param' => array(
				'siteorigin_test_field_sanitize_named',
			),
			'invokable object with one param' => array(
				$recorder,
			),
			'instance array callable' => array(
				array( $recorder, 'record' ),
			),
			'static array callable' => array(
				array( SiteOrigin_Test_Field_Sanitize_Recorder::class, 'record_static' ),
			),
			'Class::method string' => array(
				SiteOrigin_Test_Field_Sanitize_Recorder::class . '::record_static',
			),
			'sanitize_title_with_dashes (Premium lightbox)' => array(
				'sanitize_title_with_dashes',
			),
			'sanitize_reserved_post_types (Premium CPT Builder)' => array(
				array( $recorder, 'sanitize_reserved_post_types' ),
			),
		);
	}
}

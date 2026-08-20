<?php

use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * Unit tests for SiteOrigin_Widget_Field_Base::sanitize().
 *
 * base.class.php is a pure class definition and makes no top-level WordPress
 * calls, so it can be required directly without the function stubs the
 * widget-level tests need.
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
 * Callables used by the data providers below.
 */
if ( ! class_exists( 'SiteOrigin_Test_Field_Sanitize_Static' ) ) {
	class SiteOrigin_Test_Field_Sanitize_Static {
		public static function record( $value, $old_value ) {
			return array( $value, $old_value );
		}
	}
}

if ( ! class_exists( 'SiteOrigin_Test_Field_Sanitize_Single' ) ) {
	class SiteOrigin_Test_Field_Sanitize_Single {
		public function clean( $value ) {
			return $value;
		}
	}
}

class BaseFieldSanitizeTest extends SiteOriginTests {
	/**
	 * PHP errors captured while the code under test runs.
	 */
	private $php_errors = array();

	/**
	 * Build a field with the given field options.
	 */
	private function field( $options ) {
		return new SiteOrigin_Test_Field_Stub(
			'test_field',
			'test_field',
			'test_field',
			$options
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
	 * to fire at base.class.php line 405.
	 */
	public function test_intval_sanitize_with_null_old_value_is_silent() {
		list( $result, $errors ) = $this->sanitize_capturing_errors(
			$this->field( array( 'type' => 'text', 'sanitize' => 'intval' ) ),
			'42abc'
		);

		$this->assertSame( array(), $errors );
		$this->assertSame( 42, $result );
	}

	public function test_intval_sanitize_ignores_a_passed_old_value() {
		list( $result, $errors ) = $this->sanitize_capturing_errors(
			$this->field( array( 'type' => 'text', 'sanitize' => 'intval' ) ),
			'42abc',
			'16'
		);

		$this->assertSame( array(), $errors );
		$this->assertSame( 42, $result );
	}

	/**
	 * A callable that requires a second parameter still receives $old_value.
	 */
	#[DataProvider( 'two_param_callables' )]
	public function test_callable_with_required_old_value_receives_it( $callable ) {
		list( $result, $errors ) = $this->sanitize_capturing_errors(
			$this->field( array( 'type' => 'text', 'sanitize' => $callable ) ),
			'abc',
			'old-value'
		);

		$this->assertSame( array(), $errors );
		$this->assertSame( array( 'abc', 'old-value' ), $result );
	}

	public static function two_param_callables() {
		$recorder = new class {
			public function record( $value, $old_value ) {
				return array( $value, $old_value );
			}
		};

		return array(
			'closure with two required params' => array(
				function ( $value, $old_value ) {
					return array( $value, $old_value );
				},
			),
			'array callable' => array(
				array( $recorder, 'record' ),
			),
			'static method string' => array(
				SiteOrigin_Test_Field_Sanitize_Static::class . '::record',
			),
		);
	}

	/**
	 * One-parameter callables must only ever receive the value, never a null
	 * placeholder as a second argument.
	 */
	#[DataProvider( 'one_param_callables' )]
	public function test_callable_without_old_value_is_not_passed_a_second_argument( $callable ) {
		$args = array( 'unset' );

		$wrapper = function ( $value, ...$captured ) use ( $callable, &$args ) {
			$args = $captured;

			return call_user_func( $callable, $value );
		};

		list( $result, $errors ) = $this->sanitize_capturing_errors(
			$this->field( array( 'type' => 'text', 'sanitize' => $wrapper ) ),
			'abc',
			'old-value'
		);

		$this->assertSame( array(), $errors );
		$this->assertSame( 'abc', $result );
		$this->assertSame( array(), $args );
	}

	/**
	 * A callable with an optional second parameter is treated as a one-
	 * parameter callable: only the value is passed, and any default is left
	 * to the callable itself.
	 */
	public function test_optional_second_param_is_left_to_the_callables_default() {
		$received = null;

		$callback = function ( $value, $old_value = 'default-old' ) use ( &$received ) {
			$received = $old_value;

			return $value;
		};

		list( $result, $errors ) = $this->sanitize_capturing_errors(
			$this->field( array( 'type' => 'text', 'sanitize' => $callback ) ),
			'abc',
			'old-value'
		);

		$this->assertSame( array(), $errors );
		$this->assertSame( 'abc', $result );
		$this->assertSame( 'default-old', $received );
	}

	public static function one_param_callables() {
		return array(
			'closure with one param' => array(
				function ( $value ) {
					return $value;
				},
			),
			'array callable with one param' => array(
				array( new SiteOrigin_Test_Field_Sanitize_Single(), 'clean' ),
			),
		);
	}
}
<?php

use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * Number save bounds must include numeric zero (#2438).
 */
class NumberFieldZeroBoundsTest extends SiteOriginTests {
	private function field( $options ) {
		return new SiteOrigin_Widget_Field_Number(
			'number', 'number', 'number', array_merge( array( 'type' => 'number' ), $options )
		);
	}

	public static function zero_bounds() {
		$cases = array();

		foreach ( array( 0, 0.0, '0', '0.0' ) as $index => $zero ) {
			$cases[ 'minimum ' . $index ] = array( array( 'min' => $zero ), '-2.5', 0.0 );
			$cases[ 'maximum ' . $index ] = array( array( 'max' => $zero ), '2.5', 0.0 );
			$cases[ 'minimum in range ' . $index ] = array( array( 'min' => $zero ), '2.5', 2.5 );
			$cases[ 'maximum in range ' . $index ] = array( array( 'max' => $zero ), '-2.5', -2.5 );
			$cases[ 'both bounds negative ' . $index ] = array( array( 'min' => $zero, 'max' => $zero ), -3, 0.0 );
			$cases[ 'both bounds positive ' . $index ] = array( array( 'min' => $zero, 'max' => $zero ), 3, 0.0 );
			$cases[ 'at zero ' . $index ] = array( array( 'min' => $zero, 'max' => $zero ), $zero, 0.0 );
		}

		return $cases;
	}

	#[DataProvider( 'zero_bounds' )]
	public function test_zero_bounds_clamp_saved_numbers_and_return_floats( $options, $value, $expected ) {
		$this->assertSame( $expected, $this->field( $options )->sanitize( $value ) );
	}

	public static function healthy_values() {
		return array(
			'nonzero minimum' => array( array( 'min' => 2.5 ), '-1', 2.5 ),
			'nonzero maximum' => array( array( 'max' => '5.5' ), '9', 5.5 ),
			'nonzero range' => array( array( 'min' => -5, 'max' => 5 ), '1.25', 1.25 ),
			'unset bounds' => array( array(), '-12.75', -12.75 ),
			'null bounds' => array( array( 'min' => null, 'max' => null ), '12.75', 12.75 ),
			'empty bounds' => array( array( 'min' => '', 'max' => '' ), '-12.75', -12.75 ),
			'invalid text' => array( array( 'min' => 0, 'max' => 0 ), 'abc', false ),
			'invalid array' => array( array( 'min' => 0 ), array( 1 ), false ),
			'invalid boolean' => array( array( 'max' => 0 ), false, false ),
			'empty input' => array( array( 'min' => 0 ), '', '' ),
			'null input' => array( array( 'max' => 0 ), null, '' ),
			'absolute after minimum' => array( array( 'min' => -5, 'abs' => true ), -9, 5.0 ),
			'absolute after maximum' => array( array( 'max' => -2, 'abs' => true ), 3, 2.0 ),
		);
	}

	#[DataProvider( 'healthy_values' )]
	public function test_existing_save_behavior_is_preserved( $options, $value, $expected ) {
		$this->assertSame( $expected, $this->field( $options )->sanitize( $value ) );
	}
}

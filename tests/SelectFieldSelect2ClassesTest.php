<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * Select2 must retain developer input classes (#2439).
 */
class SelectFieldSelect2ClassesTest extends SiteOriginTests {
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'siteorigin_sanitize_attribute_key' )->returnArg();
		Functions\when( 'selected' )->alias(
			function ( $selected, $current, $echo = true ) {
				$result = (string) $selected === (string) $current ? ' selected="selected"' : '';

				if ( $echo ) {
					echo $result;
				}

				return $result;
			}
		);
	}

	private function field( $options ) {
		return new SiteOrigin_Widget_Field_Select(
			'choice', 'choice-id', 'choice-name',
			array_merge(
				array(
					'type' => 'select',
					'label' => 'Choice',
					'options' => array( 'first' => 'First & foremost', 'second' => 'Second' ),
					'default' => 'first',
				),
				$options
			)
		);
	}

	private function select_element( $field, $value ) {
		ob_start();

		try {
			$field->render( $value );
			$html = ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$document = new DOMDocument();
		$this->assertTrue( $document->loadHTML( $html ) );

		return $document->getElementsByTagName( 'select' )->item( 0 );
	}

	public static function class_options() {
		return array(
			'Select2 custom classes' => array( array( 'select2' => true, 'input_css_classes' => array( 'custom', 'developer-hook' ) ), array( 'custom', 'developer-hook', 'sow-select2' ) ),
			'Select2 empty classes' => array( array( 'select2' => true, 'input_css_classes' => array() ), array( 'sow-select2' ) ),
			'Select2 default classes' => array( array( 'select2' => true ), array( 'sow-select2' ) ),
			'disabled custom classes' => array( array( 'select2' => false, 'input_css_classes' => array( 'custom', 'developer-hook' ) ), array( 'custom', 'developer-hook' ) ),
			'disabled empty classes' => array( array( 'select2' => false, 'input_css_classes' => array() ), array() ),
			'default classes' => array( array(), array() ),
		);
	}

	#[DataProvider( 'class_options' )]
	public function test_render_preserves_classes_options_and_selection( $options, $expected_classes ) {
		$select = $this->select_element( $this->field( $options ), 'second' );
		$this->assertSame(
			array_merge( array( 'siteorigin-widget-input', 'siteorigin-widget-input-select' ), $expected_classes ),
			explode( ' ', $select->getAttribute( 'class' ) )
		);
		$this->assertSame( 'choice-name', $select->getAttribute( 'name' ) );
		$this->assertSame( 'choice-id', $select->getAttribute( 'id' ) );
		$choices = $select->getElementsByTagName( 'option' );
		$this->assertSame( 2, $choices->length );
		$this->assertSame( 'first', $choices->item( 0 )->getAttribute( 'value' ) );
		$this->assertSame( 'First & foremost', $choices->item( 0 )->textContent );
		$this->assertFalse( $choices->item( 0 )->hasAttribute( 'selected' ) );
		$this->assertSame( 'second', $choices->item( 1 )->getAttribute( 'value' ) );
		$this->assertSame( 'selected', $choices->item( 1 )->getAttribute( 'selected' ) );
	}

	public function test_select2_keeps_multiple_selection_and_sanitization() {
		$field = $this->field( array( 'select2' => true, 'multiple' => true, 'input_css_classes' => array( 'custom' ) ) );
		$select = $this->select_element( $field, array( 'first', 'second' ) );
		$this->assertSame( 'choice-name[]', $select->getAttribute( 'name' ) );
		$this->assertTrue( $select->hasAttribute( 'multiple' ) );

		foreach ( $select->getElementsByTagName( 'option' ) as $option ) {
			$this->assertSame( 'selected', $option->getAttribute( 'selected' ) );
		}

		$this->assertSame( 'second', $field->sanitize( 'second' ) );
		$this->assertSame( 'first', $field->sanitize( 'unknown' ) );
		$this->assertSame( array( 'second', 'first' ), $field->sanitize( array( 'second', 'unknown' ) ) );
	}
}

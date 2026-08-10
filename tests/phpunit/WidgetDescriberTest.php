<?php

namespace SiteOrigin\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Child widget stub exposing a literal form_options() array, for the
 * widget-in-widget recursion tests. Extends the shared SiteOrigin_Widget
 * marker (defined by AbilitiesTest/WidgetBlockChokepointTest at file scope).
 */
class Describer_ChildWidgetStub extends \SiteOrigin_Widget {
	public function form_options( $parent = false ) {
		return array(
			'text_in_child' => array(
				'type' => 'text',
				'label' => 'Child text',
			),
		);
	}
}

/**
 * Self-referencing widget stub: its form contains a widget field pointing at
 * its own class, for the cycle-guard test.
 */
class Describer_CyclicWidgetStub extends \SiteOrigin_Widget {
	public function form_options( $parent = false ) {
		return array(
			'self' => array(
				'type' => 'widget',
				'class' => 'SiteOrigin\\Tests\\Describer_CyclicWidgetStub',
			),
			'plain' => array(
				'type' => 'text',
			),
		);
	}
}

/**
 * Unit tests for SiteOrigin_Widgets_Bundle_Widget_Describer — the pure
 * form_options → JSON-schema translator. Fed literal form arrays; no shipped
 * widget classes are loaded.
 */
class WidgetDescriberTest extends TestCase {
	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg();
		$GLOBALS['wp_widget_factory'] = (object) array( 'widgets' => array() );
		$GLOBALS['sowb_test_missing_widgets'] = array();

		if ( ! class_exists( 'SiteOrigin_Widgets_Bundle_Widget_Describer', false ) ) {
			require_once dirname( dirname( __DIR__ ) ) . '/compat/block-editor/widget-describer.php';
		}
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_widget_factory'], $GLOBALS['sowb_test_missing_widgets'] );
		Monkey\tearDown();
		parent::tearDown();
	}

	private function describer() {
		return \SiteOrigin_Widgets_Bundle_Widget_Describer::single();
	}

	private function field( array $field ) {
		return $this->describer()->translate_field( $field, 0 );
	}

	public function test_mapping_table_per_field_type() {
		$this->assertSame(
			array( 'type' => 'string', 'x-sowb-text' => true, 'x-sowb-field-type' => 'text', 'title' => 'T' ),
			$this->field( array( 'type' => 'text', 'label' => 'T' ) )
		);

		$tinymce = $this->field( array( 'type' => 'tinymce' ) );
		$this->assertSame( 'string', $tinymce['type'] );
		$this->assertSame( 'html', $tinymce['format'] );
		$this->assertTrue( $tinymce['x-sowb-text'] );

		$this->assertSame( 'boolean', $this->field( array( 'type' => 'checkbox' ) )['type'] );
		$this->assertSame( 'boolean', $this->field( array( 'type' => 'toggle' ) )['type'] );

		$slider = $this->field( array( 'type' => 'slider', 'min' => 0, 'max' => 100 ) );
		$this->assertSame( 'number', $slider['type'] );
		$this->assertSame( 0, $slider['minimum'] );
		$this->assertSame( 100, $slider['maximum'] );

		$select = $this->field(
			array(
				'type' => 'select',
				'options' => array( 'left' => 'Left', 'right' => 'Right' ),
				'default' => 'left',
			)
		);
		$this->assertSame( array( 'left', 'right' ), $select['enum'] );
		$this->assertSame( 'left', $select['default'] );

		$measurement = $this->field( array( 'type' => 'measurement', 'units' => array( 'px', 'em', '%' ) ) );
		$this->assertSame( 'string', $measurement['type'] );
		$this->assertStringContainsString( 'px|em|%', $measurement['pattern'] );

		$this->assertSame( 'integer', $this->field( array( 'type' => 'media' ) )['type'] );
		$this->assertSame( 'array', $this->field( array( 'type' => 'multiple-media' ) )['type'] );

		$color = $this->field( array( 'type' => 'color' ) );
		$this->assertSame( 'string', $color['type'] );
		$this->assertSame( 'color', $color['format'] );

		// Code and plain strings carry no text-bearing flag.
		$this->assertArrayNotHasKey( 'x-sowb-text', $this->field( array( 'type' => 'code' ) ) );
	}

	public function test_repeater_and_section_recursion() {
		$schema = $this->field(
			array(
				'type' => 'repeater',
				'fields' => array(
					'label' => array( 'type' => 'text' ),
					'design' => array(
						'type' => 'section',
						'fields' => array(
							'color' => array( 'type' => 'color' ),
						),
					),
				),
			)
		);

		$this->assertSame( 'array', $schema['type'] );
		$this->assertSame( 'string', $schema['items']['properties']['label']['type'] );
		$this->assertSame( 'color', $schema['items']['properties']['design']['properties']['color']['format'] );
	}

	public function test_widget_field_recursion_depth_cap_and_cycle_guard() {
		$GLOBALS['wp_widget_factory']->widgets['SiteOrigin\\Tests\\Describer_ChildWidgetStub'] = new Describer_ChildWidgetStub();
		$GLOBALS['wp_widget_factory']->widgets['SiteOrigin\\Tests\\Describer_CyclicWidgetStub'] = new Describer_CyclicWidgetStub();

		// Normal expansion of an available child widget.
		$expanded = $this->field(
			array(
				'type' => 'widget',
				'class' => 'SiteOrigin\\Tests\\Describer_ChildWidgetStub',
			)
		);
		$this->assertSame( 'string', $expanded['properties']['text_in_child']['type'] );

		// Depth cap: at MAX_WIDGET_DEPTH the schema is not expanded.
		$capped = $this->describer()->translate_field(
			array(
				'type' => 'widget',
				'class' => 'SiteOrigin\\Tests\\Describer_ChildWidgetStub',
			),
			\SiteOrigin_Widgets_Bundle_Widget_Describer::MAX_WIDGET_DEPTH
		);
		$this->assertArrayNotHasKey( 'properties', $capped );

		// Cycle guard: a self-referencing widget terminates with the inner
		// reference unexpanded.
		$cyclic = $this->field(
			array(
				'type' => 'widget',
				'class' => 'SiteOrigin\\Tests\\Describer_CyclicWidgetStub',
			)
		);
		$this->assertSame( 'string', $cyclic['properties']['plain']['type'] );
		$this->assertArrayNotHasKey( 'properties', $cyclic['properties']['self'] );

		// Unavailable child widget degrades to an unexpanded object schema.
		$missing = $this->field(
			array(
				'type' => 'widget',
				'class' => 'Nonexistent_Child_Widget',
			)
		);
		$this->assertSame( 'object', $missing['type'] );
		$this->assertArrayNotHasKey( 'properties', $missing );
	}

	public function test_state_dependent_and_omitted_fields() {
		// posts: generic string schema; the query options are never enumerated.
		$posts = $this->field( array( 'type' => 'posts' ) );
		$this->assertSame( 'string', $posts['type'] );
		$this->assertSame( 'posts', $posts['x-sowb-field-type'] );

		// Omitted types translate to null and are dropped from forms.
		$this->assertNull( $this->field( array( 'type' => 'presets' ) ) );
		$this->assertNull( $this->field( array( 'type' => 'builder' ) ) );
		$this->assertNull( $this->field( array( 'type' => 'error' ) ) );
		$this->assertNull( $this->field( array( 'type' => 'html' ) ) );

		$form = $this->describer()->translate_form(
			array(
				'keep' => array( 'type' => 'text' ),
				'drop' => array( 'type' => 'presets' ),
			)
		);
		$this->assertArrayHasKey( 'keep', $form['properties'] );
		$this->assertArrayNotHasKey( 'drop', $form['properties'] );
	}

	public function test_unknown_type_falls_back_with_field_type_annotation() {
		$exotic = $this->field( array( 'type' => 'some-future-field' ) );

		$this->assertSame( 'string', $exotic['type'] );
		$this->assertSame( 'some-future-field', $exotic['x-sowb-field-type'] );
	}
}

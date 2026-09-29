<?php

use SiteOrigin\Tests\SiteOriginTests;

if ( ! class_exists( 'SiteOrigin_Widget_Button_Widget' ) ) {
	require __DIR__ . '/../widgets/button/button.php';
}

if ( ! class_exists( 'SiteOrigin_Widget_Hero_Widget' ) ) {
	require __DIR__ . '/../widgets/hero/hero.php';
}

require_once __DIR__ . '/../base/inc/widget-describer.php';

/**
 * Base for the describer fixture widgets: a widget whose form is set per
 * class, and whose modify_child_widget_form() hook records each call.
 */
abstract class SiteOrigin_Describer_Fixture_Widget extends SiteOrigin_Widget {
	/**
	 * Calls to modify_child_widget_form(), as [ parent class, child class ].
	 */
	public static $child_form_calls = array();

	public function __construct() {
		parent::__construct(
			strtolower( str_replace( '_', '-', get_class( $this ) ) ),
			get_class( $this ),
			array( 'has_preview' => false ),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	public function get_less_variables( $instance ) {
		return array();
	}

	/**
	 * Remove a field from any child form built against this widget.
	 *
	 * @return string|null The field name to remove.
	 */
	protected function removes_child_field() {
		return null;
	}

	public function modify_child_widget_form( $child_widget_form, $child_widget ) {
		self::$child_form_calls[] = array( get_class( $this ), get_class( $child_widget ) );

		$remove = $this->removes_child_field();

		if ( $remove !== null ) {
			unset( $child_widget_form[ $remove ] );
		}

		return $child_widget_form;
	}
}

class SiteOrigin_Describer_Missing_Child_Widget extends SiteOrigin_Describer_Fixture_Widget {
	public function get_widget_form() {
		return array(
			'child' => array(
				'type' => 'widget',
				'class' => 'SiteOrigin_Describer_Class_That_Does_Not_Exist',
				'label' => 'Child',
			),
		);
	}
}

class SiteOrigin_Describer_Self_Widget extends SiteOrigin_Describer_Fixture_Widget {
	public function get_widget_form() {
		return array(
			'title' => array(
				'type' => 'text',
			),
			'self' => array(
				'type' => 'widget',
				'class' => 'SiteOrigin_Describer_Self_Widget',
			),
		);
	}
}

class SiteOrigin_Describer_Empty_Widget extends SiteOrigin_Describer_Fixture_Widget {
	public function get_widget_form() {
		return array();
	}
}

class SiteOrigin_Describer_Outer_Widget extends SiteOrigin_Describer_Fixture_Widget {
	public function get_widget_form() {
		return array(
			'middle' => array(
				'type' => 'widget',
				'class' => 'SiteOrigin_Describer_Middle_Widget',
			),
		);
	}

	protected function removes_child_field() {
		return 'a';
	}
}

class SiteOrigin_Describer_Middle_Widget extends SiteOrigin_Describer_Fixture_Widget {
	public function get_widget_form() {
		return array(
			'inner' => array(
				'type' => 'widget',
				'class' => 'SiteOrigin_Describer_Inner_Widget',
			),
		);
	}

	protected function removes_child_field() {
		return 'b';
	}
}

class SiteOrigin_Describer_Inner_Widget extends SiteOrigin_Describer_Fixture_Widget {
	public function get_widget_form() {
		return array(
			'a' => array(
				'type' => 'text',
			),
			'b' => array(
				'type' => 'text',
			),
		);
	}
}

/**
 * SiteOrigin_Widgets_Widget_Describer translates real widget forms into a
 * JSON schema, building nested widget forms the way the editor's widget
 * field does.
 */
class WidgetDescriberTest extends SiteOriginTests {
	protected function setUp(): void {
		parent::setUp();

		SiteOrigin_Describer_Fixture_Widget::$child_form_calls = array();
	}

	private function describer() {
		return SiteOrigin_Widgets_Widget_Describer::single();
	}

	private function hero_schema() {
		return $this->describer()->get_schema( new SiteOrigin_Widget_Hero_Widget() );
	}

	/**
	 * Convert a schema to plain arrays, the way a JSON consumer sees it.
	 */
	private static function as_json_array( $schema ) {
		return json_decode( json_encode( $schema ), true );
	}

	public function test_hero_frames_and_nested_button_follow_the_real_forms() {
		$schema = self::as_json_array( $this->hero_schema() );

		$frames = $schema['properties']['frames'];
		$this->assertSame( 'array', $frames['type'] );
		$this->assertSame( 'object', $frames['items']['type'] );

		$button = $frames['items']['properties']['buttons']['items']['properties']['button'];
		$this->assertSame( 'object', $button['type'] );
		$this->assertSame( 'widget', $button['x-sowb-field-type'] );
		$this->assertArrayHasKey( 'text', $button['properties'] );

		// Hero's form_filter removed the alignment fields.
		$design = $button['properties']['design']['properties'];
		$this->assertArrayNotHasKey( 'align', $design );
		$this->assertArrayNotHasKey( 'mobile_align', $design );

		// The standalone Button form has them, so the difference is the filter.
		$standalone = self::as_json_array( $this->describer()->get_schema( new SiteOrigin_Widget_Button_Widget() ) );
		$this->assertArrayHasKey( 'align', $standalone['properties']['design']['properties'] );
		$this->assertArrayHasKey( 'mobile_align', $standalone['properties']['design']['properties'] );
	}

	public function test_every_hero_property_carries_its_field_type() {
		$schema = self::as_json_array( $this->hero_schema() );
		$count = 0;

		$walk = function ( $node, $path ) use ( &$walk, &$count ) {
			if ( ! empty( $node['properties'] ) ) {
				foreach ( $node['properties'] as $name => $property ) {
					$this->assertArrayHasKey( 'x-sowb-field-type', $property, $path . '.' . $name );
					++$count;
					$walk( $property, $path . '.' . $name );
				}
			}

			if ( ! empty( $node['items'] ) && is_array( $node['items'] ) ) {
				$walk( $node['items'], $path . '[]' );
			}
		};
		$walk( $schema, 'hero' );

		$this->assertGreaterThan( 20, $count );

		$content = $schema['properties']['frames']['items']['properties']['content'];
		$this->assertSame( 'tinymce', $content['x-sowb-field-type'] );
		$this->assertTrue( $content['x-sowb-text'] );
		$this->assertSame( 'html', $content['format'] );
	}

	public function test_a_missing_nested_widget_class_is_a_placeholder_and_is_not_autoloaded() {
		$missing = 'SiteOrigin_Describer_Class_That_Does_Not_Exist';
		$autoload_requests = array();
		$autoloader = function ( $class ) use ( &$autoload_requests ) {
			$autoload_requests[] = $class;
		};
		spl_autoload_register( $autoloader );

		try {
			$schema = $this->describer()->get_schema( new SiteOrigin_Describer_Missing_Child_Widget() );
		} finally {
			spl_autoload_unregister( $autoloader );
		}

		$child = $schema['properties']['child'];
		$this->assertSame( 'object', $child['type'] );
		$this->assertSame( 'widget', $child['x-sowb-field-type'] );
		$this->assertArrayNotHasKey( 'properties', $child );
		$this->assertNotContains( $missing, $autoload_requests );
		$this->assertFalse( class_exists( $missing, false ) );
	}

	public function test_a_widget_field_that_points_at_its_own_class_stops_at_the_cycle_guard() {
		list( $schema, $errors ) = $this->run_capturing_errors(
			fn() => $this->describer()->get_schema( new SiteOrigin_Describer_Self_Widget() )
		);

		$self = $schema['properties']['self'];
		$this->assertSame( 'object', $self['type'] );
		$this->assertArrayNotHasKey( 'properties', $self );
		$this->assertSame( array(), $errors );
	}

	public function test_omitted_types_select_enum_and_media_fallback() {
		$schema = self::as_json_array(
			$this->describer()->translate_form(
				array(
					'presets' => array( 'type' => 'presets' ),
					'notice' => array( 'type' => 'html' ),
					'layout' => array( 'type' => 'builder' ),
					'broken' => array( 'type' => 'error' ),
					'size' => array(
						'type' => 'select',
						'options' => array(
							'small' => 'Small',
							'large' => 'Large',
							10 => 'Ten',
						),
					),
					'image' => array(
						'type' => 'media',
						'fallback' => true,
					),
					'icon' => array( 'type' => 'media' ),
				)
			)
		);

		$properties = $schema['properties'];
		$this->assertArrayNotHasKey( 'presets', $properties );
		$this->assertArrayNotHasKey( 'notice', $properties );
		$this->assertArrayNotHasKey( 'layout', $properties );
		$this->assertArrayNotHasKey( 'broken', $properties );

		$this->assertSame( 'string', $properties['size']['type'] );
		$this->assertSame( array( 'small', 'large', '10' ), $properties['size']['enum'] );

		$this->assertSame( 'integer', $properties['image']['type'] );
		$this->assertSame(
			array(
				'type' => 'string',
				'format' => 'uri',
				'x-sowb-field-type' => 'media-fallback',
			),
			$properties['image_fallback']
		);
		$this->assertArrayNotHasKey( 'icon_fallback', $properties );
	}

	public function test_measurement_pattern_matches_stored_values() {
		$schema = $this->describer()->translate_field( array(
			'type' => 'measurement',
			'units' => array( 'px', 'em' ),
		) );
		$pattern = '/' . str_replace( '/', '\\/', $schema['pattern'] ) . '/';

		foreach ( array( '-5px', '1.5em', '0' ) as $value ) {
			$this->assertSame( 1, preg_match( $pattern, $value ), $value );
		}

		foreach ( array( '...px', '5pxx' ) as $value ) {
			$this->assertSame( 0, preg_match( $pattern, $value ), $value );
		}
	}

	public function test_an_empty_form_encodes_properties_as_an_object() {
		$schema = $this->describer()->get_schema( new SiteOrigin_Describer_Empty_Widget() );

		$this->assertStringContainsString( '"properties":{}', json_encode( $schema ) );
		$this->assertStringContainsString( '"properties":{}', json_encode( $this->describer()->translate_form( array() ) ) );
	}

	public function test_a_toggle_is_an_object_container() {
		$schema = $this->describer()->translate_form(
			array(
				'shadow' => array(
					'type' => 'toggle',
					'label' => 'Shadow',
					'fields' => array(
						'color' => array( 'type' => 'text' ),
						'enabled' => array( 'type' => 'checkbox' ),
					),
				),
			)
		);

		$toggle = $schema['properties']['shadow'];
		$this->assertSame( 'object', $toggle['type'] );
		$this->assertNotSame( 'boolean', $toggle['type'] );
		$this->assertSame( 'toggle', $toggle['x-sowb-field-type'] );
		$this->assertSame( 'string', $toggle['properties']['color']['type'] );
		$this->assertSame( 'boolean', $toggle['properties']['enabled']['type'] );
	}

	public function test_two_level_nesting_builds_child_forms_against_the_top_level_widget() {
		$outer = new SiteOrigin_Describer_Outer_Widget();

		$schema = self::as_json_array( $this->describer()->get_schema( $outer ) );
		$inner = $schema['properties']['middle']['properties']['inner']['properties'];
		$this->assertArrayNotHasKey( 'a', $inner );
		$this->assertArrayHasKey( 'b', $inner );

		$describer_calls = SiteOrigin_Describer_Fixture_Widget::$child_form_calls;
		$this->assertSame(
			array(
				array( 'SiteOrigin_Describer_Outer_Widget', 'SiteOrigin_Describer_Middle_Widget' ),
				array( 'SiteOrigin_Describer_Outer_Widget', 'SiteOrigin_Describer_Inner_Widget' ),
			),
			$describer_calls
		);

		// Oracle: the editor's own widget field, with the same fixtures.
		SiteOrigin_Describer_Fixture_Widget::$child_form_calls = array();
		$outer_form = $outer->form_options();
		$field = SiteOrigin_Widget_Field_Factory::single()->create_field( 'middle', $outer_form['middle'], $outer );
		$field->sanitize( array( 'inner' => array() ), array() );

		$this->assertSame( $describer_calls, SiteOrigin_Describer_Fixture_Widget::$child_form_calls );
	}
}

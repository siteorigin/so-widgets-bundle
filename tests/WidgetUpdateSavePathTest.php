<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

require_once __DIR__ . '/fixtures/TestWidget.php';

/**
 * SiteOrigin_Widget::update() reads the stored instance before sanitizing:
 * modify_instance() migrates it, and delete_css() computes the style hash
 * of the old instance through get_less_variables(). Neither runs
 * add_defaults() first, so a container stored as an empty string would
 * reach widget code as a string. normalize_container_values() runs before
 * modify_instance() on the save path and the render paths, turning each
 * empty-string container into an empty array in memory and changing
 * nothing else.
 */
class WidgetUpdateSavePathTest extends SiteOriginTests {
	/**
	 * Arguments every apply_filters() call received, keyed by hook.
	 */
	private $filtered = array();

	protected function setUp(): void {
		parent::setUp();

		$this->filtered = array();
		SiteOrigin_Test_Widget::$modify_instance_callback = null;
		SiteOrigin_Test_Widget::$form_filter_calls = 0;

		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value ) {
				$this->filtered[ $hook ][] = func_get_args();

				return $value;
			}
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['SITEORIGIN_PANELS_PREVIEW_RENDER'] );

		parent::tearDown();
	}

	private function widget() {
		return new SiteOrigin_Test_Widget();
	}

	private function complete_instance() {
		return array(
			'title' => 'Hello',
			'design' => array(
				'text' => 'body',
				'box' => array( 'color' => '#123456' ),
			),
			'shadow' => array( 'color' => '#000000' ),
			'items' => array(
				array( 'text' => 'one', 'meta' => array( 'note' => 'a' ) ),
			),
			'button' => array( 'icon' => array( 'name' => 'star' ) ),
			'query' => 'post_type=post',
		);
	}

	/**
	 * Stored shapes with one container as an empty string, keyed by the
	 * dot path that holds it.
	 */
	public static function empty_container_shapes() {
		return array(
			'section' => array( 'design' ),
			'nested section' => array( 'design.box' ),
			'toggle' => array( 'shadow' ),
			'repeater' => array( 'items' ),
			'repeater row' => array( 'items.0' ),
			'widget' => array( 'button' ),
		);
	}

	private function with_empty( $path ) {
		$instance = $this->complete_instance();
		$ref = &$instance;

		foreach ( explode( '.', $path ) as $key ) {
			$ref = &$ref[ $key ];
		}
		$ref = '';

		return $instance;
	}

	/**
	 * Harness self-check: the fake filesystem lets delete_css() run to the
	 * style hash, so the raw nested read in get_less_variables() executes
	 * against the stored old instance. A stored old instance whose
	 * container holds a value the normaliser leaves alone still reaches
	 * that read and still fatals, which proves the read is on the path.
	 */
	public function test_save_path_reads_the_stored_old_instance() {
		$old = $this->complete_instance();
		$old['design'] = 'not-an-array';

		$this->expectException( TypeError::class );
		$this->expectExceptionMessage( 'Cannot access offset of type string on string' );

		$this->widget()->update( $this->complete_instance(), $old );
	}

	/**
	 * The test widget reads its nested section raw, so an emptied section
	 * still produces "undefined array key" warnings from that read; they are
	 * collected rather than surfaced because what this row proves is that
	 * the read no longer throws. The shipped widgets that read raw on this
	 * path coalesce their reads, and WidgetStringContainerTest asserts those
	 * are silent.
	 */
	#[DataProvider( 'empty_container_shapes' )]
	public function test_update_survives_an_empty_string_container_in_the_stored_instance( $path ) {
		list( $stored ) = $this->run_capturing_errors(
			fn() => $this->widget()->update( $this->complete_instance(), $this->with_empty( $path ) )
		);

		$this->assertSame( 'Hello', $stored['title'] );
		$this->assertSame( $this->complete_instance()['design'], $stored['design'] );
	}

	#[DataProvider( 'empty_container_shapes' )]
	public function test_update_survives_a_modify_instance_that_writes_through_the_container( $path ) {
		// The shape seven shipped widgets use: an empty() test that is safe
		// on a string, followed by a write that is not.
		SiteOrigin_Test_Widget::$modify_instance_callback = function ( $instance ) {
			if ( empty( $instance['design']['box']['color'] ) ) {
				$instance['design']['box']['color'] = '#000000';
			}

			if ( empty( $instance['items'][0]['text'] ) ) {
				$instance['items'][0]['text'] = 'default';
			}

			return $instance;
		};

		list( $stored ) = $this->run_capturing_errors(
			fn() => $this->widget()->update( $this->with_empty( $path ), $this->with_empty( $path ) )
		);

		$this->assertIsArray( $stored['design'] );
		$this->assertIsArray( $stored['items'] );
	}

	public function test_update_adds_no_key_the_submitted_instance_lacked() {
		$new = $this->complete_instance();
		unset( $new['shadow'], $new['button'] );

		list( $stored ) = $this->run_capturing_errors(
			fn() => $this->widget()->update( $new, $this->with_empty( 'design' ) )
		);
		unset( $stored['_sow_form_timestamp'] );

		// The sanitizer stores every declared field, so the keys are the
		// form's keys; nothing beyond them is added by the save path.
		$keys = array_keys( $stored );
		sort( $keys );
		$this->assertSame( array( 'button', 'design', 'items', 'query', 'shadow', 'title' ), $keys );
	}

	public function test_a_second_update_of_sanitised_output_is_byte_identical() {
		$widget = $this->widget();

		list( $first ) = $this->run_capturing_errors(
			fn() => $widget->update( $this->with_empty( 'design' ), $this->with_empty( 'design' ) )
		);
		unset( $first['_sow_form_timestamp'] );

		$second = $widget->update( $first, $first );
		unset( $second['_sow_form_timestamp'] );

		$this->assertSame( $first, $second );
	}

	public static function untouched_container_values() {
		return array(
			'arrays' => array( "\0keep" ),
			'null' => array( null ),
			'non-empty string' => array( 'not-an-array' ),
			'zero' => array( 0 ),
			'false' => array( false ),
			'object' => array( (object) array( 'text' => 'x' ) ),
		);
	}

	private function normalize( $widget, $instance ) {
		$method = new ReflectionMethod( $widget, 'normalize_container_values' );
		$method->setAccessible( true );

		return $method->invoke( $widget, $widget->form_options(), $instance );
	}

	/**
	 * Only '' is a shape the sanitizer stored for an absent container, so
	 * only '' is normalised; anything else is left exactly as found.
	 */
	#[DataProvider( 'untouched_container_values' )]
	public function test_normaliser_leaves_every_other_value_identical( $value ) {
		$instance = $this->complete_instance();

		if ( $value !== "\0keep" ) {
			foreach ( array( 'design', 'shadow', 'items', 'button' ) as $key ) {
				$instance[ $key ] = $value;
			}
		}

		$widget = $this->widget();

		$this->assertSame( $instance, $this->normalize( $widget, $instance ) );
	}

	/**
	 * A container the instance lacks stays absent: the normaliser repairs
	 * shape, it never supplies defaults.
	 */
	public function test_normaliser_adds_no_key_to_a_partial_instance() {
		$instance = array( 'title' => 'Hello', 'design' => '' );

		$this->assertSame(
			array( 'title' => 'Hello', 'design' => array() ),
			$this->normalize( $this->widget(), $instance )
		);
	}

	public function test_widget_render_normalises_the_instance_it_reads() {
		$GLOBALS['SITEORIGIN_PANELS_PREVIEW_RENDER'] = true;
		Functions\when( 'siteorigin_widget_print_styles' )->justReturn( null );
		Functions\when( 'siteorigin_sanitize_attribute_key' )->returnArg();

		ob_start();
		$this->run_capturing_errors( fn() => $this->widget()->widget( array(), $this->with_empty( 'design' ) ) );
		$html = ob_get_clean();

		$this->assertStringContainsString( '<p>Hello</p>', $html );
		$this->assertSame(
			array(),
			$this->filtered['siteorigin_widgets_instance_so-test-widget'][0][1]['design'],
			'siteorigin_widgets_instance_{id_base} must receive the normalised copy'
		);
	}

	/**
	 * The form's instance filter fires after the instance is normalised,
	 * migrated and defaulted, and before any markup is rendered; the
	 * assertion is made there and the render is cut short with a sentinel,
	 * so the admin form's markup dependencies stay out of the suite. The
	 * repeater is the container to watch: add_defaults() repairs an
	 * empty-string section on its own but leaves an empty-string repeater
	 * as it is, so only the normaliser can turn it into an array here.
	 */
	public function test_form_normalises_the_instance_it_reads() {
		$seen = null;
		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value ) use ( &$seen ) {
				if ( $hook === 'siteorigin_widgets_form_instance_so-test-widget' ) {
					$seen = $value;
					throw new RuntimeException( 'stop before rendering' );
				}

				return $value;
			}
		);

		$instance = $this->with_empty( 'items' );
		$instance['design'] = '';

		try {
			$this->widget()->form( $instance );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'stop before rendering', $e->getMessage() );
		}

		$this->assertSame( array(), $seen['items'] );
		$this->assertIsArray( $seen['design'] );
		$this->assertSame( array( 'color' => '' ), $seen['design']['box'] );
	}

	/**
	 * Neither the normaliser nor the CSS deletion may run callbacks the
	 * save path did not run before: one update() and one render construct
	 * the widget field's sub-form exactly the number of times they did
	 * before the normaliser existed (measured on the unmodified base class).
	 */
	public function test_form_filter_call_counts_are_unchanged() {
		$GLOBALS['SITEORIGIN_PANELS_PREVIEW_RENDER'] = true;
		Functions\when( 'siteorigin_widget_print_styles' )->justReturn( null );
		Functions\when( 'siteorigin_sanitize_attribute_key' )->returnArg();

		$widget = $this->widget();
		$widget->update( $this->complete_instance(), $this->complete_instance() );
		$after_update = SiteOrigin_Test_Widget::$form_filter_calls;

		ob_start();
		$widget->widget( array(), $this->complete_instance() );
		ob_end_clean();
		$after_render = SiteOrigin_Test_Widget::$form_filter_calls;

		$this->assertSame(
			array( 'after_update' => 1, 'after_render' => 2 ),
			array( 'after_update' => $after_update, 'after_render' => $after_render )
		);

		// Repairing an empty-string container must not add a call either.
		SiteOrigin_Test_Widget::$form_filter_calls = 0;
		$widget = $this->widget();
		$this->run_capturing_errors( fn() => $widget->update( $this->with_empty( 'button' ), $this->with_empty( 'design' ) ) );
		$this->assertSame( 1, SiteOrigin_Test_Widget::$form_filter_calls );

		ob_start();
		$this->run_capturing_errors( fn() => $widget->widget( array(), $this->with_empty( 'button' ) ) );
		ob_end_clean();
		$this->assertSame( 2, SiteOrigin_Test_Widget::$form_filter_calls );
	}

	public function test_stylesheet_deleted_hook_receives_a_normalised_instance() {
		$received = null;
		Functions\when( 'do_action' )->alias(
			function ( $hook, $name = null, $instance = null ) use ( &$received ) {
				if ( $hook === 'siteorigin_widgets_stylesheet_deleted' ) {
					$received = $instance;
				}
			}
		);

		$this->run_capturing_errors(
			fn() => $this->widget()->update( $this->complete_instance(), $this->with_empty( 'design' ) )
		);

		$this->assertSame( array(), $received['design'] );
	}
}

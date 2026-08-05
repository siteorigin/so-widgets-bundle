<?php

namespace SiteOrigin\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/*
 * Real-class collaborators for compat/block-editor/widget-block.php, defined at
 * FILE LOAD time so combined-suite runs get the same definitions regardless of
 * load order. The chokepoint resolves widgets via
 * SiteOrigin_Widgets_Widget_Manager::get_widget_instance() and
 * SiteOrigin_Widgets_Bundle::single()->load_missing_widget(), and gates on
 * is_subclass_of( $widget, 'SiteOrigin_Widget' ) — so a marker base class and
 * both collaborator shims are required.
 */
if ( ! class_exists( 'WP_Error', false ) ) {
	eval(
		'class WP_Error {'
		. ' public $code; public $message; public $data;'
		. ' public function __construct( $code = "", $message = "", $data = "" ) {'
		. '   $this->code = $code; $this->message = $message; $this->data = $data;'
		. ' }'
		. ' public function get_error_code() { return $this->code; }'
		. ' public function get_error_message() { return $this->message; }'
		. '}'
	);
}

if ( ! function_exists( 'is_wp_error' ) ) {
	eval( 'function is_wp_error( $thing ) { return $thing instanceof \WP_Error; }' );
}

if ( ! class_exists( 'SiteOrigin_Widget', false ) ) {
	eval( 'class SiteOrigin_Widget { public $id_base = "sow-test"; public $name = "SiteOrigin Test"; }' );
}

if ( ! class_exists( 'SiteOrigin_Widgets_Bundle', false ) ) {
	eval(
		'class SiteOrigin_Widgets_Bundle {'
		. ' public static function single() {'
		. '   static $single;'
		. '   return empty( $single ) ? $single = new self() : $single;'
		. ' }'
		. ' public function get_widgets_list() { return array(); }'
		. ' public function load_missing_widget( $widget, $class ) {'
		. '   return isset( $GLOBALS["sowb_test_missing_widgets"][ $class ] ) ? $GLOBALS["sowb_test_missing_widgets"][ $class ] : null;'
		. ' }'
		. '}'
	);
}

if ( ! class_exists( 'SiteOrigin_Widgets_Widget_Manager', false ) ) {
	eval(
		'class SiteOrigin_Widgets_Widget_Manager {'
		. ' public static function single() {'
		. '   static $single;'
		. '   return empty( $single ) ? $single = new self() : $single;'
		. ' }'
		. ' public static function get_widget_instance( $class ) {'
		. '   global $wp_widget_factory;'
		. '   return ! empty( $wp_widget_factory->widgets[ $class ] ) ? $wp_widget_factory->widgets[ $class ] : null;'
		. ' }'
		. ' public function get_class_from_path( $path ) { return ""; }'
		. '}'
	);
}

/**
 * Widget stub whose update() passes content through UNCHANGED while counting
 * invocations. Sanitization is deliberately a no-op so these tests isolate the
 * kses floor: any content stripping observed after the chokepoint came from
 * the floor, never from the widget's own sanitizer.
 */
class Chokepoint_IdentityWidgetStub extends \SiteOrigin_Widget {
	public $update_calls = 0;

	public function update( $new, $old ) {
		$this->update_calls++;

		return $new;
	}

	public function widget( $args, $instance ) {
		echo '<div class="sow-rendered">' . ( isset( $instance['content'] ) ? $instance['content'] : '' ) . '</div>';
	}
}

/**
 * Widget stub whose update() throws, for proving flag restoration on the
 * exception path.
 */
class Chokepoint_ThrowingWidgetStub extends \SiteOrigin_Widget {
	public function update( $new, $old ) {
		throw new \RuntimeException( 'widget update exploded' );
	}

	public function widget( $args, $instance ) {
	}
}

/**
 * Tests for SiteOrigin_Widgets_Bundle_Widget_Block::sanitize_widget_block_untrusted()
 * against the REAL widget-block class.
 *
 * Properties locked:
 * (16) The widget's update() runs exactly ONCE per untrusted sanitize.
 * (17) The kses floor is forced regardless of `unfiltered_html` — hostile
 *      markup is stripped from both the persisted widgetData AND the
 *      regenerated widgetMarkup (markup is rendered from the floored instance).
 * (18) Inbound widgetMarkup/widgetIcons are never persisted — both are
 *      regenerated server-side.
 * (19) anchor and className survive the chokepoint byte-for-byte.
 * (20) Non-string leaves pass the floor untouched.
 * (21) Unresolvable widgetClass yields a WP_Error, never a partial write.
 * (21b) force_kses_floor flag discipline: restored after success, restored
 *       when update() throws, prior value preserved on nested use.
 *
 * NOTE: `: void` return types on setUp()/tearDown() are required by PHPUnit 12.
 */
class WidgetBlockChokepointTest extends TestCase {
	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'admin_url' )->returnArg();
		Functions\when( 'wp_normalize_path' )->returnArg();
		Functions\when( 'current_user_can' )->justReturn( true );

		// wp_kses_post(): emulate the relevant behaviour — strip <script>
		// elements and on* event-handler attributes carrying the XSS payload.
		Functions\when( 'wp_kses_post' )->alias(
			array( \SiteOrigin\Tests\KsesEmulation::class, 'filter' )
		);

		// Icon collection reads the global styles queue.
		Functions\when( 'wp_styles' )->alias(
			function () {
				return (object) array(
					'queue' => array(
						'siteorigin-widget-icon-font-fontawesome',
						'unrelated-style',
					),
				);
			}
		);

		$GLOBALS['wp_widget_factory'] = (object) array( 'widgets' => array() );
		$GLOBALS['sowb_test_missing_widgets'] = array();

		$this->require_classes();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_widget_factory'], $GLOBALS['sowb_test_missing_widgets'] );
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Load compat/block-editor/widget-block.php once per process, with the
	 * constructor-time function stubs already in place (the file
	 * self-instantiates its singleton at load).
	 */
	private function require_classes() {
		if ( class_exists( 'SiteOrigin_Widgets_Bundle_Widget_Block', false ) ) {
			return;
		}

		Functions\when( 'register_block_type' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'get_post_types' )->justReturn( array() );

		require_once dirname( dirname( __DIR__ ) ) . '/compat/block-editor/widget-block.php';
	}

	private function widget_block() {
		$reflection = new \ReflectionClass( \SiteOrigin_Widgets_Bundle_Widget_Block::class );

		return $reflection->newInstanceWithoutConstructor();
	}

	private function register_widget( $class, $widget ) {
		$GLOBALS['wp_widget_factory']->widgets[ $class ] = $widget;
	}

	private function attrs( array $overrides = array() ) {
		return array_merge(
			array(
				'widgetClass' => 'Chokepoint_Test_Widget',
				'widgetData' => array( 'content' => 'safe text' ),
			),
			$overrides
		);
	}

	private function flag_value( $instance ) {
		$property = new \ReflectionProperty( \SiteOrigin_Widgets_Bundle_Widget_Block::class, 'force_kses_floor' );
		$property->setAccessible( true );

		return $property->getValue( $instance );
	}

	private function set_flag( $instance, $value ) {
		$property = new \ReflectionProperty( \SiteOrigin_Widgets_Bundle_Widget_Block::class, 'force_kses_floor' );
		$property->setAccessible( true );
		$property->setValue( $instance, $value );
	}

	public function test_untrusted_write_runs_widget_update_exactly_once() {
		$widget = new Chokepoint_IdentityWidgetStub();
		$this->register_widget( 'Chokepoint_Test_Widget', $widget );

		$block = $this->widget_block();
		$result = $block->sanitize_widget_block_untrusted( $this->attrs() );

		$this->assertIsArray( $result );
		$this->assertSame( 1, $widget->update_calls );
	}

	public function test_kses_floor_forced_despite_unfiltered_html() {
		// current_user_can() returns true in setUp — the credential is
		// `unfiltered_html`-capable, and the identity widget performs no
		// sanitization of its own. Any stripping below is the forced floor.
		$widget = new Chokepoint_IdentityWidgetStub();
		$this->register_widget( 'Chokepoint_Test_Widget', $widget );

		$hostile = '<script>alert(1)</script><b>ok</b><img src="x" onerror="alert(2)">';
		$block = $this->widget_block();
		$result = $block->sanitize_widget_block_untrusted(
			$this->attrs( array( 'widgetData' => array( 'content' => $hostile ) ) )
		);

		$this->assertIsArray( $result );
		$this->assertStringNotContainsString( '<script>', $result['widgetData']['content'] );
		$this->assertStringNotContainsString( 'onerror', $result['widgetData']['content'] );
		$this->assertStringContainsString( '<b>ok</b>', $result['widgetData']['content'] );

		// The regenerated markup was rendered FROM the floored instance.
		$this->assertStringNotContainsString( '<script>', $result['widgetMarkup'] );
		$this->assertStringNotContainsString( 'onerror', $result['widgetMarkup'] );
	}

	public function test_inbound_widget_markup_is_dropped_and_regenerated() {
		$widget = new Chokepoint_IdentityWidgetStub();
		$this->register_widget( 'Chokepoint_Test_Widget', $widget );

		$block = $this->widget_block();
		$result = $block->sanitize_widget_block_untrusted(
			$this->attrs(
				array(
					'widgetMarkup' => '<script>poisoned cache</script>',
					'widgetIcons' => array( 'attacker-supplied-style' ),
				)
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringNotContainsString( 'poisoned cache', $result['widgetMarkup'] );
		$this->assertStringContainsString( 'sow-rendered', $result['widgetMarkup'] );
		$this->assertSame( array( 'siteorigin-widget-icon-font-fontawesome' ), $result['widgetIcons'] );
	}

	public function test_anchor_and_class_name_preserved() {
		$widget = new Chokepoint_IdentityWidgetStub();
		$this->register_widget( 'Chokepoint_Test_Widget', $widget );

		$block = $this->widget_block();
		$result = $block->sanitize_widget_block_untrusted(
			$this->attrs(
				array(
					'anchor' => 'my-custom-anchor',
					'className' => 'is-style-fancy extra-class',
				)
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'my-custom-anchor', $result['anchor'] );
		$this->assertSame( 'is-style-fancy extra-class', $result['className'] );
	}

	public function test_non_string_leaves_untouched() {
		$widget = new Chokepoint_IdentityWidgetStub();
		$this->register_widget( 'Chokepoint_Test_Widget', $widget );

		$data = array(
			'content' => 'text',
			'count' => 42,
			'enabled' => true,
			'nothing' => null,
			'nested' => array( 'depth' => 3.5, 'label' => '<script>x</script>keep' ),
		);

		$block = $this->widget_block();
		$result = $block->sanitize_widget_block_untrusted(
			$this->attrs( array( 'widgetData' => $data ) )
		);

		$this->assertIsArray( $result );
		$this->assertSame( 42, $result['widgetData']['count'] );
		$this->assertTrue( $result['widgetData']['enabled'] );
		$this->assertNull( $result['widgetData']['nothing'] );
		$this->assertSame( 3.5, $result['widgetData']['nested']['depth'] );
		$this->assertSame( 'keep', $result['widgetData']['nested']['label'] );
	}

	public function test_invalid_widget_class_returns_wp_error() {
		// No widget registered and load_missing_widget resolves nothing.
		$block = $this->widget_block();
		$result = $block->sanitize_widget_block_untrusted(
			$this->attrs( array( 'widgetClass' => 'Nonexistent_Widget_Class' ) )
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	public function test_flag_restored_after_success() {
		$widget = new Chokepoint_IdentityWidgetStub();
		$this->register_widget( 'Chokepoint_Test_Widget', $widget );

		$block = $this->widget_block();
		$block->sanitize_widget_block_untrusted( $this->attrs() );

		$this->assertFalse( $this->flag_value( $block ) );
	}

	public function test_update_throw_becomes_wp_error_and_flag_restored() {
		// A widget whose update()/render chain throws (e.g. a
		// DivisionByZeroError in LESS variable generation on a partial
		// instance) must be caught and returned as a structured WP_Error, not
		// allowed to fatal the request. The flag is still restored.
		$widget = new Chokepoint_ThrowingWidgetStub();
		$this->register_widget( 'Chokepoint_Test_Widget', $widget );

		$block = $this->widget_block();
		$result = $block->sanitize_widget_block_untrusted( $this->attrs() );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'sowb_widget_sanitize_failed', $result->get_error_code() );
		$this->assertFalse( $this->flag_value( $block ) );
	}

	public function test_flag_prior_value_preserved_on_reentrant_call() {
		$widget = new Chokepoint_IdentityWidgetStub();
		$this->register_widget( 'Chokepoint_Test_Widget', $widget );

		$block = $this->widget_block();
		$this->set_flag( $block, true );

		$block->sanitize_widget_block_untrusted( $this->attrs() );

		// The pre-existing TRUE state (an outer untrusted sanitize in
		// progress) survives the nested call.
		$this->assertTrue( $this->flag_value( $block ) );
	}

	public function test_flag_restored_when_preview_returns_wp_error() {
		$block = $this->widget_block();
		$result = $block->sanitize_widget_block_untrusted(
			$this->attrs( array( 'widgetData' => array() ) )
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertFalse( $this->flag_value( $block ) );
	}
}

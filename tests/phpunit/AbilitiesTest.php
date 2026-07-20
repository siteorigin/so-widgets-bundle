<?php

namespace SiteOrigin\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/*
 * File-scope collaborator shims, all guarded so combined-suite runs get one
 * definition regardless of load order (WidgetBlockChokepointTest.php defines
 * the same set). Brain Monkey mocks functions, not classes.
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

/*
 * Real wp_slash()/wp_unslash() so the stubbed wp_update_post can mirror core's
 * unslashing (wp_insert_post runs wp_unslash on its input). This is what lets
 * the slashing regression test observe the bug the production wp_slash()
 * guards against: unslashed content would have its JSON-escape backslashes
 * stripped by the stub's wp_unslash().
 */
if ( ! function_exists( 'wp_slash' ) ) {
	eval(
		'function wp_slash( $value ) {'
		. ' if ( is_array( $value ) ) { return array_map( "wp_slash", $value ); }'
		. ' return is_string( $value ) ? addcslashes( $value, "\'\"\\\\" ) : $value;'
		. '}'
		. 'function wp_unslash( $value ) {'
		. ' if ( is_array( $value ) ) { return array_map( "wp_unslash", $value ); }'
		. ' return is_string( $value ) ? stripslashes( $value ) : $value;'
		. '}'
	);
}

/*
 * Real-function stubs for the Abilities API. Registration guards on
 * function_exists(); Brain Monkey cannot satisfy a function_exists() check,
 * so these are genuine functions capturing each registration into globals for
 * the registration-shape tests to inspect.
 */
if ( ! function_exists( 'wp_register_ability' ) ) {
	eval(
		'function wp_register_ability( $id, $args ) {'
		. ' $GLOBALS["abilities_registered"][ $id ] = $args;'
		. ' return true;'
		. '}'
		. 'function wp_register_ability_category( $id, $args ) {'
		. ' $GLOBALS["ability_categories_registered"][ $id ] = $args;'
		. ' return true;'
		. '}'
	);
}

/**
 * Identity widget: update() passes content through unchanged while counting
 * invocations; widget() echoes a fixed marker plus the content leaf. Any
 * stripping observed after an update came from the forced kses floor, never
 * from the widget's own sanitizer.
 */
class Abilities_IdentityWidgetStub extends \SiteOrigin_Widget {
	public $update_calls = 0;
	public $last_update_input = null;

	public function update( $new, $old ) {
		$this->update_calls++;
		$this->last_update_input = $new;

		return $new;
	}

	public function widget( $args, $instance ) {
		echo '<div class="sow-rendered">' . ( isset( $instance['content'] ) ? $instance['content'] : '' ) . '</div>';
	}
}

/**
 * Unit tests for SiteOrigin_Widgets_Bundle_Abilities (registration shape,
 * permissions, targeting/ambiguity, write-path plumbing). The untrusted
 * chokepoint runs for REAL (same process as WidgetBlockChokepointTest) with
 * the identity widget doubling as the spy: chokepoint reached ⇔ update_calls
 * incremented.
 */
class AbilitiesTest extends TestCase {
	use MockeryPHPUnitIntegration;

	/** @var object|null Post fixture returned by the get_post alias. */
	private $post;

	/** @var array Block fixture returned by the parse_blocks alias. */
	private $blocks = array();

	/** @var array|null Captured wp_update_post input (post-unslash, mirroring core). */
	private $updated_post = null;

	/** @var int|\WP_Error Return value for the wp_update_post stub. */
	private $update_post_result = 123;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->post = null;
		$this->blocks = array();
		$this->updated_post = null;
		$this->update_post_result = 123;

		$GLOBALS['abilities_registered'] = array();
		$GLOBALS['ability_categories_registered'] = array();
		$GLOBALS['wp_widget_factory'] = (object) array( 'widgets' => array() );
		$GLOBALS['sowb_test_missing_widgets'] = array();

		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'admin_url' )->returnArg();
		Functions\when( 'wp_normalize_path' )->returnArg();

		// Granted by default so happy-path tests exercise behavior; denial
		// tests override with justReturn( false ).
		Functions\when( 'current_user_can' )->justReturn( true );

		Functions\when( 'wp_is_post_revision' )->justReturn( false );

		$test = $this;
		Functions\when( 'get_post' )->alias(
			function ( $post_id = null ) use ( $test ) {
				return $test->post;
			}
		);

		Functions\when( 'has_blocks' )->alias(
			function ( $content ) {
				return is_string( $content ) && strpos( $content, '<!-- wp:' ) !== false;
			}
		);

		Functions\when( 'parse_blocks' )->alias(
			function ( $content ) use ( $test ) {
				return $test->blocks;
			}
		);

		// Real-ish serialize_blocks: JSON-encodes each block's attrs into a
		// comment-delimited string (recursing innerBlocks), so backslash and
		// quote survival through the slashing round-trip is actually
		// exercised.
		Functions\when( 'serialize_blocks' )->alias(
			function ( $blocks ) use ( $test ) {
				return $test->serialize_blocks_fixture( $blocks );
			}
		);

		Functions\when( 'wp_update_post' )->alias(
			function ( $postarr, $wp_error = false ) use ( $test ) {
				// Mirror core: wp_insert_post() unslashes its input.
				$test->updated_post = wp_unslash( $postarr );

				return $test->update_post_result;
			}
		);

		// Chokepoint plumbing (the real chokepoint runs in these tests).
		Functions\when( 'wp_kses_post' )->alias(
			function ( $value ) {
				$value = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', (string) $value );

				return preg_replace( '/\s*on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $value );
			}
		);

		Functions\when( 'wp_styles' )->alias(
			function () {
				return (object) array( 'queue' => array() );
			}
		);

		$this->require_classes();
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['abilities_registered'],
			$GLOBALS['ability_categories_registered'],
			$GLOBALS['wp_widget_factory'],
			$GLOBALS['sowb_test_missing_widgets']
		);
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Load the production classes once per process, with constructor-time
	 * function stubs already in place (each file self-instantiates at load).
	 */
	private function require_classes() {
		Functions\when( 'register_block_type' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'get_post_types' )->justReturn( array() );

		if ( ! class_exists( 'SiteOrigin_Widgets_Bundle_Widget_Block', false ) ) {
			require_once dirname( dirname( __DIR__ ) ) . '/compat/block-editor/widget-block.php';
		}

		if ( ! class_exists( 'SiteOrigin_Widgets_Bundle_AI_Exposure', false ) ) {
			require_once dirname( dirname( __DIR__ ) ) . '/compat/block-editor/ai-exposure.php';
		}

		if ( ! class_exists( 'SiteOrigin_Widgets_Bundle_Abilities', false ) ) {
			require_once dirname( dirname( __DIR__ ) ) . '/compat/block-editor/abilities.php';
		}
	}

	public function serialize_blocks_fixture( $blocks ) {
		$out = '';

		foreach ( $blocks as $block ) {
			$name = isset( $block['blockName'] ) ? $block['blockName'] : '';
			$attrs = isset( $block['attrs'] ) ? json_encode( $block['attrs'] ) : '{}';
			$inner = ! empty( $block['innerBlocks'] ) ? $this->serialize_blocks_fixture( $block['innerBlocks'] ) : '';
			$out .= '<!-- wp:' . $name . ' ' . $attrs . ' -->' . $inner . '<!-- /wp:' . $name . ' -->';
		}

		return $out;
	}

	private function abilities() {
		return \SiteOrigin_Widgets_Bundle_Abilities::single();
	}

	private function register_abilities() {
		$abilities = $this->abilities();
		$abilities->register_ability_category();
		$abilities->register_abilities();
	}

	private function register_identity_widget( $class = 'Abilities_Test_Widget' ) {
		$widget = new Abilities_IdentityWidgetStub();
		$GLOBALS['wp_widget_factory']->widgets[ $class ] = $widget;

		return $widget;
	}

	private function sowb_block( $class = 'Abilities_Test_Widget', array $data = array( 'content' => 'hello' ), array $extra_attrs = array() ) {
		return array(
			'blockName' => 'sowb/abilities-test-widget',
			'attrs' => array_merge(
				array(
					'widgetClass' => $class,
					'widgetData' => $data,
				),
				$extra_attrs
			),
			'innerBlocks' => array(),
		);
	}

	private function set_post_with_blocks( array $blocks ) {
		$this->post = (object) array(
			'ID' => 123,
			'post_content' => '<!-- wp:fixture -->',
		);
		$this->blocks = $blocks;
	}

	// ---- Registration shape ----

	public function test_registers_exactly_the_locked_abilities() {
		$this->register_abilities();

		$this->assertSame(
			array( 'sowb/widget-get', 'sowb/widget-update' ),
			array_keys( $GLOBALS['abilities_registered'] )
		);
	}

	public function test_widget_get_registration_meta_and_category() {
		$this->register_abilities();

		$get = $GLOBALS['abilities_registered']['sowb/widget-get'];
		$this->assertTrue( $get['meta']['readonly'] );
		$this->assertTrue( $get['meta']['show_in_rest'] );
		$this->assertSame( 'so-widgets-bundle', $get['category'] );
		$this->assertSame( array( 'post_id' ), $get['input_schema']['required'] );
		$this->assertFalse( $get['input_schema']['additionalProperties'] );
	}

	public function test_widget_update_registration_not_readonly() {
		$this->register_abilities();

		$update = $GLOBALS['abilities_registered']['sowb/widget-update'];
		$this->assertArrayNotHasKey( 'readonly', $update['meta'] );
		$this->assertTrue( $update['meta']['show_in_rest'] );
		$this->assertSame( array( 'post_id', 'widget_data' ), $update['input_schema']['required'] );
		$this->assertNotEmpty( $update['permission_callback'] );
	}

	public function test_registers_the_ability_category() {
		$this->register_abilities();

		$this->assertArrayHasKey( 'so-widgets-bundle', $GLOBALS['ability_categories_registered'] );
	}

	// ---- Permissions ----

	public function test_permissions_denied_without_edit_post() {
		Functions\when( 'current_user_can' )->justReturn( false );

		$abilities = $this->abilities();

		$this->assertInstanceOf( \WP_Error::class, $abilities->widget_get_permission( array( 'post_id' => 5 ) ) );
		$this->assertInstanceOf( \WP_Error::class, $abilities->widget_update_permission( array( 'post_id' => 5 ) ) );
	}

	public function test_execute_callbacks_recheck_cap_on_direct_call() {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'get_post' )->never();

		$abilities = $this->abilities();

		$get = $abilities->widget_get( array( 'post_id' => 5 ) );
		$update = $abilities->widget_update( array( 'post_id' => 5, 'widget_data' => array( 'a' => 'b' ) ) );

		$this->assertInstanceOf( \WP_Error::class, $get );
		$this->assertInstanceOf( \WP_Error::class, $update );
	}

	// ---- Walk / indexing agreement ----

	public function test_get_and_update_walk_agree_on_nested_indexing() {
		$widget = $this->register_identity_widget();
		$nested = array(
			array(
				'blockName' => 'core/group',
				'attrs' => array(),
				'innerBlocks' => array( $this->sowb_block() ),
			),
		);
		$this->set_post_with_blocks( $nested );

		$get = $this->abilities()->widget_get( array( 'post_id' => 123 ) );

		$this->assertSame( 1, $get['widget_count'] );
		$this->assertSame( 0, $get['widgets'][0]['widget_index'] );
		$this->assertSame( array( 'content' => 'hello' ), $get['widgets'][0]['widget_data'] );

		// Update the index widget-get reported; the nested block is the one
		// that gets rewritten.
		$update = $this->abilities()->widget_update(
			array(
				'post_id' => 123,
				'widget_index' => 0,
				'widget_data' => array( 'content' => 'rewritten' ),
			)
		);

		$this->assertTrue( $update['updated'] );
		$this->assertSame( 1, $widget->update_calls );
		$this->assertSame( array( 'content' => 'rewritten' ), $widget->last_update_input );
		$this->assertStringContainsString( 'rewritten', $this->updated_post['post_content'] );
		$this->assertStringContainsString( 'core/group', $this->updated_post['post_content'] );
	}

	// ---- Ambiguity ----

	public function test_update_multi_block_missing_index_is_ambiguous() {
		$widget = $this->register_identity_widget();
		$this->set_post_with_blocks( array( $this->sowb_block(), $this->sowb_block() ) );

		$result = $this->abilities()->widget_update(
			array( 'post_id' => 123, 'widget_data' => array( 'content' => 'x' ) )
		);

		$this->assertFalse( $result['updated'] );
		$this->assertSame( 'widget-ambiguous', $result['status'] );
		$this->assertStringContainsString( '0-1', $result['message'] );
		$this->assertSame( 0, $widget->update_calls );
		$this->assertNull( $this->updated_post );
	}

	public function test_update_out_of_range_index_is_ambiguous() {
		$widget = $this->register_identity_widget();
		$this->set_post_with_blocks( array( $this->sowb_block(), $this->sowb_block() ) );

		$result = $this->abilities()->widget_update(
			array( 'post_id' => 123, 'widget_index' => 5, 'widget_data' => array( 'content' => 'x' ) )
		);

		$this->assertFalse( $result['updated'] );
		$this->assertSame( 'widget-ambiguous', $result['status'] );
		$this->assertSame( 0, $widget->update_calls );
	}

	public function test_update_single_block_no_index_writes_block_zero() {
		$widget = $this->register_identity_widget();
		$this->set_post_with_blocks( array( $this->sowb_block() ) );

		$result = $this->abilities()->widget_update(
			array( 'post_id' => 123, 'widget_data' => array( 'content' => 'new' ) )
		);

		$this->assertTrue( $result['updated'] );
		$this->assertSame( 0, $result['widget_index'] );
		$this->assertSame( 'ok', $result['status'] );
		$this->assertSame( 1, $widget->update_calls );
	}

	public function test_update_single_block_nonzero_index_declined() {
		$widget = $this->register_identity_widget();
		$this->set_post_with_blocks( array( $this->sowb_block() ) );

		$result = $this->abilities()->widget_update(
			array( 'post_id' => 123, 'widget_index' => 1, 'widget_data' => array( 'content' => 'new' ) )
		);

		$this->assertFalse( $result['updated'] );
		$this->assertSame( 'widget-ambiguous', $result['status'] );
		$this->assertSame( 0, $widget->update_calls );
	}

	// ---- Input validation ----

	public function test_non_array_widget_data_is_declined_without_wiping() {
		$widget = $this->register_identity_widget();
		$this->set_post_with_blocks( array( $this->sowb_block() ) );

		$result = $this->abilities()->widget_update(
			array( 'post_id' => 123, 'widget_data' => 'a string' )
		);

		$this->assertFalse( $result['updated'] );
		$this->assertSame( 'unsupported', $result['status'] );
		$this->assertSame( 0, $widget->update_calls );
		$this->assertNull( $this->updated_post );
	}

	public function test_empty_widget_data_is_declined() {
		$widget = $this->register_identity_widget();
		$this->set_post_with_blocks( array( $this->sowb_block() ) );

		$result = $this->abilities()->widget_update(
			array( 'post_id' => 123, 'widget_data' => array() )
		);

		$this->assertFalse( $result['updated'] );
		$this->assertSame( 'unsupported', $result['status'] );
		$this->assertSame( 0, $widget->update_calls );
	}

	public function test_object_widget_data_is_normalized_full_depth() {
		$widget = $this->register_identity_widget();
		$this->set_post_with_blocks( array( $this->sowb_block() ) );

		$data = new \stdClass();
		$data->content = 'from object';
		$data->nested = new \stdClass();
		$data->nested->label = 'deep';

		$result = $this->abilities()->widget_update(
			array( 'post_id' => 123, 'widget_data' => $data )
		);

		$this->assertTrue( $result['updated'] );
		$this->assertSame(
			array( 'content' => 'from object', 'nested' => array( 'label' => 'deep' ) ),
			$widget->last_update_input
		);
	}

	public function test_widget_class_assertion_mismatch_declined() {
		$widget = $this->register_identity_widget();
		$this->set_post_with_blocks( array( $this->sowb_block() ) );

		$result = $this->abilities()->widget_update(
			array(
				'post_id' => 123,
				'widget_data' => array( 'content' => 'x' ),
				'widget_class' => 'Some_Other_Widget',
			)
		);

		$this->assertFalse( $result['updated'] );
		$this->assertSame( 'unsupported', $result['status'] );
		$this->assertStringContainsString( 'Abilities_Test_Widget', $result['message'] );
		$this->assertSame( 0, $widget->update_calls );
	}

	public function test_update_missing_post_is_declined() {
		$this->post = null;

		$result = $this->abilities()->widget_update(
			array( 'post_id' => 999, 'widget_data' => array( 'content' => 'x' ) )
		);

		$this->assertFalse( $result['updated'] );
		$this->assertSame( 'not-found', $result['status'] );
	}

	public function test_get_missing_post_is_wp_error_404() {
		$this->post = null;

		$result = $this->abilities()->widget_get( array( 'post_id' => 999 ) );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'sowb_widgets_post_not_found', $result->get_error_code() );
	}

	// ---- Legacy block ----

	public function test_legacy_widget_block_with_class_is_targetable() {
		$widget = $this->register_identity_widget();
		$legacy = array(
			'blockName' => 'sowb/widget-block',
			'attrs' => array(
				'widgetClass' => 'Abilities_Test_Widget',
				'widgetData' => array( 'content' => 'legacy' ),
			),
			'innerBlocks' => array(),
		);
		$this->set_post_with_blocks( array( $legacy ) );

		$get = $this->abilities()->widget_get( array( 'post_id' => 123 ) );
		$this->assertSame( 1, $get['widget_count'] );

		$update = $this->abilities()->widget_update(
			array( 'post_id' => 123, 'widget_data' => array( 'content' => 'legacy rewritten' ) )
		);
		$this->assertTrue( $update['updated'] );
		$this->assertSame( 1, $widget->update_calls );
	}

	public function test_legacy_widget_block_without_class_is_invisible() {
		$legacy = array(
			'blockName' => 'sowb/widget-block',
			'attrs' => array(),
			'innerBlocks' => array(),
		);
		$this->set_post_with_blocks( array( $legacy ) );

		$get = $this->abilities()->widget_get( array( 'post_id' => 123 ) );
		$this->assertSame( 0, $get['widget_count'] );

		$update = $this->abilities()->widget_update(
			array( 'post_id' => 123, 'widget_data' => array( 'content' => 'x' ) )
		);
		$this->assertFalse( $update['updated'] );
		$this->assertSame( 'unsupported', $update['status'] );
	}

	// ---- Reusable-block refs ----

	public function test_core_block_refs_reported_and_refs_aware_decline() {
		$ref = array(
			'blockName' => 'core/block',
			'attrs' => array( 'ref' => 456 ),
			'innerBlocks' => array(),
		);
		$this->set_post_with_blocks( array( $ref ) );

		$get = $this->abilities()->widget_get( array( 'post_id' => 123 ) );
		$this->assertSame( 0, $get['widget_count'] );
		$this->assertTrue( $get['unscanned_refs'] );

		$update = $this->abilities()->widget_update(
			array( 'post_id' => 123, 'widget_data' => array( 'content' => 'x' ) )
		);
		$this->assertFalse( $update['updated'] );
		$this->assertStringContainsString( 'reusable-block', $update['message'] );
	}

	// ---- Non-block content ----

	public function test_non_block_content_get_zero_update_declined() {
		Functions\expect( 'parse_blocks' )->never();

		$this->post = (object) array(
			'ID' => 123,
			'post_content' => '<p>Classic editor content, no blocks.</p>',
		);

		$get = $this->abilities()->widget_get( array( 'post_id' => 123 ) );
		$this->assertSame( 0, $get['widget_count'] );
		$this->assertFalse( $get['unscanned_refs'] );

		$update = $this->abilities()->widget_update(
			array( 'post_id' => 123, 'widget_data' => array( 'content' => 'x' ) )
		);
		$this->assertFalse( $update['updated'] );
		$this->assertSame( 'unsupported', $update['status'] );
		$this->assertStringContainsString( 'does not contain blocks', $update['message'] );
	}

	// ---- Write plumbing ----

	public function test_update_slashes_so_content_survives_unslashing() {
		$this->register_identity_widget();
		$this->set_post_with_blocks( array( $this->sowb_block() ) );

		$tricky = 'Quote " and back\\slash and unicode é 多字节';
		$result = $this->abilities()->widget_update(
			array( 'post_id' => 123, 'widget_data' => array( 'content' => $tricky ) )
		);

		$this->assertTrue( $result['updated'] );

		// What survived core's unslashing must equal the serializer's own
		// output — i.e. the JSON escapes were protected by wp_slash().
		$expected = $this->serialize_blocks_fixture(
			array( $this->sowb_block( 'Abilities_Test_Widget', array( 'content' => $tricky ), array( 'widgetMarkup' => '<div class="sow-rendered">' . $tricky . '</div>', 'widgetIcons' => array() ) ) )
		);
		$this->assertSame( $expected, $this->updated_post['post_content'] );
	}

	public function test_update_reports_failure_when_post_update_fails() {
		$this->register_identity_widget();
		$this->set_post_with_blocks( array( $this->sowb_block() ) );
		$this->update_post_result = 0;

		$result = $this->abilities()->widget_update(
			array( 'post_id' => 123, 'widget_data' => array( 'content' => 'x' ) )
		);

		$this->assertFalse( $result['updated'] );
		$this->assertSame( 'unsupported', $result['status'] );
		$this->assertStringContainsString( 'could not be saved', $result['message'] );
	}

	public function test_update_returns_persisted_post_update_widget_data() {
		$this->register_identity_widget();
		$this->set_post_with_blocks( array( $this->sowb_block() ) );

		$hostile = '<script>alert(1)</script><b>ok</b>';
		$result = $this->abilities()->widget_update(
			array( 'post_id' => 123, 'widget_data' => array( 'content' => $hostile ) )
		);

		$this->assertTrue( $result['updated'] );
		$this->assertStringNotContainsString( '<script>', $result['widget_data']['content'] );
		$this->assertStringContainsString( '<b>ok</b>', $result['widget_data']['content'] );
	}
}

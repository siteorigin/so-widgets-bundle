<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

// WordPress and the plugin manager are intentionally absent from this suite.
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $data;

		public function __construct( $code, $message, $data = null ) {
			$this->code = $code;
			$this->data = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $value ) {
		return $value instanceof WP_Error;
	}
}

if ( ! class_exists( 'SiteOrigin_Widgets_Widget_Manager' ) ) {
	class SiteOrigin_Widgets_Widget_Manager {
		public static $instances = array();
		public static $paths = array();

		public static function single() {
			static $instance;
			return $instance ?: $instance = new self();
		}

		public function get_class_from_path( $path ) {
			return self::$paths[ $path ] ?? false;
		}

		public static function get_widget_instance( $class ) {
			return self::$instances[ $class ] ?? null;
		}
	}
}

/**
 * Tests that the REST save of a widget block keeps a valid HTML anchor and
 * Additional CSS classes, validated before the widget preview is built.
 */
class WidgetBlockSaveAttributesTest extends SiteOriginTests {
	/**
	 * The keys get_widget_preview() returns, in order.
	 */
	const PREVIEW_KEYS = array( 'widgetClass', 'widgetData', 'widgetMarkup', 'html', 'widgetIcons' );

	/**
	 * @var SiteOrigin_Widgets_Bundle_Widget_Block
	 */
	private $subject;

	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['wp_widget_factory'] = (object) array( 'widgets' => array() );
		Functions\when( 'register_block_type' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'get_post_types' )->justReturn( array( 'post', 'page' ) );

		// The file creates its singleton when it loads, so it must load while
		// Brain Monkey is active.
		require_once __DIR__ . '/../compat/block-editor/widget-block.php';

		$this->subject = new class extends SiteOrigin_Widgets_Bundle_Widget_Block {
			/**
			 * Every $block passed to get_widget_preview(), in order.
			 *
			 * @var array
			 */
			public $received = array();

			/**
			 * When true, get_widget_preview() returns a WP_Error.
			 *
			 * @var bool
			 */
			public $fail = false;

			public function __construct() {
			}

			public function get_widget_preview( $block, $just_html = true ) {
				$this->received[] = $block;

				if ( $this->fail ) {
					return new WP_Error( 400, 'Preview failed.', array( 'status' => 400 ) );
				}

				return WidgetBlockSaveAttributesTest::preview_output( $block );
			}
		};
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_widget_factory'] );
		parent::tearDown();
	}

	/**
	 * The deterministic output of the stubbed preview.
	 *
	 * @param array $block Block attrs.
	 *
	 * @return array
	 */
	public static function preview_output( $block ) {
		return array(
			'widgetClass' => $block['widgetClass'],
			'widgetData' => $block['widgetData'],
			'widgetMarkup' => 'M',
			'html' => 'H',
			'widgetIcons' => array(),
		);
	}

	/**
	 * Build a sowb block in the shape parse_blocks() returns.
	 *
	 * @param array $extra Attrs added after the widget attrs.
	 *
	 * @return array
	 */
	private static function widget_block( $extra = array() ) {
		return array(
			'blockName' => 'sowb/siteorigin-widget-button-widget',
			'attrs' => array_merge(
				array(
					'widgetClass' => 'SiteOrigin_Widget_Button_Widget',
					'widgetData' => array( 'text' => 'Pricing', 'url' => '#' ),
				),
				$extra
			),
			'innerBlocks' => array(),
			'innerHTML' => '',
			'innerContent' => array(),
		);
	}

	public function test_valid_anchor_and_class_name_are_kept() {
		$block = $this->subject->sanitize_block(
			self::widget_block( array( 'anchor' => 'pricing', 'className' => 'northfield-btn extra' ) )
		);

		$this->assertSame( 'pricing', $block['attrs']['anchor'] ?? null );
		$this->assertSame( 'northfield-btn extra', $block['attrs']['className'] ?? null );
	}

	public function test_kept_attrs_follow_the_preview_keys() {
		$block = $this->subject->sanitize_block(
			self::widget_block( array( 'className' => 'northfield-btn', 'anchor' => 'pricing' ) )
		);

		$this->assertSame(
			array_merge( self::PREVIEW_KEYS, array( 'anchor', 'className' ) ),
			array_keys( $block['attrs'] )
		);
	}

	/**
	 * @return array[] Key, input value, expected stored value or null.
	 */
	public static function validation_cases() {
		$tokens = array_fill( 0, 30, 'abcdefghi' );
		// 25 tokens of 9 bytes and 24 spaces: 249 bytes. A 26th is 259.
		$long_class = implode( ' ', array_slice( $tokens, 0, 25 ) );

		return array(
			'anchor plain' => array( 'anchor', 'pricing', 'pricing' ),
			'anchor hyphen digit' => array( 'anchor', 'section-2', 'section-2' ),
			'anchor unicode' => array( 'anchor', 'präise', 'präise' ),
			'anchor double zero' => array( 'anchor', '00', '00' ),
			'anchor 255 bytes' => array( 'anchor', str_repeat( 'a', 255 ), str_repeat( 'a', 255 ) ),

			'anchor empty' => array( 'anchor', '', null ),
			'anchor zero' => array( 'anchor', '0', null ),
			'anchor space' => array( 'anchor', 'a b', null ),
			'anchor tab' => array( 'anchor', "a\tb", null ),
			'anchor newline' => array( 'anchor', "ab\n", null ),
			'anchor hash' => array( 'anchor', 'a#b', null ),
			'anchor quote' => array( 'anchor', 'x" onmouseover="y', null ),
			'anchor markup' => array( 'anchor', '<b>', null ),
			'anchor ampersand' => array( 'anchor', 'a&b', null ),
			'anchor apostrophe' => array( 'anchor', "a'b", null ),
			'anchor nbsp' => array( 'anchor', "a\u{00A0}b", null ),
			'anchor control' => array( 'anchor', "a\x01b", null ),
			'anchor invalid utf8' => array( 'anchor', "\xC3(", null ),
			'anchor 256 bytes' => array( 'anchor', str_repeat( 'a', 256 ), null ),
			'anchor int' => array( 'anchor', 5, null ),
			'anchor array' => array( 'anchor', array( 'x' ), null ),
			'anchor null' => array( 'anchor', null, null ),

			'class single' => array( 'className', 'northfield-btn', 'northfield-btn' ),
			'class idempotent' => array( 'className', 'btn', 'btn' ),
			'class underscore pair' => array( 'className', 'a_b c-d', 'a_b c-d' ),
			'class colon token' => array( 'className', 'btn md:flex', 'btn' ),
			'class dot token' => array( 'className', 'a.b keep', 'keep' ),
			'class extra spaces' => array( 'className', '  a   b  ', 'a b' ),
			'class tab token' => array( 'className', "a\tb c", 'c' ),
			'class markup token' => array( 'className', 'a"><b> keep', 'keep' ),
			'class zero with token' => array( 'className', '0 btn', '0 btn' ),
			'class over 255 bytes' => array( 'className', implode( ' ', $tokens ), $long_class ),

			'class empty' => array( 'className', '', null ),
			'class zero' => array( 'className', '0', null ),
			'class spaced zero' => array( 'className', ' 0 ', null ),
			'class spaces only' => array( 'className', '   ', null ),
			'class colon only' => array( 'className', 'md:flex', null ),
			'class markup only' => array( 'className', 'a"><b>', null ),
			'class dot only' => array( 'className', 'a.b', null ),
			'class array' => array( 'className', array( 'x' ), null ),
			'class int' => array( 'className', 5, null ),
			'class null' => array( 'className', null, null ),
		);
	}

	#[DataProvider( 'validation_cases' )]
	public function test_validation( $key, $value, $expected ) {
		$block = $this->subject->sanitize_block( self::widget_block( array( $key => $value ) ) );

		if ( $expected === null ) {
			$this->assertArrayNotHasKey( $key, $block['attrs'] );
		} else {
			$this->assertSame( $expected, $block['attrs'][ $key ] ?? null );
			$this->assertLessThanOrEqual( 255, strlen( $block['attrs'][ $key ] ) );
		}

		$this->assertSame( self::PREVIEW_KEYS, array_slice( array_keys( $block['attrs'] ), 0, 5 ) );
	}

	/**
	 * @return array[] An input block, and whether it holds a valid value.
	 */
	public static function replay_cases() {
		$group = array(
			'blockName' => 'core/group',
			'attrs' => array( 'layout' => array( 'type' => 'constrained' ) ),
			'innerBlocks' => array(
				self::widget_block( array( 'anchor' => 'pricing', 'className' => 'northfield-btn' ) ),
				self::widget_block(),
			),
			'innerHTML' => '<div class="wp-block-group"></div>',
			'innerContent' => array( '<div class="wp-block-group">', null, null, '</div>' ),
		);

		return array(
			'no extra attrs' => array( self::widget_block(), false ),
			'anchor only' => array( self::widget_block( array( 'anchor' => 'pricing' ) ), true ),
			'class only' => array( self::widget_block( array( 'className' => 'northfield-btn extra' ) ), true ),
			'both' => array( self::widget_block( array( 'anchor' => 'pricing', 'className' => 'northfield-btn' ) ), true ),
			'both invalid' => array( self::widget_block( array( 'anchor' => 'x" onmouseover="y', 'className' => 'md:flex' ) ), false ),
			'mixed class' => array( self::widget_block( array( 'className' => 'northfield-btn md:flex' ) ), true ),
			'non-string' => array( self::widget_block( array( 'anchor' => 5, 'className' => array( 'x' ) ) ), false ),
			'other attrs' => array( self::widget_block( array( 'lock' => array( 'move' => true ) ) ), false ),
			'group with inner widgets' => array( $group, true ),
		);
	}

	/**
	 * The 1.75.0 save: each sowb block's attrs replaced by the preview output.
	 *
	 * @param array $block Parsed block.
	 *
	 * @return array
	 */
	private static function replay_old( $block ) {
		if ( strpos( (string) $block['blockName'], 'sowb/' ) === 0 && ! empty( $block['attrs']['widgetClass'] ) ) {
			$block['attrs'] = self::preview_output( $block['attrs'] );
		}

		foreach ( $block['innerBlocks'] as $i => $inner ) {
			$block['innerBlocks'][ $i ] = self::replay_old( $inner );
		}

		return $block;
	}

	/**
	 * Remove only anchor and className from sowb block attrs, recursively.
	 *
	 * @param array $block Parsed block.
	 *
	 * @return array
	 */
	private static function strip( $block ) {
		if ( strpos( (string) $block['blockName'], 'sowb/' ) === 0 ) {
			unset( $block['attrs']['anchor'], $block['attrs']['className'] );
		}

		foreach ( $block['innerBlocks'] as $i => $inner ) {
			$block['innerBlocks'][ $i ] = self::strip( $inner );
		}

		return $block;
	}

	#[DataProvider( 'replay_cases' )]
	public function test_replay_matches_previous_save_apart_from_the_two_keys( $input, $has_valid_value ) {
		$old = self::replay_old( $input );
		$new = $this->subject->sanitize_blocks( $input );

		$this->assertSame( json_encode( $old ), json_encode( self::strip( $new ) ) );

		if ( ! $has_valid_value ) {
			$this->assertSame( json_encode( $old ), json_encode( $new ) );
		}
	}

	public function test_preview_error_is_returned_unchanged() {
		$this->subject->fail = true;

		$result = $this->subject->sanitize_block(
			self::widget_block( array( 'anchor' => 'pricing', 'className' => 'northfield-btn' ) )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 400, $result->get_error_code() );
		$this->assertSame( array( 'status' => 400 ), $result->get_error_data() );
	}

	/**
	 * @return array[] Extra attrs, and the anchor and className the preview
	 *                 should receive (null when the key must be absent).
	 */
	public static function preview_input_cases() {
		return array(
			'hostile anchor' => array( array( 'anchor' => 'x" onmouseover="y' ), null, null ),
			'zero anchor' => array( array( 'anchor' => '0' ), null, null ),
			'int anchor' => array( array( 'anchor' => 5 ), null, null ),
			'mixed class' => array( array( 'className' => 'btn md:flex' ), null, 'btn' ),
			'colon class' => array( array( 'className' => 'md:flex' ), null, null ),
			'zero class' => array( array( 'className' => '0' ), null, null ),
			'valid values' => array( array( 'anchor' => 'pricing', 'className' => 'northfield-btn extra' ), 'pricing', 'northfield-btn extra' ),
		);
	}

	#[DataProvider( 'preview_input_cases' )]
	public function test_values_are_validated_before_the_preview( $extra, $anchor, $class_name ) {
		$extra = array_merge( array( 'lock' => array( 'remove' => true ), 'metadata' => array( 'name' => 'CTA' ) ), $extra );
		$input = self::widget_block( $extra );

		$this->subject->sanitize_block( $input );

		$this->assertCount( 1, $this->subject->received );
		$received = $this->subject->received[0];

		foreach ( array( 'anchor' => $anchor, 'className' => $class_name ) as $key => $expected ) {
			if ( $expected === null ) {
				$this->assertArrayNotHasKey( $key, $received );
			} else {
				$this->assertSame( $expected, $received[ $key ] ?? null );
			}
		}

		$other = $input['attrs'];
		unset( $other['anchor'], $other['className'], $received['anchor'], $received['className'] );
		$this->assertSame( $other, $received );
	}
}

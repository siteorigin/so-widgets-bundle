<?php

use Brain\Monkey\Functions;
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
 * Tests which REST saves have their widget blocks rebuilt, and that a save of
 * a template, template part or synced pattern is never rejected over one.
 */
class WidgetBlockSaveHooksTest extends SiteOriginTests {
	const BUTTON = 'SiteOrigin_Widget_Button_Widget';

	/**
	 * @var SiteOrigin_Widgets_Bundle_Widget_Block
	 */
	private $subject;

	/**
	 * The post types this site has: name => array( public, show_in_rest, supports editor ).
	 *
	 * @var array
	 */
	private $post_types = array(
		'post'             => array( true, true, true ),
		'page'             => array( true, true, true ),
		'wp_block'         => array( false, true, true ),
		'wp_template'      => array( false, true, true ),
		'wp_template_part' => array( false, true, true ),
		'wp_font_family'   => array( false, true, false ),
		'private_notes'    => array( false, false, true ),
	);

	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['wp_widget_factory'] = (object) array( 'widgets' => array() );
		SiteOrigin_Widgets_Widget_Manager::$instances = array( self::BUTTON => new stdClass() );

		$post_types = $this->post_types;

		Functions\when( 'register_block_type' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'get_post_types' )->alias(
			function ( $args = array() ) use ( $post_types ) {
				$index = isset( $args['public'] ) ? 0 : 1;

				return array_keys( array_filter(
					$post_types,
					function ( $type ) use ( $index ) {
						return $type[ $index ];
					}
				) );
			}
		);
		Functions\when( 'post_type_supports' )->alias(
			function ( $post_type, $feature ) use ( $post_types ) {
				return $post_type === 'late_notes' || ! empty( $post_types[ $post_type ][2] );
			}
		);

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
			 * What get_widget_preview() does: 'render', 'fail' or 'throw'.
			 *
			 * @var string
			 */
			public $preview = 'render';

			public function __construct() {
			}

			public function get_widget_preview( $block, $just_html = true ) {
				$this->received[] = $block;

				if ( $this->preview === 'throw' ) {
					throw new RuntimeException( 'The widget threw.' );
				}

				if ( $this->preview === 'fail' ) {
					return new WP_Error( 400, 'Preview failed.', array( 'status' => 400 ) );
				}

				return array(
					'widgetClass' => $block['widgetClass'],
					'widgetData' => $block['widgetData'],
					'widgetMarkup' => 'BUILT',
					'html' => 'BUILT',
					'widgetIcons' => array( 'siteorigin-widget-icon-font-fontawesome' ),
				);
			}

			public function resolve_widget_class( $attrs, $block_name ) {
				if ( ! empty( $attrs['widgetClass'] ) ) {
					return $attrs['widgetClass'];
				}

				return $block_name === 'sowb/siteorigin-widget-button-widget' ? WidgetBlockSaveHooksTest::BUTTON : null;
			}
		};
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_widget_factory'] );
		SiteOrigin_Widgets_Widget_Manager::$instances = array();
		parent::tearDown();
	}

	private static function widget_block( $attrs = array(), $replace = false ) {
		return array(
			'blockName' => 'sowb/siteorigin-widget-button-widget',
			'attrs' => $replace ? $attrs : array_merge(
				array(
					'widgetClass' => self::BUTTON,
					'widgetData' => array( 'text' => 'Pricing', 'url' => '#' ),
				),
				$attrs
			),
			'innerBlocks' => array(),
			'innerHTML' => '',
			'innerContent' => array(),
		);
	}

	/**
	 * Run the save filter for a post type over one widget block.
	 */
	private function save( $post_type, $block ) {
		Functions\when( 'current_filter' )->justReturn( 'rest_pre_insert_' . $post_type );
		Functions\when( 'parse_blocks' )->justReturn( array( $block ) );
		Functions\when( 'serialize_blocks' )->alias( 'json_encode' );

		$this->subject->setup_rest_validation();

		$result = $this->subject->server_side_validation(
			(object) array( 'post_content' => '<!-- wp:sowb/siteorigin-widget-button-widget /-->' ),
			null
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return json_decode( $result->post_content, true )[0]['attrs'];
	}

	/**
	 * Run the hook setup and return what it added: hook name => callbacks.
	 */
	private function hooks_added( $after_setup = null ) {
		$added = array();

		Functions\when( 'add_action' )->alias(
			function ( $hook, $callback ) use ( &$added ) {
				$added[ $hook ][] = $callback;
			}
		);

		$this->subject->setup_rest_validation();

		if ( $after_setup ) {
			$after_setup();
		}

		return $added;
	}

	public function test_the_block_editors_content_types_are_hooked() {
		$added = $this->hooks_added();

		foreach ( array( 'post', 'page', 'wp_block', 'wp_template', 'wp_template_part' ) as $post_type ) {
			$this->assertSame(
				array( array( $this->subject, 'server_side_validation' ) ),
				$added[ 'rest_pre_insert_' . $post_type ] ?? null,
				$post_type . ' is not hooked exactly once.'
			);
		}
	}

	public function test_a_type_that_holds_no_block_content_is_not_hooked() {
		$added = $this->hooks_added();

		// In REST, but with no editor support.
		$this->assertArrayNotHasKey( 'rest_pre_insert_wp_font_family', $added );
		// With editor support, but not in REST.
		$this->assertArrayNotHasKey( 'rest_pre_insert_private_notes', $added );
	}

	public function test_page_builder_data_is_only_cleared_for_public_types() {
		$added = $this->hooks_added();

		$this->assertCount( 1, $added['rest_after_insert_post'] ?? array() );
		$this->assertCount( 1, $added['rest_after_insert_page'] ?? array() );
		$this->assertArrayNotHasKey( 'rest_after_insert_wp_block', $added );
		$this->assertArrayNotHasKey( 'rest_after_insert_wp_template', $added );
	}

	public function test_a_type_registered_later_is_hooked_once() {
		$late = (object) array( 'public' => false, 'show_in_rest' => true );
		$added = $this->hooks_added( function () use ( $late ) {
			$this->subject->add_rest_validation_for_registered_post_type( 'late_notes', $late );
			$this->subject->add_rest_validation_for_registered_post_type( 'late_notes', $late );
		} );

		$this->assertSame(
			array( array( $this->subject, 'add_rest_validation_for_registered_post_type' ) ),
			$added['registered_post_type'] ?? null
		);
		$this->assertCount( 1, $added['rest_pre_insert_late_notes'] ?? array() );
		$this->assertArrayNotHasKey( 'rest_after_insert_late_notes', $added );
	}

	public function test_a_type_registered_later_without_rest_is_not_hooked() {
		$added = $this->hooks_added( function () {
			$this->subject->add_rest_validation_for_registered_post_type(
				'late_notes',
				(object) array( 'public' => false, 'show_in_rest' => false )
			);
		} );

		$this->assertArrayNotHasKey( 'rest_pre_insert_late_notes', $added );
	}

	public function test_content_without_a_widget_block_is_not_parsed() {
		Functions\when( 'current_filter' )->justReturn( 'rest_pre_insert_wp_block' );
		Functions\expect( 'parse_blocks' )->never();

		$prepared = (object) array( 'post_content' => '<!-- wp:paragraph --><p>sowb/ is only mentioned here</p><!-- /wp:paragraph -->' );
		$result = $this->subject->server_side_validation( $prepared, null );

		$this->assertSame( $prepared, $result );
		$this->assertSame( '<!-- wp:paragraph --><p>sowb/ is only mentioned here</p><!-- /wp:paragraph -->', $result->post_content );
	}

	public function test_a_widget_block_comment_with_other_white_space_is_still_found() {
		Functions\when( 'current_filter' )->justReturn( 'rest_pre_insert_wp_block' );
		Functions\when( 'serialize_blocks' )->alias( 'json_encode' );
		Functions\expect( 'parse_blocks' )->once()->andReturn( array( self::widget_block( array( 'widgetMarkup' => 'STALE' ) ) ) );

		$this->subject->setup_rest_validation();

		$result = $this->subject->server_side_validation(
			(object) array( 'post_content' => "<!--\twp:sowb/siteorigin-widget-button-widget /-->" ),
			null
		);

		$this->assertSame( 'BUILT', json_decode( $result->post_content, true )[0]['attrs']['widgetMarkup'] );
	}

	public function test_queued_icon_fonts_are_listed_with_what_they_were_queued_with() {
		$fonts = SiteOrigin_Widgets_Bundle_Widget_Block::queued_icon_fonts(
			array( 'wp-block-library', 'siteorigin-widget-icon-font-fontawesome', 'siteorigin-widget-icon-font-custom', 42 ),
			array( 'siteorigin-widget-icon-font-custom' => 'variant=outline', 'wp-block-library' => 'x=1' )
		);

		$this->assertSame(
			array(
				'siteorigin-widget-icon-font-fontawesome' => 'siteorigin-widget-icon-font-fontawesome',
				'siteorigin-widget-icon-font-custom' => 'siteorigin-widget-icon-font-custom?variant=outline',
			),
			$fonts
		);
	}

	public function test_a_block_that_cannot_be_rendered_rejects_a_post_save() {
		$this->subject->preview = 'fail';

		$this->assertTrue( is_wp_error( $this->save( 'post', self::widget_block() ) ) );
	}

	public function test_a_block_that_cannot_be_rendered_does_not_reject_a_pattern_save() {
		$this->subject->preview = 'fail';

		$attrs = $this->save( 'wp_block', self::widget_block( array( 'widgetMarkup' => 'STALE', 'html' => 'STALE', 'widgetIcons' => array( 'old' ) ) ) );

		$this->assertIsArray( $attrs );
		$this->assertSame( '', $attrs['widgetMarkup'] );
		$this->assertSame( array(), $attrs['widgetIcons'] );
		$this->assertArrayNotHasKey( 'html', $attrs );
		$this->assertSame( array( 'text' => 'Pricing', 'url' => '#' ), $attrs['widgetData'] );
	}

	public function test_a_widget_that_throws_does_not_reject_a_template_save() {
		$this->subject->preview = 'throw';

		$attrs = $this->save( 'wp_template', self::widget_block( array( 'widgetMarkup' => 'STALE' ) ) );

		$this->assertIsArray( $attrs );
		$this->assertSame( '', $attrs['widgetMarkup'] );
	}

	public function test_a_widget_that_is_not_active_is_neither_rendered_nor_activated_on_a_template_part_save() {
		SiteOrigin_Widgets_Widget_Manager::$instances = array();

		$attrs = $this->save( 'wp_template_part', self::widget_block( array( 'widgetMarkup' => 'STALE' ) ) );

		// The preview is what would activate it.
		$this->assertSame( array(), $this->subject->received );
		$this->assertSame( '', $attrs['widgetMarkup'] );
		$this->assertSame( self::BUTTON, $attrs['widgetClass'] );
	}

	public function test_a_block_whose_class_cannot_be_worked_out_is_kept_with_no_markup() {
		$block = self::widget_block( array( 'widgetData' => array( 'text' => 'x' ), 'widgetMarkup' => 'STALE' ), true );
		$block['blockName'] = 'sowb/unknown-widget';

		$attrs = $this->save( 'wp_block', $block );

		$this->assertSame( array(), $this->subject->received );
		$this->assertSame( '', $attrs['widgetMarkup'] );
		$this->assertSame( array( 'text' => 'x' ), $attrs['widgetData'] );
	}

	public function test_a_block_with_a_malformed_class_or_data_does_not_reject_a_pattern_save() {
		$attrs = $this->save( 'wp_block', self::widget_block( array(
			'widgetClass' => array( 'not', 'a', 'class' ),
			'widgetData' => 'not data',
			'widgetMarkup' => array( 'not markup' ),
		) ) );

		$this->assertIsArray( $attrs );
		$this->assertSame( array(), $this->subject->received );
		$this->assertSame( '', $attrs['widgetMarkup'] );
		$this->assertSame( array( 'not', 'a', 'class' ), $attrs['widgetClass'] );
	}

	public function test_a_block_with_no_class_attribute_is_rendered_from_its_name_on_a_pattern_save() {
		$attrs = $this->save( 'wp_block', self::widget_block( array( 'widgetData' => array( 'text' => 'x' ) ), true ) );

		$this->assertSame( 'BUILT', $attrs['widgetMarkup'] );
		$this->assertSame( self::BUTTON, $attrs['widgetClass'] );
	}

	public function test_a_pattern_save_builds_the_markup_and_keeps_the_blocks_other_attributes() {
		$attrs = $this->save(
			'wp_block',
			self::widget_block( array(
				'lock' => array( 'move' => true, 'remove' => true ),
				'metadata' => array( 'name' => 'Pricing button' ),
				'widgetMarkup' => 'STALE',
				'html' => 'STALE',
				'widgetIcons' => array( 'old' ),
				'anchor' => 'pricing',
				'className' => 'northfield-btn <bad>',
			) )
		);

		$this->assertSame( array( 'move' => true, 'remove' => true ), $attrs['lock'] );
		$this->assertSame( array( 'name' => 'Pricing button' ), $attrs['metadata'] );
		$this->assertSame( 'BUILT', $attrs['widgetMarkup'] );
		$this->assertSame( 'BUILT', $attrs['html'] );
		$this->assertSame( array( 'siteorigin-widget-icon-font-fontawesome' ), $attrs['widgetIcons'] );
		$this->assertSame( 'pricing', $attrs['anchor'] );
		$this->assertSame( 'northfield-btn', $attrs['className'] );

		// The preview is given the validated wrapper values, never the stale markup.
		$this->assertSame( 'pricing', $this->subject->received[0]['anchor'] );
		$this->assertSame( 'northfield-btn', $this->subject->received[0]['className'] );
		$this->assertArrayNotHasKey( 'widgetMarkup', $this->subject->received[0] );
	}

	public function test_a_post_save_stores_exactly_the_keys_it_did() {
		$attrs = $this->save(
			'post',
			self::widget_block( array( 'lock' => array( 'move' => true ), 'anchor' => 'pricing' ) )
		);

		$this->assertSame(
			array( 'widgetClass', 'widgetData', 'widgetMarkup', 'html', 'widgetIcons', 'anchor' ),
			array_keys( $attrs )
		);
	}

	public function test_a_block_with_no_class_attribute_is_left_alone_on_a_post_save() {
		$block = self::widget_block( array( 'widgetData' => array( 'text' => 'x' ), 'widgetMarkup' => 'KEPT' ), true );

		$attrs = $this->save( 'post', $block );

		$this->assertSame( array(), $this->subject->received );
		$this->assertSame( 'KEPT', $attrs['widgetMarkup'] );
	}
}

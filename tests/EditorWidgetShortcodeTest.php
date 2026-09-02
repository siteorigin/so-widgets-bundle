<?php

use Brain\Monkey\Functions;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * Minimal stand-ins so widgets/editor/editor.php can be required outside
 * WordPress. The Editor widget only needs a parent with a constructor and
 * is_preview(); siteorigin_widget_register() is called at the top level of the
 * file, as it's written to run inside WordPress.
 */
// PriceTableLessVariablesTest declares a leaner SiteOrigin_Widget stub behind
// the same guard. This file loads first alphabetically, so this stub - which
// also provides is_preview() and the six-argument constructor the widget's
// parent call needs - is the one that wins. Both suites depend on that order.
if ( ! class_exists( 'SiteOrigin_Widget' ) ) {
	class SiteOrigin_Widget {
		public function __construct( $id = '', $name = '', $widget_options = array(), $control_options = array(), $form_options = array(), $base_folder = false ) {
		}

		public function is_preview() {
			return false;
		}
	}
}

if ( ! function_exists( 'siteorigin_widget_register' ) ) {
	function siteorigin_widget_register() {
		return true;
	}
}

if ( ! class_exists( 'SiteOrigin_Widget_Editor_Widget' ) ) {
	require __DIR__ . '/../widgets/editor/editor.php';
}

/**
 * The Editor widget must only execute shortcodes when rendering for the front
 * end. During Page Builder's post content (copy-content) render, executing a
 * shortcode bakes its output into post_content — a Ninja Forms shortcode, for
 * example, is stored as rendered placeholder markup that displays broken
 * (without the plugin's JS/CSS) wherever the mirror is shown, and plugins that
 * check post_content with has_shortcode() to decide whether to enqueue their
 * assets no longer find their shortcode. Keeping the shortcode intact lets it
 * render normally, through the_content, when the copied content is displayed.
 */
class EditorWidgetShortcodeTest extends SiteOriginTests {
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_parse_args' )->alias(
			fn( $args, $defaults ) => array_merge( $defaults, $args )
		);
		Functions\when( 'has_filter' )->justReturn( false );
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value ) => $value
		);
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['SITEORIGIN_PANELS_POST_CONTENT_RENDER'],
			$GLOBALS['SITEORIGIN_PANELS_CACHE_RENDER']
		);

		parent::tearDown();
	}

	private function widget(): SiteOrigin_Widget_Editor_Widget {
		$ref = new ReflectionClass( SiteOrigin_Widget_Editor_Widget::class );

		return $ref->newInstanceWithoutConstructor();
	}

	private function instance(): array {
		return array(
			'text' => 'Intro text [ninja_forms id=1]',
			'autop' => false,
		);
	}

	public function test_front_end_render_executes_shortcodes() {
		Functions\when( 'shortcode_unautop' )->returnArg();
		Functions\expect( 'do_shortcode' )
			->once()
			->with( 'Intro text [ninja_forms id=1]' )
			->andReturn( 'Intro text <div class="rendered-form"></div>' );

		$variables = $this->widget()->get_template_variables( $this->instance(), array() );

		$this->assertSame( 'Intro text <div class="rendered-form"></div>', $variables['text'] );
	}

	public function test_post_content_render_keeps_shortcodes_intact() {
		$GLOBALS['SITEORIGIN_PANELS_POST_CONTENT_RENDER'] = true;

		Functions\expect( 'do_shortcode' )->never();
		Functions\expect( 'shortcode_unautop' )->never();

		$variables = $this->widget()->get_template_variables( $this->instance(), array() );

		$this->assertSame(
			'Intro text [ninja_forms id=1]',
			$variables['text'],
			'The post content mirror must keep raw shortcodes so has_shortcode() checks pass and the shortcode renders live when the mirror is displayed.'
		);
	}

	// Regression lock on the legacy cache guard: no current Page Builder sets
	// SITEORIGIN_PANELS_CACHE_RENDER (the content cache was removed), but the
	// guard still protects sites running an old Page Builder that does.
	public function test_legacy_cache_render_keeps_shortcodes_intact() {
		$GLOBALS['SITEORIGIN_PANELS_CACHE_RENDER'] = true;

		Functions\expect( 'do_shortcode' )->never();
		Functions\expect( 'shortcode_unautop' )->never();

		$variables = $this->widget()->get_template_variables( $this->instance(), array() );

		$this->assertSame( 'Intro text [ninja_forms id=1]', $variables['text'] );
	}
}

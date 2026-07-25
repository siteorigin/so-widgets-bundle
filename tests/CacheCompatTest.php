<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * Unit tests for the cache plugin compatibility layer.
 *
 * compat.php ends with a call to SiteOrigin_Widgets_Bundle_Compatibility::single(),
 * whose constructor calls add_action() to hook init. That runs while the file is
 * being required, before any test has set Brain Monkey up, so add_action has to
 * exist as a real function at that moment.
 *
 * This is declared after tests/bootstrap.php has loaded the autoloader, so
 * Patchwork is already in place and Brain Monkey can still redefine the
 * functions the tests actually assert on (do_action, get_post_status).
 */
if ( ! function_exists( 'add_action' ) ) {
	function add_action() {
		return true;
	}
}

if ( ! class_exists( 'SiteOrigin_Widgets_Bundle_Compatibility' ) ) {
	require __DIR__ . '/../compat/compat.php';
}

/**
 * Mirrors the early return in SiteOrigin_Widgets_Bundle::clear_widget_cache().
 *
 * The real method requires ABSPATH and the WordPress filesystem API, which
 * would mean booting far more of WordPress than this guard warrants.
 * test_double_matches_the_real_guard() fails if the two drift apart.
 */
class SowbClearWidgetCacheDouble {
	public static function would_clear( $hook_extra = array() ) {
		if (
			! empty( $hook_extra['type'] ) &&
			in_array( $hook_extra['type'], array( 'translation', 'core' ), true )
		) {
			return false;
		}

		return true;
	}
}

class CacheCompatTest extends SiteOriginTests {
	/**
	 * Actions captured from the code under test.
	 */
	private $actions = array();

	protected function setUp(): void {
		parent::setUp();
		$this->actions = array();
	}

	/**
	 * Record every do_action() call so tests can assert on what was fired.
	 */
	private function capture_actions() {
		Functions\when( 'do_action' )->alias(
			function () {
				$args = func_get_args();
				$this->actions[] = array(
					'tag' => array_shift( $args ),
					'args' => $args,
				);

				return null;
			}
		);
	}

	/**
	 * Treat the given ids as real published posts, and everything else as
	 * missing.
	 *
	 * @param $existing Post ids that should be considered to exist.
	 */
	private function mock_posts( $existing = array() ) {
		Functions\when( 'get_post_status' )->alias(
			function ( $id ) use ( $existing ) {
				return in_array( (int) $id, $existing, true ) ? 'publish' : false;
			}
		);
	}

	/**
	 * The tags of every action fired, in order.
	 */
	private function fired_tags() {
		return array_column( $this->actions, 'tag' );
	}

	/**
	 * The arguments of the first action fired with the given tag.
	 */
	private function args_for( $tag ) {
		foreach ( $this->actions as $action ) {
			if ( $action['tag'] === $tag ) {
				return $action['args'];
			}
		}

		return null;
	}

	private function compat() {
		return SiteOrigin_Widgets_Bundle_Compatibility::single();
	}

	/**
	 * Call the private id resolver directly. Isolating it from the cache
	 * plugin branches keeps the filename cases readable.
	 */
	private function resolve_id( $name, $instance = array() ) {
		$method = new ReflectionMethod( 'SiteOrigin_Widgets_Bundle_Compatibility', 'get_cache_post_id' );
		$method->setAccessible( true );

		return $method->invoke( $this->compat(), $name, $instance );
	}

	/**
	 * LiteSpeed detection relies on a constant, which can't be undefined once
	 * set, so these two tests run in their own processes.
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_purges_all_when_litespeed_constant_is_defined() {
		define( 'LSCWP_V', '7.8.1' );
		$this->capture_actions();

		$this->compat()->clear_all_cache();

		$this->assertContains( 'litespeed_purge_all', $this->fired_tags() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_purges_all_when_only_the_legacy_function_exists() {
		if ( ! function_exists( 'run_litespeed_cache' ) ) {
			eval( 'function run_litespeed_cache() {}' );
		}

		$this->capture_actions();

		$this->compat()->clear_all_cache();

		$this->assertContains( 'litespeed_purge_all', $this->fired_tags() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_does_not_purge_when_litespeed_is_absent() {
		// Guards against the constant leaking in from another test and making
		// this pass for the wrong reason.
		$this->assertFalse( defined( 'LSCWP_V' ) );
		$this->assertFalse( function_exists( 'run_litespeed_cache' ) );

		$this->capture_actions();

		$this->compat()->clear_all_cache();

		$this->assertNotContains( 'litespeed_purge_all', $this->fired_tags() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_purges_the_post_from_the_widget_instance() {
		define( 'LSCWP_V', '7.8.1' );
		$this->mock_posts( array( 4821 ) );
		$this->capture_actions();

		$this->compat()->clear_page_cache(
			'sow-button-default-a3f9c2e81b04.css',
			array( 'panels_info' => array( 'post_id' => 4821 ) )
		);

		$this->assertSame( array( 4821 ), $this->args_for( 'litespeed_purge_post' ) );
	}

	/**
	 * clear_file_cache() fires the deleted action with only a filename, so
	 * WordPress pads $instance with null rather than the array default.
	 */
	public function test_survives_a_null_instance() {
		$this->mock_posts( array() );
		$this->capture_actions();

		$this->compat()->clear_page_cache( 'sow-button-default-a3f9c2e81b04.css', null );

		$this->assertNotContains( 'litespeed_purge_post', $this->fired_tags() );
	}

	#[DataProvider( 'post_id_cases' )]
	public function test_resolves_the_post_id( $name, $instance, $existing, $expected ) {
		$this->mock_posts( $existing );

		$this->assertSame( $expected, $this->resolve_id( $name, $instance ) );
	}

	/**
	 * clear_widget_cache() bails before touching the filesystem for updates
	 * that can't affect the generated CSS.
	 *
	 * Loading SiteOrigin_Widgets_Bundle would pull in the whole plugin, so the
	 * guard is exercised through a double carrying the same condition. Keep the
	 * two in step: this asserts the decision, not the deletion.
	 */
	#[DataProvider( 'update_type_cases' )]
	public function test_only_clears_for_relevant_updates( $hook_extra, $should_clear ) {
		$this->assertSame( $should_clear, SowbClearWidgetCacheDouble::would_clear( $hook_extra ) );
	}

	/**
	 * The guard as it appears in SiteOrigin_Widgets_Bundle::clear_widget_cache().
	 */
	public function test_double_matches_the_real_guard() {
		$source = file_get_contents( __DIR__ . '/../so-widgets-bundle.php' );

		$this->assertStringContainsString(
			"in_array( \$hook_extra['type'], array( 'translation', 'core' ), true )",
			$source,
			'The guard in so-widgets-bundle.php changed; update SowbClearWidgetCacheDouble to match.'
		);
	}

	public static function update_type_cases() {
		return array(
			'translation update is skipped' => array( array( 'type' => 'translation' ), false ),
			'core update is skipped' => array( array( 'type' => 'core' ), false ),
			'plugin update clears' => array( array( 'type' => 'plugin' ), true ),
			'theme update clears' => array( array( 'type' => 'theme' ), true ),
			'bulk plugin update clears' => array(
				array( 'type' => 'plugin', 'bulk' => true, 'plugins' => array( 'a/a.php' ) ),
				true,
			),
			'unrecognised type clears' => array( array( 'type' => 'something-else' ), true ),
			'no update details clears' => array( array(), true ),
		);
	}

	public static function post_id_cases() {
		return array(
			'hash only, no post id suffix' => array(
				'sow-button-default-a3f9c2e81b04.css',
				array(),
				array( 4821 ),
				false,
			),
			'post id suffix for an existing post' => array(
				'sow-image-default-1a2b3c4d5e6f-4821.css',
				array(),
				array( 4821 ),
				4821,
			),
			'all digit hash with no matching post' => array(
				'sow-editor-default-123456789012.css',
				array(),
				array( 4821 ),
				false,
			),
			'trailing hyphen from a missing post id' => array(
				'sow-button-default-abc123def456-.css',
				array(),
				array( 4821 ),
				false,
			),
			'directory index file' => array(
				'index.php',
				array(),
				array( 4821 ),
				false,
			),
			'scientific notation is not a post id' => array(
				'sow-button-default-1e5.css',
				array(),
				array( 100000 ),
				false,
			),
			'instance wins over a differing filename suffix' => array(
				'sow-image-default-1a2b3c4d5e6f-4821.css',
				array( 'panels_info' => array( 'post_id' => 99 ) ),
				array( 99, 4821 ),
				99,
			),
			'stale suffix with no instance still purges its post' => array(
				'sow-image-default-1a2b3c4d5e6f-4821.css',
				array(),
				array( 4821 ),
				4821,
			),
			'stale suffix for a deleted post' => array(
				'sow-image-default-1a2b3c4d5e6f-4821.css',
				array(),
				array(),
				false,
			),
		);
	}
}

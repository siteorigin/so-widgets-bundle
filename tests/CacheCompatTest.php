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
 * whose constructor calls add_action() to hook init. tests/bootstrap.php declares
 * add_action so that call has something to run against.
 */
if ( ! class_exists( 'SiteOrigin_Widgets_Bundle_Compatibility' ) ) {
	require __DIR__ . '/../compat/compat.php';
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
	private function resolve_id( $name ) {
		$method = new ReflectionMethod( 'SiteOrigin_Widgets_Bundle_Compatibility', 'get_cache_post_id' );
		$method->setAccessible( true );

		return $method->invoke( $this->compat(), $name );
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
	public function test_purges_the_post_named_in_the_filename() {
		define( 'LSCWP_V', '7.8.1' );
		$this->mock_posts( array( 4821 ) );
		$this->capture_actions();

		$this->compat()->clear_page_cache( 'sow-image-default-1a2b3c4d5e6f-4821.css' );

		$this->assertSame( array( 4821 ), $this->args_for( 'litespeed_purge_post' ) );
	}

	/**
	 * Most stylesheets belong to a widget outside Page Builder, so their
	 * filename ends in a hash and there's no page to purge.
	 */
	public function test_does_not_purge_without_a_post_id() {
		$this->mock_posts( array() );
		$this->capture_actions();

		$this->compat()->clear_page_cache( 'sow-button-default-a3f9c2e81b04.css' );

		$this->assertNotContains( 'litespeed_purge_post', $this->fired_tags() );
	}

	#[DataProvider( 'post_id_cases' )]
	public function test_resolves_the_post_id( $name, $existing, $expected ) {
		$this->mock_posts( $existing );

		$this->assertSame( $expected, $this->resolve_id( $name ) );
	}

	public static function post_id_cases() {
		return array(
			'hash only, no post id suffix' => array(
				'sow-button-default-a3f9c2e81b04.css',
				array( 4821 ),
				false,
			),
			'post id suffix for an existing post' => array(
				'sow-image-default-1a2b3c4d5e6f-4821.css',
				array( 4821 ),
				4821,
			),
			// delete_css() fires the deleted action with the name it built,
			// which has no extension.
			'name without an extension' => array(
				'sow-image-default-1a2b3c4d5e6f-4821',
				array( 4821 ),
				4821,
			),
			'all digit hash with no matching post' => array(
				'sow-editor-default-123456789012.css',
				array( 4821 ),
				false,
			),
			'trailing hyphen from a missing post id' => array(
				'sow-button-default-abc123def456-.css',
				array( 4821 ),
				false,
			),
			'directory index file' => array(
				'index.php',
				array( 4821 ),
				false,
			),
			'scientific notation is not a post id' => array(
				'sow-button-default-1e5.css',
				array( 100000 ),
				false,
			),
			'stale suffix for a deleted post' => array(
				'sow-image-default-1a2b3c4d5e6f-4821.css',
				array(),
				false,
			),
		);
	}
}

<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * A widget whose template name comes from its settings, the way Price Table's
 * design theme does.
 */
class SiteOrigin_Template_File_Test_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'so-template-file-test',
			'Template File Test',
			array( 'has_preview' => false ),
			array(),
			false,
			__DIR__ . '/fixtures/template-file/'
		);
	}

	public function get_widget_form() {
		return array();
	}

	public function get_template_name( $instance ) {
		return isset( $instance['name'] ) ? $instance['name'] : 'default';
	}

	public function get_style_name( $instance ) {
		return false;
	}
}

/**
 * Covers which template file SiteOrigin_Widget::widget() includes, and what
 * the siteorigin_widgets_template_file_{id_base} filter receives, when the
 * template name comes from widget settings.
 *
 * Fixture markers: tpl/default.php prints TPL-DEFAULT, tpl/sub/part.php
 * prints TPL-PART, outside.php (beside tpl/) prints OUTSIDE and
 * tpl-evil/default.php (a sibling folder sharing the tpl prefix) prints EVIL.
 */
class TemplateFileTest extends SiteOriginTests {
	private const FILTER = 'siteorigin_widgets_template_file_so-template-file-test';

	// The filter callback returns its input unchanged.
	private const PASS_THROUGH = '__pass_through__';

	private $fixtures;
	private $tmp;
	private $filter_calls = array();
	private $filter_return = self::PASS_THROUGH;

	protected function setUp(): void {
		parent::setUp();

		$this->fixtures = __DIR__ . '/fixtures/template-file/';
		$this->tmp = sys_get_temp_dir() . '/sow-template-file-' . uniqid();

		// A widget folder whose tpl/ is a symlink to the fixture tpl/.
		mkdir( $this->tmp . '/linked-widget', 0777, true );
		symlink( $this->fixtures . 'tpl', $this->tmp . '/linked-widget/tpl' );

		// A widget folder whose tpl/default.php is a symlink to a file outside it.
		mkdir( $this->tmp . '/file-link-widget/tpl', 0777, true );
		symlink( $this->fixtures . 'outside.php', $this->tmp . '/file-link-widget/tpl/default.php' );

		$GLOBALS['SITEORIGIN_PANELS_PREVIEW_RENDER'] = true;

		Functions\when( 'wp_normalize_path' )->alias(
			function ( $path ) {
				$path = str_replace( '\\', '/', $path );
				$path = preg_replace( '|(?<=.)/+|', '/', $path );

				if ( ':' === substr( $path, 1, 1 ) ) {
					$path = ucfirst( $path );
				}

				return $path;
			}
		);
		Functions\when( 'trailingslashit' )->alias(
			function ( $value ) {
				return rtrim( $value, '/\\' ) . '/';
			}
		);
		Functions\when( 'siteorigin_sanitize_attribute_key' )->returnArg();
		// The preview render flag also marks the render as a preview, which
		// prints its styles inline after the widget.
		Functions\when( 'siteorigin_widget_print_styles' )->justReturn( null );
		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value ) {
				if ( $hook !== self::FILTER ) {
					return $value;
				}

				$this->filter_calls[] = $value;

				return $this->filter_return === self::PASS_THROUGH ? $value : $this->filter_return;
			}
		);
		$this->use_widget_folder( $this->fixtures );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['SITEORIGIN_PANELS_PREVIEW_RENDER'] );

		@unlink( $this->tmp . '/file-link-widget/tpl/default.php' );
		@rmdir( $this->tmp . '/file-link-widget/tpl' );
		@rmdir( $this->tmp . '/file-link-widget' );
		@unlink( $this->tmp . '/linked-widget/tpl' );
		@rmdir( $this->tmp . '/linked-widget' );
		@rmdir( $this->tmp );

		parent::tearDown();
	}

	private function use_widget_folder( $folder ) {
		Functions\when( 'siteorigin_widget_get_plugin_dir_path' )->justReturn( $folder );
	}

	private function render( $name ) {
		$widget = new SiteOrigin_Template_File_Test_Widget();

		ob_start();
		$widget->widget( array(), array( 'name' => $name ) );

		return ob_get_clean();
	}

	public static function template_names() {
		return array(
			'shipped name' => array( 'default', 'TPL-DEFAULT', 'tpl/default.php' ),
			'subfolder' => array( 'sub/part', 'TPL-PART', 'tpl/sub/part.php' ),
			'.. that stays inside' => array( 'sub/../default', 'TPL-DEFAULT', 'tpl/sub/../default.php' ),
			'.. to a file beside the folder' => array( '../outside', null, null ),
			'.. to a sibling folder sharing the prefix' => array( '../tpl-evil/default', null, null ),
			'.. with a Windows separator' => array( '..\\outside', null, null ),
			'missing name' => array( 'missing', null, 'tpl/missing.php' ),
			'.. to a missing file' => array( '../missing', null, null ),
			// A case-insensitive filesystem resolves this into tpl/, but realpath() keeps
			// the TPL spelling, so it is refused like any other name that steps out and back.
			'.. back in with different case' => array( '../TPL/default', null, null ),
		);
	}

	#[DataProvider( 'template_names' )]
	public function test_template_name( $name, $marker, $filtered ) {
		$output = $this->render( $name );

		foreach ( array( 'TPL-DEFAULT', 'TPL-PART', 'OUTSIDE', 'EVIL' ) as $known ) {
			if ( $known === $marker ) {
				$this->assertStringContainsString( $known, $output );
			} else {
				$this->assertStringNotContainsString( $known, $output, "$name must not include the file that prints $known." );
			}
		}

		if ( $filtered === null ) {
			$this->assertSame( array(), $this->filter_calls, "$name must not reach the template file filter." );
		} else {
			$this->assertSame( array( $this->fixtures . $filtered ), $this->filter_calls, 'The filter receives the unresolved path, as before.' );
		}
	}

	public function test_filter_may_return_a_template_outside_the_widget_folder() {
		$this->filter_return = $this->fixtures . 'outside.php';

		$output = $this->render( 'default' );

		$this->assertStringContainsString( 'OUTSIDE', $output );
		$this->assertSame( array( $this->fixtures . 'tpl/default.php' ), $this->filter_calls );
	}

	public function test_filter_may_supply_a_template_for_a_missing_candidate() {
		$this->filter_return = $this->fixtures . 'outside.php';

		$output = $this->render( 'missing' );

		$this->assertStringContainsString( 'OUTSIDE', $output );
		$this->assertSame( array( $this->fixtures . 'tpl/missing.php' ), $this->filter_calls );
	}

	public function test_filter_returning_a_non_php_file_includes_nothing() {
		$this->filter_return = $this->fixtures . 'tpl/notes.txt';

		$this->assertStringNotContainsString( 'TPL-NOTES', $this->render( 'default' ) );
	}

	public function test_filter_returning_an_empty_value_includes_nothing() {
		foreach ( array( '', false ) as $empty ) {
			$this->filter_return = $empty;

			$output = $this->render( 'default' );

			$this->assertStringNotContainsString( 'TPL-DEFAULT', $output );
			$this->assertStringNotContainsString( 'OUTSIDE', $output );
		}
	}

	public function test_symlinked_template_folder_renders() {
		$this->use_widget_folder( $this->tmp . '/linked-widget/' );

		$this->assertStringContainsString( 'TPL-DEFAULT', $this->render( 'default' ) );
		$this->assertSame( array( $this->tmp . '/linked-widget/tpl/default.php' ), $this->filter_calls );
	}

	public function test_sibling_folder_with_a_backslash_in_its_name_is_not_included() {
		if ( DIRECTORY_SEPARATOR === '\\' ) {
			$this->markTestSkipped( 'A backslash is a path separator on Windows, not a file name character.' );
		}

		// On Unix, tpl\evil is one folder beside tpl/, not tpl/evil.
		mkdir( $this->tmp . '/backslash-widget/tpl', 0777, true );
		mkdir( $this->tmp . '/backslash-widget/tpl\\evil', 0777, true );
		file_put_contents( $this->tmp . '/backslash-widget/tpl\\evil/payload.php', "<?php\necho 'BACKSLASH';\n" );
		$this->use_widget_folder( $this->tmp . '/backslash-widget/' );

		$output = $this->render( '../tpl\\evil/payload' );

		unlink( $this->tmp . '/backslash-widget/tpl\\evil/payload.php' );
		rmdir( $this->tmp . '/backslash-widget/tpl\\evil' );
		rmdir( $this->tmp . '/backslash-widget/tpl' );
		rmdir( $this->tmp . '/backslash-widget' );

		$this->assertStringNotContainsString( 'BACKSLASH', $output );
		$this->assertSame( array(), $this->filter_calls );
	}

	public function test_template_file_symlinked_outside_the_folder_is_not_included() {
		$this->use_widget_folder( $this->tmp . '/file-link-widget/' );

		$this->assertStringNotContainsString( 'OUTSIDE', $this->render( 'default' ) );
		$this->assertSame( array(), $this->filter_calls );
	}
}

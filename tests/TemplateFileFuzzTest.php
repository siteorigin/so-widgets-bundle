<?php

use Brain\Monkey\Functions;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * A widget whose template folder and name are set per render, so one widget
 * can stand in for any widget built on SiteOrigin_Widget.
 */
class SiteOrigin_Template_File_Fuzz_Widget extends SiteOrigin_Widget {
	public $template_dir = 'tpl';
	public $template_name = 'default';

	public function __construct() {
		parent::__construct( 'so-template-file-fuzz', 'Template File Fuzz', array( 'has_preview' => false ), array(), false, false );
	}

	public function get_widget_form() {
		return array();
	}

	public function get_template_dir( $instance ) {
		return $this->template_dir;
	}

	public function get_template_name( $instance ) {
		return $this->template_name;
	}

	public function get_style_name( $instance ) {
		return false;
	}
}

/**
 * Compares the template widget() includes with the template the code before
 * the template folder check included, over generated template names and the
 * ways plugins and themes use the siteorigin_widgets_template_file_ filter.
 *
 * The rule: both include the same file, unless the template name has a `..`
 * segment, or the old code resolved the widget's own template outside its
 * template folder. Any other difference fails the test and is listed.
 *
 * The filter behaviours model real uses:
 * - pass_through: no filter, or one that returns its input.
 * - fixed_file: returns its own template whatever it receives (Premium card
 *   carousel, so-bootstrap-widgets-bundle).
 * - setting_keyed: returns its own template when a setting matches, else its
 *   input (the pattern in the SiteOrigin docs, Premium Blog masonry).
 * - empty_string: returns '' (Auxin Elements, which renders through another hook).
 * - theme_folder: maps the widget's tpl/ folder onto a theme override folder
 *   when that file exists, else returns its input.
 * - theme_basename: looks for the template's file name in a theme folder.
 * - non_php, false_value, null_value: return values the include must refuse.
 *
 * Every fixture template prints INC: and its own path, so the output shows
 * which file was included.
 */
class TemplateFileFuzzTest extends SiteOriginTests {
	private const FILTER = 'siteorigin_widgets_template_file_so-template-file-fuzz';

	private const SEGMENTS = array(
		'default', 'a', 'sub', 'b', '..', '.', '', 'TPL', 'Sub', 'x.y', 'sp ace',
		'missing', 'tpl', 'tpl-evil', 'outside', 'link', '...', '.. ', 'overrides',
		'theme', 'Default', 'widget', 'tpl\\evil', 'payload',
	);

	private const BEHAVIOURS = array(
		'pass_through', 'fixed_file', 'setting_keyed', 'empty_string', 'theme_folder',
		'theme_basename', 'non_php', 'false_value', 'null_value',
	);

	private $root;
	private $widget_folder;
	private $behaviour = 'pass_through';
	private $setting_matches = false;

	protected function setUp(): void {
		parent::setUp();

		$this->root = sys_get_temp_dir() . '/sow-template-fuzz-' . uniqid();
		$this->widget_folder = $this->root . '/plugin/widget/';
		$this->build_tree();

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
		Functions\when( 'siteorigin_widget_print_styles' )->justReturn( null );
		Functions\when( 'siteorigin_widget_get_plugin_dir_path' )->justReturn( $this->widget_folder );
		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value ) {
				return $hook === self::FILTER ? $this->filter( $value ) : $value;
			}
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['SITEORIGIN_PANELS_PREVIEW_RENDER'] );
		$this->remove( $this->root );

		parent::tearDown();
	}

	/**
	 * plugin/widget/tpl/          the widget's templates, a subfolder, a dotted
	 *                             name, a name with a space, a symlink to a
	 *                             file outside the folder
	 * plugin/widget/tpl-evil/     a sibling folder sharing the tpl prefix
	 * plugin/widget/tpl\evil/     a sibling folder with a backslash (not Windows)
	 * plugin/widget/outside.php   a file beside the template folder
	 * plugin/other/own.php        a filter's own template
	 * theme/overrides/            a theme's template overrides
	 * payload.php                 a file far outside the plugin
	 */
	private function build_tree() {
		$files = array(
			'plugin/widget/tpl/default.php',
			'plugin/widget/tpl/a.php',
			'plugin/widget/tpl/sub/b.php',
			'plugin/widget/tpl/x.y.php',
			'plugin/widget/tpl/sp ace.php',
			'plugin/widget/tpl/Default.php',
			'plugin/widget/tpl-evil/default.php',
			'plugin/widget/tpl-evil/payload.php',
			'plugin/widget/outside.php',
			'plugin/widget/payload.php',
			'plugin/other/own.php',
			'theme/overrides/default.php',
			'theme/overrides/sub/b.php',
			'theme/overrides/payload.php',
			'payload.php',
		);

		if ( DIRECTORY_SEPARATOR === '/' ) {
			$files[] = 'plugin/widget/tpl\\evil/payload.php';
		}

		foreach ( $files as $file ) {
			$path = $this->root . '/' . $file;

			if ( ! is_dir( dirname( $path ) ) ) {
				mkdir( dirname( $path ), 0777, true );
			}

			file_put_contents( $path, "<?php\necho 'INC:' . __FILE__ . ':END';\n" );
		}

		file_put_contents( $this->root . '/plugin/widget/tpl/notes.txt', 'NOTES' );
		symlink( $this->root . '/plugin/widget/outside.php', $this->root . '/plugin/widget/tpl/link.php' );
	}

	private function remove( $path ) {
		if ( is_link( $path ) || is_file( $path ) ) {
			unlink( $path );

			return;
		}

		if ( ! is_dir( $path ) ) {
			return;
		}

		foreach ( scandir( $path ) as $entry ) {
			if ( $entry !== '.' && $entry !== '..' ) {
				$this->remove( $path . '/' . $entry );
			}
		}

		rmdir( $path );
	}

	private function filter( $file ) {
		switch ( $this->behaviour ) {
			case 'fixed_file':
				return $this->root . '/plugin/other/own.php';
			case 'setting_keyed':
				return $this->setting_matches ? $this->root . '/plugin/other/own.php' : $file;
			case 'empty_string':
				return '';
			case 'theme_folder':
				$mapped = str_replace( $this->widget_folder . 'tpl/', $this->root . '/theme/overrides/', (string) $file );

				return file_exists( $mapped ) ? $mapped : $file;
			case 'theme_basename':
				$candidate = $this->root . '/theme/overrides/' . basename( (string) $file );

				return file_exists( $candidate ) ? $candidate : $file;
			case 'non_php':
				return $this->widget_folder . 'tpl/notes.txt';
			case 'false_value':
				return false;
			case 'null_value':
				return null;
			default:
				return $file;
		}
	}

	/**
	 * The template the code before the template folder check included, for the
	 * same folder, name and filter.
	 */
	private function previous_include( $template_dir, $template_name ) {
		$template_file = $this->widget_folder . $template_dir . '/' . $template_name . '.php';
		$template_file = $this->filter( $template_file );
		$template_file = @realpath( (string) $template_file );

		if ( substr( (string) $template_file, -4 ) != '.php' ) {
			$template_file = false;
		}

		return ( ! empty( $template_file ) && file_exists( $template_file ) ) ? $template_file : false;
	}

	private function current_include( $widget ) {
		ob_start();
		$widget->widget( array(), array() );
		$output = ob_get_clean();

		return preg_match( '/INC:(.*?):END/s', $output, $match ) ? $match[1] : false;
	}

	private function has_parent_segment( $value ) {
		return (bool) preg_match( '#(?:^|/)\.\.[. ]*(?:/|$)#', str_replace( '\\', '/', $value ) );
	}

	/**
	 * Whether the widget's own computed template, before any filter, resolved
	 * to a file outside its template folder.
	 */
	private function own_template_outside( $template_dir, $template_name ) {
		$real_file = realpath( $this->widget_folder . $template_dir . '/' . $template_name . '.php' );
		$real_dir = realpath( $this->widget_folder . $template_dir );

		return $real_file !== false && ( $real_dir === false || strpos( $real_file, rtrim( $real_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR ) !== 0 );
	}

	private function names() {
		$names = array();

		// Every one and two segment name, joined with / and with \.
		foreach ( self::SEGMENTS as $first ) {
			$names[] = $first;

			foreach ( self::SEGMENTS as $second ) {
				$names[] = $first . '/' . $second;
				$names[] = $first . '\\' . $second;
			}
		}

		// Seeded three to five segment names.
		mt_srand( 20261118 );

		for ( $i = 0; $i < 1500; $i++ ) {
			$parts = array();
			$count = mt_rand( 3, 5 );

			for ( $j = 0; $j < $count; $j++ ) {
				$parts[] = self::SEGMENTS[ mt_rand( 0, count( self::SEGMENTS ) - 1 ) ];
			}

			$name = implode( mt_rand( 0, 4 ) ? '/' : '\\', $parts );
			$names[] = mt_rand( 0, 9 ) ? $name : '/' . $name;
		}

		return array_values( array_unique( $names ) );
	}

	public function test_only_names_that_step_out_of_the_template_folder_change() {
		$widget = new SiteOrigin_Template_File_Fuzz_Widget();
		$template_dirs = array( 'tpl', '' );
		$cases = 0;
		$changed = 0;
		$unexpected = array();
		$outside = array();

		foreach ( $this->names() as $index => $name ) {
			foreach ( self::BEHAVIOURS as $behaviour ) {
				foreach ( $template_dirs as $template_dir ) {
					$this->behaviour = $behaviour;
					$this->setting_matches = $index % 2 === 0;
					$widget->template_dir = $template_dir;
					$widget->template_name = $name;

					$before = $this->previous_include( $template_dir, $name );
					$after = $this->current_include( $widget );
					$cases++;

					// Only an accepted candidate reaches the filter, so a file outside
					// the template folder can only come from the filter's own choice.
					if ( $after !== false && $behaviour === 'pass_through' ) {
						$real_dir = realpath( $this->widget_folder . $template_dir );

						if ( $real_dir === false || strpos( $after, rtrim( $real_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR ) !== 0 ) {
							$outside[] = "$behaviour | dir '$template_dir' | name '$name' | included $after";
						}
					}

					if ( $before === $after ) {
						continue;
					}

					$changed++;

					if ( ! $this->has_parent_segment( $name ) && ! $this->own_template_outside( $template_dir, $name ) ) {
						$unexpected[] = "$behaviour | dir '$template_dir' | name '$name' | before " . var_export( $before, true ) . ' | after ' . var_export( $after, true );
					}
				}
			}
		}

		if ( getenv( 'SOW_FUZZ_REPORT' ) ) {
			fwrite( STDERR, "\nTemplate file fuzz: $cases cases, $changed changed, " . count( $unexpected ) . ' unexpected, ' . count( $outside ) . " outside.\n" );
		}

		$this->assertGreaterThan( 45000, $cases );
		$this->assertSame( array(), $outside, 'With no filter, only files inside the template folder are included.' );
		$this->assertSame( array(), $unexpected, 'Only names that step out of the template folder render differently.' );
	}
}

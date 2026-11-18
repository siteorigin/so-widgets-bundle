<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

// price-table.php registers itself while it is being required, and the widget
// constructor resolves its plugin folder, so both need real functions then.
if ( ! function_exists( 'siteorigin_widget_register' ) ) {
	function siteorigin_widget_register() {
		return true;
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return rtrim( dirname( $file ), '/\\' ) . '/';
	}
}

if ( ! defined( 'SOW_BUNDLE_BASE_FILE' ) ) {
	define( 'SOW_BUNDLE_BASE_FILE', __DIR__ . '/../so-widgets-bundle.php' );
}

if ( ! class_exists( 'SiteOrigin_Widget_PriceTable_Widget' ) ) {
	require __DIR__ . '/../widgets/price-table/price-table.php';
}

/**
 * Covers the theme name Price Table uses for its template, LESS file and CSS
 * file name. Only a plain name of letters, digits, hyphens and underscores is
 * used; anything else falls back to Atom.
 */
class PriceTableThemeNameTest extends SiteOriginTests {
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_parse_args' )->alias(
			function ( $args, $defaults ) {
				return array_merge( $defaults, (array) $args );
			}
		);
	}

	public static function themes() {
		return array(
			'atom' => array( array( 'design' => array( 'theme' => 'atom' ) ), 'atom' ),
			'empty' => array( array( 'design' => array( 'theme' => '' ) ), 'atom' ),
			// empty() treats '0' as empty, which keeps the fallback the widget always had.
			'zero' => array( array( 'design' => array( 'theme' => '0' ) ), 'atom' ),
			'unset' => array( array( 'design' => array() ), 'atom' ),
			'no design section' => array( array(), 'atom' ),
			'parent directory path' => array( array( 'design' => array( 'theme' => '../../../../../../wp-includes/theme-compat/embed-404' ) ), 'atom' ),
			'subfolder path' => array( array( 'design' => array( 'theme' => 'atom/../atom' ) ), 'atom' ),
			'dot' => array( array( 'design' => array( 'theme' => 'a.b' ) ), 'atom' ),
			'trailing newline' => array( array( 'design' => array( 'theme' => "atom\n" ) ), 'atom' ),
			'array' => array( array( 'design' => array( 'theme' => array( 'x' ) ) ), 'atom' ),
			'plain name with hyphen' => array( array( 'design' => array( 'theme' => 'Atom-2' ) ), 'Atom-2' ),
			'plain name with underscore' => array( array( 'design' => array( 'theme' => 'custom_1' ) ), 'custom_1' ),
		);
	}

	#[DataProvider( 'themes' )]
	public function test_theme_name( $instance, $expected ) {
		$widget = new SiteOrigin_Widget_PriceTable_Widget();

		list( $names, $errors ) = $this->run_capturing_errors(
			function () use ( $widget, $instance ) {
				return array( $widget->get_style_name( $instance ), $widget->get_template_name( $instance ) );
			}
		);

		$this->assertSame( array( $expected, $expected ), $names, 'The template and style both use the checked theme name.' );
		$this->assertSame( array(), $errors );
	}
}

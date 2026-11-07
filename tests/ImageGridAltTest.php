<?php

use Brain\Monkey\Functions;
use SiteOrigin\Tests\SiteOriginTests;

if ( ! class_exists( 'SiteOrigin_Widgets_ImageGrid_Widget' ) ) {
	require __DIR__ . '/../widgets/image-grid/image-grid.php';
}

/**
 * Checks that the Image Grid passes an image's alt text through unchanged,
 * for both library images and images added by URL.
 */
class ImageGridAltTest extends SiteOriginTests {
	private $attachment_attr = null;

	protected function setUp(): void {
		parent::setUp();

		$this->attachment_attr = null;

		Functions\when( 'plugin_dir_path' )->justReturn( __DIR__ . '/../widgets/image-grid/' );
		Functions\when( 'siteorigin_widget_get_plugin_dir_path' )->justReturn( __DIR__ . '/../widgets/image-grid/' );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'siteorigin_sanitize_attribute_key' )->returnArg( 1 );
		Functions\when( 'siteorigin_loading_optimization_attributes' )->returnArg( 1 );
		Functions\when( 'wp_get_attachment_image' )->alias(
			function ( $id, $size, $icon, $attr ) {
				$this->attachment_attr = $attr;
				return '<img>';
			}
		);
	}

	private function variables( $image ) {
		$widget = new SiteOrigin_Widgets_ImageGrid_Widget();
		$instance = array(
			'images' => array( $image ),
			'display' => array(
				'attachment_size' => 'full',
				'max_height' => '',
				'max_width' => '',
			),
		);

		return $widget->get_template_variables( $instance, array() );
	}

	public function test_library_image_alt_has_no_trailing_quote() {
		$this->variables(
			array(
				'image' => 12,
				'title' => 'Harbour',
				'alt' => 'Harbour at dawn',
			)
		);

		$this->assertSame( 'Harbour at dawn', $this->attachment_attr['alt'] );
	}

	public function test_url_image_alt_has_no_trailing_quote() {
		$vars = $this->variables(
			array(
				'image' => '',
				'image_fallback' => 'https://example.com/a.jpg',
				'title' => 'Harbour',
				'alt' => 'Harbour at dawn',
			)
		);

		$html = reset( $vars['images'] )['image_html'];
		$this->assertStringContainsString( 'alt="Harbour at dawn" ', $html );
	}

	public function test_empty_alt_stays_empty() {
		$this->variables(
			array(
				'image' => 12,
				'title' => 'Harbour',
				'alt' => '',
			)
		);

		$this->assertSame( '', $this->attachment_attr['alt'] );
	}
}

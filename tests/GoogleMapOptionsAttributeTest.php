<?php

use Brain\Monkey\Functions;
use SiteOrigin\Tests\SiteOriginTests;

/**
 * Checks that the Google Maps Widget writes its settings into data-options
 * as JSON the browser can read back, whatever markup a marker's info holds.
 */
class GoogleMapOptionsAttributeTest extends SiteOriginTests {
	protected function setUp(): void {
		parent::setUp();

		// As WordPress's esc_attr() does, leave existing entities as they are.
		Functions\when( 'esc_attr' )->alias(
			function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false );
			}
		);
		Functions\when( 'wp_json_encode' )->alias(
			function ( $data, $flags = 0 ) {
				return json_encode( $data, $flags );
			}
		);
	}

	/**
	 * Render the template and return data-options as the browser reads it.
	 */
	private function read_options( $map_data ) {
		$map_id = 'test';
		$map_consent = false;
		$fallback_image_data = array( 'img' => '' );

		ob_start();
		include __DIR__ . '/../widgets/google-map/tpl/js-map.php';
		$html = ob_get_clean();

		$this->assertSame( 1, preg_match( '/data-options="([^"]*)"/', $html, $matches ) );

		return json_decode( html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ), true );
	}

	public function test_marker_info_with_an_entity_in_an_attribute_stays_valid_json() {
		// What the SiteOrigin Premium Web Font Selector saves for a font
		// applied in a marker's Info Window Content.
		$info = '<p><span class="so-premium-web-font" style="font-family: DynaPuff;" data-font-info="{&quot;font&quot;:&quot;DynaPuff&quot;,&quot;variant&quot;:&quot;regular&quot;}">Boutique</span></p>';
		$map_data = array(
			'apiKey' => 'KEY',
			'markerPositions' => array(
				array(
					'place' => '47.862430, 5.334115',
					'info' => $info,
				),
			),
		);

		$options = $this->read_options( $map_data );

		$this->assertIsArray( $options, 'data-options is not valid JSON.' );
		$this->assertSame( $map_data, $options );
	}

	public function test_plain_text_and_ampersands_come_back_unchanged() {
		$map_data = array(
			'address' => 'Fish & Chips, "The Quay", O\'Brien St <b>',
			'markerPositions' => array(
				array(
					'place' => '1, 2',
					'info' => 'Tom &amp; Jerry &lt;3 caf&eacute; &#039;x&#039;',
				),
			),
		);

		$this->assertSame( $map_data, $this->read_options( $map_data ) );
	}
}

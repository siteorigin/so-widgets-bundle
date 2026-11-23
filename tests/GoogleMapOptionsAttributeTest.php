<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

// The template asks the widget class for the settings the script receives.
if ( ! class_exists( 'SiteOrigin_Widget_GoogleMap_Widget' ) ) {
	require __DIR__ . '/../widgets/google-map/google-map.php';
}

if ( ! function_exists( 'siteorigin_widgets_underscores_to_camel_case' ) ) {
	require __DIR__ . '/../base/inc/string-utils.php';
}

if ( ! class_exists( 'SiteOrigin_GoogleMap_Options_Test_Widget' ) ) {
	/**
	 * The Google Maps widget, with its global settings fixed so no option is read.
	 */
	class SiteOrigin_GoogleMap_Options_Test_Widget extends SiteOrigin_Widget_GoogleMap_Widget {
		public function get_global_settings( $key = null ) {
			$values = array(
				'map_consent'           => false,
				'responsive_breakpoint' => '780',
			);

			return ! empty( $key ) ? $values[ $key ] : $values;
		}
	}
}

/**
 * Checks that the Google Maps Widget writes its settings into data-options
 * as JSON the browser can read back, whatever markup a marker's info holds,
 * and that the script receives text settings as plain text.
 *
 * WordPress isn't loaded, so its HTML decoder is absent and the widget's
 * older-WordPress decoding is what runs here.
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

	/**
	 * What the script reads when the settings are written without
	 * JSON_HEX_AMP: esc_attr() leaves the entities in the JSON and the browser
	 * decodes them. Null where the result isn't valid JSON.
	 */
	private function read_options_written_without_hex_amp( $map_data ) {
		return json_decode( html_entity_decode( esc_attr( wp_json_encode( $map_data ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), true );
	}

	/**
	 * Map settings with one stored string in every text setting and in a
	 * marker's info.
	 */
	private function map_data_holding( $stored ) {
		return array(
			'address' => $stored,
			'markerPositions' => array(
				array(
					'place' => $stored,
					'info' => $stored,
				),
			),
			'directions' => array(
				'origin' => $stored,
				'destination' => $stored,
				'waypoints' => array(
					array(
						'location' => $stored,
						'stopover' => true,
					),
				),
			),
			'mapName' => $stored,
		);
	}

	/**
	 * The text settings, in the order text_settings_of() returns them.
	 */
	private const TEXT_SETTINGS = array( 'address', 'place', 'origin', 'destination', 'waypoint', 'mapName' );

	private function text_settings_of( $options ) {
		return array(
			$options['address'],
			$options['markerPositions'][0]['place'],
			$options['directions']['origin'],
			$options['directions']['destination'],
			$options['directions']['waypoints'][0]['location'],
			$options['mapName'],
		);
	}

	/**
	 * Strings as the widget's text fields store them: kses saves a typed
	 * ampersand, and a typed greater-than sign, as an entity.
	 */
	public static function stored_text(): array {
		return array(
			'an ampersand' => array( 'Marks &amp; Spencer, 458 Oxford Street, London', 'Marks & Spencer, 458 Oxford Street, London' ),
			'a greater-than sign' => array( 'a &gt; b', 'a > b' ),
			'a numeric apostrophe' => array( 'it&#039;s', "it's" ),
			'a non-breaking space' => array( 'a&nbsp;b', "a\u{a0}b" ),
			'an accented letter' => array( 'x &eacute; y', "x \u{e9} y" ),
			'a quote entity' => array( 'say &quot;hi&quot;', 'say "hi"' ),
			'a real tag' => array( 'A <b>bold</b> one', 'A <b>bold</b> one' ),
			'no entity' => array( 'plain text', 'plain text' ),
			// Without JSON_HEX_AMP an encoded backslash starts a JSON escape.
			'an encoded backslash before n' => array( 'place &#092;n', "place \n" ),
			'two encoded backslashes' => array( 'two &#092;&#092; x', 'two \\ x' ),
			// Alone, it makes that JSON invalid.
			'an encoded backslash alone' => array( 'back &#092; slash', 'back \\ slash' ),
		);
	}

	#[DataProvider( 'stored_text' )]
	public function test_text_settings_reach_the_script_as_plain_text( $stored, $plain ) {
		$map_data = $this->map_data_holding( $stored );

		$options = $this->read_options( $map_data );
		$this->assertIsArray( $options, 'data-options is not valid JSON.' );

		foreach ( $this->text_settings_of( $options ) as $index => $value ) {
			$this->assertSame( $plain, $value, self::TEXT_SETTINGS[ $index ] . ' is not plain text.' );
		}

		// The script gets what it would read without JSON_HEX_AMP, wherever
		// that is valid JSON.
		$plainly_written = $this->read_options_written_without_hex_amp( $map_data );
		if ( is_array( $plainly_written ) ) {
			$this->assertSame( $this->text_settings_of( $plainly_written ), $this->text_settings_of( $options ) );
		}
	}

	#[DataProvider( 'stored_text' )]
	public function test_marker_info_reaches_the_script_as_stored( $stored, $plain ) {
		$options = $this->read_options( $this->map_data_holding( $stored ) );

		$this->assertSame( $stored, $options['markerPositions'][0]['info'] );
	}

	public function test_a_quote_entity_in_a_text_setting_no_longer_breaks_the_json() {
		$map_data = $this->map_data_holding( 'say &quot;hi&quot;' );

		$this->assertNull( $this->read_options_written_without_hex_amp( $map_data ), 'Without JSON_HEX_AMP this is expected to be invalid JSON.' );
		$this->assertIsArray( $this->read_options( $map_data ) );
	}

	public function test_settings_that_are_not_text_are_left_alone() {
		$map_data = array(
			'address' => '47.862430, 5.334115',
			'zoom' => 12,
			'markerAtCenter' => true,
			'markerPositions' => false,
			'mapName' => false,
			'mapStyles' => false,
			'directions' => '',
		);

		$this->assertSame( $map_data, $this->read_options( $map_data ) );
		$this->assertSame( 'not an array', SiteOrigin_Widget_GoogleMap_Widget::script_map_data( 'not an array' ) );
	}

	public function test_the_settings_passed_in_are_not_modified() {
		$map_data = $this->map_data_holding( 'Tom &amp; Jerry' );
		$original = $map_data;

		$for_script = SiteOrigin_Widget_GoogleMap_Widget::script_map_data( $map_data );

		$this->assertSame( $original, $map_data );
		$this->assertSame( 'Tom & Jerry', $for_script['address'] );
		$this->assertSame( 'Tom &amp; Jerry', $for_script['markerPositions'][0]['info'] );
	}

	/**
	 * The siteorigin_widgets_template_variables_sow-google-map filter receives
	 * what get_template_variables() returns, so text must leave it as stored.
	 */
	public function test_the_template_variables_filter_still_receives_text_as_stored() {
		Functions\when( 'wp_get_attachment_image_src' )->justReturn( false );
		Functions\when( 'plugin_dir_url' )->justReturn( '' );

		$stored = 'Tom &amp; Jerry, 1 High St, Town';
		$instance = array(
			'map_center' => array( 'address' => $stored ),
			'settings' => array(
				'map_type' => 'interactive',
				'zoom' => 12,
				'mobile_zoom' => 12,
				'disable_default_ui' => false,
			),
			'markers' => array(
				'marker_icon' => 0,
				'info_display' => 'click',
				'info_multiple' => false,
				'marker_positions' => array(
					array(
						'place' => array( 'address' => $stored ),
						'info' => $stored,
					),
				),
			),
			'styles' => array(
				'style_method' => 'custom',
				'styled_map_name' => 'Night &amp; Day',
				'custom_map_styles' => array(
					array(
						'map_feature' => 'water',
						'element_type' => 'geometry',
						'color' => '#000000',
					),
				),
			),
			'directions' => array(
				'origin' => $stored,
				'destination' => $stored,
				'waypoints' => array(
					array( 'location' => $stored ),
				),
			),
		);

		$widget = new SiteOrigin_GoogleMap_Options_Test_Widget();
		$variables = $widget->get_template_variables( $instance, array() );
		$map_data = $variables['map_data'];

		$this->assertSame( $stored, $map_data['address'] );
		$this->assertSame( $stored, $map_data['markerPositions'][0]['place'] );
		$this->assertSame( $stored, $map_data['markerPositions'][0]['info'] );
		$this->assertSame( 'Night &amp; Day', $map_data['mapName'] );
		$this->assertSame( $stored, $map_data['directions']['origin'] );
		$this->assertSame( $stored, $map_data['directions']['destination'] );
		$this->assertSame( $stored, $map_data['directions']['waypoints'][0]['location'] );
	}
}

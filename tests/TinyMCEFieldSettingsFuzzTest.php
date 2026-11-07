<?php

use Brain\Monkey\Functions;
use SiteOrigin\Tests\SiteOriginTests;

if ( ! function_exists( 'siteorigin_widget_register' ) ) {
	function siteorigin_widget_register() {
		return true;
	}
}

if ( ! class_exists( 'SiteOrigin_Widget_Editor_Widget' ) ) {
	require __DIR__ . '/../widgets/editor/editor.php';
}

/**
 * Fuzzes the TinyMCE field's editor settings: random tiny_mce_before_init
 * results go through the real render, and the emitted settings must match the
 * reference below exactly, key order included.
 *
 * The reference copies the field's settings loop as it stood before a false
 * wordpress_adv_hidden was kept, then applies that one exception. Any other
 * difference between the field and the reference is a regression.
 *
 * SOWB_FUZZ_CASES sets the number of cases (default 10000) and
 * SOWB_FUZZ_SEED the first seed (default 1). A failure names its seed.
 */
class TinyMCEFieldSettingsFuzzTest extends SiteOriginTests {
	private $filtered;

	const KEYS = array(
		'toolbar1', 'toolbar2', 'toolbar3', 'toolbar4', 'plugins', 'external_plugins',
		'content_css', 'wpautop', 'wp_skip_init', 'wordpress_adv_hidden', 'indent',
		'forced_root_block', 'verify_html', 'valid_elements', 'extended_valid_elements',
		'paste_as_text', 'menubar', 'table_toolbar', 'table_grid', 'textcolor_map',
		'style_formats', 'directionality', 'tadv_noautop', 'readonly', 'setup',
		'custom_elements', 'image_advtab', 'rel_list', 'fontsize_formats',
	);

	protected function setUp(): void {
		parent::setUp();

		if ( ! defined( 'SCRIPT_DEBUG' ) ) {
			define( 'SCRIPT_DEBUG', false );
		}

		$GLOBALS['wp_version'] = '7.0';

		Functions\when( 'user_can_richedit' )->justReturn( true );
		Functions\when( 'includes_url' )->alias( fn( $path = '' ) => 'https://example.test/wp-includes/' . $path );
		Functions\when( 'get_bloginfo' )->justReturn( '7.0' );
		Functions\when( 'get_editor_stylesheets' )->justReturn( array() );
		Functions\when( 'esc_attr' )->alias( fn( $text ) => htmlspecialchars( (string) $text, ENT_QUOTES ) );
		Functions\when( 'esc_html' )->alias( fn( $text ) => htmlspecialchars( (string) $text, ENT_QUOTES ) );
		Functions\when( 'esc_textarea' )->alias( fn( $text ) => htmlspecialchars( (string) $text, ENT_QUOTES ) );
		Functions\when( 'siteorigin_sanitize_attribute_key' )->returnArg();
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value = null ) => 'tiny_mce_before_init' === $tag ? $this->filtered : $value
		);
	}

	/**
	 * The settings loop as it stood before the wordpress_adv_hidden exception,
	 * copied verbatim, followed by the exception.
	 */
	private static function reference( $settings, $tmce_settings ) {
		foreach ( $tmce_settings as $name => $setting ) {
			unset( $jdec );

			if ( ! empty( $tmce_settings[ $name ] ) ) {
				// Attempt to decode setting as JSON. For back compat with filters used by WP editor.
				if ( is_string( $setting ) ) {
					$jdec = json_decode( $setting, true );
				}
				$settings['tinymce'][ $name ] = empty( $jdec ) ? $setting : $jdec;
			}
		}

		if ( array_key_exists( 'wordpress_adv_hidden', $tmce_settings ) && false === $tmce_settings['wordpress_adv_hidden'] ) {
			$settings['tinymce']['wordpress_adv_hidden'] = false;
		}

		return $settings['tinymce'];
	}

	private function render_tinymce_settings( $filtered ) {
		$this->filtered = $filtered;

		$widget = ( new ReflectionClass( SiteOrigin_Widget_Editor_Widget::class ) )->newInstanceWithoutConstructor();
		$widget->id_base = 'sow-editor';
		$widget->number = 1;

		$field = new SiteOrigin_Widget_Field_TinyMCE(
			'text',
			'widget-sow-editor-1-text',
			'widget-sow-editor[1][text]',
			array(
				'type' => 'tinymce',
				'media_buttons' => false,
			),
			$widget
		);

		ob_start();
		try {
			$field->render( '' );
		} finally {
			$html = ob_get_clean();
		}

		if ( ! preg_match( '/data-editor-settings="([^"]*)"/', $html, $matches ) ) {
			return null;
		}

		$settings = json_decode( html_entity_decode( $matches[1], ENT_QUOTES ), true );

		return $settings['tinymce'] ?? null;
	}

	private static function random_string() {
		$chars = 'abcdefghijklmnopqrstuvwxyz0123456789_,- ';
		$length = mt_rand( 1, 12 );
		$string = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$string .= $chars[ mt_rand( 0, strlen( $chars ) - 1 ) ];
		}

		return $string;
	}

	private static function random_value( $depth = 0 ) {
		$pool = array(
			false, true, 0, 1, 0.0, 1.5, '0', '', null, array(), 'false', 'true', 'null',
			'[]', '{}', '[1,2]', '{"a":1}', '{"bold":{"inline":"b"}}', '[{"title":"B","inline":"b"}]',
			'{not json', '[1,', 'bold,italic', '*[*]', 'p',
		);
		$choice = mt_rand( 0, count( $pool ) + 2 );

		if ( $choice < count( $pool ) ) {
			return $pool[ $choice ];
		}

		if ( $choice === count( $pool ) && $depth < 2 ) {
			$array = array();
			$size = mt_rand( 0, 3 );

			for ( $i = 0; $i < $size; $i++ ) {
				if ( mt_rand( 0, 1 ) ) {
					$array[] = self::random_value( $depth + 1 );
				} else {
					$array[ self::random_string() ] = self::random_value( $depth + 1 );
				}
			}

			return $array;
		}

		return self::random_string();
	}

	private static function random_filter_result() {
		$result = array(
			'toolbar1' => 'formatselect,bold,italic',
			'toolbar2' => 'strikethrough,hr',
			'toolbar3' => '',
			'toolbar4' => '',
			'plugins' => 'charmap,colorpicker',
			'external_plugins' => array(),
			'content_css' => 'https://example.test/wp-includes/css/dashicons.min.css',
		);

		// Filters change, remove and add keys.
		foreach ( array_keys( $result ) as $key ) {
			$roll = mt_rand( 0, 9 );

			if ( $roll === 0 ) {
				unset( $result[ $key ] );
			} elseif ( $roll < 3 ) {
				$result[ $key ] = self::random_value();
			}
		}

		$added = mt_rand( 0, 6 );

		for ( $i = 0; $i < $added; $i++ ) {
			$key = mt_rand( 0, 4 ) ? self::KEYS[ mt_rand( 0, count( self::KEYS ) - 1 ) ] : self::random_string();
			$result[ $key ] = self::random_value();
		}

		// Keep the case under test common.
		if ( mt_rand( 0, 3 ) === 0 ) {
			$result['wordpress_adv_hidden'] = mt_rand( 0, 1 ) ? false : self::random_value();
		}

		return $result;
	}

	public function test_emitted_settings_match_the_reference() {
		$cases = (int) ( getenv( 'SOWB_FUZZ_CASES' ) ?: 10000 );
		$first_seed = (int) ( getenv( 'SOWB_FUZZ_SEED' ) ?: 1 );
		$field_settings = array(
			'tinymce' => array(
				'wp_skip_init' => false,
				'wpautop' => true,
			),
		);
		$adv_hidden_false = 0;

		for ( $seed = $first_seed; $seed < $first_seed + $cases; $seed++ ) {
			mt_srand( $seed );
			$filtered = self::random_filter_result();

			if ( array_key_exists( 'wordpress_adv_hidden', $filtered ) && false === $filtered['wordpress_adv_hidden'] ) {
				$adv_hidden_false++;
			}

			$expected = json_decode( json_encode( self::reference( $field_settings, $filtered ) ), true );
			$actual = $this->render_tinymce_settings( $filtered );

			if ( $expected !== $actual ) {
				$this->fail(
					"Seed $seed: emitted settings differ from the reference.\nFilter result: " . var_export( $filtered, true ) .
					"\nExpected: " . json_encode( $expected ) . "\nActual: " . json_encode( $actual )
				);
			}
		}

		// The generator must exercise the exception, or the run proves nothing about it.
		$this->assertGreaterThan( $cases / 20, $adv_hidden_false );
	}
}

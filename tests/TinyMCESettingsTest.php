<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SiteOrigin_Widget_Field_TinyMCE::prepare_tinymce_settings().
 *
 * The method turns the filtered TinyMCE settings (after tiny_mce_before_init)
 * into the values passed to the editor. Unset values are skipped so TinyMCE
 * keeps its own defaults; explicit false and zero values are real settings and
 * reach the editor, for example Advanced Editor Tools' wordpress_adv_hidden.
 */
if ( ! class_exists( 'SiteOrigin_Widget_Field_Base' ) ) {
	require __DIR__ . '/../base/inc/fields/base.class.php';
}

if ( ! class_exists( 'SiteOrigin_Widget_Field_Text_Input_Base' ) ) {
	require __DIR__ . '/../base/inc/fields/text-input-base.class.php';
}

if ( ! class_exists( 'SiteOrigin_Widget_Field_TinyMCE' ) ) {
	require __DIR__ . '/../base/inc/fields/tinymce.class.php';
}

class TinyMCESettingsTest extends TestCase {
	public function test_false_setting_reaches_the_editor() {
		$settings = SiteOrigin_Widget_Field_TinyMCE::prepare_tinymce_settings(
			array( 'wordpress_adv_hidden' => false )
		);

		$this->assertArrayHasKey( 'wordpress_adv_hidden', $settings );
		$this->assertFalse( $settings['wordpress_adv_hidden'] );
	}

	public function test_zero_setting_reaches_the_editor() {
		$settings = SiteOrigin_Widget_Field_TinyMCE::prepare_tinymce_settings(
			array( 'min_height' => 0 )
		);

		$this->assertSame( array( 'min_height' => 0 ), $settings );
	}

	public function test_unset_values_are_skipped() {
		$settings = SiteOrigin_Widget_Field_TinyMCE::prepare_tinymce_settings(
			array(
				'toolbar3' => '',
				'toolbar4' => null,
				'external_plugins' => array(),
			)
		);

		$this->assertSame( array(), $settings );
	}

	public function test_json_strings_are_decoded() {
		$settings = SiteOrigin_Widget_Field_TinyMCE::prepare_tinymce_settings(
			array(
				'formats' => '{"bold":{"inline":"b"}}',
				'toolbar1' => 'bold,italic',
			)
		);

		$this->assertSame( array( 'bold' => array( 'inline' => 'b' ) ), $settings['formats'] );
		$this->assertSame( 'bold,italic', $settings['toolbar1'] );
	}

	public function test_field_settings_are_kept() {
		$settings = SiteOrigin_Widget_Field_TinyMCE::prepare_tinymce_settings(
			array(
				'wpautop' => false,
				'wp_skip_init' => true,
				'wordpress_adv_hidden' => false,
			),
			array(
				'wpautop' => true,
				'wp_skip_init' => false,
			)
		);

		$this->assertSame(
			array(
				'wpautop' => true,
				'wp_skip_init' => false,
				'wordpress_adv_hidden' => false,
			),
			$settings
		);
	}
}

<?php

use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

require_once __DIR__ . '/../base/inc/widget-describer.php';

/**
 * Both multiple-media spellings describe attachment ID arrays (#2441).
 */
class WidgetDescriberMultipleMediaTest extends SiteOriginTests {
	public static function media_types() {
		return array(
			'underscore' => array( 'multiple_media' ),
			'hyphen' => array( 'multiple-media' ),
		);
	}

	#[DataProvider( 'media_types' )]
	public function test_multiple_media_schema_preserves_raw_type_and_metadata( $type ) {
		$schema = SiteOrigin_Widgets_Widget_Describer::single()->translate_form(
			array(
				'attachments' => array(
					'type' => $type,
					'label' => 'Gallery',
					'description' => 'Attachment IDs.',
					'fallback' => true,
				),
			)
		);

		$this->assertSame(
			array(
				'type' => 'object',
				'properties' => array(
					'attachments' => array(
						'type' => 'array',
						'items' => array( 'type' => 'integer' ),
						'x-sowb-field-type' => $type,
						'title' => 'Gallery',
						'description' => 'Attachment IDs.',
					),
				),
			),
			$schema
		);
	}

	public static function other_types() {
		return array(
			'single media' => array( 'media', array( 'type' => 'integer', 'description' => 'Attachment ID.', 'x-sowb-field-type' => 'media' ) ),
			'text' => array( 'text', array( 'type' => 'string', 'x-sowb-text' => true, 'x-sowb-field-type' => 'text' ) ),
			'known hyphen type' => array( 'image-radio', array( 'type' => 'string', 'enum' => array( 'one' ), 'x-sowb-field-type' => 'image-radio' ) ),
			'unrequested underscore alias' => array( 'image_radio', array( 'type' => 'string', 'x-sowb-field-type' => 'image_radio' ) ),
			'unknown type' => array( 'custom_media', array( 'type' => 'string', 'x-sowb-field-type' => 'custom_media' ) ),
		);
	}

	#[DataProvider( 'other_types' )]
	public function test_other_schemas_and_unrequested_aliases_are_unchanged( $type, $expected ) {
		$this->assertSame(
			$expected,
			SiteOrigin_Widgets_Widget_Describer::single()->translate_field(
				array( 'type' => $type, 'options' => array( 'one' => 'One' ) )
			)
		);
	}
}

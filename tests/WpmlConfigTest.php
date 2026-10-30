<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Keeps the two sections of wpml-config.xml in step.
 *
 * WPML reads <siteorigin-widgets> for widgets in Page Builder and
 * <gutenberg-blocks> for the Widgets Bundle blocks. Each block must list
 * exactly its widget's fields. WPML matches block keys one level at a time, so
 * a path is compared in full, "*" included, and a key name holding ">" never
 * matches a field.
 *
 * The test reads the file only; it needs no WordPress.
 */
class WpmlConfigTest extends TestCase {
	private const CONFIG_FILE = __DIR__ . '/../wpml-config.xml';

	/**
	 * WPML 4.7.4's res/xsd/wpml-config.xsd. WPML drops a config file that fails
	 * this schema, and every mapping in the file goes with it.
	 */
	private const SCHEMA_FILE = __DIR__ . '/fixtures/wpml-config.xsd';

	private const LEGACY_BLOCK = 'sowb/widget-block';

	/**
	 * Sections inside an items_of path. Every other segment is a repeater, and
	 * WPML needs a "*" key for its items.
	 */
	private const ITEMS_OF_SECTIONS = array(
		'SiteOrigin_Widget_GoogleMap_Widget' => array( 'markers' ),
	);

	/**
	 * Builder fields hold a layout, not text, so no widget entry lists them.
	 * In a block they are stored as a JSON string, and a key with no children
	 * sends that string to translators whole.
	 */
	private const BUILDER_FIELDS = array(
		'SiteOrigin_Widget_Accordion_Widget' => 'panels>*>content_layout',
		'SiteOrigin_Widget_Anything_Carousel_Widget' => 'items>*>content_layout',
		'SiteOrigin_Widget_LayoutSlider_Widget' => 'frames>*>content',
		'SiteOrigin_Widget_Tabs_Widget' => 'tabs>*>content_layout',
	);

	/**
	 * Page Builder owns the Button Grid widget entry, so this mirrors
	 * SiteOrigin_Widget_Button_Grid_Widget in its wpml-config.xml.
	 */
	private const BUTTON_GRID_BLOCK = 'sowb/siteorigin-widget-button-grid-widget';

	private const BUTTON_GRID_FIELDS = array(
		'buttons>*>widget>attributes>title' => '',
		'buttons>*>widget>text' => '',
		'buttons>*>widget>url' => 'link',
	);

	/**
	 * Fields translators must get, with their editor_type. The block test reads
	 * its paths from the widget entries, so a field removed from both sections
	 * fails only here.
	 */
	private const REQUIRED_FIELDS = array(
		'SiteOrigin_Widget_Blog_Widget' => array(
			'settings>excerpt_trim' => '',
			'settings>read_more_text' => '',
			'title' => '',
		),
		'SiteOrigin_Widget_Cta_Widget' => array(
			'button>attributes>title' => '',
			'button>text' => '',
			'button>url' => 'LINK',
		),
		'SiteOrigin_Widget_GoogleMap_Widget' => array(
			'settings>destination_url' => 'LINK',
		),
		'SiteOrigin_Widget_Hero_Widget' => array(
			'frames>*>background>alt' => '',
			'frames>*>background>url' => 'LINK',
		),
		'SiteOrigin_Widget_LayoutSlider_Widget' => array(
			'frames>*>background>alt' => '',
			'frames>*>background>url' => 'LINK',
		),
		'SiteOrigin_Widget_Lottie_Player_Widget' => array(
			'url' => 'LINK',
		),
		'SiteOrigin_Widget_PriceTable_Widget' => array(
			'columns>*>price' => '',
			'columns>*>sale_price' => '',
		),
		'SiteOrigin_Widget_Recent_Posts_Widget' => array(
			'recent_settings>excerpt_trim' => '',
			'recent_settings>read_more_text' => '',
			'title' => '',
		),
		'SiteOrigin_Widget_Simple_Masonry_Widget' => array(
			'widget_title' => '',
		),
		'SiteOrigin_Widget_Video_Widget' => array(
			'video>external_video' => 'LINK',
		),
		'SiteOrigin_Widgets_ContactForm_Widget' => array(
			'settings>required_field_indicator_message' => '',
			'settings>success_message' => 'VISUAL',
		),
		'SiteOrigin_Widgets_Testimonials_Widget' => array(
			'testimonials>*>location' => '',
			'testimonials>*>name' => '',
		),
	);

	/**
	 * The paths WPML matches in a legacy block. These keys apply to every widget
	 * class in the block, third-party ones included, whose forms can't be
	 * checked. So a new path stays out, and a block gets it once migrated.
	 */
	private const LEGACY_FIELDS = array(
		'alt',
		'attributes>title',
		'caption',
		'columns>*>button_text',
		'columns>*>image_alt',
		'columns>*>image_title',
		'columns>*>per',
		'columns>*>subtitle',
		'columns>*>title',
		'columns>*>url',
		'content',
		'cta>*>sub_title',
		'cta>*>title',
		'features>*>icon_title',
		'features>*>more_text',
		'features>*>more_url',
		'features>*>text',
		'features>*>title',
		'fields>*>description',
		'fields>*>label',
		'fields>*>required>missing_message',
		'frames>*>content',
		'headline>destination_url',
		'headline>text',
		'images>*>alt',
		'images>*>title',
		'images>*>url',
		'items>*>content_layout',
		'items>*>content_text',
		'items>*>title',
		'items>*>url',
		'networks>*>icon_title',
		'networks>*>url',
		'panels>*>content_layout',
		'panels>*>content_text',
		'panels>*>title',
		'settings>default_subject',
		'settings>submit_text',
		'settings>subject_prefix',
		'settings>success_message',
		'sub_headline>destination_url',
		'sub_headline>text',
		'tabs>*>content_layout',
		'tabs>*>content_text',
		'tabs>*>title',
		'tabs>*>url',
		'taxonomy>*>label',
		'testimonials>*>text',
		'testimonials>*>url',
		'text',
		'title',
		'url',
	);

	private static function xpath(): DOMXPath {
		$document = new DOMDocument();
		$document->load( self::CONFIG_FILE );

		return new DOMXPath( $document );
	}

	/**
	 * @return array<string, DOMElement> Each block entry, keyed by block name.
	 */
	private static function blocks(): array {
		$blocks = array();

		foreach ( self::xpath()->query( '/wpml-config/gutenberg-blocks/gutenberg-block' ) as $block ) {
			$blocks[ $block->getAttribute( 'type' ) ] = $block;
		}

		return $blocks;
	}

	/**
	 * @return DOMElement[] The <key> elements directly inside an element.
	 */
	private static function child_keys( DOMElement $element ): array {
		$keys = array();

		foreach ( $element->childNodes as $child ) {
			if ( $child instanceof DOMElement && $child->tagName === 'key' ) {
				$keys[] = $child;
			}
		}

		return $keys;
	}

	/**
	 * @return array<string, string> The type attribute of each leaf key, keyed
	 *                               by its path below widgetData.
	 */
	private static function leaves( DOMElement $element, string $prefix = '' ): array {
		$leaves = array();

		foreach ( self::child_keys( $element ) as $key ) {
			$path = $prefix . $key->getAttribute( 'name' );
			$children = self::leaves( $key, $path . '>' );

			if ( empty( $children ) ) {
				$leaves[ $path ] = $key->getAttribute( 'type' );
			}

			$leaves += $children;
		}

		ksort( $leaves );

		return $leaves;
	}

	private static function block_leaves( DOMElement $block ): array {
		$roots = self::child_keys( $block );

		return count( $roots ) === 1 && $roots[0]->getAttribute( 'name' ) === 'widgetData'
			? self::leaves( $roots[0] )
			: array();
	}

	private static function block_name( string $widget_class ): string {
		return 'sowb/' . strtolower( str_replace( '_', '-', $widget_class ) );
	}

	/**
	 * @return string The block path of a field in a widget entry.
	 */
	private static function field_path( string $widget_class, string $items_of, string $field ): string {
		if ( $items_of === '' ) {
			return $field;
		}

		$sections = self::ITEMS_OF_SECTIONS[ $widget_class ] ?? array();
		$path = array();

		foreach ( explode( '>', $items_of ) as $segment ) {
			$path[] = $segment;

			if ( ! in_array( $segment, $sections, true ) ) {
				$path[] = '*';
			}
		}

		return implode( '>', $path ) . '>' . $field;
	}

	/**
	 * @return array<string, string> The editor_type of each field in a widget
	 *                               entry, keyed by its block path.
	 */
	private static function entry_fields( DOMElement $widget ): array {
		$fields = array();

		foreach ( $widget->getElementsByTagName( 'field' ) as $field ) {
			$group = $field->parentNode;
			$items_of = $group->tagName === 'fields-in-item' ? $group->getAttribute( 'items_of' ) : '';
			$path = self::field_path( $widget->getAttribute( 'name' ), $items_of, trim( $field->textContent ) );

			$fields[ $path ] = $field->getAttribute( 'editor_type' );
		}

		return $fields;
	}

	/**
	 * @return array<string, array> The expected leaf keys of each widget's block.
	 */
	public static function widget_entries(): array {
		$entries = array();

		foreach ( self::xpath()->query( '/wpml-config/siteorigin-widgets/widget' ) as $widget ) {
			$widget_class = $widget->getAttribute( 'name' );
			$expected = array_map(
				function ( $editor_type ) {
					return $editor_type === 'LINK' ? 'link' : '';
				},
				self::entry_fields( $widget )
			);

			if ( isset( self::BUILDER_FIELDS[ $widget_class ] ) ) {
				$expected[ self::BUILDER_FIELDS[ $widget_class ] ] = '';
			}

			ksort( $expected );
			$entries[ $widget_class ] = array( $widget_class, $expected );
		}

		return $entries;
	}

	public function test_config_passes_wpml_schema(): void {
		$previous = libxml_use_internal_errors( true );
		$document = new DOMDocument();
		$document->load( self::CONFIG_FILE );
		$valid = $document->schemaValidate( self::SCHEMA_FILE );
		$errors = array_map(
			function ( $error ) {
				return trim( $error->message );
			},
			libxml_get_errors()
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$this->assertTrue( $valid, implode( "\n", $errors ) );
	}

	public function test_required_fields_are_in_their_widget_entries(): void {
		$xpath = self::xpath();

		foreach ( self::REQUIRED_FIELDS as $widget_class => $required ) {
			$widget = $xpath->query( '/wpml-config/siteorigin-widgets/widget[@name="' . $widget_class . '"]' )->item( 0 );

			$this->assertNotNull( $widget, $widget_class );
			$fields = self::entry_fields( $widget );

			foreach ( $required as $path => $editor_type ) {
				$this->assertArrayHasKey( $path, $fields, $widget_class );
				$this->assertSame( $editor_type, $fields[ $path ], "$widget_class $path" );
			}
		}
	}

	public function test_every_block_is_translatable(): void {
		foreach ( self::blocks() as $block_name => $block ) {
			$this->assertSame( '1', $block->getAttribute( 'translate' ), $block_name );
		}
	}

	public function test_every_block_entry_has_a_source(): void {
		$expected = array_map( array( self::class, 'block_name' ), array_keys( self::widget_entries() ) );
		array_push( $expected, self::BUTTON_GRID_BLOCK, self::LEGACY_BLOCK );

		$actual = array_keys( self::blocks() );
		sort( $expected );
		sort( $actual );

		$this->assertSame( $expected, $actual );
	}

	#[DataProvider( 'widget_entries' )]
	public function test_block_lists_its_widget_entry( string $widget_class, array $expected ): void {
		$blocks = self::blocks();
		$block_name = self::block_name( $widget_class );

		$this->assertArrayHasKey( $block_name, $blocks );
		$this->assertSame( $expected, self::block_leaves( $blocks[ $block_name ] ) );
	}

	public function test_button_grid_block_mirrors_page_builder(): void {
		$this->assertSame(
			self::BUTTON_GRID_FIELDS,
			self::block_leaves( self::blocks()[ self::BUTTON_GRID_BLOCK ] )
		);
	}

	public function test_legacy_block_keeps_its_mapping(): void {
		$expected = self::LEGACY_FIELDS;
		$actual = array_keys( self::block_leaves( self::blocks()[ self::LEGACY_BLOCK ] ) );
		sort( $expected );
		sort( $actual );

		$this->assertSame( $expected, $actual );
	}

	public function test_builder_fields_are_keys_without_children(): void {
		$blocks = self::blocks();

		foreach ( self::BUILDER_FIELDS as $widget_class => $path ) {
			$this->assertArrayHasKey(
				$path,
				self::block_leaves( $blocks[ self::block_name( $widget_class ) ] ),
				$widget_class
			);
		}
	}

	public function test_block_keys_are_well_formed(): void {
		$keys = self::xpath()->query( '/wpml-config/gutenberg-blocks//key' );

		$this->assertGreaterThan( 0, $keys->length );

		foreach ( $keys as $key ) {
			$name = $key->getAttribute( 'name' );
			$child_names = array_map(
				function ( $child ) {
					return $child->getAttribute( 'name' );
				},
				self::child_keys( $key )
			);

			$this->assertStringNotContainsString( '>', $name );
			$this->assertSame( array_unique( $child_names ), $child_names, "Duplicate keys in $name" );

			foreach ( $key->attributes as $attribute ) {
				$this->assertContains( $attribute->name, array( 'name', 'type' ), $name );
			}

			if ( $key->hasAttribute( 'type' ) ) {
				$this->assertSame( 'link', $key->getAttribute( 'type' ), $name );
			}
		}
	}
}

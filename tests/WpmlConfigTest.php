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
	 * The mapping WPML compiled from the shared tree this block used to hold,
	 * without the keys whose name held ">". A legacy block can hold any
	 * SiteOrigin widget, third-party ones included, so a path added here
	 * applies to widgets whose forms can't be checked.
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
	 * @return array<string, array> The expected leaf keys of each widget's block.
	 */
	public static function widget_entries(): array {
		$entries = array();

		foreach ( self::xpath()->query( '/wpml-config/siteorigin-widgets/widget' ) as $widget ) {
			$widget_class = $widget->getAttribute( 'name' );
			$expected = array();

			foreach ( $widget->getElementsByTagName( 'field' ) as $field ) {
				$group = $field->parentNode;
				$items_of = $group->tagName === 'fields-in-item' ? $group->getAttribute( 'items_of' ) : '';
				$path = self::field_path( $widget_class, $items_of, trim( $field->textContent ) );

				$expected[ $path ] = $field->getAttribute( 'editor_type' ) === 'LINK' ? 'link' : '';
			}

			if ( isset( self::BUILDER_FIELDS[ $widget_class ] ) ) {
				$expected[ self::BUILDER_FIELDS[ $widget_class ] ] = '';
			}

			ksort( $expected );
			$entries[ $widget_class ] = array( $widget_class, $expected );
		}

		return $entries;
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

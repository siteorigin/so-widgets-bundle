<?php

use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

require_once __DIR__ . '/../compat/block-editor/widget-block-untrusted.php';

/**
 * A JsonSerializable value, used to check that normalize() serialises it.
 */
class SiteOrigin_Untrusted_Json_Fixture implements JsonSerializable {
	private $data;

	public function __construct( $data ) {
		$this->data = $data;
	}

	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->data;
	}
}

/**
 * Tests the pure helpers used for untrusted widget data.
 */
class UntrustedWidgetDataTest extends SiteOriginTests {
	/**
	 * @return array[]
	 */
	public static function neutralize_cases() {
		return array(
			'literal'                 => array( '[x]' ),
			'decimal'                 => array( '&#91;x&#93;' ),
			'decimal zero padded'     => array( '&#091;x]' ),
			'hex'                     => array( '&#x5B;x]' ),
			'hex upper X'             => array( '&#X5b;x]' ),
			'decimal no semicolon'    => array( '&#91x]' ),
			'named lsqb and rsqb'     => array( '&lsqb;x&rsqb;' ),
			'named lbrack'            => array( '&lbrack;x]' ),
			'encoded ampersand'       => array( '&amp;#91;x]' ),
			'double encoded hex'      => array( '&amp;amp;#x5b;x]' ),
			'numeric ampersand'       => array( '&#38;#91;x]' ),
			'hex no semicolon'        => array( '&#x5bx&#x5d' ),
			'named upper case'        => array( '&LSQB;x&RBRACK;' ),
		);
	}

	#[DataProvider( 'neutralize_cases' )]
	public function test_neutralize_shortcodes_makes_brackets_full_width( $input ) {
		$this->assertSame(
			"\u{FF3B}x\u{FF3D}",
			SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::neutralize_shortcodes( $input )
		);
	}

	public function test_neutralize_shortcodes_keeps_other_code_points_and_plain_text() {
		$this->assertSame( '&#912;', SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::neutralize_shortcodes( '&#912;' ) );
		$this->assertSame( '&#x5bc;', SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::neutralize_shortcodes( '&#x5bc;' ) );
		$this->assertSame( 'Plain &amp; <b>text</b>', SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::neutralize_shortcodes( 'Plain &amp; <b>text</b>' ) );
		$this->assertSame( '', SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::neutralize_shortcodes( '' ) );
	}

	public function test_neutralize_shortcodes_full_shortcode() {
		$this->assertSame(
			"\u{FF3B}gallery ids=\"1\"\u{FF3D}\u{FF3B}/gallery\u{FF3D}",
			SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::neutralize_shortcodes( '&#91;gallery ids="1"&#93;[/gallery]' )
		);
	}

	public function test_normalize_converts_objects_to_arrays() {
		$input = (object) array(
			'a' => 'one',
			'b' => (object) array( 'c' => 'two', 'd' => 3 ),
			'e' => new ArrayObject( array( 'f' => 'three', 'g' => (object) array( 'h' => true ) ) ),
			'i' => new SiteOrigin_Untrusted_Json_Fixture( array( 'j' => 'four', 'k' => (object) array( 'l' => null ) ) ),
		);

		$this->assertSame(
			array(
				'a' => 'one',
				'b' => array( 'c' => 'two', 'd' => 3 ),
				'e' => array( 'f' => 'three', 'g' => array( 'h' => true ) ),
				'i' => array( 'j' => 'four', 'k' => array( 'l' => null ) ),
			),
			SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::normalize( $input )
		);
	}

	public function test_normalize_removes_unsupported_values() {
		$resource = fopen( 'php://memory', 'r' );

		try {
			$result = SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::normalize(
				array(
					'keep' => 'x',
					'handle' => $resource,
					'nested' => array( 'handle' => $resource, 'n' => 1.5 ),
					'infinite' => INF,
				)
			);
		} finally {
			fclose( $resource );
		}

		$this->assertSame(
			array(
				'keep' => 'x',
				'nested' => array( 'n' => 1.5 ),
			),
			$result
		);
	}

	public function test_normalize_throws_on_self_reference() {
		$object = new stdClass();
		$object->self = $object;

		$this->expectException( InvalidArgumentException::class );
		SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::normalize( $object );
	}

	public function test_normalize_throws_on_json_serializable_returning_itself() {
		$fixture = new SiteOrigin_Untrusted_Json_Fixture( null );
		$reflection = new ReflectionProperty( $fixture, 'data' );
		$reflection->setValue( $fixture, $fixture );

		$this->expectException( InvalidArgumentException::class );
		SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::normalize( $fixture );
	}

	public function test_normalize_depth_limit() {
		$ok = 'leaf';
		for ( $i = 0; $i < SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::MAX_DEPTH; $i++ ) {
			$ok = array( 'a' => $ok );
		}
		$this->assertIsArray( SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::normalize( $ok ) );

		$this->expectException( InvalidArgumentException::class );
		SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::normalize( array( 'a' => $ok ) );
	}

	public function test_assert_patch_keys_accepts_valid_keys() {
		SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::assert_patch_keys(
			array(
				'hero_title' => 'x',
				0 => 'y',
				'so_field_container_state' => 'open',
				'frames' => array( 1 => array( 'content' => 'z', 'some-key' => 'w' ) ),
			)
		);
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @return array[]
	 */
	public static function invalid_key_cases() {
		return array(
			'space'          => array( array( 'bad key' => 'x' ) ),
			'angle bracket'  => array( array( 'a<b' => 'x' ) ),
			'square bracket' => array( array( '[x]' => 'x' ) ),
			'nested'         => array( array( 'ok' => array( 'bad key' => 'x' ) ) ),
		);
	}

	#[DataProvider( 'invalid_key_cases' )]
	public function test_assert_patch_keys_rejects_invalid_keys( $patch ) {
		$this->expectException( InvalidArgumentException::class );
		SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::assert_patch_keys( $patch );
	}

	public function test_assert_patch_keys_rejects_deep_nesting() {
		$patch = 'leaf';
		for ( $i = 0; $i < SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::MAX_DEPTH + 2; $i++ ) {
			$patch = array( 'a' => $patch );
		}

		$this->expectException( InvalidArgumentException::class );
		SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::assert_patch_keys( $patch );
	}

	public function test_merge() {
		$stored = array(
			'title' => 'Stored',
			'text' => 'Keep me',
			'design' => array( 'color' => 'red', 'size' => 'large' ),
			'frames' => array(
				0 => array( 'content' => 'A', 'button' => 'a' ),
				1 => array( 'content' => 'B', 'button' => 'b' ),
			),
			'list' => array( 'x', 'y' ),
			'scalar_then_array' => 'plain',
			'array_then_scalar' => array( 'deep' => 1 ),
		);

		$patch = array(
			'title' => 'New',
			'design' => array( 'color' => 'blue' ),
			'frames' => array( 1 => array( 'content' => 'B2' ) ),
			'list' => array(),
			'scalar_then_array' => array( 'now' => 'array' ),
			'array_then_scalar' => 'now scalar',
			'added' => 'extra',
		);

		$this->assertSame(
			array(
				'title' => 'New',
				'text' => 'Keep me',
				'design' => array( 'color' => 'blue', 'size' => 'large' ),
				'frames' => array(
					0 => array( 'content' => 'A', 'button' => 'a' ),
					1 => array( 'content' => 'B2', 'button' => 'b' ),
				),
				'list' => array(),
				'scalar_then_array' => array( 'now' => 'array' ),
				'array_then_scalar' => 'now scalar',
				'added' => 'extra',
			),
			SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::merge( $stored, $patch )
		);
	}

	public function test_leaf_paths() {
		$this->assertSame(
			array(
				array( 'title' ),
				array( 'frames', 1, 'content' ),
				array( 'list' ),
				array( 'design', 'color' ),
			),
			SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::leaf_paths(
				array(
					'title' => 'x',
					'frames' => array( 1 => array( 'content' => 'y' ) ),
					'list' => array(),
					'design' => array( 'color' => null ),
				)
			)
		);
	}

	public function test_paths_overlap() {
		$paths = array(
			array( 'frames', 1, 'content' ),
			array( 'list' ),
		);

		// Equal.
		$this->assertTrue( SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::paths_overlap( array( 'frames', 1, 'content' ), $paths ) );
		// A supplied path is a prefix of the tested path.
		$this->assertTrue( SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::paths_overlap( array( 'list', 0 ), $paths ) );
		// The tested path is a prefix of a supplied path.
		$this->assertTrue( SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::paths_overlap( array( 'frames', 1 ), $paths ) );
		$this->assertTrue( SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::paths_overlap( array( 'frames', '1' ), $paths ) );
		// Disjoint.
		$this->assertFalse( SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::paths_overlap( array( 'frames', 0, 'content' ), $paths ) );
		$this->assertFalse( SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::paths_overlap( array( 'title' ), $paths ) );
		$this->assertFalse( SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::paths_overlap( array( 'title' ), array() ) );
	}

	public function test_get_path() {
		$data = array(
			'frames' => array( 0 => array( 'content' => 'A', 'empty' => null ) ),
		);

		$found = null;
		$this->assertSame( 'A', SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::get_path( $data, array( 'frames', 0, 'content' ), $found ) );
		$this->assertTrue( $found );

		$this->assertNull( SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::get_path( $data, array( 'frames', 0, 'empty' ), $found ) );
		$this->assertTrue( $found );

		$this->assertNull( SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::get_path( $data, array( 'frames', 1, 'content' ), $found ) );
		$this->assertFalse( $found );

		$this->assertNull( SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data::get_path( $data, array( 'frames', 0, 'content', 'x' ), $found ) );
		$this->assertFalse( $found );
	}
}

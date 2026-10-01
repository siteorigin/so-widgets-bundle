<?php

/**
 * Pure helpers for widget data that comes from an untrusted source, such as
 * an ability caller.
 *
 * Nothing in this class calls WordPress. It covers:
 *
 * - Shortcode neutralising: every bracket, literal or encoded as an HTML
 *   entity, becomes a full-width bracket that the shortcode parser never
 *   matches and that survives a round trip through the widget form.
 * - Normalising: objects become arrays, so the stored value is exactly the
 *   value that was checked.
 * - Patch handling: key checks, a per-path merge into the stored instance,
 *   and path helpers used to tell supplied fields from stored ones.
 */
class SiteOrigin_Widgets_Bundle_Untrusted_Widget_Data {
	/**
	 * The deepest nesting accepted in widget data.
	 */
	const MAX_DEPTH = 16;

	/**
	 * Encoded opening or closing bracket, with any number of leading encoded
	 * ampersands. A decimal or hex reference without a closing semicolon is
	 * still decoded by browsers, so it matches when no further digit follows.
	 */
	const ENCODED_BRACKET = '/&(?:amp;|#0*38;|#x0*26;)*(?:#0*(?:91|93)(?:;|(?![0-9]))|#x0*(?:5b|5d)(?:;|(?![0-9a-f]))|(?:lsqb|lbrack|rsqb|rbrack);)/i';

	/**
	 * Make every shortcode in a string inert.
	 *
	 * Encoded brackets are first decoded until the string is stable, then every
	 * literal bracket becomes its full-width form (U+FF3B, U+FF3D).
	 *
	 * @param string $text The text to neutralise.
	 *
	 * @return string The text with no brackets, literal or encoded.
	 */
	public static function neutralize_shortcodes( $text ) {
		if ( ! is_string( $text ) || $text === '' ) {
			return $text;
		}

		do {
			$previous = $text;
			$text = preg_replace_callback(
				self::ENCODED_BRACKET,
				array( __CLASS__, 'decode_bracket' ),
				$text
			);

			if ( ! is_string( $text ) ) {
				// A regex failure must not return the unprocessed string.
				$text = str_replace( '&', '&amp;', $previous );
				break;
			}
		} while ( $text !== $previous );

		return str_replace(
			array( '[', ']' ),
			array( "\u{FF3B}", "\u{FF3D}" ),
			$text
		);
	}

	/**
	 * Map one encoded bracket match to its literal bracket.
	 *
	 * @param array $match The regex match.
	 *
	 * @return string
	 */
	private static function decode_bracket( $match ) {
		$entity = strtolower( $match[0] );

		if (
			strpos( $entity, 'lsqb' ) !== false ||
			strpos( $entity, 'lbrack' ) !== false ||
			preg_match( '/#0*91(?:;|$)|#x0*5b(?:;|$)/', $entity )
		) {
			return '[';
		}

		return ']';
	}

	/**
	 * Convert a value to plain arrays and scalars.
	 *
	 * Objects become arrays: JsonSerializable through jsonSerialize(),
	 * Traversable through iterator_to_array(), anything else through its public
	 * properties. Strings, integers, finite floats, booleans and null pass.
	 * Any other value is removed from its parent array (and is null at the top
	 * level).
	 *
	 * @param mixed $value The value to normalise.
	 * @param int   $depth The current nesting depth.
	 *
	 * @throws InvalidArgumentException When nesting exceeds MAX_DEPTH.
	 *
	 * @return mixed
	 */
	public static function normalize( $value, $depth = 0 ) {
		if ( $depth > self::MAX_DEPTH ) {
			throw new InvalidArgumentException( 'Widget data is nested too deeply.' );
		}

		// A conversion can return another object, so convert until it stops.
		// The cap stops an object that converts to itself.
		$conversions = 0;

		while ( is_object( $value ) ) {
			if ( ++$conversions > self::MAX_DEPTH ) {
				throw new InvalidArgumentException( 'Widget data is nested too deeply.' );
			}

			if ( $value instanceof JsonSerializable ) {
				$value = $value->jsonSerialize();
			} elseif ( $value instanceof Traversable ) {
				$value = iterator_to_array( $value );
			} else {
				$value = get_object_vars( $value );
			}
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				if ( ! self::is_supported( $item ) ) {
					unset( $value[ $key ] );
					continue;
				}

				$value[ $key ] = self::normalize( $item, $depth + 1 );
			}

			return $value;
		}

		return self::is_supported( $value ) ? $value : null;
	}

	/**
	 * Whether a value can be kept by normalize().
	 *
	 * @param mixed $value The value.
	 *
	 * @return bool
	 */
	private static function is_supported( $value ) {
		if ( is_float( $value ) ) {
			return is_finite( $value );
		}

		return is_array( $value ) || is_object( $value ) || is_scalar( $value ) || $value === null;
	}

	/**
	 * Check the keys of a patch.
	 *
	 * Every key must be an integer or contain only letters, digits,
	 * underscores and hyphens, and the patch must not nest deeper than
	 * MAX_DEPTH.
	 *
	 * @param array $patch The normalised patch.
	 * @param int   $depth The current nesting depth.
	 *
	 * @throws InvalidArgumentException When a key or the depth is not allowed.
	 */
	public static function assert_patch_keys( array $patch, $depth = 0 ) {
		if ( $depth > self::MAX_DEPTH ) {
			throw new InvalidArgumentException( 'Widget data is nested too deeply.' );
		}

		foreach ( $patch as $key => $value ) {
			if ( ! is_int( $key ) && ! preg_match( '/^[A-Za-z0-9_-]+$/', (string) $key ) ) {
				throw new InvalidArgumentException( 'Widget data contains an invalid field name.' );
			}

			if ( is_array( $value ) ) {
				self::assert_patch_keys( $value, $depth + 1 );
			}
		}
	}

	/**
	 * Merge a patch into a stored instance, field by field.
	 *
	 * A non-empty array in the patch merges into a stored array at the same
	 * key, so sections merge by field and repeaters merge by item index. Any
	 * other patch value, including an empty array, replaces the stored value.
	 * Keys absent from the patch keep their stored value.
	 *
	 * @param array $stored The stored instance.
	 * @param array $patch  The patch.
	 *
	 * @return array
	 */
	public static function merge( array $stored, array $patch ): array {
		foreach ( $patch as $key => $value ) {
			if (
				is_array( $value ) &&
				! empty( $value ) &&
				isset( $stored[ $key ] ) &&
				is_array( $stored[ $key ] )
			) {
				$stored[ $key ] = self::merge( $stored[ $key ], $value );
			} else {
				$stored[ $key ] = $value;
			}
		}

		return $stored;
	}

	/**
	 * List the path to every value a patch sets.
	 *
	 * A path ends at every non-array value and at every empty array.
	 *
	 * @param array $patch  The patch.
	 * @param array $prefix Keys that lead to $patch.
	 *
	 * @return array[] Paths, each a list of keys.
	 */
	public static function leaf_paths( array $patch, array $prefix = array() ): array {
		$paths = array();

		foreach ( $patch as $key => $value ) {
			$path = $prefix;
			$path[] = $key;

			if ( is_array( $value ) && ! empty( $value ) ) {
				$paths = array_merge( $paths, self::leaf_paths( $value, $path ) );
			} else {
				$paths[] = $path;
			}
		}

		return $paths;
	}

	/**
	 * Whether a path equals, contains or is contained by any of the paths.
	 *
	 * @param array   $path  The path to test.
	 * @param array[] $paths The paths to test against.
	 *
	 * @return bool
	 */
	public static function paths_overlap( array $path, array $paths ) {
		$path = array_values( $path );

		foreach ( $paths as $other ) {
			$other = array_values( (array) $other );
			$length = min( count( $path ), count( $other ) );
			$overlap = true;

			for ( $i = 0; $i < $length; $i++ ) {
				if ( (string) $path[ $i ] !== (string) $other[ $i ] ) {
					$overlap = false;
					break;
				}
			}

			if ( $overlap ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the value at a path.
	 *
	 * @param array $data  The data to read.
	 * @param array $path  The path, a list of keys.
	 * @param bool  $found Set to whether the path exists.
	 *
	 * @return mixed The value, or null when the path does not exist.
	 */
	public static function get_path( array $data, array $path, &$found ) {
		$found = false;
		$current = $data;

		foreach ( $path as $key ) {
			if ( ! is_array( $current ) || ! array_key_exists( $key, $current ) ) {
				return null;
			}

			$current = $current[ $key ];
		}

		$found = true;

		return $current;
	}
}

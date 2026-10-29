<?php

if ( ! class_exists( 'SiteOrigin_LessC' ) ) {
	require __DIR__ . '/lessc.inc.php';
}

/**
 * Exposes the parsed rules, which Less_Parser keeps protected.
 */
class SiteOrigin_Widgets_Less_Value_Parser extends Less_Parser {
	public function get_rules() {
		return $this->rules;
	}
}

/**
 * Checks a setting value before it is written into LESS source.
 *
 * The exact value is parsed by the bundled less.php inside an isolated probe.
 * The value is refused if it holds a line break, if it is not exactly one
 * declaration (or one named mixin argument) with one value, or if its parsed
 * tree holds a comment, inline JavaScript, or a construct that reads a
 * resource or references another variable.
 *
 * A line break would let a value start a line of its own in the widget's LESS,
 * where get_instance_css() moves lines that start with @import.
 *
 * check_stylesheet() parses the whole stylesheet before it is compiled, and
 * refuses a function or import that reads a file, whatever the source.
 *
 * Parser contract. The checks are tested against the bundled less.php 3.0.0,
 * and read against wikimedia/less.php 3.2.1, 4.4.1 and 5.4.0. Another less.php
 * may be loaded first by another plugin; the checks still run on it, refuse a
 * tree they can't inspect, and refuse node classes and function names outside
 * the bundled lists below. They assume such a parser keeps tree children in
 * public properties, reads no file in parse(), and gives the bundled function
 * names their bundled meaning.
 *
 * A LESS function registered by PHP code, such as through the
 * siteorigin_widgets_less_compiler filter, is trusted like that code; the
 * checks cover the parser's own functions and imports.
 */
class SiteOrigin_Widgets_Less_Value_Guard {
	const PROBE_VARIABLE = '@sow-less-value';
	const PROBE_MIXIN = '.sow-less-mixin';
	const PROBE_END_ARGUMENT = 'sow-less-end';

	/**
	 * The public methods of the bundled Less_Functions, lowercased: the
	 * built-in functions a stylesheet may call.
	 */
	const BUNDLED_FUNCTIONS = array(
		'_percent', 'abs', 'acos', 'alpha', 'argb', 'asin', 'atan', 'average',
		'blue', 'ceil', 'clamp', 'color', 'colorblend', 'colorblendaverage', 'colorblendnegation', 'contrast',
		'convert', 'cos', 'darken', 'datauri', 'desaturate', 'difference', 'e', 'encodeuricomponent',
		'escape', 'exclusion', 'extract', 'fade', 'fadein', 'fadeout', 'floor', 'fround',
		'getunit', 'green', 'greyscale', 'hardlight', 'hsl', 'hsla', 'hsla_hue', 'hsv',
		'hsva', 'hsvhue', 'hsvsaturation', 'hsvvalue', 'hue', 'iscolor', 'isem', 'iskeyword',
		'isnumber', 'ispercentage', 'ispixel', 'isstring', 'isunit', 'isurl', 'length', 'lighten',
		'lightness', 'luma', 'luminance', 'max', 'min', 'mix', 'mod', 'multiply',
		'negation', 'number', 'operate', 'overlay', 'percentage', 'pi', 'pow', 'red',
		'replace', 'replace_flags', 'rgb', 'rgba', 'round', 'saturate', 'saturation', 'scaled',
		'screen', 'shade', 'sin', 'softlight', 'spin', 'sqrt', 'svggradient', 'tan',
		'tint', 'unit',
	);

	/**
	 * The node classes the bundled parser declares in lib/Less/Tree/.
	 */
	const BUNDLED_NODES = array(
		'Less_Tree_Alpha', 'Less_Tree_Anonymous', 'Less_Tree_Assignment', 'Less_Tree_Attribute',
		'Less_Tree_Call', 'Less_Tree_Color', 'Less_Tree_Comment', 'Less_Tree_Condition',
		'Less_Tree_DefaultFunc', 'Less_Tree_DetachedRuleset', 'Less_Tree_Dimension', 'Less_Tree_Directive',
		'Less_Tree_Element', 'Less_Tree_Expression', 'Less_Tree_Extend', 'Less_Tree_Import',
		'Less_Tree_Javascript', 'Less_Tree_Keyword', 'Less_Tree_Media', 'Less_Tree_Mixin_Call',
		'Less_Tree_Mixin_Definition', 'Less_Tree_NameValue', 'Less_Tree_Negative', 'Less_Tree_Operation',
		'Less_Tree_Paren', 'Less_Tree_Quoted', 'Less_Tree_Rule', 'Less_Tree_Ruleset',
		'Less_Tree_RulesetCall', 'Less_Tree_Selector', 'Less_Tree_UnicodeDescriptor', 'Less_Tree_Unit',
		'Less_Tree_UnitConversions', 'Less_Tree_Url', 'Less_Tree_Value', 'Less_Tree_Variable',
	);

	/**
	 * Functions that read a file: data-uri in every less.php, and the image
	 * size functions added in less.php 4.
	 */
	const RESOURCE_FUNCTIONS = array( 'datauri', 'imagesize', 'imagewidth', 'imageheight' );

	/**
	 * Check a value written as `@name: <value>;`.
	 *
	 * @param mixed $value The setting value.
	 *
	 * @return true|string True if the value is safe, or the reason it was refused.
	 */
	public static function check_variable( $value ) {
		if ( ! is_scalar( $value ) ) {
			return 'not scalar';
		}

		$rule = self::parse_single_node( self::PROBE_VARIABLE . ': ' . $value . ';' );

		if ( is_string( $rule ) ) {
			return $rule;
		}

		if ( ! $rule instanceof Less_Tree_Rule || ! empty( $rule->important ) ) {
			return 'not one declaration';
		}

		return self::find_denied( $rule->value );
	}

	/**
	 * Check a value written as the named mixin argument `@name:<value>`.
	 *
	 * The probe adds a trailing argument, so a value that adds arguments or
	 * closes the call is refused.
	 *
	 * @param string $name The argument name, without `@`.
	 * @param mixed $value The setting value.
	 *
	 * @return true|string True if the value is safe, or the reason it was refused.
	 */
	public static function check_mixin_argument( $name, $value ) {
		if ( ! is_scalar( $value ) ) {
			return 'not scalar';
		}

		return self::check_mixin_call(
			'( @' . $name . ':' . $value . ', @' . self::PROBE_END_ARGUMENT . ': 1);',
			array( $name, self::PROBE_END_ARGUMENT )
		);
	}

	/**
	 * Check a mixin call holds exactly the expected named arguments.
	 *
	 * @param string $arguments The call after the mixin name, for example `( @name:x, @color:red);`.
	 * @param array $names The expected argument names in order, without `@`.
	 *
	 * @return true|string True if the call is safe, or the reason it was refused.
	 */
	public static function check_mixin_call( $arguments, $names ) {
		$call = self::parse_single_node( self::PROBE_MIXIN . $arguments );

		if ( is_string( $call ) ) {
			return $call;
		}

		if ( ! $call instanceof Less_Tree_Mixin_Call || ! empty( $call->important ) ) {
			return 'not one mixin call';
		}

		if ( count( $call->arguments ) !== count( $names ) ) {
			return 'wrong argument count';
		}

		foreach ( array_values( $call->arguments ) as $i => $argument ) {
			if ( ! isset( $argument['name'] ) || $argument['name'] !== '@' . $names[ $i ] ) {
				return 'unexpected argument';
			}

			$denied = self::find_denied( $argument['value'] );

			if ( $denied !== true ) {
				return $denied;
			}
		}

		return true;
	}

	/**
	 * Check a whole widget stylesheet before it is compiled.
	 *
	 * The root must hold only imports and comments beside the one widget
	 * ruleset. Anywhere in the tree, a function that reads a file, a built-in
	 * function outside the bundled list, an unknown node, or an import other
	 * than a static remote CSS import is refused.
	 *
	 * @param string $less The stylesheet, as it would be compiled.
	 *
	 * @return true|string True if the stylesheet is safe, or the reason it was refused.
	 */
	public static function check_stylesheet( $less ) {
		if ( ! class_exists( 'Less_Tree_Import' ) || ! class_exists( 'Less_Tree_Call' ) ) {
			return 'parse error';
		}

		try {
			$parser = new SiteOrigin_Widgets_Less_Value_Parser( array( 'relativeUrls' => false ) );
			@$parser->parse( $less );
			$rules = $parser->get_rules();
		} catch ( Throwable $e ) {
			return 'parse error';
		}

		if ( ! is_array( $rules ) ) {
			return 'parse error';
		}

		$rulesets = 0;

		foreach ( $rules as $rule ) {
			if ( $rule instanceof Less_Tree_Ruleset ) {
				$rulesets++;
			} elseif ( ! $rule instanceof Less_Tree_Import && ! $rule instanceof Less_Tree_Comment ) {
				return 'unexpected structure';
			}
		}

		if ( $rulesets !== 1 ) {
			return 'unexpected structure';
		}

		return self::find_resource_read( $rules );
	}

	/**
	 * Report a refused value when debugging.
	 *
	 * @param SiteOrigin_Widget $widget The widget the value belongs to.
	 * @param string $name The variable or argument name.
	 * @param string $reason Why the value was refused.
	 */
	public static function refused( $widget, $name, $reason ) {
		if ( defined( 'SITEORIGIN_WIDGETS_DEBUG' ) && SITEORIGIN_WIDGETS_DEBUG ) {
			trigger_error(
				sprintf( 'SiteOrigin Widgets: LESS value "%s" in %s was skipped (%s).', $name, $widget->id_base, $reason ),
				E_USER_NOTICE
			);
		}
	}

	/**
	 * Parse LESS inside a probe ruleset. It must hold exactly one node.
	 *
	 * @param string $less The LESS to parse.
	 *
	 * @return object|string The node, or the reason it was refused.
	 */
	private static function parse_single_node( $less ) {
		if ( strpbrk( $less, "\n\r\x0B\x0C" ) !== false ) {
			return 'line break';
		}

		try {
			$parser = new SiteOrigin_Widgets_Less_Value_Parser( array( 'relativeUrls' => false ) );
			// Silenced like the compile call in get_instance_css(), as less.php
			// raises PHP 8.2 dynamic property deprecations.
			@$parser->parse( '.sow-less-probe{' . $less . '}' );
			$rules = $parser->get_rules();
		} catch ( Throwable $e ) {
			// less.php throws Error, not only Less_Exception_Parser, on some malformed input.
			return 'parse error';
		}

		// Another less.php, loaded first, may expose its tree in another shape.
		if (
			! is_array( $rules ) ||
			( isset( $rules[0] ) && $rules[0] instanceof Less_Tree_Ruleset && ! is_array( $rules[0]->rules ) )
		) {
			return 'parse error';
		}

		if (
			count( $rules ) !== 1 ||
			! $rules[0] instanceof Less_Tree_Ruleset ||
			count( $rules[0]->rules ) !== 1
		) {
			return 'extra statement';
		}

		return $rules[0]->rules[0];
	}

	/**
	 * Search a parsed value for a block, or for a construct that reads a
	 * resource or references another variable.
	 *
	 * @param mixed $node A parsed node, or an array of nodes.
	 * @param int $depth The nesting depth.
	 *
	 * @return true|string True if nothing is found, or the construct found.
	 */
	private static function find_denied( $node, $depth = 0 ) {
		if ( $depth > 64 ) {
			return 'too deep';
		}

		if ( is_array( $node ) ) {
			$children = $node;
		} elseif ( is_object( $node ) ) {
			// A block, such as one holding @import, is not a value.
			if ( $node instanceof Less_Tree_DetachedRuleset ) {
				return 'not one value';
			}

			if ( $node instanceof Less_Tree_Variable ) {
				return 'variable reference';
			}

			if ( $node instanceof Less_Tree_Comment ) {
				return 'comment';
			}

			if ( $node instanceof Less_Tree_Javascript ) {
				return 'javascript';
			}

			// Less_Tree_Quoted::compile() replaces @{name} with the variable.
			if ( $node instanceof Less_Tree_Quoted && strpos( (string) $node->value, '@{' ) !== false ) {
				return 'variable interpolation';
			}

			// Less_Tree_Call lowercases names and maps data-uri to datauri, which reads a file.
			if ( $node instanceof Less_Tree_Call && in_array( strtolower( $node->name ), array( 'datauri', 'data-uri' ), true ) ) {
				return 'resource read';
			}

			$children = get_object_vars( $node );
			unset( $children['currentFileInfo'] );
		} else {
			return true;
		}

		foreach ( $children as $child ) {
			$denied = self::find_denied( $child, $depth + 1 );

			if ( $denied !== true ) {
				return $denied;
			}
		}

		return true;
	}

	/**
	 * Search a parsed stylesheet for an unknown node, a function that reads a
	 * file or isn't a bundled built-in, or an import that isn't allowed.
	 *
	 * @param mixed $node A parsed node, or an array of nodes.
	 * @param int $depth The nesting depth.
	 *
	 * @return true|string True if nothing is found, or the reason.
	 */
	private static function find_resource_read( $node, $depth = 0 ) {
		if ( $depth > 256 ) {
			return 'too deep';
		}

		if ( is_array( $node ) ) {
			$children = $node;
		} elseif ( is_object( $node ) ) {
			if ( ! in_array( get_class( $node ), static::BUNDLED_NODES, true ) ) {
				return 'unknown node';
			}

			if ( $node instanceof Less_Tree_Call ) {
				// Less_Tree_Call lowercases the name, maps % to _percent, and
				// maps hyphenated names such as data-uri to the method name.
				$name = strtolower( (string) $node->name );
				$name = $name === '%' ? '_percent' : str_replace( '-', '', $name );

				if ( in_array( $name, self::RESOURCE_FUNCTIONS, true ) ) {
					return 'resource read';
				}

				if ( method_exists( 'Less_Functions', $name ) && ! in_array( $name, static::BUNDLED_FUNCTIONS, true ) ) {
					return 'unknown function';
				}
			}

			if ( $node instanceof Less_Tree_Import && ! self::is_remote_css_import( $node ) ) {
				return 'import';
			}

			$children = get_object_vars( $node );
			unset( $children['currentFileInfo'] );
		} else {
			return true;
		}

		foreach ( $children as $child ) {
			$found = self::find_resource_read( $child, $depth + 1 );

			if ( $found !== true ) {
				return $found;
			}
		}

		return true;
	}

	/**
	 * Whether an import is a plain CSS import of a static remote URL, which
	 * the compiler emits without resolving a local path or reading a file.
	 *
	 * @param Less_Tree_Import $import The import.
	 *
	 * @return bool
	 */
	private static function is_remote_css_import( $import ) {
		if ( empty( $import->css ) || ! is_array( $import->options ) ) {
			return false;
		}

		foreach ( array( 'inline', 'optional', 'less' ) as $option ) {
			if ( ! empty( $import->options[ $option ] ) ) {
				return false;
			}
		}

		if ( self::find_denied( $import->path ) !== true ) {
			return false;
		}

		// An unquoted url() path is an Anonymous node, so its text is checked too.
		$path = $import->path instanceof Less_Tree_Url ? $import->path->value : $import->path;

		if ( ! $path instanceof Less_Tree_Quoted && ! $path instanceof Less_Tree_Anonymous ) {
			return false;
		}

		$path = $path->value;

		return is_string( $path ) &&
			// The URL test Less_Parser::AbsPath() uses, so the import's path is never resolved locally.
			preg_match( '_^(https?:)?//\w+(\.\w+)+/\w+_i', $path ) &&
			! preg_match( '#@\{|/\*|\*/|`|\\\\|(^|/)\.\.(/|\?|\#|$)#', $path );
	}
}

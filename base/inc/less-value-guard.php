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
 * The value is refused if it is not exactly one declaration (or one named
 * mixin argument) with one value, or if its parsed tree holds a construct
 * that reads a resource or references another variable.
 */
class SiteOrigin_Widgets_Less_Value_Guard {
	const PROBE_VARIABLE = '@sow-less-value';
	const PROBE_MIXIN = '.sow-less-mixin';
	const PROBE_END_ARGUMENT = 'sow-less-end';

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

		if (
			! $rule instanceof Less_Tree_Rule ||
			$rule->name !== self::PROBE_VARIABLE ||
			! empty( $rule->important )
		) {
			return 'not one declaration';
		}

		if ( ! $rule->value instanceof Less_Tree_Value && ! $rule->value instanceof Less_Tree_Anonymous ) {
			return 'not one value';
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
		try {
			$parser = new SiteOrigin_Widgets_Less_Value_Parser( array( 'relativeUrls' => false ) );
			$parser->parse( '.sow-less-probe{' . $less . '}' );
			$rules = $parser->get_rules();
		} catch ( Exception $e ) {
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
}

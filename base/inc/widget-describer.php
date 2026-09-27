<?php

/**
 * Translates a widget's form_options() array into a JSON-schema-style
 * description that an AI consumer can use to build a valid widget instance.
 *
 * This is a pure translator. It receives an already-resolved widget object
 * and never looks widgets up by name, activates them, or includes widget
 * files. Field classes are not instantiated and option-builder callbacks are
 * not run, so state-dependent fields (posts, taxonomy, autocomplete) emit a
 * generic string schema.
 *
 * Every translated field carries `x-sowb-field-type` (the raw SOWB field
 * type) and text-bearing fields (text, textarea, tinymce) carry
 * `x-sowb-text: true`.
 */
class SiteOrigin_Widgets_Widget_Describer {
	/**
	 * Maximum depth for nested widget-in-widget schema recursion.
	 */
	const MAX_WIDGET_DEPTH = 3;

	/**
	 * Widget classes currently being described. Guards against widget
	 * fields that reference each other.
	 *
	 * @var array
	 */
	private $visited_classes = array();

	/**
	 * The top-level widget being described. Nested widget fields build their
	 * form against this widget at every depth, as the editor's widget field
	 * does.
	 *
	 * @var SiteOrigin_Widget|null
	 */
	private $current_widget = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return SiteOrigin_Widgets_Widget_Describer
	 */
	public static function single(): self {
		static $single;

		if ( empty( $single ) ) {
			$single = new self();
		}

		return $single;
	}

	/**
	 * Describe a widget's editable schema.
	 *
	 * @param SiteOrigin_Widget $widget An already-resolved widget instance.
	 *
	 * @return array { type: 'object', properties: object }
	 */
	public function get_schema( SiteOrigin_Widget $widget ): array {
		$previous_widget = $this->current_widget;
		$previous_visited = $this->visited_classes;

		$this->current_widget = $widget;
		$this->visited_classes = array( get_class( $widget ) => true );

		try {
			return $this->translate_form( (array) $widget->form_options(), 0 );
		} finally {
			$this->current_widget = $previous_widget;
			$this->visited_classes = $previous_visited;
		}
	}

	/**
	 * Translate a form_options array into JSON-schema object properties.
	 *
	 * @param array $form_options Raw form_options array (field name => field).
	 * @param int   $depth Current widget-in-widget recursion depth.
	 *
	 * @return array { type: 'object', properties: object }
	 */
	public function translate_form( array $form_options, int $depth = 0 ): array {
		$properties = array();

		foreach ( $form_options as $field_name => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$translated = $this->translate_field( $field, $depth );

			if ( $translated === null ) {
				continue;
			}

			$properties[ $field_name ] = $translated;

			// A media field with a fallback stores the external URL in a
			// sibling `{name}_fallback` key.
			if (
				$translated['x-sowb-field-type'] === 'media' &&
				! empty( $field['fallback'] )
			) {
				$properties[ $field_name . '_fallback' ] = array(
					'type' => 'string',
					'format' => 'uri',
					'x-sowb-field-type' => 'media-fallback',
				);
			}
		}

		return array(
			'type' => 'object',
			// An empty stdClass encodes as {} rather than [].
			'properties' => empty( $properties ) ? new stdClass() : $properties,
		);
	}

	/**
	 * Translate one field definition.
	 *
	 * @param array $field The raw field definition.
	 * @param int   $depth Current widget-in-widget recursion depth.
	 *
	 * @return array|null Null when the field type is omitted from the schema.
	 */
	public function translate_field( array $field, int $depth = 0 ) {
		$type = isset( $field['type'] ) ? (string) $field['type'] : 'text';

		// Display-only fields and foreign builder payloads are not part of
		// the instance schema.
		if ( in_array( $type, array( 'presets', 'builder', 'error', 'html' ), true ) ) {
			return null;
		}

		$sub_fields = ! empty( $field['fields'] ) && is_array( $field['fields'] ) ? $field['fields'] : array();

		switch ( $type ) {
			case 'text':
			case 'textarea':
				$schema = array(
					'type' => 'string',
					'x-sowb-text' => true,
				);
				break;

			case 'tinymce':
				$schema = array(
					'type' => 'string',
					'format' => 'html',
					'x-sowb-text' => true,
				);
				break;

			case 'code':
				$schema = array( 'type' => 'string' );
				break;

			case 'checkbox':
				$schema = array( 'type' => 'boolean' );
				break;

			case 'number':
			case 'slider':
				$schema = array( 'type' => 'number' );

				if ( isset( $field['min'] ) && is_numeric( $field['min'] ) ) {
					$schema['minimum'] = $field['min'] + 0;
				}

				if ( isset( $field['max'] ) && is_numeric( $field['max'] ) ) {
					$schema['maximum'] = $field['max'] + 0;
				}
				break;

			case 'select':
			case 'radio':
			case 'image-radio':
				$schema = array( 'type' => 'string' );

				if ( ! empty( $field['options'] ) && is_array( $field['options'] ) ) {
					$schema['enum'] = array_map( 'strval', array_keys( $field['options'] ) );
				}
				break;

			case 'checkboxes':
				$schema = array(
					'type' => 'array',
					'items' => array( 'type' => 'string' ),
				);

				if ( ! empty( $field['options'] ) && is_array( $field['options'] ) ) {
					$schema['items']['enum'] = array_map( 'strval', array_keys( $field['options'] ) );
				}
				break;

			case 'measurement':
				// Use the field's own `units` list when declared, else the
				// plugin's global measurement list.
				if ( ! empty( $field['units'] ) && is_array( $field['units'] ) ) {
					$units = $field['units'];
				} elseif ( function_exists( 'siteorigin_widgets_get_measurements_list' ) ) {
					$units = siteorigin_widgets_get_measurements_list();
				} else {
					$units = array();
				}

				$schema = array( 'type' => 'string' );

				if ( ! empty( $units ) ) {
					$schema['pattern'] = '^[0-9.]+(' . implode( '|', array_map( 'preg_quote', $units ) ) . ')$';
				}
				break;

			case 'media':
				$schema = array(
					'type' => 'integer',
					'description' => __( 'Attachment ID.', 'so-widgets-bundle' ),
				);
				break;

			case 'multiple-media':
				$schema = array(
					'type' => 'array',
					'items' => array( 'type' => 'integer' ),
				);
				break;

			case 'link':
				$schema = array(
					'type' => 'string',
					'description' => __( 'URL or post: reference.', 'so-widgets-bundle' ),
				);
				break;

			case 'color':
				$schema = array(
					'type' => 'string',
					'format' => 'color',
				);
				break;

			case 'icon':
				$schema = array(
					'type' => 'string',
					'description' => __( 'Icon reference in family-iconname form.', 'so-widgets-bundle' ),
				);
				break;

			case 'font':
				$schema = array( 'type' => 'string' );
				break;

			case 'repeater':
				$schema = array(
					'type' => 'array',
					'items' => $this->translate_form( $sub_fields, $depth ),
				);
				break;

			case 'section':
			case 'tabs':
			case 'toggle':
				// Containers store their sub-fields as an array.
				$schema = $this->translate_form( $sub_fields, $depth );
				break;

			case 'widget':
				$schema = $this->translate_widget_field( $field, $depth );
				break;

			default:
				// image-size, image-shape, order, date-range, autocomplete,
				// posts and any unknown type. State-dependent options are
				// never enumerated.
				$schema = array( 'type' => 'string' );
				break;
		}

		$schema['x-sowb-field-type'] = $type;

		if ( isset( $field['label'] ) && is_string( $field['label'] ) ) {
			$schema['title'] = $field['label'];
		}

		if ( isset( $field['description'] ) && is_string( $field['description'] ) ) {
			$schema['description'] = $field['description'];
		}

		if ( isset( $field['default'] ) && is_scalar( $field['default'] ) ) {
			$schema['default'] = $field['default'];
		}

		return $schema;
	}

	/**
	 * Translate a widget-in-widget field by recursing into the child widget's
	 * form. This mirrors SiteOrigin_Widget_Field_Widget: the child class is
	 * used only if it is already loaded, its form is built against the
	 * top-level widget, and the field's form_filter is applied.
	 *
	 * @param array $field The raw widget field definition (carries 'class').
	 * @param int   $depth Current recursion depth.
	 *
	 * @return array
	 */
	private function translate_widget_field( array $field, int $depth ): array {
		$child_class = isset( $field['class'] ) && is_string( $field['class'] ) ? $field['class'] : '';

		if (
			$depth >= self::MAX_WIDGET_DEPTH ||
			empty( $child_class ) ||
			isset( $this->visited_classes[ $child_class ] )
		) {
			return array(
				'type' => 'object',
				'description' => __( 'Nested widget instance (schema not expanded).', 'so-widgets-bundle' ),
			);
		}

		$child = class_exists( $child_class, false ) ? new $child_class() : null;

		if ( ! $child instanceof SiteOrigin_Widget ) {
			return array(
				'type' => 'object',
				'description' => __( 'Nested widget instance (widget not available).', 'so-widgets-bundle' ),
			);
		}

		$this->visited_classes[ $child_class ] = true;

		try {
			$child_form = $child->form_options( $this->current_widget );

			if ( ! empty( $field['form_filter'] ) && is_callable( $field['form_filter'] ) ) {
				$child_form = call_user_func( $field['form_filter'], $child_form );
			}

			return $this->translate_form( (array) $child_form, $depth + 1 );
		} finally {
			unset( $this->visited_classes[ $child_class ] );
		}
	}
}

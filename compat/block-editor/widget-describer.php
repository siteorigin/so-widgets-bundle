<?php

/**
 * SiteOrigin Widgets Bundle — widget schema describer.
 *
 * Translates a widget's form_options() array into a JSON-schema-style
 * description an AI consumer can use to construct a valid widget_data payload
 * for sowb/widget-update. Pure raw-array mapping: widget forms are plain
 * nested arrays keyed by field name with 'type' keys, so NO field classes are
 * instantiated and NO option-builder callbacks or content queries ever run —
 * state-dependent fields (posts, taxonomy, autocomplete) emit a generic
 * string schema.
 *
 * Every translated field carries `x-sowb-field-type` (the raw SOWB field
 * type, lossless) and text-bearing fields (text, textarea, tinymce) carry
 * `x-sowb-text: true` — the v1 AI-write targets.
 *
 * Strictly per-widget on demand; never iterates the widget catalog.
 */
class SiteOrigin_Widgets_Bundle_Widget_Describer {
	/**
	 * Maximum depth for nested widget-in-widget schema recursion.
	 */
	const MAX_WIDGET_DEPTH = 3;

	/**
	 * @var SiteOrigin_Widgets_Bundle_Widget_Describer
	 */
	private static $single;

	/**
	 * Widget classes currently being described (cycle guard for widget-type
	 * fields referencing each other).
	 *
	 * @var array
	 */
	private $visited_classes = array();

	/**
	 * Get the singleton instance.
	 *
	 * @return SiteOrigin_Widgets_Bundle_Widget_Describer
	 */
	public static function single() {
		if ( empty( self::$single ) ) {
			self::$single = new self();
		}

		return self::$single;
	}

	/**
	 * Describe a widget's editable schema.
	 *
	 * @param string $widget_or_block A widget PHP class name, or a `sowb/...`
	 *                                block name.
	 *
	 * @return array|WP_Error { widget_class, block_name, name, description,
	 *                          schema } or WP_Error 'sowb_widget_not_found'.
	 */
	public function describe( $widget_or_block ) {
		$widget_class = $this->resolve_class( $widget_or_block );

		if ( empty( $widget_class ) ) {
			return $this->not_found_error( $widget_or_block );
		}

		$widget = $this->resolve_widget( $widget_class );

		if ( empty( $widget ) ) {
			return $this->not_found_error( $widget_class );
		}

		$this->visited_classes = array( get_class( $widget ) => true );
		$schema = $this->translate_form( (array) $widget->form_options(), 0 );
		$this->visited_classes = array();

		return array(
			'widget_class' => get_class( $widget ),
			'block_name' => 'sowb/' . strtolower( str_replace( array( '_', '\\' ), '-', get_class( $widget ) ) ),
			'name' => isset( $widget->name ) ? $widget->name : '',
			'description' => ! empty( $widget->widget_options['description'] ) ? $widget->widget_options['description'] : '',
			'schema' => $schema,
		);
	}

	/**
	 * Resolve the input to a widget class name.
	 *
	 * Block names always start `sowb/`; anything else is treated as a class
	 * name directly.
	 *
	 * @param string $widget_or_block Class or block name.
	 *
	 * @return string|null
	 */
	private function resolve_class( $widget_or_block ) {
		if ( ! is_string( $widget_or_block ) || $widget_or_block === '' ) {
			return null;
		}

		if ( strpos( $widget_or_block, 'sowb/' ) === 0 ) {
			if ( ! class_exists( 'SiteOrigin_Widgets_Bundle_Widget_Block' ) ) {
				return null;
			}

			return SiteOrigin_Widgets_Bundle_Widget_Block::single()->resolve_widget_class( array(), $widget_or_block );
		}

		return $widget_or_block;
	}

	/**
	 * Resolve a widget instance, attempting activation for inactive widgets —
	 * the same chain get_widget_preview() uses.
	 *
	 * @param string $widget_class The widget class name.
	 *
	 * @return SiteOrigin_Widget|null
	 */
	private function resolve_widget( $widget_class ) {
		$widget = SiteOrigin_Widgets_Widget_Manager::get_widget_instance( $widget_class );

		if ( empty( $widget ) ) {
			$widget = SiteOrigin_Widgets_Bundle::single()->load_missing_widget( false, $widget_class );
		}

		if (
			empty( $widget ) ||
			! is_object( $widget ) ||
			! is_subclass_of( $widget, 'SiteOrigin_Widget' )
		) {
			return null;
		}

		return $widget;
	}

	/**
	 * @param string $widget_or_block The unresolvable input.
	 *
	 * @return WP_Error
	 */
	private function not_found_error( $widget_or_block ) {
		return new WP_Error(
			'sowb_widget_not_found',
			sprintf(
				__( 'Widget %s could not be found. Please make sure the widget has been activated in SiteOrigin Widgets.', 'so-widgets-bundle' ),
				(string) $widget_or_block
			),
			array( 'status' => 404 )
		);
	}

	/**
	 * Translate a form_options array into JSON-schema object properties.
	 *
	 * @param array $form_options Raw form_options array (field name => field).
	 * @param int   $depth Current widget-in-widget recursion depth.
	 *
	 * @return array { type: 'object', properties: { ... } }
	 */
	public function translate_form( $form_options, $depth = 0 ) {
		$properties = array();

		foreach ( $form_options as $field_name => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$translated = $this->translate_field( $field, $depth );

			if ( $translated !== null ) {
				$properties[ $field_name ] = $translated;
			}
		}

		return array(
			'type' => 'object',
			'properties' => $properties,
		);
	}

	/**
	 * Translate one field definition. Returns null for omitted field types
	 * (display-only or embedding foreign builder data).
	 *
	 * @param array $field The raw field definition.
	 * @param int   $depth Current widget-in-widget recursion depth.
	 *
	 * @return array|null
	 */
	public function translate_field( $field, $depth = 0 ) {
		$type = isset( $field['type'] ) ? (string) $field['type'] : 'text';

		// Display-only / UI sugar / foreign-builder payloads: not part of an
		// AI-writable instance schema.
		if ( in_array( $type, array( 'presets', 'builder', 'error', 'html' ), true ) ) {
			return null;
		}

		switch ( $type ) {
			case 'text':
			case 'textarea':
				$schema = array( 'type' => 'string', 'x-sowb-text' => true );
				break;

			case 'tinymce':
				$schema = array( 'type' => 'string', 'format' => 'html', 'x-sowb-text' => true );
				break;

			case 'code':
				$schema = array( 'type' => 'string' );
				break;

			case 'checkbox':
			case 'toggle':
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
				// The unit list comes from the field's own literal `units`
				// array when declared, else the plugin's global measurement
				// list. Never hard-coded, never parsed from class source.
				$units = ! empty( $field['units'] ) && is_array( $field['units'] ) ?
					$field['units'] :
					( function_exists( 'siteorigin_widgets_get_measurements_list' ) ? siteorigin_widgets_get_measurements_list() : array() );

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
				$schema = array( 'type' => 'string', 'format' => 'color' );
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
					'items' => $this->translate_form(
						! empty( $field['fields'] ) && is_array( $field['fields'] ) ? $field['fields'] : array(),
						$depth
					),
				);
				break;

			case 'section':
			case 'tabs':
				$schema = $this->translate_form(
					! empty( $field['fields'] ) && is_array( $field['fields'] ) ? $field['fields'] : array(),
					$depth
				);
				break;

			case 'widget':
				$schema = $this->translate_widget_field( $field, $depth );
				break;

			default:
				// image-size, image-shape, order, date-range, autocomplete,
				// posts and any unknown/exotic type: generic string schema.
				// State-dependent options are never enumerated.
				$schema = array( 'type' => 'string' );
				break;
		}

		// Lossless: always carry the raw SOWB field type.
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
	 * own form_options(), depth-capped and cycle-guarded.
	 *
	 * @param array $field The raw widget field definition (carries 'class').
	 * @param int   $depth Current recursion depth.
	 *
	 * @return array
	 */
	private function translate_widget_field( $field, $depth ) {
		$child_class = isset( $field['class'] ) ? (string) $field['class'] : '';

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

		$child = $this->resolve_widget( $child_class );

		if ( empty( $child ) ) {
			return array(
				'type' => 'object',
				'description' => __( 'Nested widget instance (widget not available).', 'so-widgets-bundle' ),
			);
		}

		$this->visited_classes[ $child_class ] = true;
		$schema = $this->translate_form( (array) $child->form_options(), $depth + 1 );
		unset( $this->visited_classes[ $child_class ] );

		return $schema;
	}
}

<?php

/**
 * The common base class for fields which may contain and render other fields.
 *
 * Class SiteOrigin_Widget_Field_Container_Base
 */
abstract class SiteOrigin_Widget_Field_Container_Base extends SiteOrigin_Widget_Field_Base {
	/**
	 * The child field options.
	 *
	 * @var array
	 */
	protected $fields;
	/**
	 * The child field instances.
	 *
	 * @var array
	 */
	protected $sub_fields;
	/**
	 * Whether or not this container's fields should initially be hidden.
	 *
	 * @var bool
	 */
	protected $hide;
	/**
	 * The current visibility state of this container.
	 *
	 * @var string
	 */
	protected $state;
	/**
	 * Whether or not this container's fields are rendered within a collapsible container.
	 *
	 * @var bool
	 */
	protected $collapsible = true;

	protected function render_before_field( $value, $instance ) {
		if ( ! empty( $value[ 'so_field_container_state' ] ) ) {
			$this->state = $value[ 'so_field_container_state' ];
		} else {
			$this->state = $this->hide ? 'closed' : 'open';
		}

		parent::render_before_field( $value, $instance );
	}

	protected function get_label_classes( $value, $instance ) {
		$label_classes = parent::get_label_classes( $value, $instance );

		if ( $this->state == 'open' ) {
			$label_classes[] = 'siteorigin-widget-section-visible';
		}

		return $label_classes;
	}

	protected function render_field_label( $value, $instance ) {
		if ( $this->collapsible ) {
			parent::render_field_label( $value, $instance );
		}
	}

	protected function create_and_render_sub_fields( $values, $parent_container = null, $is_template = false ) {
		$this->sub_fields = array();

		if ( isset( $parent_container ) ) {
			if ( ! in_array( $parent_container, $this->parent_container, true ) ) {
				$this->parent_container[] = $parent_container;
			}
		}
		/* @var $field_factory SiteOrigin_Widget_Field_Factory */
		$field_factory = SiteOrigin_Widget_Field_Factory::single();

		foreach ( $this->fields as $sub_field_name => $sub_field_options ) {
			/* @var $field SiteOrigin_Widget_Field_Base */
			$field = $field_factory->create_field(
				$sub_field_name,
				$sub_field_options,
				$this->for_widget,
				$this->parent_container,
				$is_template
			);
			$sub_value = ( ! empty( $values ) && isset( $values[$sub_field_name] ) ) ? $values[$sub_field_name] : null;
			$field->render( $sub_value, $values );
			$field_js_vars = $field->get_javascript_variables();

			if ( ! empty( $field_js_vars ) ) {
				$this->javascript_variables[$sub_field_name] = $field_js_vars;
			}
			$field->enqueue_scripts();
			$this->sub_fields[$sub_field_name] = $field;
		}
	}

	protected function sanitize_field_input( $value, $instance ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		// A container can legitimately reach sanitization with no declared
		// sub-fields — e.g. a 'widget' field whose sub-widget class isn't
		// available this request (that widget deactivated in SOWB), or a
		// section declared without a 'fields' key. There is nothing to
		// sanitize against and nothing to allowlist, so preserve the value
		// as-is — matching this method's pre-strip degenerate behavior —
		// rather than wiping a full sub-widget instance whose class is
		// temporarily unavailable. Also avoids a PHP 8 TypeError from
		// array_keys( null ) below.
		if ( empty( $this->fields ) || ! is_array( $this->fields ) ) {
			return $value;
		}

		/* @var $field_factory SiteOrigin_Widget_Field_Factory */
		$field_factory = SiteOrigin_Widget_Field_Factory::single();

		$known_keys = array_keys( $this->fields );

		foreach ( $this->fields as $sub_field_name => $sub_field_options ) {
			/* @var $sub_field SiteOrigin_Widget_Field_Base */
			if ( ! empty( $this->sub_fields ) && ! empty( $this->sub_fields[$sub_field_name] ) ) {
				$sub_field = $this->sub_fields[$sub_field_name];
			} else {
				$sub_field = $field_factory->create_field(
					$this->base_name . '][' . $sub_field_name,
					$sub_field_options,
					$this->for_widget,
					$this->parent_container
				);
			}
			$value[$sub_field_name] = $sub_field->sanitize( isset( $value[$sub_field_name] ) ? $value[$sub_field_name] : null, $value );
			$value = $sub_field->sanitize_instance( $value );

			// Collect companion keys from the sub-field instance actually
			// used for this row. This must happen inside the loop:
			// $this->sub_fields is only populated by the render path
			// (create_and_render_sub_fields()), so on a save-only request
			// the instances created above never enter that cache and a
			// post-loop scan of $this->sub_fields would miss them.
			$known_keys = array_merge( $known_keys, $sub_field->get_related_instance_keys() );
		}

		// See SiteOrigin_Widget::update_fields() for the top-level
		// equivalent and full reasoning. Strip any sibling key in this
		// row/section that isn't a declared sub-field, a sub-field-declared
		// companion key, or the container's own state-tracking key.
		$known_keys[] = 'so_field_container_state';

		$value = array_intersect_key( $value, array_flip( $known_keys ) );

		return $value;
	}
}

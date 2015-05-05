<?php

/**
 * Class SiteOrigin_Widget_Field_Section
 */
class SiteOrigin_Widget_Field_Section extends SiteOrigin_Widget_Field {
	/**
	 * The set of fields to be grouped together. This should contain any combination of other field types, even
	 * repeaters and sections.
	 *
	 * @access protected
	 * @var array
	 */
	protected $fields;
	/**
	 * The set of field classes to be rendered together as one item.
	 *
	 * @var array
	 */
	private $sub_fields;
	/**
	 * Reference to the containing widget required for creating subfields.
	 *
	 * @access private
	 * @var SiteOrigin_Widget
	 */
	private $for_widget;
	/**
	 * An array of field names of parent repeaters.
	 *
	 * @var array
	 */
	private $parent_repeater;

	public function __construct( $base_name, $element_id, $element_name, $options, SiteOrigin_Widget $for_widget, $parent_repeater = array() ) {
		parent::__construct( $base_name, $element_id, $element_name, $options );

		if( isset( $options['fields'] ) ) $this->fields = $options['fields'];

		$this->for_widget = $for_widget;
		$this->parent_repeater = $parent_repeater;
	}

	protected function render_field( $value, $instance ) {
		$this->sub_fields = array();
		?><div class="siteorigin-widget-section <?php if( !empty( $this->hide ) ) echo 'siteorigin-widget-section-hide'; ?>"><?php
		if ( !isset( $this->fields ) || empty($this->fields ) ) return;
		foreach( $this->fields as $sub_field_name => $sub_field_options ) {
			/* @var $field SiteOrigin_Widget_Field */
			$field = SiteOrigin_Widget_Field_Factory::create_field(
				$this->base_name . '][' . $sub_field_name,
				$sub_field_options,
				$this->for_widget,
				$this->parent_repeater
			);
			$field->render( isset( $value[$sub_field_name] ) ? $value[$sub_field_name] : null );
			$this->sub_fields[$sub_field_name] = $field;
		}
		?></div><?php
	}

	protected function sanitize_field_input( $value ) {

		foreach( $this->fields as $sub_field_name => $sub_field_options ) {
			if( empty( $value[$sub_field_name] ) ) continue;
			/* @var $sub_field SiteOrigin_Widget_Field */
			if( !empty( $this->sub_fields ) && ! empty( $this->fields[$sub_field_name] ) ) {
				$sub_field = $this->sub_fields[$sub_field_name];
			}
			else {
				$sub_field = SiteOrigin_Widget_Field_Factory::create_field(
					$this->base_name . '][' . $sub_field_name,
					$sub_field_options,
					$this->for_widget,
					$this->parent_repeater
				);
			}
			$value[$sub_field_name] = $sub_field->sanitize( $value[$sub_field_name], $value );
		}

		return $value;
	}

}
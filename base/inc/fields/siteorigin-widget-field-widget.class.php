<?php

/**
 * Class SiteOrigin_Widget_Field_Widget
 */
class SiteOrigin_Widget_Field_Widget extends SiteOrigin_Widget_Field {
	/**
	 * The class name of the widget to be included.
	 *
	 * @access protected
	 * @var string
	 */
	protected $class_name;
	/**
	 * The set of field classes to be rendered together as one item.
	 *
	 * @var array
	 */
	private $sub_fields;
	/**
	 * A reference to the containing widget required for creating subfields.
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
	/**
	 * A reference to the created sub widget required for sanitizing it's fields.
	 *
	 * @access private
	 * @var SiteOrigin_Widget
	 */
	private $sub_widget;

	public function __construct( $base_name, $element_id, $element_name, $field_options, SiteOrigin_Widget $for_widget, $parent_repeater = array() ) {
		parent::__construct( $base_name, $element_id, $element_name, $field_options );

		if( isset( $field_options['class'] ) ) $this->class_name = $field_options['class'];

		$this->for_widget = $for_widget;
		$this->parent_repeater = $parent_repeater;
	}


	protected function render_field( $value, $instance ) {
		$this->sub_fields = array();
		// Create the extra form entries
		?><div class="siteorigin-widget-section <?php if( !empty($this->hide ) ) echo 'siteorigin-widget-section-hide'; ?>"><?php

		if( !class_exists( $this->class_name ) ) {
			printf( __( '%s does not exist', 'siteorigin-widgets' ), $this->class_name );
			echo '</div>';
			return;
		}

		$this->sub_widget = new $this->class_name;
		if( !is_a( $this->sub_widget, 'SiteOrigin_Widget' ) ) {
			printf( __( '%s is not a SiteOrigin Widget', 'siteorigin-widgets' ), $this->class_name );
			echo '</div>';
			return;
		}

		foreach( $this->sub_widget->form_options( $this->for_widget ) as $sub_field_name => $sub_field_options) {
			/* @var $field SiteOrigin_Widget_Field */
			$field = SiteOrigin_Widget_Field_Factory::create_field(
				$this->base_name . '][' . $sub_field_name,
				$sub_field_options,
				$this->for_widget,
				$this->parent_repeater
			);
			$field->render( isset( $value[$sub_field_name] ) ? $value[$sub_field_name] : null, $value );
			$this->sub_fields[$sub_field_name] = $field;
		}
		?></div><?php
	}

	protected function sanitize_field_input( $value ) {
		if( !empty( $this->sub_widget ) && is_a( $this->sub_widget, 'SiteOrigin_Widget' ) ) {
			foreach( $this->sub_widget->form_options( $this->for_widget ) as $sub_field_name => $sub_field_options ) {
				if( empty( $value[$sub_field_name] ) ) continue;
				/* @var $field SiteOrigin_Widget_Field */
				$field = $this->sub_fields[$sub_field_name];
				$value[$sub_field_name] = $field->sanitize( $value[$sub_field_name], $value );
			}
		}
		else {
			$value = array();
		}
		return $value;
	}

}
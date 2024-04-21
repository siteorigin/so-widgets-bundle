<?php

/**
 * Class SiteOrigin_Widget_Field_Widget
 */
class SiteOrigin_Widget_Field_Widget extends SiteOrigin_Widget_Field_Container_Base {
	/**
	 * The class name of the widget to be included.
	 *
	 * @var string
	 */
	protected $class;
	/**
	 * A filter for the widget's form fields. In some cases we may want to filter some fields out of a sub-widget form.
	 *
	 * @var callable
	 */
	protected $form_filter;

	private $sub_widget;

	public function __construct( $base_name, $element_id, $element_name, $field_options, SiteOrigin_Widget $for_widget, $parent_container = array() ) {
		parent::__construct( $base_name, $element_id, $element_name, $field_options, $for_widget, $parent_container );

		if ( ! isset( $this->class ) || ! class_exists( $this->class ) ) {
			return;
		}

		/* @var $sub_widget SiteOrigin_Widget */
		$sub_widget = new $this->class();
		if ( ! is_a( $sub_widget, 'SiteOrigin_Widget' ) ) {
			return;
		}

		if ( ! empty( $this->form_filter ) && is_callable( $this->form_filter ) ) {
			$this->fields = call_user_func( $this->form_filter, $sub_widget->form_options( $this->for_widget ) );
		} else {
			$this->fields = $sub_widget->form_options( $this->for_widget );
		}

		$this->sub_widget = $sub_widget;
	}

	protected function render_field( $value, $instance ) {
		if ( empty( $value ) ) {
			$value = array();
		}

		echo '<div class="siteorigin-widget-widget">';

		if ( $this->collapsible ) {
			?><div class="siteorigin-widget-section <?php if ( $this->state == 'closed' ) {
				echo 'siteorigin-widget-section-hide';
			} ?>"><?php
		}

		if ( ! class_exists( $this->class ) ) {
			printf( __( '%s does not exist', 'so-widgets-bundle' ), $this->class );

			if ( $this->collapsible ) {
				echo '</div>';
			}

			return;
		}

		if ( ! is_a( $this->sub_widget, 'SiteOrigin_Widget' ) ) {
			printf( __( '%s is not a SiteOrigin Widget', 'so-widgets-bundle' ), $this->class );

			if ( $this->collapsible ) {
				echo '</div>';
			}

			return;
		}

		// Allow migrations.
		$value = $this->sub_widget->modify_instance( $value );

 		// Add any missing default values to the instance.
		$value = $this->sub_widget->add_defaults( $this->fields, $value );

		$this->create_and_render_sub_fields( $value, array( 'name' => $this->base_name, 'type' => 'widget' ) );

		if ( $this->collapsible ) {
			?></div><?php
		}
		echo '</div>';
	}
}

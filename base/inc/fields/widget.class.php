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

	public function __construct( $base_name, $element_id, $element_name, $field_options, SiteOrigin_Widget $for_widget, $parent_container = array() ) {
		parent::__construct( $base_name, $element_id, $element_name, $field_options, $for_widget, $parent_container );

		if ( isset( $this->class ) ) {
			if ( class_exists( $this->class ) ) {
				/* @var $sub_widget SiteOrigin_Widget */
				$sub_widget = new $this->class();

				if ( is_a( $sub_widget, 'SiteOrigin_Widget' ) ) {
					if ( ! empty( $this->form_filter ) && is_callable( $this->form_filter ) ) {
						$this->fields = call_user_func( $this->form_filter, $sub_widget->form_options( $this->for_widget ) );
					} else {
						$this->fields = $sub_widget->form_options( $this->for_widget );
					}
				}
			}
		}
	}

	protected function render_field( $value, $instance ) {
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

		/* @var $sub_widget SiteOrigin_Widget */
		$sub_widget = new $this->class();

		if ( ! is_a( $sub_widget, 'SiteOrigin_Widget' ) ) {
			printf( __( '%s is not a SiteOrigin Widget', 'so-widgets-bundle' ), $this->class );

			if ( $this->collapsible ) {
				echo '</div>';
			}

			return;
		}
		$this->create_and_render_sub_fields( $value, array( 'name' => $this->base_name, 'type' => 'widget' ) );

		if ( $this->collapsible ) {
			?></div><?php
		}
		echo '</div>';
	}
}

<?php

/**
 * The base class for all SiteOrigin_Widget form fields.
 *
 * Class SiteOrigin_Widget_Field
 */

abstract class SiteOrigin_Widget_Field_Base {

	/* ============================================================================================================== */
	/* CORE FIELD PROPERTIES                                                                                          */
	/* Properties which are essential to successful rendering of fields and saving of data input into fields.         */
	/* ============================================================================================================== */


	/**
	 * The base name for this field. It is used in the generation of HTML element id and name attributes.
	 *
	 * @access protected
	 * @var string
	 */
	protected $base_name;
	/**
	 * The rendered HTML element id attribute.
	 *
	 * @access protected
	 * @var string
	 */
	protected $element_id;
	/**
	 * The rendered HTML element name attribute
	 *
	 * @access protected
	 * @var string
	 */
	protected $element_name;
	/**
	 * The field configuration options.
	 *
	 * @access protected
	 * @var array
	 */
	protected $field_options;
	/**
	 * Variables may be added to this array which will be propagated to the front end for use in dynamic rendering.
	 *
	 * @access protected
	 * @var array
	 */
	protected $javascript_variables;


	/* ============================================================================================================== */
	/* BASE FIELD CONFIGURATION PROPERTIES                                                                            */
	/* Common configuration properties used by all fields.                                                            */
	/* ============================================================================================================== */


	/**
	 * The type of the field.
	 *
	 * @access protected
	 * @var string
	 */
	protected $type;
	/**
	 * Render a label for the field with the given value.
	 *
	 * @access protected
	 * @var string
	 */
	protected $label;
	/**
	 * The field will be prepopulated with this default value.
	 *
	 * @access protected
	 * @var mixed
	 */
	protected $default;
	/**
	 * Render small italic text below the field to describe the field's purpose.
	 *
	 * @access protected
	 * @var string
	 */
	protected $description;
	/**
	 * Append '(Optional)' to this field's label as a small green superscript.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $optional;
	/**
	 * @var bool Is this field required.
	 */
	protected $required;
	/**
	 * Specifies an additional sanitization to be performed. Available sanitizations are 'email' and 'url'. If the
	 * specified sanitization isn't recognized it is assumed to be a custom sanitization and a filter is applied using
	 * the pattern `'siteorigin_widgets_sanitize_field_' . $sanitize`, in case the sanitization is defined elsewhere.
	 *
	 * @access protected
	 * @var string
	 */
	protected $sanitize;
	/**
	 * Reference to the parent widget required for creating child fields.
	 *
	 * @access private
	 * @var SiteOrigin_Widget
	 */
	protected $for_widget;
	/**
	 * An array of field names of parent containers.
	 *
	 * @var array
	 */
	protected $parent_container;
	/**
	 * Whether or not this field contains other fields.
	 *
	 * @access protected
	 * @var boolean
	 */
	protected $is_container;
	/**
	 * Additional CSS classes to output in this field's HTML class attribute. It is left up to the field's render_field
	 * function to output these classes.
	 *
	 * @access protected
	 * @var array
	 */
	protected $input_css_classes;


	/* ============================================================================================================== */
	/* FIELD STATES PROPERTIES                                                                                        */
	/* Configuration of field state emitters and handlers.                                                            */
	/* See https://siteorigin.com/docs/widgets-bundle/form-building/state-emitters/ for more detail on the topic of   */
	/* state emitters and handlers.                                                                                   */
	/* ============================================================================================================== */

	/**
	 *
	 * Specifies the callback type and arguments to use when deciding on the state to be emitted.
	 *
	 * @access protected
	 * @var array
	 */
	protected $state_emitter;
	/**
	 *
	 * Specifies the different possible states to be handled by this field and the resulting effect of the each state.
	 *
	 * @access protected
	 * @var array
	 */
	protected $state_handler;
	/**
	 * @var
	 */
	protected $state_handler_initial;

	/**
	 * @param $base_name string The name of the field.
	 * @param $element_id string The id to be used as the id attribute of the wrapping HTML element.
	 * @param $element_name string The name to be used as the name attribute of the wrapping HTML element.
	 * @param $field_options array Configuration for the field.
	 *
	 * @param SiteOrigin_Widget $for_widget
	 * @param array $parent_container
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $base_name, $element_id, $element_name, $field_options, $for_widget = null, $parent_container = array() ) {
		if( isset( $field_options['type'] ) ) {
			$this->type = $field_options['type'];
		}
		else {
			throw new InvalidArgumentException( 'SiteOrigin_Widget_Field_Base::__construct: $field_options must contain a \'type\' field.' );
		}

		$this->base_name = $base_name;
		$this->element_id = $element_id;
		$this->element_name = $element_name;
		$this->field_options = $field_options;
		$this->javascript_variables = array();

		$this->for_widget = $for_widget;
		$this->parent_container = $parent_container;

		$this->init();
	}

	private function init() {
		$this->init_options();
		$this->initialize();
	}

	/**
	 * Initialization function which may be overridden if required.
	 */
	protected function initialize() {
	}

	/**
	 * This method ensures that configuration options are set on the corresponding field class instance properties. If
	 * a field has defined default options, those are set first and then can be overwritten by options which were
	 * passed in.
	 */
	private function init_options() {
		// First set properties from default options if any have been set.
		$default_field_options = $this->get_default_options();
		if( ! empty( $default_field_options ) ) {
			foreach ( $default_field_options as $key => $value ) {
				if ( property_exists( $this, $key ) ) {
					if ( isset( $default_field_options[$key] ) ) {
						$this->$key = $value;
					}
				}
			}
		}

		$field_options = $this->field_options;
		foreach ( $field_options as $key => $value ) {
			if( property_exists( $this, $key ) ) {
				if ( isset( $field_options[$key] ) ) {
					$this->$key = $value;
				}
			}
		}
	}


	protected function get_default_options() {
		//Stub: This function may be overridden by subclasses to have default field options.
		return null;
	}

	/**
	 * The CSS classes to be applied to the default label.
	 * This function should be overridden by subclasses when they want to add custom CSS classes to the HTML input label.
	 *
	 * @return array The array of label CSS classes.
	 */
	protected function get_label_classes( $value, $instance ) {
		return array( 'siteorigin-widget-field-label' );
	}

	/**
	 * The CSS classes to be applied to the default description.
	 * This function should be overridden by subclasses when they want to add custom CSS classes to the description text.
	 *
	 * @return array The modified array of description text CSS classes.
	 */
	protected function get_description_classes() {
		return array( 'siteorigin-widget-description' );
	}

	/**
	 * This function is called by the containing SiteOrigin_Widget when rendering it's form.
	 *
	 * @param $value mixed The current instance value of the field.
	 * @param $instance array Optionally pass in the widget instance, if rendering of additional values is required.
	 */
	public function render( $value, $instance = array() ) {
		if ( is_null( $value ) && isset( $this->default ) ) {
			$value = $this->default;
		}
		$wrapper_attributes = array(
			'class' => array(
				'siteorigin-widget-field',
				'siteorigin-widget-field-type-' . $this->type,
				'siteorigin-widget-field-' . $this->base_name
			)
		);

		if( !empty( $this->optional ) ) $wrapper_attributes['class'][] = 'siteorigin-widget-field-is-optional';
		if( !empty( $this->required ) ) $wrapper_attributes['class'][] = 'siteorigin-widget-field-is-required';
		$wrapper_attributes['class'] = implode(' ', array_map('sanitize_html_class', $wrapper_attributes['class']) );

		if( !empty( $this->state_emitter ) ) {
			// State emitters create new states for the form
			$wrapper_attributes['data-state-emitter'] = json_encode( $this->state_emitter );
		}

		if( !empty( $this->state_handler ) ) {
			// State handlers decide what to do with form states
			$wrapper_attributes['data-state-handler'] = json_encode( $this->state_handler );
		}

		if( !empty( $this->state_handler_initial ) ) {
			// Initial state handlers are only run when the form is first loaded
			$wrapper_attributes['data-state-handler-initial'] = json_encode( $this->state_handler_initial );
		}

		?><div <?php foreach( $wrapper_attributes as $attr => $attr_val ) echo $attr.'="' . esc_attr( $attr_val ) . '" ' ?>><?php

		// Allow subclasses and to render something before and after the render_field() function is called.
		$this->render_before_field( $value, $instance );
		$this->render_field( $value, array() );
		$this->render_after_field( $value, $instance);

		?></div><?php
	}

	/**
	 * This function is called before the main render function.
	 *
	 * @param $value mixed The current value of this field.
	 * @param $instance array The current widget instance.
	 */
	protected function render_before_field( $value, $instance ) {
		$this->render_field_label( $value, $instance );
	}

	/**
	 * Default label rendering implementation. Subclasses should override if necessary to render labels differently.
	 */
	protected function render_field_label( $value, $instance ) {
		?>
		<label for="<?php echo esc_attr( $this->element_id ) ?>" <?php $this->render_CSS_classes( $this->get_label_classes( $value, $instance ) ) ?>>
			<?php
			echo esc_html( $this->label );
			if( !empty( $this->optional ) ) {
				echo '<span class="field-optional">(' . __('Optional', 'so-widgets-bundle') . ')</span>';
			}
			if( !empty( $this->required ) ) {
				echo '<span class="field-required">(' . __('Required', 'so-widgets-bundle') . ')</span>';
			}
			?>
		</label>
		<?php
	}

	/**
	 * Helper function to render the HTML class attribute with the array of classes.
	 */
	protected function render_CSS_classes( $CSS_classes ) {
		if( ! empty( $CSS_classes ) ) {
			?>class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $CSS_classes ) ) ) ?>"<?php
		}
	}

	/**
	 *
	 * The main field rendering function. This function should be overridden by all subclasses and used to render their
	 * specific form field HTML for display.
	 *
	 * @param $value mixed The current value of this field.
	 * @param $instance array The current widget instance.
	 * @return mixed Should output the desired HTML.
	 */
	abstract protected function render_field( $value, $instance );

	/**
	 * The default sanitization function.
	 *
	 * @param $value mixed The value to be sanitized.
	 * @param $instance array The widget instance.
	 * @param $old_value mixed The old value of this field.
	 *
	 * @return mixed|string
	 */
	public function sanitize( $value, $instance = array(), $old_value = null ) {

		$value = $this->sanitize_field_input( $value, $instance );

		if( isset( $this->sanitize ) ) {
			// This field also needs some custom sanitization
			switch( $this->sanitize ) {
				case 'url':
					$value = sow_esc_url_raw( $value );
					break;

				case 'email':
					$value = sanitize_email( $value );
					break;

				default:
					// This isn't a built in sanitization. Maybe it's handled elsewhere.
					if( is_callable( $this->sanitize ) ) {
						$value = call_user_func( $this->sanitize, $value, $old_value );
					}
					else if( is_string( $this->sanitize ) ) {
						$value = apply_filters( 'siteorigin_widgets_sanitize_field_' . $this->sanitize, $value );
					}
					break;
			}
		}

		return $value;
	}

	/**
	 * This function is called after the main render function.
	 *
	 * @param $value mixed The current value of this field.
	 * @param $instance array The current widget instance.
	 */
	protected function render_after_field( $value, $instance ) {
		$this->render_field_description();
	}

	/**
	 * Default description rendering implementation. Subclasses should override if necessary to render descriptions
	 * differently.
	 */
	protected function render_field_description() {
		if( ! empty( $this->description ) ) {
			?><div <?php $this->render_CSS_classes( $this->get_description_classes() ) ?>><?php echo wp_kses_post( $this->description ) ?></div><?php
		}
	}

	/**
	 *
	 * The main sanitization function. This function should be overridden by all subclasses and used to sanitize the
	 * input received from their HTML form field.
	 *
	 * @param $value mixed The current value of this field.
	 * @param $instance array The widget instance.
	 *
	 * @return mixed The sanitized value.
	 */
	abstract protected function sanitize_field_input( $value, $instance );

	/**
	 * There are cases where a field may affect values on the widget instance, other than it's own input. It then becomes
	 * necessary to perform additional sanitization on the widget instance, which should be done here.
	 *
	 * @param $instance
	 * @return mixed
	 */
	public function sanitize_instance( $instance ) {
		//Stub: This function may be overridden by subclasses wishing to sanitize additional instance fields.
		return $instance;
	}

	/**
	 * Occasionally it is necessary for a field to set a variable to be used in the front end. Override this function
	 * and set any necessary values on the `javascript_variables` instance property.
	 *
	 * @return array
	 */
	public function get_javascript_variables() {
		return $this->javascript_variables;
	}

	/**
	 * Some more complex fields may require some JavaScript in the front end. Enqueue them here.
	 */
	public function enqueue_scripts() {
	}

	public function __get( $name ) {
		if ( isset( $this->$name ) ) {
			return $this->$name;
		}
	}
}

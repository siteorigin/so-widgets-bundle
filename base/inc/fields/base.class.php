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
	 * The CSS classes to be applied to the rendered label.
	 *
	 * @access protected
	 * @var array
	 */
	protected $label_classes;
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
	 * The CSS classes to be applied to the rendered description.
	 *
	 * @access protected
	 * @var array
	 */
	protected $description_classes;
	/**
	 * Append '(Optional)' to this field's label as a small green superscript.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $optional;
	/**
	 * Specifies an additional sanitization to be performed. Available sanitizations are 'email' and 'url'. If the
	 * specified sanitization isn't recognized it is assumed to be a custom sanitization and a filter is applied using
	 * the pattern `'siteorigin_widgets_sanitize_field_' . $sanitize`, in case the sanitization is defined elsewhere.
	 *
	 * @access protected
	 * @var string
	 */
	protected $sanitize;


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
	 */
	public function __construct( $base_name, $element_id, $element_name, $field_options ){
		$this->type = $field_options['type'];
		$this->base_name = $base_name;
		$this->element_id = $element_id;
		$this->element_name = $element_name;
		$this->field_options = $field_options;
		$this->javascript_variables = array();

		$this->initialize();
	}

	protected function initialize() {
		$this->init_options();
		$this->init_CSS_classes();
	}

	protected function init_options() {
		$field_options = $this->field_options;
		foreach ( $field_options as $key => $value ) {
			if( property_exists( $this, $key ) ) {
				if ( isset( $field_options[$key] ) ) {
					$this->$key = $value;
				}
			}
		}
	}

	protected function init_CSS_classes() {
		$this->label_classes = $this->add_label_classes( array( 'siteorigin-widget-field-label' ) );
		$this->description_classes = $this->add_description_classes( array( 'siteorigin-widget-field-description' ) );
	}

	protected function add_label_classes( $label_classes ) {
		return $label_classes;
	}

	protected function add_description_classes( $description_classes ) {
		return $description_classes;
	}

	/**
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

	protected function render_before_field( $value, $instance ) {
		$this->render_field_label();
	}

	/**
	 * Default label rendering implementation. Subclasses should override if necessary to render labels differently.
	 */
	protected function render_field_label() {
		?>
		<label for="<?php echo $this->element_id ?>" <?php $this->render_label_classes() ?>>
			<?php
		echo $this->label;
		if( !empty( $this->optional ) ) {
			echo '<span class="field-optional">(' . __('Optional', 'siteorigin-panels') . ')</span>';
		}
		?>
		</label>
		<?php
	}

	protected function render_label_classes() {
		if( ! empty( $this->label_classes ) ) {
			?>class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $this->label_classes ) ) ) ?>"<?php
		}
	}

	abstract protected function render_field( $value, $instance );

	/**
	 * The default sanitization function.
	 *
	 * @param $value mixed The value to be sanitized.
	 * @param $instance array The widget instance.
	 * @return mixed|string|void
	 */
	public function sanitize( $value, $instance = array() ) {

		$this->sanitize_field_input( $value );
		$this->sanitize_instance( $instance );

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
					$value = apply_filters( 'siteorigin_widgets_sanitize_field_' . $this->sanitize, $value );
					break;
			}
		}

		return $value;

	}

	protected function render_after_field( $value, $instance ) {
		$this->render_field_description();
	}

	protected function render_field_description() {
		if( ! empty( $this->description ) ) {
			?><div <?php $this->render_description_classes() ?>><?php echo wp_kses_post( $this->description ) ?></div><?php
		}
	}

	protected function render_description_classes() {
		if( ! empty( $this->description_classes ) ) {
			?>class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $this->description_classes ) ) ) ?>"<?php
		}
	}

	abstract protected function sanitize_field_input( $value );

	protected function sanitize_instance( $instance ) {
		//Stub: This function may be overridden by subclasses wishing to sanitize additional instance fields.
	}

	public function get_javascript_variables() {
		return $this->javascript_variables;
	}

}
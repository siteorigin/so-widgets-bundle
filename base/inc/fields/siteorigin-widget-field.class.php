<?php

/**
 * The class that other fields most extend
 *
 * Class SiteOrigin_Widget_Field
 */

abstract class SiteOrigin_Widget_Field {

	protected $base_name;
	protected $element_id;
	protected $element_name;
	protected $field_options;
	protected $javascript_variables;

	/* BASE FIELD PROPERTIES */

	/**
	 * The type. Available types listed as constants.
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

	protected $hide;

	protected $sanitize;

	/* FIELD STATES PROPERTIES */

	protected $state_name;
	protected $hidden;
	protected $state_emitter;
	protected $state_handler;
	protected $state_handler_initial;

	/**
	 * @param $base_name string The name of the field.
	 * @param $element_id string The id to be used as the id attribute of the wrapping HTML element.
	 * @param $element_name string The name to be used as the name attribute of the wrapping HTML element.
	 * @param $field_options array Configuration for the field.
	 */
	public function __construct( $base_name, $element_id, $element_name, $field_options ){
		$this->base_name = $base_name;
		$this->element_id = $element_id;
		$this->element_name = $element_name;
		$this->field_options = $field_options;
		$this->javascript_variables = array();

		$this->type = $field_options['type'];
		if( isset($field_options['label'] ) ) $this->label = $field_options['label'];
		if( isset($field_options['default'] ) ) $this->default = $field_options['default'];
		if( isset($field_options['description'] ) ) $this->description = $field_options['description'];
		if( isset($field_options['optional'] ) ) $this->optional = $field_options['optional'];
		if( isset($field_options['hide'] ) ) $this->hide = $field_options['hide'];
		if( isset($field_options['sanitize'] ) ) $this->sanitize = $field_options['sanitize'];
	}

	public function get_name() {
		return $this->base_name;
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

		if( !empty( $this->state_name ) ) $wrapper_attributes['class'][] = 'siteorigin-widget-field-state-' . $this->state_name;
		if( !empty( $this->hidden ) ) $wrapper_attributes['class'][] = 'siteorigin-widget-field-is-hidden';
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

		// Allow subclasses and/or plugins to render something before and after the render_field() function is called.
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
		<label for="<?php echo $this->element_id ?>" class="siteorigin-widget-field-label <?php if( empty( $this->hide ) ) echo 'siteorigin-widget-section-visible'; ?>">
			<?php
		echo $this->label;
		if( !empty( $this->optional ) ) {
			echo ' <span class="field-optional">(' . __('Optional', 'siteorigin-panels') . ')</span>';
		}
		?>
		</label>
		<?php
	}

	abstract protected function render_field( $value, $instance );

	/**
	 * The default sanitization function. Even if implemented in a child class, this should still be called.
	 *
	 * @param $value mixed The value to be sanitized.
	 * @param array $instance array The widget instance.
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

	private function render_field_description() {
		if( ! empty( $this->description ) ) {
			?><div class="siteorigin-widget-field-description"><?php echo wp_kses_post( $this->description ) ?></div><?php
		}
	}

	abstract protected function sanitize_field_input( $value );

	protected function sanitize_instance( $instance ) {
		//Stub: This function may be overridden by subclasses wishing to sanitize additional instance fields.
	}

	public function get_javascript_variables() {
		return $this->javascript_variables;
	}

	//TODO: These functions should not stay here. They are only here temporarily while refactoring fields into classes.

	/**
	 * Convert underscore naming convention to camel case. Useful for data to be handled by javascript.
	 *
	 * @param $array array Input array of which the keys will be transformed.
	 * @return array The transformed array with camel case keys.
	 */
	protected function underscores_to_camel_case( $array ) {
		$transformed = array();
		if ( !empty( $array ) ) {
			foreach ( $array as $key => $val ) {
				$jsKey = preg_replace_callback( '/_(.?)/', array($this, 'match_to_upper'), $key );
				$transformed[ $jsKey ] = $val;
			}
		}
		return $transformed;
	}

	/**
	 * Convert a matched string to uppercase. Used as a preg callback.
	 *
	 * @param $matches
	 *
	 * @return string
	 */
	private function match_to_upper( $matches ) {
		return strtoupper( $matches[1] );
	}

}
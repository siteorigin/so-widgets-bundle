<?php

/**
 * The class that other fields most extend
 *
 * Class SiteOrigin_Widget_Field
 */

abstract class SiteOrigin_Widget_Field {

	// List of field types available in the Widgets Bundle.
	const TYPE_TEXT = 'text';

	protected $base_name;
	protected $element_id;
	protected $element_name;
	protected $options;

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
	 * @param $options array Configuration for the field.
	 */
	function __construct( $base_name, $element_id, $element_name, $options ){
		$this->base_name = $base_name;
		$this->element_id = $element_id;
		$this->element_name = $element_name;
		$this->options = $options;

		$this->type = $options['type'];

		if( isset($options['label'] ) ) $this->label = $options['label'];
		if( isset($options['default'] ) ) $this->default = $options['default'];
		if( isset($options['description'] ) ) $this->description = $options['description'];
		if( isset($options['optional'] ) ) $this->optional = $options['optional'];
		if( isset($options['hide'] ) ) $this->hide = $options['hide'];
	}

	/**
	 * @param $value mixed The current instance value of the field.
	 */
	public function render( $value ) {
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

		?><div <?php foreach( $wrapper_attributes as $attr => $attr_val ) echo $attr.'="' . esc_attr($attr_val) . '" ' ?>><?php

		// Allow subclasses or plugins to render something before and after the render_field() function is called.

//		$this->render_pre_field();
		$this->render_field_label();
		$this->render_field( $value );
//		$this->render_post_field();

		if( ! empty( $this->description ) ) {
			?><div class="siteorigin-widget-field-description"><?php echo wp_kses_post($this->description ) ?></div><?php
		}

		?></div><?php
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

	abstract protected function render_field( $value );

	/**
	 * The default sanitization function. Even if implemented in a child class, this should still be called.
	 */
	public function sanitize( $value ) {

		$this->sanitize_field_input( $value );

		if( isset($this->options['sanitize']) ) {
			// This field also needs some custom sanitization
			switch($this->options['sanitize']) {
				case 'url':
					$value = sow_esc_url_raw( $value );
					break;

				case 'email':
					$value = sanitize_email( $value );
					break;

				default:
					// This isn't a built in sanitization. Maybe it's handled elsewhere.
					$value = apply_filters( 'siteorigin_widgets_sanitize_field_' . $this->options['sanitize'], $value );
					break;
			}
		}

		return $value;

	}

	abstract protected function sanitize_field_input( $value );

}
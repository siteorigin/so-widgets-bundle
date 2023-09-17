<?php

/**
 * Class SiteOrigin_Widget_Field_Select
 */
class SiteOrigin_Widget_Field_Select extends SiteOrigin_Widget_Field_Base {
	/**
	 * The list of options which may be selected.
	 *
	 * @var array
	 */
	protected $options;
	/**
	 * If present, this string is included as a disabled (not selectable) value at the top of the list of options. If
	 * there is no default value, it is selected by default. You might even want to leave the label value blank when
	 * you use this.
	 *
	 * @var string
	 */
	protected $prompt;
	/**
	 * Determines whether this is a single or multiple select field.
	 *
	 * @var bool
	 */
	protected $multiple;

	/**
	 * Determines whether this is a single or multiple select field.
	 *
	 * @var bool
	 */
	protected $select2;

	public function enqueue_scripts() {
		if (
			! empty( $this->multiple ) &&
			! empty( $this->select2 )
		) {
			wp_enqueue_script( 'select2' );
			wp_enqueue_style( 'select2' );
			wp_enqueue_script(
				'so-select-field',
				plugin_dir_url( __FILE__ ) . 'js/select-field' . SOW_BUNDLE_JS_SUFFIX . '.js',
				array( 'jquery' ),
				SOW_BUNDLE_VERSION
			);
		}
	}

	protected function render_field( $value, $instance ) {

		if (
			! empty( $this->multiple ) &&
			! empty( $this->select2 )
		) {
			if ( ! empty( $this->input_css_classes ) ) {
				$this->input_css_classes = array();
			}
			$this->input_css_classes[] = 'sow-select2';
		}
		?>
		<select
			name="<?php echo esc_attr( $this->element_name ); ?>"
			id="<?php echo esc_attr( $this->element_id ); ?>"
			class="siteorigin-widget-input siteorigin-widget-input-select<?php if ( ! empty( $this->input_css_classes ) ) {
					echo ' ' . implode( ' ', $this->input_css_classes );
				} ?>"
			<?php if ( ! empty( $this->multiple ) ) {
				echo 'multiple';
			} ?>>
			<?php if ( empty( $this->multiple ) && isset( $this->prompt ) ) { ?>
				<option value="default" disabled="disabled" selected="selected"><?php echo esc_html( $this->prompt ); ?></option>
			<?php } ?>

			<?php if ( isset( $this->options ) && ! empty( $this->options ) ) { ?>
				<?php foreach ( $this->options as $key => $val ) { ?>
					<?php
					if ( is_array( $value ) ) {
						$selected = selected( true, in_array( $key, $value ), false );
					} else {
						$selected = selected( $key, $value, false );
					} ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php echo $selected; ?>><?php echo esc_html( $val ); ?></option>
				<?php } ?>
			<?php } ?>
		</select>
		<?php
	}

	protected function sanitize_field_input( $value, $instance ) {
		$values = is_array( $value ) ? $value : array( $value );
		$keys = array_keys( $this->options );
		$sanitized_value = array();

		foreach ( $values as $value ) {
			if ( ! in_array( $value, $keys ) ) {
				$sanitized_value[] = isset( $this->default ) ? $this->default : false;
			} else {
				$sanitized_value[] = $value;
			}
		}

		return count( $sanitized_value ) == 1 ? $sanitized_value[0] : $sanitized_value;
	}
}

<?php

class SiteOrigin_Widget_ContactForm_Field_Text extends SiteOrigin_Widget_ContactForm_Field_Base {
	public function __construct( $options ) {
		$this->type = 'text';
		parent::__construct( $options );
	}

	public function render_field( $options ) {
		?>
		<input
			type="<?php echo $options['field']['type']; ?>"
			name="<?php echo esc_attr( $options['field_name'] ); ?>"
			id="<?php echo esc_attr( $options['field_id'] ); ?>"
			value="<?php echo esc_attr( $options['value'] ); ?>"
			class="sow-text-field"<?php echo $options['show_placeholder'] ? 'placeholder="' . esc_attr( $options['label'] ) . '"' : ''; ?>
			<?php self::add_custom_attrs( $this->type ); ?>
		/>
		<?php
	}
}

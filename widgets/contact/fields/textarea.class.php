<?php

class SiteOrigin_Widget_ContactForm_Field_TextArea extends SiteOrigin_Widget_ContactForm_Field_Base {
	public function render_field( $options ) {
		?>
		<textarea
			name="<?php echo esc_attr( $options['field_name'] ); ?>"
			id="<?php echo esc_attr( $options['field_id'] ); ?>"
			rows="10"
			<?php echo $options['show_placeholder'] ? 'placeholder="' . esc_attr( $options['label'] ) . '"' : ''; ?>
			<?php self::add_custom_attrs( $this->type ); ?>
		><?php
		echo esc_textarea( $options['value'] );
		?></textarea>
		<?php
	}
}

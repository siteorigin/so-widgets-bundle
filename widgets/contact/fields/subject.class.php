<?php

class SiteOrigin_Widget_ContactForm_Field_Subject extends SiteOrigin_Widget_ContactForm_Field_Base {
	public function render_field( $options ) {
		?>
		<input
			type="text"
			name="<?php echo esc_attr( $options['field_name'] ); ?>"
			id="<?php echo esc_attr( $options['field_id'] ); ?>"
			value="<?php echo esc_attr( $options['value'] ); ?>"
			class="sow-text-field"
			<?php echo $options['show_placeholder'] ? 'placeholder="' . esc_attr( $options['label'] ) . '"' : ''; ?>
			<?php self::add_custom_attrs( 'subject' ); ?>
		/>
		<?php
	}
}

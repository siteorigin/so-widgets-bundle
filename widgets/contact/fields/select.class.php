<?php

class SiteOrigin_Widget_ContactForm_Field_Select extends SiteOrigin_Widget_ContactForm_Field_Base {

	public function render_field( $options ) {
		?><select  name="<?php echo esc_attr( $options['field_name'] ) ?>"
		           id="<?php echo esc_attr( $options['field_id'] ) ?>">
		<?php if ( ! empty( $options['field']['options'] ) ) {
			foreach ( $options['field']['options'] as $option ) { ?>
				<option
					value="<?php echo esc_attr( $option['value'] ) ?>"<?php echo selected( $option['value'], $options['value'], false ) ?>>
					<?php echo esc_html( $option['value'] ) ?>
				</option>
			<?php }
		} ?>
		</select><?php
	}
}

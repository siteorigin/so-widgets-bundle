<?php

class SiteOrigin_Widget_ContactForm_Field_Radio extends SiteOrigin_Widget_ContactForm_Field_Base {

	public function render_field( $options ) {
		if ( ! empty( $options['field']['options'] ) ): ?>
			<ul>
				<?php foreach ( $options['field']['options'] as $i => $option ): ?>
					<li>
						<label>
							<input type="radio" value="<?php echo esc_attr( $option['value'] ) ?>" name="<?php echo esc_attr( $options['field_name'] ) ?>" id="<?php echo esc_attr( $options['field_id'] ) . '-' . $i ?>"<?php echo checked( $option['value'], $options['value'], false ) ?>/>
							<?php echo esc_html( $option['value'] ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif;
	}
}

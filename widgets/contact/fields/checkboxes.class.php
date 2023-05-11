<?php

class SiteOrigin_Widget_ContactForm_Field_Checkboxes extends SiteOrigin_Widget_ContactForm_Field_Base {
	public function render_field( $options ) {
		if ( ! empty( $options['field']['options'] ) ) {
			if ( empty( $options['value'] ) || ! is_array( $options['value'] ) ) {
				$options['value'] = array();
			}
			?>
			<ul>
				<?php foreach ( $options['field']['options'] as $i => $option ) { ?>
					<?php
					$is_checked = in_array( $option['value'], $options['value'] ) || ( isset( $option['default'] ) && $option['default'] );
					?>
					<li>
						<input
							type="checkbox"
							value="<?php echo esc_attr( $option['value'] ); ?>"
							name="<?php echo esc_attr( $options['field_name'] ); ?>[]"
							id="<?php echo esc_attr( $options['field_id'] ) . '-' . $i; ?>"
							<?php echo checked( $is_checked, true, false ); ?>
							<?php self::add_custom_attrs( 'checkboxes' ); ?>
						/>
						<label for="<?php echo esc_attr( $options['field_id'] ) . '-' . $i; ?>">
							<?php echo wp_kses_post( $option['value'] ); ?>
						</label>
					</li>
				<?php } ?>
			</ul>
		<?php
		}
	}
}

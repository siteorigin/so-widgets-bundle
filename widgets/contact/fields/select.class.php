<?php

class SiteOrigin_Widget_ContactForm_Field_Select extends SiteOrigin_Widget_ContactForm_Field_Base {
	public function render_field( $options ) {
		?>
		<select
			name="<?php echo esc_attr( $options['field_name'] ); ?><?php echo ! empty( $options['field']['multiple_select'] ) ? '[]' : ''; ?>"
			id="<?php echo esc_attr( $options['field_id'] ); ?>"
			<?php self::add_custom_attrs( 'select' ); ?>
			<?php echo ! empty( $options['field']['multiple_select'] ) ? 'multiple' : ''; ?>
		>
			<?php
			if ( $options['show_placeholder'] ) {
				?>
				<option selected disabled><?php esc_html_e( $options['field']['label'] ); ?></option>
				<?php
			}

			if ( ! empty( $options['field']['options'] ) ) {
				if ( ! $options['show_placeholder'] && $options['field']['required']['required'] ) {
					?>
					<option selected <?php if ( ! $options['field']['required']['required'] ) {
						echo 'disabled';
					} ?>></option>
					<?php
				}

				foreach ( $options['field']['options'] as $i => $option ) {
					$value = ! empty( $options['field']['multiple_select'] ) && is_array( $options['value'] ) ? $options['value'][ $i ] : $options['value'];
					?>
					<option
						value="<?php echo esc_attr( $option['value'] ); ?>"<?php echo selected( $option['value'], $value, false ); ?>>
						<?php echo esc_html( $option['value'] ); ?>
					</option>
				<?php
				}
			}
		?>
		</select>
		<?php
	}
}

<?php

/**
 * A full instance of SiteOrigin Page Builder as a field.
 *
 * Class SiteOrigin_Widget_Field_Builder
 */
class SiteOrigin_Widget_Field_Builder extends SiteOrigin_Widget_Field_Base {

	protected function render_field( $value, $instance ) {
		if ( ! siteorigin_widgets_can_render_builder_field() ) {
			?>
			<p>
				<?php
				printf(
					esc_html__( 'This field requires %sSiteOrigin Page Builder%s to be installed and activated.', 'so-widgets-bundle' ),
					'<a href="https://siteorigin.com/page-builder/" target="_blank" rel="noopener noreferrer">',
					'</a>'
				);
				?>
			</p>
			<?php
			return;
		}

		// Encode builder data if necessary.
		if ( ! empty( $value ) && is_array( $value ) ) {
			$value = json_encode( $value );
		}
		?>
		<div
			class="siteorigin-page-builder-field"
			data-mode="dialog"
			data-type="<?php echo isset( $this->field_options['builder_type'] ) ? esc_attr( $this->field_options['builder_type'] ) : 'sow-builder-field'; ?>"
			>
			<p>
				<button class="button-secondary siteorigin-panels-display-builder">
					<?php esc_html_e( 'Open Builder', 'so-widgets-bundle' ); ?>
				</button>
			</p>
			<input
				type="hidden"
				class="siteorigin-widget-input panels-data"
				value="<?php echo sow_esc_attr( (string) $value, ENT_QUOTES, false, true ); ?>"
				name="<?php echo esc_attr( $this->element_name ); ?>"
				id="<?php echo esc_attr( $this->element_id ); ?>"
				/>
		</div>
		<?php
	}

	/**
	 * Process the panels_data
	 *
	 * @param mixed $value
	 * @param array $instance
	 *
	 * @return array|mixed|object
	 */
	protected function sanitize_field_input( $value, $instance ) {
		if ( empty( $value ) ) {
			return array();
		}

		if ( is_string( $value ) ) {
			$value = json_decode( $value, true );
		}

		if ( function_exists( 'siteorigin_panels_process_raw_widgets' ) && ! empty( $value['widgets'] ) && is_array( $value['widgets'] ) ) {
			$value['widgets'] = siteorigin_panels_process_raw_widgets( $value['widgets'] );
		}

		// Add record of widget being inside of a builder field.
		if ( ! empty( $value['widgets'] ) ) {
			foreach ( $value['widgets'] as $widget_id => $widget ) {
				$value['widgets'][ $widget_id ]['panels_info']['builder'] = true;
			}
		}

		return $value;
	}
}

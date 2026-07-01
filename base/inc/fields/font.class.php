<?php

/**
 * Class SiteOrigin_Widget_Field_Font
 */
class SiteOrigin_Widget_Field_Font extends SiteOrigin_Widget_Field_Base {

	protected function render_field( $value, $instance ) {
		static $widget_font_families;

		if ( empty( $widget_font_families ) ) {
			$widget_font_families = siteorigin_widgets_font_families();
		}
		?>
		<div class="siteorigin-widget-font-selector siteorigin-widget-field-subcontainer">
			<select
				name="<?php echo esc_attr( $this->element_name ); ?>"
				id="<?php echo esc_attr( $this->element_id ); ?>" class="siteorigin-widget-input"
				data-selected="<?php echo esc_attr( $value ); ?>"
			>
				<option value="default"<?php echo ( empty( $value ) || $value === 'default' ) ? ' selected="selected"' : ''; ?>><?php esc_html_e( 'Use theme font', 'so-widgets-bundle' ); ?></option>
				<?php if ( ! empty( $value ) && $value !== 'default' ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" data-sow-saved-option selected="selected"><?php echo esc_html( isset( $widget_font_families[ $value ] ) ? $widget_font_families[ $value ] : $value ); ?></option>
				<?php endif; ?>
			</select>
		</div>
		<?php
	}

	protected function sanitize_field_input( $value, $instance ) {
		if ( empty( $value ) ) {
			return isset( $this->default ) ? $this->default : 'default';
		}

		$sanitized_value = trim( $value );

		// Any alphanumeric character followed by alphanumeric or whitespace characters (except newline),
		// with optional colon followed by optional variant.
		if ( preg_match( '/[\w\d]+[\w\d\t\r ]*(:\w+)?/', $sanitized_value, $sanitized_matches ) ) {
			$sanitized_value = $sanitized_matches[0];
		} else {
			$sanitized_value = 'default';
		}

		static $widget_font_families;

		if ( empty( $widget_font_families ) ) {
			$widget_font_families = siteorigin_widgets_font_families();
		}

		// When the font families list didn't load this request, skip the
		// validity reset and return the regex-cleaned value so a valid stored
		// font isn't dropped to default.
		if ( empty( $widget_font_families ) ) {
			return $sanitized_value;
		}

		// If selected font isn't set to default, ensure the font is valid.
		if (
			$sanitized_value !== 'default' &&
			! isset( $widget_font_families[ $sanitized_value ] )
		) {
			$sanitized_value = isset( $this->default ) ? $this->default : 'default';
		}

		return $sanitized_value;
	}
}

<?php

/**
 * Class SiteOrigin_Widget_Field_Font
 */
class SiteOrigin_Widget_Field_Font extends SiteOrigin_Widget_Field_Base {

	protected function render_field( $value, $instance ) {
		static $widget_font_families;
		if( empty($widget_font_families) ) {

			$widget_font_families = siteorigin_widgets_font_families();
		}
		?>
		<div class="siteorigin-widget-font-selector siteorigin-widget-field-subcontainer">
			<select name="<?php echo esc_attr( $this->element_name ) ?>" id="<?php echo esc_attr( $this->element_id ) ?>" class="siteorigin-widget-input">
				<option value="default" selected="selected"><?php esc_html_e( 'Use theme font', 'siteorigin-widgets' ) ?></option>
				<?php foreach( $widget_font_families as $key => $val ) : ?>
					<option value="<?php echo esc_attr( $key ) ?>" <?php selected( $key, $value ) ?>><?php echo esc_html( $val ) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	protected function sanitize_field_input( $value ) {
		$sanitized_value = trim( $value );
		// Any alphanumeric character followed by alphanumeric or whitespace characters (except newline),
		// with optional colon and number.
		if( preg_match( '/[\w\d]+[\w\d\t\r ]*(:\d+)?/', $sanitized_value, $sanitized_matches ) ) {
			$sanitized_value = $sanitized_matches[0];
		}
		else {
			$sanitized_value = 'default';
		}

		static $widget_font_families;
		if( empty($widget_font_families) ) {
			$widget_font_families = siteorigin_widgets_font_families();
		}
		$keys = array_keys( $widget_font_families );
		if( ! in_array( $sanitized_value, $keys ) ) $sanitized_value = isset( $this->default ) ? $this->default : 'default';

 		return $sanitized_value;
	}
}
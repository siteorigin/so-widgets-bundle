<?php

/**
 * Class SiteOrigin_Widget_Field_Font
 */
class SiteOrigin_Widget_Field_Font extends SiteOrigin_Widget_Field {
	protected function render_field( $value, $instance ) {
		static $widget_font_families;
		if( empty($widget_font_families) ) {

			$widget_font_families = siteorigin_widgets_font_families();
		}
		?>
		<div class="siteorigin-widget-font-selector siteorigin-widget-field-subcontainer">
			<select name="<?php echo $this->element_name ?>" id="<?php echo $this->element_id ?>" class="siteorigin-widget-input">
				<option value="default" selected="selected"><?php _e( 'Use theme font', 'siteorigin-widgets' ) ?></option>
				<?php foreach( $widget_font_families as $key => $val ) : ?>
					<option value="<?php echo esc_attr( $key ) ?>" <?php selected( $key, $value ) ?>><?php echo esc_html( $val ) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	protected function sanitize_field_input( $value ) {
	}

}
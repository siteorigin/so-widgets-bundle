<?php

/**
 * Class SiteOrigin_Widget_Field_Link
 */
class SiteOrigin_Widget_Field_Link extends SiteOrigin_Widget_Field_Text_Input_Base {

	protected function render_field( $value ) {
		?>
		<a href="#" class="select-content-button button-secondary"><?php _e('Select Content', 'siteorigin-widgets') ?></a>
		<div class="existing-content-selector">

			<input type="text" placeholder="<?php esc_attr_e('Search Content', 'siteorigin-widgets') ?>" class="content-text-search" />

			<ul class="posts"></ul>

			<div class="buttons">
				<a href="#" class="button-close button-secondary"><?php _e('Close', 'siteorigin-widgets') ?></a>
			</div>
		</div>
		<div class="url-input-wrapper">
			<input type="text" name="<?php echo $this->element_name ?>" id="<?php echo $this->element_id ?>" value="<?php echo esc_attr( $value ) ?>" <?php if ( ! empty( $this->placeholder ) ) echo 'placeholder="' . $this->placeholder . '"' ?> class="widefat siteorigin-widget-input" <?php if( ! empty( $this->readonly ) ) echo 'readonly' ?> />
		</div>
		<?php
	}

	protected function sanitize_field_input( $value ) {
		$sanitized_value = trim( $value );
		if( preg_match( '/^post\: *([0-9]+)/', $sanitized_value, $matches ) ) {
			$sanitized_value = 'post: ' . $matches[1];
		}
		else {
			$sanitized_value = sow_esc_url_raw( $sanitized_value );
		}
		return $sanitized_value;
	}


}
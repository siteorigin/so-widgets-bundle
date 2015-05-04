<?php

/**
 * Class SiteOrigin_Widget_Field_Link
 */
class SiteOrigin_Widget_Field_Link extends SiteOrigin_Widget_Field_Text_Input_Base {

	protected function render_field( $value, $instance ) {
		?>
		<a href="#" class="select-content-button button-secondary"><?php _e('Select Content', 'siteorigin-widgets') ?></a>
		<div class="existing-content-selector">

			<input type="text" class="content-text-search"
			       placeholder="<?php esc_attr_e('Search Content', 'siteorigin-widgets') ?>"/>

			<ul class="posts"></ul>

			<div class="buttons">
				<a href="#" class="button-close button-secondary"><?php _e('Close', 'siteorigin-widgets') ?></a>
			</div>
		</div>
		<div class="url-input-wrapper">
			<?php
			$this->render_text_input( $value );
			?>
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
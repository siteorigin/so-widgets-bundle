<?php

/**
 * Class SiteOrigin_Widget_Field_Html
 */
class SiteOrigin_Widget_Field_Html extends SiteOrigin_Widget_Field_Base {
	/**
	 * The markup of this field.
	 *
	 * @var string
	 */
	protected $markup;

	protected function render_field( $value, $instance ) {
		if ( empty( $this->markup ) ) {
			return;
		}
		?>
		<div class="siteorigin-widget-html-field">
			<?php echo wp_kses_post( $this->markup ); ?>
		</div>
		<?php
	}

	protected function sanitize_field_input( $value, $instance ) {
		return;
	}
}

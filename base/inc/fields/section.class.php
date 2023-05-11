<?php

/**
 * Class SiteOrigin_Widget_Field_Section
 */
class SiteOrigin_Widget_Field_Section extends SiteOrigin_Widget_Field_Container_Base {
	protected function render_field( $value, $instance ) {
		?>
		<div class="siteorigin-widget-section <?php if ( $this->state == 'closed' ) {
			echo 'siteorigin-widget-section-hide';
		} ?>"><?php
		if ( ! isset( $this->fields ) || empty( $this->fields ) ) {
			echo '</div>';

			return;
		}
		$this->create_and_render_sub_fields(
			$value,
			array(
				'name' => $this->base_name,
				'type' => 'section',
			)
		);
		?>
			<input
				type="hidden"
				name="<?php echo esc_attr( $this->element_name . '[so_field_container_state]' ); ?>"
				id="<?php echo esc_attr( $this->element_id . '-so_field_container_state' ); ?>"
				class="siteorigin-widget-input siteorigin-widget-field-container-state"
				value="<?php echo esc_attr( $this->state ); ?>"
			/>
		</div>
		<?php
	}
}

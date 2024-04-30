<?php

/**
 * Class SiteOrigin_Widget_Field_Section
 */
class SiteOrigin_Widget_Field_Section extends SiteOrigin_Widget_Field_Container_Base {
	/**
	 * Whether to output the section as a tab. A series of sections setup as tabs will output in a single tab.
	 *
	 * @access protected
	 * @var string
	 */
	protected $tab;

	protected function get_label_classes( $value, $instance ) {
		$label_classes = parent::get_label_classes( $value, $instance );
		if ( $this->state == 'open' ) {
			$label_classes[] = 'siteorigin-widget-section-visible';
		}
		if ( ! empty( $this->tab ) ) {
			 $label_classes[] = 'siteorigin-widget-section-tab';
		}

		return $label_classes;
	}


	protected function render_field( $value, $instance ) {
		$classes = 'siteorigin-widget-section';
		$classes .= $this->state == 'closed' ? ' siteorigin-widget-section-hide' : '';
		?>
		<div class="<?php echo $classes; ?>">
			<?php
			if (
				! isset( $this->fields ) ||
				empty( $this->fields )
			) {
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

<?php

class SiteOrigin_Widget_Field_Toggle extends SiteOrigin_Widget_Field_Container_Base {
	protected $toggle_on;
	protected $toggle_off;

	protected function get_label_classes( $value, $instance ) {
		$label_classes = parent::get_label_classes( $value, $instance );
		if ( $this->state == 'open' ) {
			$label_classes[] = 'siteorigin-widget-toggle-visible';
		}
		return $label_classes;
	}

	protected function get_toggle_label( $context ) {
		if ( $context == 'on' ) {
			return ! empty( $this->toggle_on ) ?
			$this->toggle_on :
			__( 'On', 'so-widgets-bundle' );
		}

		return ! empty( $this->toggle_off ) ?
			$this->toggle_off :
			__( 'Off', 'so-widgets-bundle' );
	}

	protected function render_field_label( $value, $instance ) {
		?>
		<label
			class="siteorigin-widget-field-label sowb-toggle-switch sowb-toggled-<?php
				echo $this->state === 'open' ? 'on' : 'off';
			?>"
			for="<?php echo esc_attr( $this->element_id . '-so_field_container_state' ); ?>"
		>
			<?php echo esc_html( $this->label ); ?>

			<span class="sowb-toggle-switch-container">
				<span
					class="sowb-toggle-switch-label"
					data-on="<?php echo esc_attr( self::get_toggle_label( 'on' ) ); ?>"
					data-off="<?php echo esc_attr( self::get_toggle_label( 'off' ) ); ?>"
					aria-checked="<?php echo esc_attr( $this->state == 'open' ? 'true' : 'false' ); ?>"
				></span>
				<span class="sowb-toggle-switch-handle"></span>
			</span>
		</label>
		<?php
	}

	protected function render_field( $value, $instance ) {
		?>
		<input
			<?php checked( $this->state == 'open' ); ?>
			class="sowb-toggle-switch-input"
			id="<?php echo esc_attr( $this->element_id . '-so_field_container_state' ); ?>"
			name="<?php echo esc_attr( $this->element_name . '[so_field_container_state]' ); ?>"
			type="checkbox"
			value="<?php echo esc_attr( $this->state ); ?>"
		>

		<div
			class="siteorigin-widget-toggle"
		>
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
					'type' => 'toggle',
				)
			);
			?>
		</div>
		<?php
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			'so-toggle-field',
			plugin_dir_url( __FILE__ ) . 'js/toggle-field' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION
		);

		wp_enqueue_style(
			'so-toggle-field',
			plugin_dir_url( __FILE__ ) . 'css/toggle-field.css',
			array(),
			SOW_BUNDLE_VERSION
		);
	}
}

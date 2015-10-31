<?php

/**
 * Class SiteOrigin_Widget_Field_Measurement
 */
class SiteOrigin_Widget_Field_Measurement extends SiteOrigin_Widget_Field_Text_Input_Base {

	protected function get_input_classes() {
		$input_classes = parent::get_input_classes();
		$input_classes[] = 'siteorigin-widget-input-measurement';
		return $input_classes;
	}

	protected function render_after_field( $value, $instance ) {
		$unit_name = $this->get_unit_field_name( $this->base_name );
		$unit = ! empty( $instance[ $unit_name ] ) ? $instance[ $unit_name ] : '';
		?>
		<select class="sow-measurement-select-unit"
				name="<?php echo esc_attr( $this->for_widget->so_get_field_name( $this->base_name . '_unit', $this->parent_container ) ) ?>">
			<?php foreach ( siteorigin_widgets_get_measurements_list() as $measurement ):?>
				<option value="<?php echo esc_attr( $measurement ) ?>" <?php selected( $measurement, $unit, true ); ?>><?php echo esc_html( $measurement ) ?></option>
			<?php endforeach?>
		</select>
		<div class="clear"></div>
		<?php
		//Still want the default description, if there is one.
		parent::render_after_field( $value, $instance );
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'so-measurement-field', plugin_dir_url(__FILE__) . 'css/so-measurement-field.css', array(), SOW_BUNDLE_VERSION );
	}


	protected function sanitize_field_input( $value ) {
		return ( $value === '' ) ? false : (float) $value;
	}

	public function sanitize_instance( $instance ) {
		$unit_name = $this->get_unit_field_name( $this->base_name );
		if( ! empty( $instance[ $unit_name ] ) ) {
			$units = siteorigin_widgets_get_measurements_list();
			$instance[ $unit_name ] = in_array( $instance[ $unit_name ], $units ) ? $instance[ $unit_name ] : 'px';
			esc_url_raw( $instance[ $unit_name ] );
		}
		return $instance;
	}

	public function get_unit_field_name( $base_name ) {
		$v_name = $base_name;
		if( strpos($v_name, '][') !== false ) {
			// Remove this splitter
			$v_name = substr( $v_name, strpos($v_name, '][') + 2 );
		}
		return $v_name . '_unit';
	}
}
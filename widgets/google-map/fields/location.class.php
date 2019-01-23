<?php
/**
 * A specialized field for the Google Maps widget which will immediately geocode addresses in the front end,
 * before the form is submitted.
 */
class SiteOrigin_Widget_Field_Location extends SiteOrigin_Widget_Field_Base {
	
	protected function render_field( $value, $instance ) {
		if ( is_string( $value ) ) {
			$value = json_decode( $value, true );
			if ( empty( $value ) ) {
				$value = array();
			}
		}
		$address = '';
		if ( ! empty( $value['address'] ) ) {
			$address = $value['address'];
		} else if ( ! empty( $value['name'] ) ) {
			$address = $value['name'];
		}
		?>
		<input type="text" value="<?php echo esc_attr( $address ) ?>"
			class="widefat siteorigin-widget-location-input"/>
		<input type="hidden"
				 class="siteorigin-widget-input location-field-data"
				 value="<?php echo esc_attr( json_encode( $value ) ); ?>"
				 name="<?php echo esc_attr( $this->element_name ) ?>"
				 id="<?php echo esc_attr( $this->element_id ) ?>"/>
		<?php
	}
	
	function enqueue_scripts() {
		wp_enqueue_script(
			'so-location-field',
			plugin_dir_url( __FILE__ ) . 'js/location-field' . SOW_BUNDLE_JS_SUFFIX .  '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION
		);
		wp_enqueue_style(
			'so-location-field',
			plugin_dir_url( __FILE__ ) . 'css/location-field.css',
			array(),
			SOW_BUNDLE_VERSION
		);
	}
	
	protected function sanitize_field_input( $value, $instance ) {
		if ( empty( $value ) ) {
			return array();
		}
		if ( is_string( $value ) ) {
			$value = json_decode( $value, true );
		}
		$location = array();
		
		if ( ! empty( $value['name'] ) ) {
			$location['name'] = $value['name'];
		}
		if ( ! empty( $value['address'] ) ) {
			$location['address'] = $value['address'];
		}
		if ( ! empty( $value['location'] ) ) {
			$location['location'] = $value['location'];
		}
		
		return $location;
	}
}

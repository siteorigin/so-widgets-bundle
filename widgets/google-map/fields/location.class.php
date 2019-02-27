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
		
		$api_key = SiteOrigin_Widget_GoogleMap_Widget::get_api_key( $instance );
		
		?>
		<input type="text" value="<?php echo esc_attr( $address ) ?>"
			class="widefat siteorigin-widget-location-input"/>
		<input
			type="hidden"
			class="siteorigin-widget-input location-field-data"
			data-api-key="<?php echo esc_attr( $api_key ); ?>"
			value="<?php if ( ! empty( $value ) ) echo esc_attr( json_encode( $value ) ); ?>"
			name="<?php echo esc_attr( $this->element_name ) ?>"
			id="<?php echo esc_attr( $this->element_id ) ?>"
		/>
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
		
		wp_localize_script(
			'so-location-field',
			'soLocationField',
			array(
				'missingApiKey' => __( 'This widget requires a Google Maps API key. Please ensure you have set yours in the Google Maps Widget settings.', 'so-widgets-bundle' ),
				'globalSettingsButtonLabel' => __( 'Go to Google Maps Widget settings', 'so-widgets-bundle' ),
				'globalSettingsButtonUrl' => admin_url( 'plugins.php?page=so-widgets-plugins' ),
			)
		);
	}
	
	protected function sanitize_field_input( $value, $instance ) {
		if ( empty( $value ) ) {
			return array();
		}
		if ( is_string( $value ) ) {
			$decoded_value = json_decode( $value, true );
			// If it's not valid JSON
			if ( $decoded_value == null ) {
				$decoded_value = array( 'address' => $value );
			}
		}
		$location = array();
		
		if ( ! empty( $decoded_value['name'] ) ) {
			$location['name'] = wp_kses_post( $decoded_value['name'] );
		}
		if ( ! empty( $decoded_value['address'] ) ) {
			$location['address'] = wp_kses_post( $decoded_value['address'] );
		}
		if ( ! empty( $decoded_value['location'] ) ) {
			$location['location'] = wp_kses_post( $decoded_value['location'] );
		}
		
		return $location;
	}
}

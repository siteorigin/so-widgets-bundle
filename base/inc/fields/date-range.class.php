<?php

/**
 * Class SiteOrigin_Widget_Field_Date_Range
 */
class SiteOrigin_Widget_Field_Date_Range extends SiteOrigin_Widget_Field_Base {

	protected function render_field( $value, $instance ) {
		?><div><span><?php
		_e( 'From', 'so-widgets-bundle' );
		?></span><input type="text" class="datepicker after-picker"/></div><?php

		?><div><span><?php
		_e( 'To', 'so-widgets-bundle' );
		?></span><input type="text" class="datepicker before-picker"/></div>
		<input type="hidden"
			   class="siteorigin-widget-input"
			   value="<?php echo esc_attr( $value ) ?>"
			   name="<?php echo esc_attr( $this->element_name ) ?>" />
		<?php
	}

	public function enqueue_scripts() {
		wp_register_style( 'sowb-pikaday', plugin_dir_url(__FILE__) . 'js/lib/pikaday/pikaday.css' );
		wp_register_script( 'sowb-pikaday', plugin_dir_url(__FILE__) . 'js/lib/pikaday/pikaday' . SOW_BUNDLE_JS_SUFFIX . '.js', array( ), '1.5.1' );
		wp_enqueue_style( 'so-date-range-field', plugin_dir_url(__FILE__) . 'css/date-range-field.css', array( 'sowb-pikaday' ), SOW_BUNDLE_VERSION );
		wp_enqueue_script( 'so-date-range-field', plugin_dir_url(__FILE__) . 'js/date-range-field' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery', 'sowb-pikaday' ), SOW_BUNDLE_VERSION );
	}

	protected function sanitize_field_input( $value, $instance ) {
		if ( ! empty( $value ) ) {
			$value = json_decode( $value, true );
			if ( ! empty( $value['after'] ) ) {
				$value_after    = new DateTime( $value['after'] );
				$value['after'] = $value_after->format( 'Y-m-d' );
			}
			if ( ! empty( $value['before'] ) ) {
				$value_before    = new DateTime( $value['before'] );
				$value['before'] = $value_before->format( 'Y-m-d' );
			}
		} else {
			$value = array( 'after' => '', 'before' => '' );
		}
		return json_encode( $value );
	}
}

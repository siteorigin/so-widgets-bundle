<?php

/**
 * Class SiteOrigin_Widget_Field_Radio
 */
class SiteOrigin_Widget_Field_Image_Radio extends SiteOrigin_Widget_Field_Base {
	/**
	 * The list of options which may be selected.
	 *
	 * @access protected
	 * @var array
	 */
	protected $options;
	
	/**
	 * Whether the image options should be laid out vertically (default) or horizontally.
	 *
	 * @access protected
	 * @var string
	 */
	protected $layout;
	
	protected function get_default_options() {
		return array(
			'layout' => 'vertical',
		);
	}
	
	protected function render_field( $value, $instance ) {
		if ( ! isset( $this->options ) || empty( $this->options ) ) return;
		$i = 0;
		?>
		<div class="siteorigin-widget-image-radio-items siteorigin-widget-image-radio-layout-<?php echo esc_attr( $this->layout )?>">
		<?php
		foreach( $this->options as $option_name => $option ) {
			?>
			<label class="so-image-radio" for="<?php echo esc_attr( $this->element_id . '-' . $i ) ?>">
				<img src="<?php echo esc_attr( $option['image'] ) ?>"/>
				<input
					type="radio" name="<?php echo esc_attr( $this->element_name ) ?>"
					id="<?php echo esc_attr( $this->element_id . '-' . $i ) ?>" class="siteorigin-widget-input"
					value="<?php echo esc_attr( $option_name ) ?>"
					<?php checked( $option_name, $value ) ?>
				><?php echo esc_html( $option['label'] ) ?>
			</label>
			<?php
			$i += 1;
		}
		?></div><?php
	}

	protected function sanitize_field_input( $value, $instance ) {
		$sanitized_value = $value;
		$keys = array_keys( $this->options );
		if( ! in_array( $sanitized_value, $keys ) ) $sanitized_value = isset( $this->default ) ? $this->default : false;
		return $sanitized_value;
	}
	
	public function enqueue_scripts(){
		wp_enqueue_style(
			'so-image-radio-field',
			plugin_dir_url( __FILE__ ) . 'css/image-radio-field.css',
			array(),
			SOW_BUNDLE_VERSION
		);
		
		wp_enqueue_script(
			'so-image-radio-field',
			plugin_dir_url(__FILE__) . 'js/image-radio-field' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery'),
			SOW_BUNDLE_VERSION
		);
	}
	
}

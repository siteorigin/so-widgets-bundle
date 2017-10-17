<?php

/**
 * Class SiteOrigin_Widget_Field_Presets
 */

class SiteOrigin_Widget_Field_Presets extends SiteOrigin_Widget_Field_Base {
	/**
	 * The list of options which may be selected.
	 *
	 * @access protected
	 * @var array
	 */
	protected $options;
	
	protected function get_default_options() {
		return array(
			'description' => __( 'Warning! This will override some or all of the current form values.', 'so-widgets-bundle' ),
		);
	}
	
	
	protected function render_field( $value, $instance ) {
		
		$preset_options = array();
		foreach ( $this->options as $name => $preset ) {
			$preset_options[ $name ] = $preset['label'];
		}
		
		?>
		<select id="<?php echo esc_attr( $this->element_id ) ?>"
				class="siteorigin-widget-input"
				data-presets="<?php echo esc_attr( json_encode( $this->options ) ) ?>">
			<option value="default"></option>
			<?php if( ! empty( $preset_options ) ) : ?>
				<?php foreach( $preset_options as $key => $val ) : ?>
					<?php
					if( is_array( $value ) ) {
						$selected = selected( true, in_array( $key, $value ), false );
					}
					else {
						$selected = selected( $key, $value, false );
					} ?>
					<option value="<?php echo esc_attr( $key ) ?>" <?php echo $selected ?>><?php echo esc_html( $val ) ?></option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
		<a href="#" class="sowb-presets-field-undo"><?php _e( 'Undo', 'so-widgets-bundle' ) ?></a>
		<?php
	}
	
	public function enqueue_scripts() {
		wp_enqueue_script( 'so-presets-field',
			plugin_dir_url( __FILE__ ) . 'js/presets-field' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery' ),
			SOW_BUNDLE_VERSION );
	}
	
	protected function sanitize_field_input( $value, $instance ) {
		return $value;
	}
}

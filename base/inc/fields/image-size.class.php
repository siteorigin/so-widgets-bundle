<?php

/**
 * Class SiteOrigin_Widget_Field_Image_Size
 */
class SiteOrigin_Widget_Field_Image_Size extends SiteOrigin_Widget_Field_Select {
	/**
	 * Whether to allow custom image sizes. By default, Custom Sizes are disabled.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $custom_size;

	protected function get_default_options() {
		$image_size_configs = siteorigin_widgets_get_image_sizes();
		// Hardcoded 'full' and 'thumb' because they're not registered image sizes.
		// 'full' will result in the original uploaded image size being used.
		// 'thumb' is a small thumbnail image size defined by the current theme.
		$sizes = array(
			'full' => __( 'Full', 'so-widgets-bundle' ),
			'thumb' => __( 'Thumbnail (Theme-defined)', 'so-widgets-bundle' ),
		);
		foreach( $image_size_configs as $name => $size_config) {
			$sizes[$name] = ucwords(preg_replace('/[-_]/', ' ', $name)) . ' (' . $size_config['width'] . 'x' . $size_config['height'] . ')';
		}

		return array(
			'options' => $sizes
		);
	}

	// get_default_options() is triggered prior to $this->custom_size being
	// available so we have to set up custom sizes at field initialization.
	protected function initialize() {
		if ( ! empty( $this->custom_size ) && ! empty( $this->options ) ) {
			$this->options['custom_size'] = __( 'Custom Size', 'so-widgets-bundle' );
		}
	}

	function enqueue_scripts() {
		if ( ! empty( $this->custom_size ) ) {
			wp_enqueue_script( 'so-image-size-field', plugin_dir_url( __FILE__ ) . 'js/image-size-field' . SOW_BUNDLE_JS_SUFFIX .  '.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
			wp_enqueue_style( 'so-image-size-field', plugin_dir_url( __FILE__ ) . 'css/image-size-field.css', array(), SOW_BUNDLE_VERSION );
		}
	}

	protected function render_after_field( $value, $instance ) {
		if ( ! empty( $this->custom_size ) ) {
			$field_prefix = $this->get_custom_size_setting_prefix( $this->base_name );
			$width = ! empty( $instance[ $field_prefix . '_width' ] ) ? $instance[ $field_prefix . '_width' ] : '';
			$height = ! empty( $instance[ $field_prefix . '_height' ] ) ? $instance[ $field_prefix . '_height' ] : '';
			?>

			<div class="custom-size-wrapper">
				<label>
					<?php _e( 'Width', 'so-widgets-bundle' ); ?>
					<input type="number" value="<?php echo esc_attr( $width ); ?>"
						name="<?php echo esc_attr( $this->for_widget->so_get_field_name( $this->base_name . '_width', $this->parent_container ) ); ?>"
						class="custom-image-size custom-image-size-width siteorigin-widget-input" />
				</label>

				<label>
					<?php _e( 'Height', 'so-widgets-bundle' ); ?>
					<input type="number" value="<?php echo esc_attr( $height ); ?>"
						name="<?php echo esc_attr( $this->for_widget->so_get_field_name( $this->base_name . '_height', $this->parent_container ) ); ?>"
						class="custom-image-size custom-image-size-height siteorigin-widget-input" />
				</label>
			</div>
			<?php
		}
	}

	public function get_custom_size_setting_prefix( $base_name ) {
		if ( strpos( $base_name, '][' ) !== false ) {
			// Remove this splitter
			$base_name = substr( $base_name, strpos( $base_name, '][' ) + 2 );
		}
		return $base_name;
	}

}

<?php

/**
 * Class SiteOrigin_Widget_Field_Icon
 */
class SiteOrigin_Widget_Field_Icon extends SiteOrigin_Widget_Field_Base {
	/**
	 * The number of visible rows in the icons selector.
	 *
	 * @access protected
	 * @var int
	 *
	 */
	protected $rows = 3;

	protected $icons_callback;

	protected function render_field( $value, $instance ) {
		$widget_icon_families = $this->get_widget_icon_families();
		list( $value_family, $null ) = !empty($value) ? explode('-', $value, 2) : array('fontawesome', '');

		?>

		<div class="siteorigin-widget-icon-selector-current">
			<div class="siteorigin-widget-icon"><span></span></div>
			<label><?php _e('Choose Icon', 'so-widgets-bundle') ?></label>
		</div>

		<a class="so-icon-remove" style="display: <?php echo !empty( $value ) ? 'inline-block' : 'none' ?>;"><?php esc_html_e( 'Remove', 'so-widgets-bundle' ) ?></a>

		<div class="clear"></div>

		<div class="siteorigin-widget-icon-selector siteorigin-widget-field-subcontainer">
			<select class="siteorigin-widget-icon-family" >
				<?php foreach( $widget_icon_families as $family_id => $family_info ) : ?>
					<option value="<?php echo esc_attr( $family_id ) ?>"
						<?php selected( $value_family, $family_id ) ?>
						<?php if( !empty( $this->icons_callback ) ) echo 'data-icons="' . esc_attr( json_encode( $family_info ) ) . '"' ?>
						>
						<?php echo esc_html( $family_info['name'] ) ?> (<?php echo count( $family_info['icons'] ) ?>)
					</option>
				<?php endforeach; ?>
			</select>

			<input type="search" class="siteorigin-widget-icon-search" placeholder="<?php esc_attr_e( 'Search Icons' ) ?>" />

			<input type="hidden" name="<?php echo esc_attr( $this->element_name ) ?>" value="<?php echo esc_attr( $value ) ?>"
			       class="siteorigin-widget-icon-icon siteorigin-widget-input" />

			<div class="siteorigin-widget-icon-icons" style="height: <?php echo ( $this->rows * 54 ) - 3 ?>px;"></div>
		</div>
		<?php
	}

	protected function sanitize_field_input( $value, $instance ) {
		$sanitized_value = $value;
		// Alphanumeric characters and hyphens.
		if( preg_match( '/[\w\d]+[\w\d-]*/', $sanitized_value, $sanitized_matches ) ) {
			$sanitized_value = $sanitized_matches[0];
		}
		else {
			$sanitized_value = '';
		}
		list( $value_family, $value_icon ) = ( ! empty( $sanitized_value ) && strpos( $sanitized_value, '-' ) !== false ) ? explode( '-', $sanitized_value, 2 ) : array('', '');

		$widget_icon_families = $this->get_widget_icon_families();
		if( ! ( isset( $widget_icon_families[$value_family] ) && isset( $widget_icon_families[$value_family]['icons'][$value_icon] ) ) ) {
			$sanitized_value = isset( $this->default ) ? $this->default : '';
		}

		return $sanitized_value;
	}

	private function get_widget_icon_families(){
		if( !empty( $this->icons_callback ) ) {
			// We'll get the icons from the callback function
			$widget_icon_families = call_user_func( $this->icons_callback );
		}
		else {
			// We'll get icons from the main filter
			static $widget_icon_families;
			if( empty( $widget_icon_families ) ) $widget_icon_families = apply_filters('siteorigin_widgets_icon_families', array() );
		}

		return $widget_icon_families;
	}

	public function enqueue_scripts(){
		wp_enqueue_script( 'so-icon-field', plugin_dir_url( __FILE__ ) . 'js/icon-field' . SOW_BUNDLE_JS_SUFFIX .  '.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
		wp_enqueue_style( 'so-icon-field', plugin_dir_url( __FILE__ ) . 'css/icon-field.css', array( ), SOW_BUNDLE_VERSION );
	}

}

<?php
/**
 *
 * Class SiteOrigin_Widget_Field_Image_Shape
 */
class SiteOrigin_Widget_Field_Image_Shape extends SiteOrigin_Widget_Field_Base {

	protected function render_field( $value, $instance ) {
		$shapes = SiteOrigin_Widget_Image_Shapes::single()->get_shapes();
		?>

		<div class="siteorigin-widget-shape-current" tabindex="0">
			<div class="siteorigin-widget-shape"><span></span></div>
			<label><?php echo esc_html( __( 'Choose Shape', 'so-widgets-bundle' ) ); ?></label>
		</div>
	
		<div class="siteorigin-widget-shapes" tabindex="0">
			<input type="search" class="siteorigin-widget-shape-search" placeholder="<?php esc_attr_e( 'Search Shapes' ); ?>" />
			<?php
			foreach ( $shapes as $shape => $name ) {
				?>
				<div class="siteorigin-widget-shape" data-shape="<?php echo esc_attr( $shape ); ?>">
					<img class="siteorigin-widget-shape-image" src="<?php echo esc_url( SiteOrigin_Widget_Image_Shapes::single()->get_image_shape( $shape ) ); ?>" />
					<div class="siteorigin-widget-shape-name"><?php echo esc_html( $name ); ?></div>
				</div>
				<?php
			}
			?>
		</div>

		<input
			type="hidden"
			name="<?php echo esc_attr( $this->element_name ); ?>"
			id="<?php echo esc_attr( $this->element_id ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="siteorigin-widget-shape siteorigin-widget-input"
		/>
		<?php
	}

	protected function sanitize_field_input( $value, $instance ) {
		if (
			empty( $value ) ||
			! SiteOrigin_Widget_Image_Shapes::single()->is_valid_shape( $value )
		) {
			$shape = 'circle';
		} else {
			$shape = $value;
		}
		return $shape;
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			'so-multiple-image-shape-field',
			plugin_dir_url( __FILE__ ) . 'js/image-shape-field' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION
		);

		wp_enqueue_style(
			'so-multiple-image-shape-field',
			plugin_dir_url( __FILE__ ) . 'css/image-shape-field.css',
			array(),
			SOW_BUNDLE_VERSION
		);
	}
}

<?php

/**
 * Class SiteOrigin_Widget_Field_Tabs
 */
class SiteOrigin_Widget_Field_Tabs extends SiteOrigin_Widget_Field_Base {
	/**
	 * The list of options which may be selected.
	 *
	 * @access protected
	 * @var array
	 */
	protected $tabs;

	protected function render_field( $value, $instance ) {
		if ( empty( $this->tabs ) ) {
			return;
		}
		?>
		<ul class="siteorigin-widget-tabs" <?php if ( count( $this->tabs ) == 1 ) echo 'style="display: none;"'; ?>>
			<?php
			foreach( $this->tabs as $id => $tab ) {
				?><li data-id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $tab ); ?></li><?php
			}
			?>
		</ul>
		<?php
	}

	protected function sanitize_field_input( $value, $instance ) {
		return;
	}

	function enqueue_scripts() {
		wp_enqueue_script( 'so-tabs-field', plugin_dir_url( __FILE__ ) . 'js/tabs-field' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
	}

}

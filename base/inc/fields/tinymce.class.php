<?php

class SiteOrigin_Widget_Field_TinyMCE extends SiteOrigin_Widget_Field_Text_Input_Base {
	/**
	 * The number of visible rows in the textarea.
	 *
	 * @access protected
	 * @var int
	 */
	protected $rows = 10;
	/**
	 * The editor to be displayed initially.
	 *
	 * @access protected
	 * @var string
	 */
	protected $default_editor = 'tinymce';
	/**
	 * The editor initial height. Overrides rows if it is set.
	 *
	 * @access protected
	 * @var int
	 */
	protected $editor_height;
	/**
	 * An array of filter callbacks to apply to the set of buttons which will be rendered for the editor.
	 *
	 * @access protected
	 * @var array
	 */
	protected $button_filters;

	protected function initialize() {
		parent::initialize();

		if ( !empty( $this->button_filters ) ) {
			foreach ( $this->button_filters as $filter_name => $filter ) {
				if ( preg_match( '/mce_buttons(?:_[1-4])?|quicktags_settings/', $filter_name ) && !empty( $filter ) && is_callable( $filter ) ) {
					add_filter( $filter_name, array( $this, $filter_name ), 10, 2 );
				}
			}
		}
	}

	/**
	 * @param $name
	 * @param $arguments
	 * @return array|mixed
	 */
	function __call( $name, $arguments ) {
		if ( preg_match( '/mce_buttons(?:_[1-4])?|quicktags_settings/', $name ) && !empty( $this->button_filters[$name] ) ) {
			$filter = $this->button_filters[$name];
			if ( !empty( $filter[0] ) && is_a( $filter[0], 'SiteOrigin_Widget' ) ) {
				$widget = $filter[0];
				$settings = !empty($arguments[0]) ? $arguments[0] : array();
				$editor_id = !empty($arguments[1]) ? $arguments[1] : '';
				if ( preg_match( '/widget-' . $widget->id_base . '-.*-' . $this->base_name . '/', $editor_id ) ) {
					return call_user_func( $filter, $settings, $editor_id );
				}
				else {
					return $settings;
				}
			}
		}
	}

	protected function render_field( $value, $instance ) {

		$settings = array(
			'textarea_name' => esc_attr( $this->element_name ),
			'default_editor' => $this->default_editor,
			'textarea_rows' => $this->rows,
			'editor_class' => 'siteorigin-widget-input',
			'tinymce' => array(
				'wp_skip_init' => strpos( $this->element_id, '__i__' ) != false || strpos( $this->element_id, '_id_' ) != false
			)
		);
		if( isset( $this->editor_height ) ) $settings['editor_height'] = $this->editor_height;
		preg_match( '/widget-(.+?)\[/', $this->element_name, $id_base_matches );
		$widget_id_base = empty($id_base_matches) || count($id_base_matches) < 2 ? '' : $id_base_matches[1];
		?>
		<div class="siteorigin-widget-tinymce-container"
		     data-mce-settings="<?php echo esc_attr( json_encode( $settings['tinymce'] ) ) ?>"
		     data-qt-settings="<?php echo esc_attr( json_encode( array() ) ) ?>"
		     data-widget-id-base="<?php echo esc_attr( $widget_id_base ) ?>"
			>
			<?php
			wp_editor( $value, esc_attr( $this->element_id ), $settings )
			?>
		</div>
		<?php

		if( $this->default_editor == 'html' ) {
			remove_filter( 'the_editor_content', 'wp_htmledit_pre' );
		}
		if( $this->default_editor == 'tinymce' ) {
			remove_filter( 'the_editor_content', 'wp_richedit_pre' );
		}
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'so-tinymce-field', plugin_dir_url(__FILE__) . 'js/so-tinymce-field' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery', 'editor', 'quicktags' ), SOW_BUNDLE_VERSION );
		wp_enqueue_style( 'so-tinymce-field', plugin_dir_url(__FILE__) . 'css/so-tinymce-field.css', array(), SOW_BUNDLE_VERSION );
	}

	protected function sanitize_field_input( $value ) {
		if( current_user_can( 'unfiltered_html' ) ) {
			$sanitized_value = preg_replace('/<\s*?script[^>]*?>[\s\S]*?<\s*\/\s*script\s*>/mi', '', $value);
		} else {
			$sanitized_value = wp_kses_post( $value );
		}
		$sanitized_value = balanceTags( $sanitized_value , true );
		return $sanitized_value;
	}
}
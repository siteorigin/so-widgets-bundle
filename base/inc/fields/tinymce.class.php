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
	 * The last editor selected by the user.
	 *
	 * @access protected
	 * @var string
	 */
	protected $selected_editor;
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
		if ( ! is_admin() ) {
			return;
		}
		add_filter( 'mce_buttons', array( $this, 'mce_buttons_filter' ), 10, 2 );
		add_filter( 'quicktags_settings', array( $this, 'quicktags_settings' ), 10, 2 );

		if ( !empty( $this->button_filters ) ) {
			foreach ( $this->button_filters as $filter_name => $filter ) {
				if ( preg_match( '/mce_buttons(?:_[1-4])?|quicktags_settings/', $filter_name ) && !empty( $filter ) && is_callable( $filter ) ) {
					add_filter( $filter_name, array( $this, $filter_name ), 10, 2 );
				}
			}
		}

		if( class_exists( 'WC_Shortcodes_TinyMCE_Buttons' ) ) {
			$screen = get_current_screen();
			if( !is_null( $screen ) && $screen->id != 'widgets' ) {
				add_filter( 'mce_external_plugins', array( $this, 'add_wpc_shortcode_plugin' ), 15 );
				add_filter( 'mce_buttons', array( $this, 'register_wpc_shortcode_button' ), 15 );
			}
		}

		if( class_exists( 'WC_Shortcodes_Admin' ) ) {
			$screen = get_current_screen();
			if( !is_null( $screen ) && $screen->id != 'widgets' ) {
				add_filter( 'mce_external_plugins', array( $this, 'add_wc_shortcode_plugin' ), 15 );
				add_filter( 'mce_buttons', array( $this, 'register_wc_shortcode_button' ), 15 );
			}
		}
	}

	function add_wc_shortcode_plugin( $plugins ) {
		if( isset( $plugins['woocommerce_shortcodes'] ) ) {
			return $plugins;
		}
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$editor_path = 'woocommerce-shortcodes/assets/js/editor' . $suffix . '.js';
		if( file_exists( WP_PLUGIN_DIR . '/' . $editor_path ) ) {
			$plugins['woocommerce_shortcodes'] = plugins_url( $editor_path );
		}
		return $plugins;
	}

	function register_wc_shortcode_button( $buttons ) {
		if( in_array( 'woocommerce_shortcodes', $buttons ) ) {
			return $buttons;
		}
		array_push( $buttons, '|', 'woocommerce_shortcodes' );
		return $buttons;
	}

	function add_wpc_shortcode_plugin( $plugins ) {
		global $wp_version;
		$ver = WC_SHORTCODES_VERSION;
		$wp_ver_gte_3_9 = version_compare( $wp_version, '3.9', '>=' );

		if( ! isset( $plugins['wpc_shortcodes'] ) ) {
			$shortcodes_filename = $wp_ver_gte_3_9 ? 'shortcodes-tinymce-4' : 'shortcodes_tinymce';
			$shortcodes_path = 'wc-shortcodes/includes/mce/js/' . $shortcodes_filename . '.js';
			if( file_exists( WP_PLUGIN_DIR . '/' . $shortcodes_path ) ) {
				$plugins['wpc_shortcodes'] = plugins_url( $shortcodes_path .  '?ver=' . $ver );
			}
		}

		if( ! isset( $plugins['wpc_font_awesome'] ) ) {
			$fontawesome_filename = $wp_ver_gte_3_9 ? 'font-awesome-tinymce-4' : 'font_awesome_tinymce';
			$fontawesome_path = 'wc-shortcodes/includes/mce/js/' . $fontawesome_filename . '.js';
			if( file_exists( WP_PLUGIN_DIR . '/' . $fontawesome_path ) ) {
				$plugins['wpc_font_awesome'] = plugins_url( $fontawesome_path . '?ver=' . $ver );
			}
		}

		return $plugins;
	}

	function register_wpc_shortcode_button( $buttons ) {
		if( ! in_array( 'wpc_shortcodes_button', $buttons ) ) {
			array_push( $buttons, 'wpc_shortcodes_button' );
		}
		if( ! in_array( 'wpcfontAwesomeGlyphSelect', $buttons ) ) {
			array_push( $buttons, 'wpcfontAwesomeGlyphSelect' );
		}
		return $buttons;
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

	public function mce_buttons_filter( $buttons, $editor_id ) {
		if (($key = array_search('fullscreen', $buttons)) !== false) {
			unset($buttons[$key]);
		}
		return $buttons;
	}

	public function quicktags_settings( $settings, $editor_id ) {
		$settings['buttons'] = preg_replace( '/,fullscreen/', '', $settings['buttons'] );
		$settings['buttons'] = preg_replace( '/,dfw/', '', $settings['buttons'] );
		return $settings;
	}

	protected function render_before_field( $value, $instance ) {
		$selected_editor_name = $this->get_selected_editor_field_name( $this->base_name );
		if( ! empty( $instance[ $selected_editor_name ] ) ) {
			$this->selected_editor = $instance[ $selected_editor_name ];
		}
		else {
			$this->selected_editor = $this->default_editor;
		}
		parent::render_before_field( $value, $instance );
	}


	protected function render_field( $value, $instance ) {

		$settings = array(
			'textarea_name' => esc_attr( $this->element_name ),
			'default_editor' => $this->selected_editor,
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
		<input type="hidden"
			   name="<?php echo esc_attr( $this->for_widget->so_get_field_name( $this->base_name . '_selected_editor', $this->parent_container) ) ?>"
			   class="siteorigin-widget-input siteorigin-widget-tinymce-selected-editor"
			   value="<?php echo esc_attr( $this->selected_editor ) ?>"/>
		<?php

		if( $this->selected_editor == 'html' ) {
			remove_filter( 'the_editor_content', 'wp_htmledit_pre' );
		}
		if( $this->selected_editor == 'tinymce' ) {
			remove_filter( 'the_editor_content', 'wp_richedit_pre' );
		}
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'so-tinymce-field', plugin_dir_url(__FILE__) . 'js/tinymce-field' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery', 'editor', 'quicktags' ), SOW_BUNDLE_VERSION );
		wp_enqueue_style( 'so-tinymce-field', plugin_dir_url(__FILE__) . 'css/tinymce-field.css', array(), SOW_BUNDLE_VERSION );
	}

	protected function sanitize_field_input( $value, $instance ) {
		if( current_user_can( 'unfiltered_html' ) ) {
			$sanitized_value = $value;
		} else {
			$sanitized_value = wp_kses_post( $value );
		}
		$sanitized_value = balanceTags( $sanitized_value , true );
		return $sanitized_value;
	}

	public function sanitize_instance( $instance ) {
		$selected_editor_name = $this->get_selected_editor_field_name( $this->base_name );
		if( ! empty( $instance[ $selected_editor_name ] ) ) {
			$selected_editor = $instance[ $selected_editor_name ];
			$instance[ $selected_editor_name ] = in_array( $selected_editor, array( 'tinymce', 'tmce', 'html' ) ) ? $selected_editor : $this->default_editor;
		}
		return $instance;
	}

	public function get_selected_editor_field_name( $base_name ) {
		$v_name = $base_name;
		if( strpos($v_name, '][') !== false ) {
			// Remove this splitter
			$v_name = substr( $v_name, strrpos($v_name, '][') + 2 );
		}
		return $v_name . '_selected_editor';
	}
}

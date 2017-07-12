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
	 * The editor initial height. Overrides rows if it is set.
	 *
	 * @access protected
	 * @var int
	 */
	protected $editor_height;
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
	 * An array of the buttons which will be rendered for the first toolbar of the TinyMCE editor.
	 *
	 * @access protected
	 * @var array
	 */
	protected $mce_buttons;
	/**
	 * An array of the buttons which will be rendered for the second toolbar of the TinyMCE editor.
	 *
	 * @access protected
	 * @var array
	 */
	protected $mce_buttons_2;
	/**
	 * An array of the buttons which will be rendered for the third toolbar of the TinyMCE editor.
	 *
	 * @access protected
	 * @var array
	 */
	protected $mce_buttons_3;
	/**
	 * An array of the buttons which will be rendered for the fourth toolbar of the TinyMCE editor.
	 *
	 * @access protected
	 * @var array
	 */
	protected $mce_buttons_4;
	/**
	 * An array of the buttons which will be rendered for the QuickTags editor.
	 *
	 * @access protected
	 * @var array
	 */
	protected $quicktags_buttons;
	/**
	 * An array of filter callbacks to apply to the set of buttons which will be rendered for the editor.
	 *
	 * @access protected
	 * @var array
	 */
	protected $button_filters;
	/**
	 * An array of the included plugins to enable for the TinyMCE editor.
	 *
	 * @access protected
	 * @var array
	 */
	protected $mce_plugins;
	/**
	 * An array of external plugins for the TinyMCE editor.
	 *
	 * @access protected
	 * @var array
	 */
	protected $mce_external_plugins;
	
	protected function get_default_options() {
		return array(
			'mce_buttons' => array(
				'formatselect',
				'bold',
				'italic',
				'bullist',
				'numlist',
				'blockquote',
				'alignleft',
				'aligncenter',
				'alignright',
				'link',
				'unlink',
				'wp_more',
				'wp_adv',
			),
			'mce_buttons_2' => array(
				'strikethrough',
				'hr',
				'forecolor',
				'pastetext',
				'removeformat',
				'charmap',
				'outdent',
				'indent',
				'undo',
				'redo',
				'wp_help',
			),
			'quicktags_buttons' => array(
				'strong',
				'em',
				'link',
				'block',
				'del',
				'ins',
				'img',
				'ul',
				'ol',
				'li',
				'code',
				'more',
				'close',
			),
			'mce_plugins' => array(
				'charmap',
				'colorpicker',
				'hr',
				'lists',
				'media',
				'paste',
				'tabfocus',
				'textcolor',
				'fullscreen',
				'wordpress',
				'wpautoresize',
				'wpeditimage',
				'wpemoji',
				'wpgallery',
				'wplink',
				'wpdialogs',
				'wptextpattern',
				'wpview',
			),
			'mce_external_plugins' => array(),
		);
	}
	
	protected function initialize() {
		if ( ! is_admin() ) {
			return;
		}
		
		// This is no longer necessary as the buttons can be specified in the appropriate fields, but need for backwards
		// compatibility.
		if ( !empty( $this->button_filters ) ) {
			foreach ( $this->button_filters as $filter_name => $filter ) {
				if ( preg_match( '/mce_buttons(?:_[1-4])?|quicktags_settings/', $filter_name ) && !empty( $filter ) && is_callable( $filter ) ) {
					add_filter( $filter_name, array( $this, $filter_name ), 10, 2 );
				}
			}
		}
		
		if( class_exists( 'WC_Shortcodes_TinyMCE_Buttons' ) ) {
			$this->add_wpc_shortcode_plugin();
		}
		
		if( class_exists( 'WC_Shortcodes_Admin' ) ) {
			$this->add_wc_shortcodes_plugin();
		}
	}
	
	function add_wc_shortcodes_plugin() {
		if( empty( $this->mce_external_plugins['woocommerce_shortcodes'] ) ) {
			$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$editor_path = 'woocommerce-shortcodes/assets/js/editor' . $suffix . '.js';
			if ( file_exists( WP_PLUGIN_DIR . '/' . $editor_path ) ) {
				$this->mce_external_plugins['woocommerce_shortcodes'] = plugins_url( $editor_path );
			}
		}
		
		if ( ! in_array( 'woocommerce_shortcodes', $this->mce_buttons ) ) {
			array_push( $this->mce_buttons, '|', 'woocommerce_shortcodes' );
		}
	}
	
	function add_wpc_shortcode_plugin() {
		global $wp_version;
		$ver = WC_SHORTCODES_VERSION;
		$wp_ver_gte_3_9 = version_compare( $wp_version, '3.9', '>=' );
		
		if( empty( $this->mce_external_plugins['wpc_shortcodes'] ) ) {
			$shortcodes_filename = $wp_ver_gte_3_9 ? 'shortcodes-tinymce-4' : 'shortcodes_tinymce';
			$shortcodes_path = 'wc-shortcodes/includes/mce/js/' . $shortcodes_filename . '.js';
			if( file_exists( WP_PLUGIN_DIR . '/' . $shortcodes_path ) ) {
				$this->mce_external_plugins['wpc_shortcodes'] = plugins_url( $shortcodes_path .  '?ver=' . $ver );
			}
		}
		
		if( empty( $this->mce_external_plugins['wpc_font_awesome'] ) ) {
			$fontawesome_filename = $wp_ver_gte_3_9 ? 'font-awesome-tinymce-4' : 'font_awesome_tinymce';
			$fontawesome_path = 'wc-shortcodes/includes/mce/js/' . $fontawesome_filename . '.js';
			if( file_exists( WP_PLUGIN_DIR . '/' . $fontawesome_path ) ) {
				$this->mce_external_plugins['wpc_font_awesome'] = plugins_url( $fontawesome_path . '?ver=' . $ver );
			}
		}
		
		if( ! in_array( 'wpc_shortcodes_button', $this->mce_buttons ) ) {
			array_push( $this->mce_buttons, 'wpc_shortcodes_button' );
		}
		if( ! in_array( 'wpcfontAwesomeGlyphSelect', $this->mce_buttons ) ) {
			array_push( $this->mce_buttons, 'wpcfontAwesomeGlyphSelect' );
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
	
	protected function get_input_classes() {
		$classes = parent::get_input_classes();
		$classes[] = 'wp-editor-area';
		return $classes;
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
		
		$selected_editor = in_array( $this->selected_editor, array( 'tinymce', 'tmce' ) ) ? 'tmce' : 'html';
		
		$toolbar_buttons = array(
			'mce_buttons' => apply_filters( 'mce_buttons', $this->mce_buttons, $this->element_id ),
			'mce_buttons_2' => apply_filters( 'mce_buttons_2', $this->mce_buttons_2, $this->element_id  ),
			'mce_buttons_3' => apply_filters( 'mce_buttons_3',$this->mce_buttons_3, $this->element_id  ),
			'mce_buttons_4' => apply_filters( 'mce_buttons_4',$this->mce_buttons_4, $this->element_id  ),
		);
		
		foreach ( $toolbar_buttons as $name => $buttons ) {
			$toolbar_buttons[ $name ] = is_array( $buttons ) ? implode( ',', $buttons ) : '';
		}
		
		$qt_settings = apply_filters(
			'quicktags_settings',
			array( 'buttons' => $this->quicktags_buttons ),
			$this->element_id
		);
		
		$qt_settings['buttons'] = ! empty( $qt_settings['buttons'] ) ? $qt_settings['buttons'] : array();
		$qt_settings['buttons'] = is_array( $qt_settings['buttons'] ) ? implode( ',', $qt_settings['buttons'] ) : '';
		
		$settings = array(
			'selectedEditor' => $selected_editor,
			'tinymce' => array(
				'wp_skip_init' => strpos( $this->element_id, '__i__' ) != false ||
				                  strpos( $this->element_id, '_id_' ) != false,
				'toolbar1' => $toolbar_buttons['mce_buttons'],
				'toolbar2' => $toolbar_buttons['mce_buttons_2'],
				'toolbar3' => $toolbar_buttons['mce_buttons_3'],
				'toolbar4' => $toolbar_buttons['mce_buttons_4'],
				'wpautop' => true,
				'plugins' => implode(',', $this->mce_plugins ),
				'external_plugins' => $this->mce_external_plugins,
			),
			'quicktags' => array(
				'buttons' => $qt_settings['buttons'],
			),
		);
		
		$value = apply_filters( 'the_editor_content', $value, $this->selected_editor );
		
		if ( false !== stripos( $value, 'textarea' ) ) {
			$value = preg_replace( '%</textarea%i', '&lt;/textarea', $value );
		}
		
		$media_buttons = $this->render_media_buttons( $this->element_id );
		
		?><div class="siteorigin-widget-tinymce-container"
		       data-editor-settings="<?php echo esc_attr( json_encode( $settings ) ) ?>"
		       data-media-buttons="<?php echo esc_attr( json_encode( array( 'html' => $media_buttons ) ) ) ?>">
		<textarea id="<?php echo esc_attr( $this->element_id ) ?>"
		          name="<?php echo esc_attr( $this->element_name ) ?>"
			<?php if ( isset( $this->editor_height ) ) : ?>
				style="height: <?php echo intval( $this->editor_height ) ?>px"
			<?php else : ?>
				rows="<?php echo esc_attr( $this->rows ) ?>"
			<?php endif; ?>
			<?php $this->render_data_attributes( $this->get_input_data_attributes() ) ?>
			<?php $this->render_CSS_classes( $this->get_input_classes() ) ?>
			<?php if ( ! empty( $this->placeholder ) ) echo 'placeholder="' . esc_attr( $this->placeholder ) . '"' ?>
			<?php if( ! empty( $this->readonly ) ) echo 'readonly' ?>><?php echo $value ?></textarea>
		</div>
		<input type="hidden"
		       name="<?php echo esc_attr( $this->for_widget->so_get_field_name( $this->base_name . '_selected_editor', $this->parent_container) ) ?>"
		       class="siteorigin-widget-input siteorigin-widget-tinymce-selected-editor"
		       value="<?php echo esc_attr( $this->selected_editor ) ?>"/>
		<?php
		
	}
	
	public function enqueue_scripts() {
		wp_enqueue_editor();
		wp_enqueue_script( 'so-tinymce-field', plugin_dir_url(__FILE__) . 'js/tinymce-field' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery' ), SOW_BUNDLE_VERSION );
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
			$instance[ $selected_editor_name ] = in_array( $selected_editor, array( 'tinymce', 'tmce', 'quicktags', 'html' ) ) ? $selected_editor : $this->default_editor;
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
	
	private function render_media_buttons( $editor_id ) {
		
		ob_start();
		if ( ! function_exists( 'media_buttons' ) ) {
			include( ABSPATH . 'wp-admin/includes/media.php' );
		}
		
		echo '<div id="wp-' . esc_attr( $editor_id ) . '-media-buttons" class="wp-media-buttons">';
		
		do_action( 'media_buttons', $editor_id );
		
		echo "</div>\n";
		
		return ob_get_clean();
	}
}

<?php

/**
 * Class SiteOrigin_Widget
 *
 * @author SiteOrigin <support@siteorigin.com>
 */
abstract class SiteOrigin_Widget extends WP_Widget {
	protected $form_options;
	protected $base_folder;
	protected $repeater_html;
	protected $field_ids;
	protected $frontend_scripts = array();
	protected $frontend_styles = array();
	protected $generated_css = array();

	protected $current_instance;
	protected $instance_storage;

	/**
	 * @var int How many seconds a CSS file is valid for.
	 */
	static $css_expire = 604800; // 7 days

	/**
	 *
	 * @param string $id
	 * @param string $name
	 * @param array $widget_options Optional Normal WP_Widget widget options and a few extras.
	 *   - help: A URL which, if present, causes a help link to be displayed on the Edit Widget modal.
	 *   - instance_storage: Whether or not to temporarily store instances of this widget.
	 * @param array $control_options Optional Normal WP_Widget control options.
	 * @param array $form_options Optional An array describing the form fields used to configure SiteOrigin widgets.
	 * @param mixed $base_folder Optional
	 *
	 */
	function __construct($id, $name, $widget_options = array(), $control_options = array(), $form_options = array(), $base_folder = false) {
		$this->form_options = $form_options;
		$this->base_folder = $base_folder;
		$this->repeater_html = array();
		$this->field_ids = array();

		$control_options = wp_parse_args($widget_options, array(
			'width' => 600,
		) );
		parent::WP_Widget($id, $name, $widget_options, $control_options);
		$this->initialize();
	}

	/**
	 * Get the form options and allow child widgets to modify that form.
	 *
	 * @return mixed
	 */
	function form_options( $parent = false ) {
		$form_options = $this->modify_form( $this->form_options );
		if( !empty($parent) ) {
			$form_options = $parent->modify_child_widget_form( $form_options, $this );
		}

		return apply_filters( 'siteorigin_widget_form_options', $form_options );
	}

	/**
	 * Display the widget.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$instance = $this->modify_instance($instance);

		$args = wp_parse_args( $args, array(
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '',
			'after_title' => '',
		) );

		// Add any missing default values to the instance
		$instance = $this->add_defaults($this->form_options, $instance);

		$css_name = $this->generate_and_enqueue_instance_styles( $instance );
		$this->enqueue_frontend_scripts( $instance );
		extract( apply_filters( 'siteorigin_widget_template_variables', $this->get_template_variables($instance, $args) ) );

		// Storage hash allows
		$storage_hash = '';
		if( !empty($this->widget_options['instance_storage']) ) {
			$stored_instance = $this->filter_stored_instance($instance);
			$storage_hash = substr( md5( serialize($stored_instance) ), 0, 8 );
			if( !empty( $stored_instance ) && empty( $instance['is_preview'] ) ) {
				// Store this if we have a non empty instance and are not previewing
				set_transient('sow_inst[' . $this->id_base . '][' . $storage_hash . ']', $stored_instance, 7*86400);
			}
		}

		echo $args['before_widget'];
		echo '<div class="so-widget-'.$this->id_base.' so-widget-'.$css_name.'">';
		ob_start();
		@ include siteorigin_widget_get_plugin_dir_path( $this->id_base ) . '/' . $this->get_template_dir( $instance ) . '/' . $this->get_template_name( $instance ) . '.php';
		echo apply_filters('siteorigin_widget_template', ob_get_clean(), get_class($this), $instance, $this );
		echo '</div>';
		echo $args['after_widget'];
	}

	function generate_and_enqueue_instance_styles( $instance ) {

		$this->current_instance = $instance;
		$style = $this->get_style_name( $instance );

		$upload_dir = wp_upload_dir();
		$this->clear_file_cache();

		if($style !== false) {
			$hash = $this->get_style_hash( $instance );
			$css_name = $this->id_base.'-'.$style.'-'.$hash;

			//Ensure styles aren't generated and enqueued more than once.
			$in_preview = is_preview() || is_customize_preview();
			if ( ! in_array( $css_name, $this->generated_css ) || $in_preview ) {
				if( ( isset( $instance['is_preview'] ) && $instance['is_preview'] ) || $in_preview ) {
					siteorigin_widget_add_inline_css( $this->get_instance_css( $instance ) );
				}
				else {
					if( !file_exists( $upload_dir['basedir'] . '/siteorigin-widgets/' . $css_name .'.css' ) || ( defined('SITEORIGIN_WIDGETS_DEBUG') && SITEORIGIN_WIDGETS_DEBUG ) ) {
						// Attempt to recreate the CSS
						$this->save_css( $instance );
					}

					if( file_exists( $upload_dir['basedir'] . '/siteorigin-widgets/' . $css_name .'.css' ) ) {
						if ( ! wp_style_is( $css_name ) ) {
							wp_enqueue_style(
								$css_name,
								$upload_dir['baseurl'] . '/siteorigin-widgets/' . $css_name .'.css'
							);
						}
					}
					else {
						// Fall back to using inline CSS if we can't find the cached CSS file.
						siteorigin_widget_add_inline_css( $this->get_instance_css( $instance ) );
					}
				}
				$this->generated_css[] = $css_name;
			}
		}
		else {
			$css_name = $this->id_base.'-base';
		}

		$this->current_instance = false;
		return $css_name;
	}

	/**
	 * Get an array of variables to make available to templates. By default, just return an array. Should be overwritten by child widgets.
	 *
	 * @param $instance
	 * @param $args
	 *
	 * @return array
	 */
	public function get_template_variables( $instance, $args ){
		return array();
	}

	public function sub_widget($class, $args, $instance){
		if(!class_exists($class)) return;
		$widget = new $class;

		$args['before_widget'] = '';
		$args['after_widget'] = '';

		$widget->widget($args, $instance);
	}

	/**
	 * Add default values to the instance.
	 *
	 * @param $form
	 * @param $instance
	 */
	function add_defaults($form, $instance, $level = 0){
		if( $level > 10 ) return $instance;

		foreach($form as $id => $field) {

			if($field['type'] == 'repeater' && !empty($instance[$id]) ) {

				foreach( array_keys($instance[$id]) as $i ){
					$instance[$id][$i] = $this->add_defaults( $field['fields'], $instance[$id][$i], $level + 1 );
				}

			}
			else {
				if( !isset($instance[$id]) && isset($field['default']) ) $instance[$id] = $field['default'];
			}
		}

		return $instance;
	}

	/**
	 * Display the widget form.
	 *
	 * @param array $instance
	 * @return string|void
	 */
	public function form( $instance ) {
		$this->enqueue_scripts();
		$instance = $this->modify_instance($instance);

		$form_id = 'siteorigin_widget_form_'.md5( uniqid( rand(), true ) );
		$class_name = str_replace( '_', '-', strtolower(get_class($this)) );

		?>
		<div class="siteorigin-widget-form siteorigin-widget-form-main siteorigin-widget-form-main-<?php echo esc_attr($class_name) ?>" id="<?php echo $form_id ?>" data-class="<?php echo get_class($this) ?>" style="display: none">
			<?php
			foreach( $this->form_options() as $field_name => $field) {
				$this->render_field(
					$field_name,
					$field,
					isset($instance[$field_name]) ? $instance[$field_name] : null,
					false
				);
			}
			?>
		</div>
		<div class="siteorigin-widget-form-no-styles">
			<p><strong><?php _e('This widget has scripts and styles that need to be loaded before you can use it. Please save and reload your current page.', 'siteorigin-widgets') ?></strong></p>
			<p><strong><?php _e('You will only need to do this once.', 'siteorigin-widgets') ?></strong></p>
		</div>

		<?php if( ! is_customize_preview() ) : ?>
		<div class="siteorigin-widget-preview" style="display: none">
			<a href="#" class="siteorigin-widget-preview-button button-secondary"><?php _e('Preview', 'siteorigin-widgets') ?></a>
		</div>
		<?php endif; ?>

		<?php if( !empty( $this->widget_options['help'] ) ) : ?>
			<a href="<?php echo sow_esc_url($this->widget_options['help']) ?>" class="siteorigin-widget-help-link siteorigin-panels-help-link" target="_blank"><?php _e('Help', 'siteorigin-widgets') ?></a>
		<?php endif; ?>

		<script type="text/javascript">
			( function($){
				if(typeof window.sow_repeater_html == 'undefined') window.sow_repeater_html = {};
				window.sow_repeater_html["<?php echo get_class($this) ?>"] = <?php echo json_encode($this->repeater_html) ?>;

				if(typeof $.fn.sowSetupForm != 'undefined') {
					$('#<?php echo $form_id ?>').sowSetupForm();
				}
				else {
					// Init once admin scripts have been loaded
					$( document).on('sowadminloaded', function(){
						$('#<?php echo $form_id ?>').sowSetupForm();
					});
				}
			} )( jQuery );
		</script>
		<?php
	}

	/**
	 * Enqueue the admin scripts for the widget form.
	 */
	function enqueue_scripts(){

		if( !wp_script_is('siteorigin-widget-admin') ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'siteorigin-widget-admin', plugin_dir_url(SOW_BUNDLE_BASE_FILE).'base/css/admin.css', array( 'media-views' ), SOW_BUNDLE_VERSION );


			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_media();
			wp_enqueue_script( 'siteorigin-widget-admin', plugin_dir_url(SOW_BUNDLE_BASE_FILE).'base/js/admin' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-slider' ), SOW_BUNDLE_VERSION, true );

			wp_localize_script( 'siteorigin-widget-admin', 'soWidgets', array(
				'ajaxurl' => wp_nonce_url( admin_url('admin-ajax.php'), 'widgets_action', '_widgets_nonce' ),
				'sure' => __('Are you sure?', 'siteorigin-widgets')
			) );
			global $wp_customize;
			if ( isset( $wp_customize ) ) {
				$this->footer_admin_templates();
			} else {
				add_action( 'admin_footer', array( $this, 'footer_admin_templates' ) );
			}
		}

		if( $this->using_posts_selector() ) {
			siteorigin_widget_post_selector_enqueue_admin_scripts();
		}

		// This lets the widget enqueue any specific admin scripts
		$this->enqueue_admin_scripts();
	}

	/**
	 * Display all the admin stuff for the footer
	 */
	function footer_admin_templates(){
		?>
		<script type="text/template" id="so-widgets-bundle-tpl-preview-dialog">
			<div class="siteorigin-widget-preview-dialog">
				<div class="siteorigin-widgets-preview-modal-overlay"></div>

				<div class="so-widget-toolbar">
					<h3><?php _e('Widget Preview', 'siteorigin-widgets') ?></h3>
					<a href="#" class="close"><span class="dashicons dashicons-arrow-left-alt2"></span></a>
				</div>

				<div class="so-widget-iframe">
					<iframe name="siteorigin-widget-preview-iframe" id="siteorigin-widget-preview-iframe" style="visibility: hidden"></iframe>
				</div>

				<form target="siteorigin-widget-preview-iframe" action="<?php echo wp_nonce_url( admin_url('admin-ajax.php'), 'widgets_action', '_widgets_nonce' ) ?>" method="post">
					<input type="hidden" name="action" value="so_widgets_preview">
					<input type="hidden" name="data" value="">
					<input type="hidden" name="class" value="">
				</form>

			</div>
		</script>
		<?php
	}

	/**
	 * Checks if the current widget is using a posts selector
	 *
	 * @return bool
	 */
	function using_posts_selector(){
		foreach($this->form_options as $field) {
			if(!empty($field['type']) && $field['type'] == 'posts') return true;
		}
		return false;
	}

	/**
	 * Update the widget instance.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array|void
	 */
	public function update( $new_instance, $old_instance ) {
		if( !class_exists('SiteOrigin_Widgets_Color_Object') ) require plugin_dir_path( __FILE__ ).'inc/color.php';
		$new_instance = $this->sanitize( $new_instance, $this->form_options() );
		// Remove the old CSS, it'll be regenerated on page load.
		$this->delete_css( $this->modify_instance( $new_instance ) );
		return $new_instance;
	}

	/**
	 * Save the CSS to the filesystem
	 *
	 * @param $instance
	 * @return bool|string
	 */
	private function save_css( $instance ){
		require_once ABSPATH . 'wp-admin/includes/file.php';

		if( WP_Filesystem() ) {
			global $wp_filesystem;
			$upload_dir = wp_upload_dir();

			if( !$wp_filesystem->is_dir( $upload_dir['basedir'] . '/siteorigin-widgets/' ) ) {
				$wp_filesystem->mkdir( $upload_dir['basedir'] . '/siteorigin-widgets/' );
			}

			$style = $this->get_style_name($instance);
			$hash = $this->get_style_hash( $instance );
			$name = $this->id_base.'-'.$style.'-'.$hash.'.css';

			$css = $this->get_instance_css($instance);

			if( !empty($css) ) {
				$wp_filesystem->delete($upload_dir['basedir'] . '/siteorigin-widgets/'.$name);
				$wp_filesystem->put_contents(
					$upload_dir['basedir'] . '/siteorigin-widgets/'.$name,
					$css
				);
			}

			return $hash;
		}
		else {
			return false;
		}
	}

	/**
	 * Clears CSS for a specific instance
	 */
	private function delete_css( $instance ){
		require_once ABSPATH . 'wp-admin/includes/file.php';

		if( WP_Filesystem() ) {
			global $wp_filesystem;
			$upload_dir = wp_upload_dir();

			$style = $this->get_style_name($instance);
			$hash = $this->get_style_hash( $instance );
			$name = $this->id_base.'-'.$style.'-'.$hash.'.css';

			$wp_filesystem->delete($upload_dir['basedir'] . '/siteorigin-widgets/'.$name);
		}
	}

	/**
	 * Clear all old CSS files
	 *
	 * @var bool $force Must we force a cache refresh.
	 */
	public static function clear_file_cache( $force_delete = false ){
		// Use this variable to ensure this only runs once
		static $done = false;
		if ( $done && !$force_delete ) return;

		if( !get_transient('sow:cleared') || $force_delete ) {

			require_once ABSPATH . 'wp-admin/includes/file.php';
			if( WP_Filesystem() ) {
				global $wp_filesystem;
				$upload_dir = wp_upload_dir();

				$list = $wp_filesystem->dirlist( $upload_dir['basedir'] . '/siteorigin-widgets/' );
				if ( ! empty( $list ) ) {
					foreach($list as $file) {
						if( $file['lastmodunix'] < time() - self::$css_expire || $force_delete ) {
							// Delete the file
							$wp_filesystem->delete( $upload_dir['basedir'] . '/siteorigin-widgets/' . $file['name'] );
						}
					}
				}
			}

			set_transient('sow:cleared', true, self::$css_expire);
		}

		$done = true;
	}

	/**
	 * Generate the CSS for the widget.
	 *
	 * @param $instance
	 * @return string
	 */
	public function get_instance_css( $instance ){
		if( !class_exists('lessc') ) require plugin_dir_path( __FILE__ ).'inc/lessc.inc.php';
		if( !class_exists('SiteOrigin_Widgets_Less_Functions') ) require plugin_dir_path( __FILE__ ).'inc/less-functions.php';

		$style_name = $this->get_style_name($instance);
		if( empty($style_name) ) return '';

		$less = file_get_contents( siteorigin_widget_get_plugin_dir_path( $this->id_base ).'styles/'.$style_name . '.less' );

		// Substitute the variables
		if( !class_exists('SiteOrigin_Widgets_Color_Object') ) require plugin_dir_path( __FILE__ ) . 'inc/color.php';

		// Lets widgets insert their own custom generated LESS
		$less = preg_replace_callback( '/\.widget-function\((.*)\);/', array( $this, 'less_widget_inject' ), $less );

		//handle less @import statements
		$less = preg_replace_callback( '/^@import\s+".*?\/?([\w-\.]+)";/m', array( $this, 'get_less_import_contents' ), $less );

		$vars = $this->get_less_variables($instance);
		if( !empty( $vars ) ){
			foreach($vars as $name => $value) {
				if(empty($value)) continue;

				$less = preg_replace('/\@'.preg_quote($name).' *\:.*?;/', '@'.$name.': '.$value.';', $less);
			}
		}

		$less = apply_filters( 'siteorigin_widget_styles', $less, get_class($this), $instance );

		$style = $this->get_style_name( $instance );
		$hash = $this->get_style_hash( $instance );
		$css_name = $this->id_base . '-' . $style . '-' . $hash;

		//we assume that any remaining @imports are plain css imports and should be kept outside selectors
		$css_imports = '';
		if ( preg_match_all( '/^@import.+/m', $less, $imports ) ) {
			$css_imports = implode( "\n", $imports[0] );
		}

		$less = $css_imports . "\n\n" . '.so-widget-'.$css_name.' { '.$less.' } ';

		$c = new lessc();
		$lc_functions = new SiteOrigin_Widgets_Less_Functions($this, $instance);
		$lc_functions->registerFunctions($c);

		return apply_filters( 'siteorigin_widget_instance_css', $c->compile( $less ), $instance );
	}

	private function get_less_import_contents($matches) {
		$fileName = $matches[1];
		//get file extenstion
		preg_match( '/\.\w+$/', $fileName, $ext );
		//if there is a file extension and it's not .less or .css we ignore
		if ( ! empty( $ext ) ) {
			if ( ! ( $ext[0] == '.less' || $ext[0] == '.css' ) ) {
				return '';
			}
		}
		else {
			$fileName .= '.less';
		}
		//first check local widget styles directory and then bundle less directory
		$searchPath = array(
			siteorigin_widget_get_plugin_dir_path( $this->id_base ) . 'styles/',
			plugin_dir_path( __FILE__ ) . 'less/'
		);

		foreach ( $searchPath as $dir ) {
			if ( file_exists( $dir . $fileName ) ) {
				return file_get_contents( $dir . $fileName )."\n\n";
			}
		}

		//file not found
		return '';
	}

	private function less_widget_inject($matches){
		// We're going to lazily split the arguments by comma
		$args = explode(',', $matches[1]);
		if( empty($args[0]) ) return '';

		// Shift the function name from the arguments
		$func = 'less_' . trim( array_shift($args) , '\'"');
		if( !method_exists($this, $func) ) return '';

		$args = array_map('trim', $args);
		return call_user_func( array($this, $func), $this->current_instance, $args );
	}

	/**
	 * Sanitize all the widget values. Should be used before saving widget into the database.
	 *
	 * @param $instance
	 * @param $fields
	 */
	public function sanitize( $instance, $fields = false ) {

		if( $fields === false ) {
			$fields = $this->form_options();
		}

		// There is nothing to sanitize
		if( empty($fields) ) return $instance;

		foreach( $fields as $name => $field ) {
			if( empty($instance[$name]) ) {
				$instance[$name] = false;
			}

			switch( $field['type'] ) {
				case 'select' :
				case 'radio' :
					$keys = array_keys( $field['options'] );
					if( !in_array( $instance[$name], $keys ) ) $instance[$name] = isset($field['default']) ? $field['default'] : false;
					break;

				case 'number' :
				case 'slider':
					$instance[$name] = (float) $instance[$name];
					break;

				case 'textarea':
				case 'text' :
					$instance[ $name ] = wp_kses_post( $instance[ $name ] );
					$instance[ $name ] = balanceTags( $instance[ $name ] , true );
					break;

				case 'color':
					if ( !preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $instance[$name] ) ){
						// 3 or 6 hex digits, or the empty string.
						$instance[$name] = false;
					}
					break;

				case 'media' :
					// Media values should be integer
					$instance[$name] = intval($instance[$name]);
					break;

				case 'checkbox':
					$instance[$name] = !empty($instance[$name]);
					break;

				case 'widget':
					if( !empty($field['class']) && class_exists($field['class']) ) {
						$the_widget = new $field['class'];

						if( is_a($the_widget, 'SiteOrigin_Widget') ) {
							$instance[$name] = $the_widget->update($instance[$name], $instance[$name]);
						}
					}
					break;

				case 'repeater':
					if( !empty($instance[$name]) ) {
						foreach ( $instance[ $name ] as $i => $sub_instance ) {
							$instance[ $name ][ $i ] = $this->sanitize( $sub_instance, $field['fields'] );
						}
					}
					break;

				case 'section':
					$instance[$name] = $this->sanitize($instance[$name], $field['fields']);
					break;

				default:
					$instance[$name] = sanitize_text_field($instance[$name]);
					break;
			}

			if( isset($field['sanitize']) ) {
				// This field also needs some custom sanitization
				switch($field['sanitize']) {
					case 'url':
						$instance[$name] = sow_esc_url_raw($instance[$name]);
						break;

					case 'email':
						$instance[$name] = sanitize_email($instance[$name]);
						break;
				}
			}
		}

		return $instance;
	}

	/**
	 * @param $field_name
	 * @param array $repeater
	 * @param string $repeater_append
	 * @return mixed|string
	 */
	public function so_get_field_name($field_name, $repeater = array(), $repeater_append = '[]') {
		if( empty($repeater) ) return $this->get_field_name($field_name);
		else {

			$repeater_extras = '';
			foreach($repeater as $r) {
				$repeater_extras .= '['.$r.'][#'.$r.'#]';
			}

			$name = $this->get_field_name('{{{FIELD_NAME}}}');
			$name = str_replace('[{{{FIELD_NAME}}}]', $repeater_extras.'['.esc_attr($field_name).']', $name);
			return $name;
		}
	}

	/**
	 * Get the ID of this field.
	 *
	 * @param $field_name
	 * @param array $repeater
	 * @param boolean $is_template
	 *
	 * @return string
	 */
	public function so_get_field_id( $field_name, $repeater = array(), $is_template = false ) {
		if( empty($repeater) ) return $this->get_field_id($field_name);
		else {
			$name = $repeater;
			$name[] = $field_name;
			$field_id_base = $this->get_field_id(implode('-', $name));
			if ( $is_template ) {
				return $field_id_base . '-{id}';
			}
			if ( ! isset( $this->field_ids[ $field_id_base ] ) ) {
				$this->field_ids[ $field_id_base ] = 1;
			}
			$curId = $this->field_ids[ $field_id_base ]++;

			return $field_id_base . '-' . $curId;
		}
	}

	/**
	 * Render a form field
	 *
	 * @param $name
	 * @param $field
	 * @param $value
	 * @param array $repeater
	 */
	function render_field( $name, $field, $value, $repeater = array(), $is_template = false ){
		if ( is_null( $value ) && isset( $field['default'] )) {
			 $value = $field['default'];
		}

		$wrapper_classes = array(
			'siteorigin-widget-field',
			'siteorigin-widget-field-type-' . $field['type'],
			'siteorigin-widget-field-' . $name
		);
		if( !empty( $field['state_name'] ) ) $wrapper_classes[] = 'siteorigin-widget-field-state-' . $field['state_name'];
		if( !empty( $field['hidden'] ) ) $wrapper_classes[] = 'siteorigin-widget-field-is-hidden';
		if( !empty( $field['optional'] ) ) $wrapper_classes[] = 'siteorigin-widget-field-is-optional';

		?><div class="<?php echo implode(' ', array_map('sanitize_html_class', $wrapper_classes) ) ?>"><?php

		$field_id = $this->so_get_field_id( $name, $repeater, $is_template );

		if( $field['type'] != 'repeater' && $field['type'] != 'checkbox' && $field['type'] != 'separator' && !empty($field['label']) ) {
			?>
			<label for="<?php echo $field_id ?>" class="siteorigin-widget-field-label <?php if( empty($field['hide']) ) echo 'siteorigin-widget-section-visible'; ?>">
				<?php
				echo $field['label'];
				if( !empty( $field['optional'] ) ) {
					echo ' <span class="field-optional">(' . __('Optional', 'siteorigin-panels') . ')</span>';
				}
				?>
			</label>
			<?php
		}

		switch( $field['type'] ) {
			case 'text' :
				?><input type="text" name="<?php echo $this->so_get_field_name($name, $repeater) ?>" id="<?php echo $field_id ?>" value="<?php echo esc_attr($value) ?>" <?php if ( ! empty( $field['placeholder'] ) ) echo 'placeholder="' . $field['placeholder'] . '"' ?> class="widefat siteorigin-widget-input" <?php if( ! empty( $field['readonly'] ) ) echo 'readonly' ?> /><?php
				break;

			case 'color' :
				?><input type="text" name="<?php echo $this->so_get_field_name($name, $repeater) ?>" id="<?php echo $field_id ?>" value="<?php echo esc_attr($value) ?>" <?php if ( ! empty( $field['placeholder'] ) ) echo 'placeholder="' . $field['placeholder'] . '"' ?> class="widefat siteorigin-widget-input siteorigin-widget-input-color" /><?php
				break;

			case 'number' :
				?><input type="text" name="<?php echo $this->so_get_field_name($name, $repeater) ?>" id="<?php echo $field_id ?>" value="<?php echo esc_attr($value) ?>" <?php if ( ! empty( $field['placeholder'] ) ) echo 'placeholder="' . $field['placeholder'] . '"' ?> class="widefat siteorigin-widget-input siteorigin-widget-input-number" <?php if( ! empty( $field['readonly'] ) ) echo 'readonly' ?> /><?php
				break;

			case 'textarea' :
				?><textarea type="text" name="<?php echo $this->so_get_field_name($name, $repeater) ?>" id="<?php echo $field_id ?>" <?php if ( ! empty( $field['placeholder'] ) ) echo 'placeholder="' . $field['placeholder'] . '"' ?> class="widefat siteorigin-widget-input" rows="<?php echo !empty($field['rows']) ? intval($field['rows']) : 4 ?>" <?php if( ! empty( $field['readonly'] ) ) echo 'readonly' ?>><?php echo esc_textarea($value) ?></textarea><?php
				break;

			case 'editor' :
				// The editor field doesn't actually work yet, this is just a placeholder
				?><textarea type="text" name="<?php echo $this->so_get_field_name($name, $repeater) ?>" id="<?php echo $field_id ?>" <?php if ( ! empty( $field['placeholder'] ) ) echo 'placeholder="' . $field['placeholder'] . '"' ?> class="widefat siteorigin-widget-input siteorigin-widget-input-editor" rows="<?php echo !empty($field['rows']) ? intval($field['rows']) : 4 ?>" <?php if( ! empty( $field['readonly'] ) ) echo 'readonly' ?>><?php echo esc_textarea($value) ?></textarea><?php
				break;

			case 'slider':
				?>
				<div class="siteorigin-widget-slider-value"><?php echo !empty($value) ? $value : 0 ?></div>
				<div class="siteorigin-widget-slider-wrapper">
					<div class="siteorigin-widget-value-slider"></div>
				</div>
				<input
					type="number"
					class="siteorigin-widget-input"
					name="<?php echo $this->so_get_field_name($name, $repeater) ?>"
					id="<?php echo $field_id ?>"
					value="<?php echo !empty($value) ? esc_attr($value) : 0 ?>"
					min="<?php echo isset($field['min']) ? intval($field['min']) : 0 ?>"
					max="<?php echo isset($field['max']) ? intval($field['max']) : 100 ?>"
					data-integer="<?php echo !empty( $field['integer'] ) ? 'true' : 'false' ?>" />
				<?php
				break;

			case 'select':
				?>
				<select name="<?php echo $this->so_get_field_name($name, $repeater) ?>" id="<?php echo $field_id ?>" class="siteorigin-widget-input">
					<?php
					if ( isset( $field['prompt'] ) ) {
						?>
						<option value="default" disabled="disabled" selected="selected"><?php echo esc_html( $field['prompt'] ) ?></option>
						<?php
					}
					?>

					<?php if( isset($field['options']) && !empty($field['options']) ) : ?>
						<?php foreach( $field['options'] as $key => $val ) : ?>
							<option value="<?php echo esc_attr($key) ?>" <?php selected($key, $value) ?>><?php echo esc_html($val) ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
				<?php
				break;

			case 'checkbox':
				?>
				<label for="<?php echo $field_id ?>">
					<input type="checkbox" name="<?php echo $this->so_get_field_name($name, $repeater) ?>" id="<?php echo $field_id ?>" class="siteorigin-widget-input" <?php checked( !empty( $value ) ) ?> />
					<?php echo $field['label'] ?>
				</label>
				<?php
				break;

			case 'radio':
				?>
				<?php if ( !isset($field['options']) || empty($field['options'])) return; ?>
				<?php foreach( $field['options'] as $k => $v ) : ?>
					<label for="<?php echo $field_id . '-' . $k ?>">
						<input type="radio" name="<?php echo $this->so_get_field_name($name, $repeater) ?>" id="<?php echo $field_id . '-' . $k ?>" class="siteorigin-widget-input" value="<?php echo esc_attr($k) ?>" <?php checked( $k, $value ) ?>> <?php echo esc_html($v) ?>
					</label>
				<?php endforeach; ?>
				<?php
				break;

			case 'media':
				if( version_compare( get_bloginfo('version'), '3.5', '<' ) ){
					printf(__('You need to <a href="%s">upgrade</a> to WordPress 3.5 to use media fields', 'siteorigin'), admin_url('update-core.php'));
					break;
				}

				if(!empty($value)) {
					if(is_array($value)) {
						$src = $value;
					}
					else {
						$post = get_post($value);
						$src = wp_get_attachment_image_src($value, 'thumbnail');
						if(empty($src)) $src = wp_get_attachment_image_src($value, 'thumbnail', true);
					}
				}
				else{
					$src = array('', 0, 0);
				}

				$choose_title = empty($field['choose']) ? __('Choose Media', 'siteorigin-widgets') : $field['choose'];
				$update_button = empty($field['update']) ? __('Set Media', 'siteorigin-widgets') : $field['update'];
				$library = empty($field['library']) ? 'image' : $field['library'];

				?>
				<div class="media-field-wrapper">
					<div class="current">
						<div class="thumbnail-wrapper">
							<img src="<?php echo sow_esc_url( $src[0] ) ?>" class="thumbnail" <?php if( empty( $src[0] ) ) echo "style='display:none'" ?> />
						</div>
						<div class="title"><?php if( !empty( $post ) ) echo esc_attr( $post->post_title ) ?></div>
					</div>
					<a href="#" class="media-upload-button" data-choose="<?php echo esc_attr($choose_title) ?>" data-update="<?php echo esc_attr( $update_button ) ?>" data-library="<?php echo esc_attr($library) ?>">
						<?php echo esc_html($choose_title) ?>
					</a>

					<a href="#" class="media-remove-button"><?php _e('Remove', 'siteorigin') ?></a>
				</div>

				<input type="hidden" value="<?php echo esc_attr( is_array( $value ) ? '-1' : $value ) ?>" name="<?php echo $this->so_get_field_name( $name, $repeater ) ?>" class="siteorigin-widget-input" />
				<div class="clear"></div>
				<?php
				break;

			case 'posts' :
				siteorigin_widget_post_selector_admin_form_field( is_array( $value ) ? '' : $value, $this->so_get_field_name( $name, $repeater ) );
				break;

			case 'repeater':
				if (!isset($field['fields']) || empty($field['fields'])) return;
				ob_start();
				$repeater[] = $name;
				foreach($field['fields'] as $sub_field_name => $sub_field) {
					$this->render_field(
						$sub_field_name,
						$sub_field,
						isset($value[$sub_field_name]) ? $value[$sub_field_name] : null,
						$repeater,
						true
					);
				}
				$html = ob_get_clean();

				$this->repeater_html[$name] = $html;

				$item_label = isset( $field['item_label'] ) ? $field['item_label'] : null;
				if ( ! empty( $item_label ) ) {
					// convert underscore naming convention to camelCase for javascript
					// and encode as json string
					$item_label = $this->underscores_to_camel_case( $item_label );
					$item_label = json_encode( $item_label );
				}
				$item_name = ! empty( $field['item_name'] ) ? $field['item_name'] : __( 'Item', 'siteorigin-widgets' );
				?>
				<div class="siteorigin-widget-field-repeater" data-item-name="<?php echo esc_attr( $item_name ) ?>" data-repeater-name="<?php echo esc_attr($name) ?>" <?php echo ! empty( $item_label ) ? 'data-item-label="' . esc_attr( $item_label ) . '"' : '' ?> <?php echo ! empty( $field['scroll_count'] ) ? 'data-scroll-count="' . esc_attr( $field['scroll_count'] ) . '"' : '' ?> <?php if( ! empty( $field['readonly'] ) ) echo 'readonly' ?>>
					<div class="siteorigin-widget-field-repeater-top">
						<div class="siteorigin-widget-field-repeater-expend"></div>
						<h3><?php echo $field['label'] ?></h3>
					</div>
					<div class="siteorigin-widget-field-repeater-items">
						<?php
						if( !empty( $value ) ) {
							foreach( $value as $v ) {
								?>
								<div class="siteorigin-widget-field-repeater-item ui-draggable">
									<div class="siteorigin-widget-field-repeater-item-top">
										<div class="siteorigin-widget-field-expand"></div>
										<?php if( empty( $field['readonly'] ) ) : ?>
										<div class="siteorigin-widget-field-remove"></div>
										<?php endif; ?>
										<h4><?php echo esc_html($field['item_name']) ?></h4>
									</div>
									<div class="siteorigin-widget-field-repeater-item-form">
										<?php
										foreach($field['fields'] as $sub_field_name => $sub_field) {
											$this->render_field(
												$sub_field_name,
												$sub_field,
												isset($v[$sub_field_name]) ? $v[$sub_field_name] : null,
												$repeater
											);
										}
										?>
									</div>
								</div>
								<?php
							}
						}
						?>
					</div>
					<?php if( empty( $field['readonly'] ) ) : ?>
						<div class="siteorigin-widget-field-repeater-add"><?php _e('Add', 'siteorigin-widgets') ?></div>
					<?php endif; ?>
				</div>
				<?php
				break;

			case 'widget' :
				// Create the extra form entries
				$sub_widget = new $field['class'];
				?><div class="siteorigin-widget-section <?php if( !empty($field['hide']) ) echo 'siteorigin-widget-section-hide'; ?>"><?php
				foreach( $sub_widget->form_options($this) as $sub_name => $sub_field) {
					$this->render_field(
						$name.']['.$sub_name,
						$sub_field,
						isset($value[$sub_name]) ? $value[$sub_name] : null,
						$repeater
					);
				}
				?></div><?php
				break;

			case 'icon':
				static $widget_icon_families;
				if( empty($widget_icon_families) ) $widget_icon_families = apply_filters('siteorigin_widgets_icon_families', array() );

				list($value_family, $null) = !empty($value) ? explode('-', $value, 2) : array('fontawesome', '');

				?>
				<div class="siteorigin-widget-icon-selector siteorigin-widget-field-subcontainer">
					<select class="siteorigin-widget-icon-family" >
						<?php foreach( $widget_icon_families as $family_id => $family_info ) : ?>
							<option value="<?php echo esc_attr($family_id) ?>" <?php selected($value_family, $family_id) ?>><?php echo esc_html( $family_info['name'] ) ?> (<?php echo count( $family_info['icons'] ) ?>)</option>
						<?php endforeach; ?>
					</select>

					<input type="hidden" name="<?php echo $this->so_get_field_name( $name, $repeater ) ?>" value="<?php echo esc_attr($value) ?>" class="siteorigin-widget-icon-icon siteorigin-widget-input" />

					<div class="siteorigin-widget-icon-icons"></div>
				</div>
				<?php

				break;

			case 'font':
				static $widget_font_families;
				if( empty($widget_font_families) ) {

					// Add the default fonts
					$widget_font_families = array(
						'Helvetica Neue' => 'Helvetica Neue',
						'Lucida Grande' => 'Lucida Grande',
						'Georgia' => 'Georgia',
						'Courier New' => 'Courier New',
					);

					$widget_font_families = apply_filters('siteorigin_widgets_font_families', $widget_font_families );
				}
				?>
				<div class="siteorigin-widget-font-selector siteorigin-widget-field-subcontainer">
					<select name="<?php echo $this->so_get_field_name($name, $repeater) ?>" id="<?php echo $field_id ?>" class="siteorigin-widget-input">
						<option value="default" selected="selected"><?php _e( 'Use theme font', 'siteorigin-widgets' ) ?></option>
						<?php foreach( $widget_font_families as $key => $val ) : ?>
							<option value="<?php echo esc_attr($key) ?>" <?php selected($key, $value) ?>><?php echo esc_html($val) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php
				break;

			case 'section' :
				?><div class="siteorigin-widget-section <?php if( !empty($field['hide']) ) echo 'siteorigin-widget-section-hide'; ?>"><?php
				if ( !isset($field['fields']) || empty($field['fields']) ) return;
				foreach( (array) $field['fields'] as $sub_name=> $sub_field ) {
					$this->render_field(
						$name.']['.$sub_name,
						$sub_field,
						isset($value[$sub_name]) ? $value[$sub_name] : null,
						$repeater
					);
				}
				?></div><?php
				break;

			case 'bucket' :
				// A bucket select and explore field
				?><input type="text" name="<?php echo $this->so_get_field_name($name, $repeater) ?>" id="<?php echo $field_id ?>" value="<?php echo esc_attr($value) ?>" class="widefat siteorigin-widget-input" /><?php
				break;

			default:
				?><?php _e('Unknown Field', 'siteorigin-widgets') ?><?php
				break;

		}

		if(!empty($field['description'])) {
			?><div class="siteorigin-widget-field-description"><?php echo wp_kses_post($field['description']) ?></div><?php
		}

		?></div><?php
	}


	/**
	 * Convert underscore naming convention to camel case. Useful for data to be handled by javascript.
	 *
	 * @param $array array Input array of which the keys will be transformed.
	 * @return array The transformed array with camel case keys.
	 */
	protected function underscores_to_camel_case( $array ) {
		$transformed = array();
		if ( !empty( $array ) ) {
			foreach ( $array as $key => $val ) {
				$jsKey = preg_replace_callback( '/_(.?)/', array($this, 'match_to_upper'), $key );
				$transformed[ $jsKey ] = $val;
			}
		}
		return $transformed;
	}

	private function match_to_upper( $matches ) {
		return strtoupper( $matches[1] );
	}

	/**
	 * Parse markdown
	 *
	 * @param $markdown
	 * @return string The HTML
	 */
	function parse_markdown($markdown){
		if( !class_exists('Markdown_Parser') ) include plugin_dir_path(__FILE__).'inc/markdown.php';
		$parser = new Markdown_Parser();

		return $parser->transform($markdown);
	}

	/**
	 * Get a hash that uniquely identifies this instance.
	 *
	 * @param $instance
	 * @return string
	 */
	function get_style_hash($instance) {
		$vars = method_exists($this, 'get_style_hash_variables') ? $this->get_style_hash_variables( $instance ) : $this->get_less_variables( $instance );
		return substr( md5( json_encode( $vars ) ), 0, 12 );
	}

	/**
	 * Get the template name that we'll be using to render this widget.
	 *
	 * @param $instance
	 * @return mixed
	 */
	abstract function get_template_name($instance);

	/**
	 * Get the name of the directory in which we should look for the template.
	 *
	 * @return mixed
	 */
	function get_template_dir($instance) {
		return 'tpl';
	}

	/**
	 * Get the LESS style name we'll be using for this widget.
	 *
	 * @param $instance
	 * @return mixed
	 */
	abstract function get_style_name($instance);

	/**
	 * Get any variables that need to be substituted by
	 *
	 * @param $instance
	 * @return array
	 */
	function get_less_variables($instance){
		return array();
	}

	/**
	 * Filter the variables we'll be storing in temporary storage for this instance if we're using `instance_storage`
	 *
	 * @param $instance
	 *
	 * @return mixed
	 */
	function filter_stored_instance($instance){
		return $instance;
	}

	/**
	 * Get the stored instance based on the hash.
	 *
	 * @param $hash
	 *
	 * @return object The instance
	 */
	function get_stored_instance($hash) {

		return get_transient('sow_inst[' . $this->id_base . '][' . $hash . ']');
	}

	/**
	 * This function can be overwritten to modify form values in the child widget.
	 *
	 * @param $form
	 * @return mixed
	 */
	function modify_form($form) {
		return $form;
	}


	/**
	 * This function can be overwritten to modify form values in the child widget.
	 *
	 * @param $child_widget_form
	 * @param $child_widget
	 * @return mixed
	 */
	function modify_child_widget_form($child_widget_form, $child_widget) {
		return $child_widget_form;
	}

	/**
	 * This function should be overwritten by child widgets to filter an instance. Run before rendering the form and widget.
	 *
	 * @param $instance
	 *
	 * @return mixed
	 */
	function modify_instance( $instance ){
		return $instance;
	}

	/**
	 * Can be overwritten by child widgets to make variables available to javascript via ajax calls.
	 */
	function get_javascript_variables(){ }

	/**
	 * Used by child widgets to register scripts to be enqueued for the frontend.
	 */
	function register_frontend_scripts( $scripts ){
		foreach ( $scripts as $script ) {
			if ( ! isset( $this->frontend_scripts[ $script[0] ] ) ) {
				$this->frontend_scripts[$script[0]] = $script;
			}
		}
	}

	function enqueue_registered_scripts() {
		foreach ( $this->frontend_scripts as $f_script ) {
			if ( ! wp_script_is( $f_script[0] ) ) {
				wp_enqueue_script(
					$f_script[0],
					isset( $f_script[1] ) ? $f_script[1] : false,
					isset( $f_script[2] ) ? $f_script[2] : array(),
					isset( $f_script[3] ) ? $f_script[3] : false,
					isset( $f_script[4] ) ? $f_script[4] : false
				);
			}
		}
	}

	/**
	 * Used by child widgets to register styles to be enqueued for the frontend.
	 */
	function register_frontend_styles( $styles ) {
		foreach ( $styles as $style ) {
			if ( ! isset( $this->frontend_styles[ $style[0] ] ) ) {
				$this->frontend_styles[$style[0]] = $style;
			}
		}
	}

	function enqueue_registered_styles() {
		foreach ( $this->frontend_styles as $f_style ) {
			if ( ! wp_style_is( $f_style[0] ) ) {
				wp_enqueue_style(
					$f_style[0],
					isset( $f_style[1] ) ? $f_style[1] : false,
					isset( $f_style[2] ) ? $f_style[2] : array(),
					isset( $f_style[3] ) ? $f_style[3] : false,
					isset( $f_style[4] ) ? $f_style[4] : "all"
				);
			}
		}
	}

	/**
	 * Can be overridden by child widgets to enqueue scripts and styles for the frontend, but child widgets should
	 * rather register scripts and styles using register_frontend_scripts() and register_frontend_styles(). This function
	 * will then ensure that the scripts are not enqueued more than once.
	 */
	function enqueue_frontend_scripts( $instance ){
		$this->enqueue_registered_scripts();
		$this->enqueue_registered_styles();
	}

	/**
	 * Can be overwritten by child widgets to enqueue admin scripts and styles if necessary.
	 */
	function enqueue_admin_scripts(){ }

	/**
	 * Initialize this widget in whatever way we need to. Run before rendering widget or form.
	 */
	function initialize(){ }
}
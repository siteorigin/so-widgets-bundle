<?php
/*
Plugin Name: SiteOrigin Widgets Bundle
Description: A highly customizable collection of widgets, ready to be used anywhere, neatly bundled into a single plugin.
Version: dev
Text Domain: so-widgets-bundle
Domain Path: /lang
Author: SiteOrigin
Author URI: https://siteorigin.com
Plugin URI: https://siteorigin.com/widgets-bundle/
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
*/

define( 'SOW_BUNDLE_VERSION', 'dev' );
define( 'SOW_BUNDLE_BASE_FILE', __FILE__ );

// Allow JS suffix to be pre-set.
if ( ! defined( 'SOW_BUNDLE_JS_SUFFIX' ) ) {
	define( 'SOW_BUNDLE_JS_SUFFIX', '' );
}

if ( ! function_exists( 'siteorigin_widget_get_plugin_path' ) ) {
	include plugin_dir_path( __FILE__ ) . 'base/base.php';
	include plugin_dir_path( __FILE__ ) . 'icons/icons.php';
}

if ( ! class_exists( 'SiteOrigin_Widgets_Bundle_Compatibility' ) ) {
	include_once plugin_dir_path( __FILE__ ) . 'compat/compat.php';
}

class SiteOrigin_Widgets_Bundle {
	private $widget_folders;

	/**
	 * @var array The array of default widgets.
	 */
	public static $default_active_widgets = array(
		'button' => true,
		'google-map' => true,
		'image' => true,
		'slider' => true,
		'post-carousel' => true,
		'editor' => true,
	);

	public function __construct() {
		add_action( 'admin_init', array( $this, 'admin_activate_widget' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu_init' ) );
		add_action( 'admin_init', array( $this, 'clear_file_cache' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_register_scripts' ) );

		// All the Ajax actions.
		add_action( 'wp_ajax_so_widgets_bundle_manage', array( $this, 'admin_ajax_manage_handler' ) );
		add_action( 'wp_ajax_sow_get_javascript_variables', array( $this, 'admin_ajax_get_javascript_variables' ) );

		add_action( 'wp_ajax_so_widgets_setting_form', array( $this, 'admin_ajax_settings_form' ) );
		add_action( 'wp_ajax_so_widgets_setting_save', array( $this, 'admin_ajax_settings_save' ) );

		// Initialize the widgets, but do it fairly late.
		add_action( 'init', array( $this, 'set_plugin_textdomain' ), 10, 0 );
		add_action( 'after_setup_theme', array( $this, 'get_widget_folders' ), 11 );
		add_action( 'after_setup_theme', array( $this, 'load_widget_plugins' ), 11 );

		// Add the plugin_action_links links.
		add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

		add_action( 'admin_init', array( $this, 'plugin_version_check' ) );
		add_action( 'siteorigin_widgets_version_update', array( $this, 'handle_update' ), 10, 2 );

		// Actions for clearing widget cache.
		add_action( 'switch_theme', array( $this, 'clear_widget_cache' ) );
		add_action( 'activated_plugin', array( $this, 'clear_widget_cache' ) );
		add_action( 'upgrader_process_complete', array( $this, 'clear_widget_cache' ) );

		// These filters are used to activate any widgets that are missing.
		add_filter( 'siteorigin_panels_data', array( $this, 'load_missing_widgets' ) );
		add_filter( 'siteorigin_panels_prebuilt_layout', array( $this, 'load_missing_widgets' ) );
		add_filter( 'siteorigin_panels_widget_object', array( $this, 'load_missing_widget' ), 10, 2 );

		add_filter( 'wp_enqueue_scripts', array( $this, 'register_general_scripts' ) );
		add_filter( 'wp_enqueue_scripts', array( $this, 'enqueue_active_widgets_scripts' ) );

		// Add compatibility for Autoptimize.
		if ( class_exists( 'autoptimizeMain', false ) ) {
			add_filter( 'autoptimize_filter_css_exclude', array( $this, 'include_widgets_css_in_autoptimize' ), 10, 2 );
		}
	}

	/**
	 * Get the single of this plugin.
	 *
	 * @return SiteOrigin_Widgets_Bundle
	 */
	public static function single() {
		static $single;

		if ( empty( $single ) ) {
			$single = new SiteOrigin_Widgets_Bundle();
		}

		return $single;
	}

	/**
	 * Set the text domain for the plugin.
	 *
	 * @action plugins_loaded
	 */
	public function set_plugin_textdomain() {
		load_plugin_textdomain( 'so-widgets-bundle', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * This clears the file cache.
	 *
	 * @action admin_init
	 */
	public function plugin_version_check() {
		$active_version = get_option( 'siteorigin_widget_bundle_version' );

		$is_new = empty( $active_version ) || version_compare( $active_version, SOW_BUNDLE_VERSION, '<' );
		$is_new = apply_filters( 'siteorigin_widgets_is_new_version', $is_new );

		if ( $is_new ) {
			update_option( 'siteorigin_widget_bundle_version', SOW_BUNDLE_VERSION );
			// If this is a new version, then trigger an action to let widgets handle the updates.
			do_action( 'siteorigin_widgets_version_update', SOW_BUNDLE_VERSION, $active_version );
			$this->clear_widget_cache();
		}
	}

	/**
	 * This should call any necessary functions when the plugin has been updated.
	 *
	 * @action siteorigin_widgets_version_update
	 */
	public function handle_update( $old_version, $new_version ) {
		// Always check for new widgets.
		$this->check_for_new_widgets();
	}

	/**
	 * Deletes any CSS generated by/for the widgets.
	 * Called on 'upgrader_process_complete', 'switch_theme', and 'activated_plugin' actions.
	 * Can also be called directly on the `SiteOrigin_Widgets_Bundle` singleton class.
	 *
	 * @action upgrader_process_complete Occurs after any theme, plugin or the WordPress core is updated to a new version.
	 * @action switch_theme Occurs after switching to a different theme.
	 * @action activated_plugin Occurs after a plugin has been activated.
	 */
	public function clear_widget_cache() {
		// Remove all cached CSS for SiteOrigin Widgets.

		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( function_exists( 'WP_Filesystem' ) && WP_Filesystem() ) {
			global $wp_filesystem;
			$upload_dir = wp_upload_dir();

			// Remove any old widget cache files, if they exist.
			$list = $wp_filesystem->dirlist( $upload_dir['basedir'] . '/siteorigin-widgets/' );

			if ( ! empty( $list ) ) {
				foreach ( $list as $file ) {
					// Delete the file
					$wp_filesystem->delete( $upload_dir['basedir'] . '/siteorigin-widgets/' . $file['name'] );
				}
			}

			// Alert other plugins that we've deleted all CSS files.
			do_action( 'siteorigin_widgets_stylesheet_cleared' );
		}
	}

	/**
	 * Setup and return the widget folders.
	 */
	public function check_for_new_widgets() {
		// get list of available widgets
		$widgets = array_keys( $this->get_widgets_list() );
		// get option for previously installed widgets
		$old_widgets = get_option( 'siteorigin_widgets_old_widgets' );
		// if this has never been set before, it's probably a new installation so we don't want to notify for all the widgets
		if ( empty( $old_widgets ) ) {
			update_option( 'siteorigin_widgets_old_widgets', implode( ',', $widgets ) );

			return;
		}
		$old_widgets = explode( ',', $old_widgets );
		$new_widgets = array_diff( $widgets, $old_widgets );

		if ( ! empty( $new_widgets ) ) {
			update_option( 'siteorigin_widgets_new_widgets', $new_widgets );
			update_option( 'siteorigin_widgets_old_widgets', implode( ',', $widgets ) );
		}
	}

	/**
	 * Setup and return the widget folders.
	 */
	public function get_widget_folders() {
		if ( empty( $this->widget_folders ) ) {
			// We can use this filter to add more folders to use for widgets.
			$this->widget_folders = apply_filters( 'siteorigin_widgets_widget_folders', array(
				plugin_dir_path( __FILE__ ) . 'widgets/',
			) );
		}

		return $this->widget_folders;
	}

	/**
	 * Load all the widgets if their plugins are not already active.
	 *
	 * @action plugins_loaded
	 */
	public function load_widget_plugins() {
		// Load all the widget we currently have active and filter them.
		$active_widgets = $this->get_active_widgets();
		$widget_folders = $this->get_widget_folders();

		foreach ( $active_widgets as $widget_id => $active ) {
			if ( empty( $active ) ) {
				continue;
			}

			foreach ( $widget_folders as $folder ) {
				if ( ! file_exists( $folder . $widget_id . '/' . $widget_id . '.php' ) ) {
					continue;
				}

				// Include this widget file.
				include_once $folder . $widget_id . '/' . $widget_id . '.php';
			}
		}
	}

	/**
	 * Get a list of currently active widgets.
	 *
	 * @param bool $filter
	 *
	 * @return mixed|void
	 */
	public function get_active_widgets( $filter = true ) {
		// Basic caching of the current active widgets.
		$active_widgets = wp_cache_get( 'active_widgets', 'siteorigin_widgets' );

		if ( empty( $active_widgets ) ) {
			$active_widgets = get_option( 'siteorigin_widgets_active', array() );
			$active_widgets = wp_parse_args( $active_widgets, apply_filters( 'siteorigin_widgets_default_active', self::$default_active_widgets ) );

			// Migrate any old names.
			foreach ( $active_widgets as $widget_name => $is_active ) {
				if ( substr( $widget_name, 0, 3 ) !== 'so-' ) {
					continue;
				}

				if ( preg_match( '/so-([a-z\-]+)-widget/', $widget_name, $matches ) && ! isset( $active_widgets[ $matches[1] ] ) ) {
					unset( $active_widgets[ $widget_name ] );
					$active_widgets[ $matches[1] ] = $is_active;
				}
			}

			if ( $filter ) {
				$active_widgets = apply_filters( 'siteorigin_widgets_active_widgets', $active_widgets );
			}

			wp_cache_add( 'active_widgets', $active_widgets, 'siteorigin_widgets' );
		}

		return $active_widgets;
	}

	/**
	 * Enqueue the admin page stuff.
	 */
	public function admin_enqueue_scripts( $prefix ) {
		if ( $prefix != 'plugins_page_so-widgets-plugins' ) {
			return;
		}
		wp_enqueue_style(
			'siteorigin-widgets-manage-admin',
			plugin_dir_url( __FILE__ ) . 'admin/admin.css',
			array(),
			SOW_BUNDLE_VERSION
		);

		wp_enqueue_script(
			'siteorigin-widgets-trianglify',
			plugin_dir_url( __FILE__ ) . 'admin/trianglify' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array(),
			SOW_BUNDLE_VERSION
		);

		wp_enqueue_script(
			'siteorigin-widgets-manage-admin',
			plugin_dir_url( __FILE__ ) . 'admin/admin' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array(),
			SOW_BUNDLE_VERSION
		);

		wp_localize_script( 'siteorigin-widgets-manage-admin', 'soWidgetsAdmin', array(
			'toggleUrl' => wp_nonce_url( admin_url( 'admin-ajax.php?action=so_widgets_bundle_manage' ), 'manage_so_widget' ),
		) );
	}

	/**
	 * The fallback (from ajax) URL handler for activating or deactivating a widget.
	 */
	public function admin_activate_widget() {
		if (
			current_user_can( apply_filters( 'siteorigin_widgets_admin_menu_capability', 'manage_options' ) )
			&& ! empty( $_GET['page'] )
			&& $_GET['page'] == 'so-widgets-plugins'
			&& ! empty( $_GET['widget_action'] ) && ! empty( $_GET['widget'] )
			&& isset( $_GET['_wpnonce'] )
			&& wp_verify_nonce( $_GET['_wpnonce'], 'siteorigin_widget_action' )
		) {
			switch ( $_GET['widget_action'] ) {
				case 'activate':
					$this->activate_widget( $_GET['widget'] );
					break;

				case 'deactivate':
					$this->deactivate_widget( $_GET['widget'] );
					break;
			}

			// Redirect and clear all the args
			wp_redirect( add_query_arg( array(
				'_wpnonce' => false,
				'widget_action_done' => 'true',
			) ) );
		}
	}

	/**
	 * Clear all old CSS files.
	 *
	 * @var bool Whether to forcefully clear the file cache.
	 * @var int  The maximum age of a file before it's removed.
	 */
	public static function clear_file_cache( $force_delete = false, $css_expire = 604800 ) {
		// Use this variable to ensure this only runs once per request.
		static $done = false;

		if ( $done && ! $force_delete ) {
			return;
		}

		if ( ! get_transient( 'sow:cleared' ) || $force_delete ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';

			if ( WP_Filesystem() ) {
				global $wp_filesystem;
				$upload_dir = wp_upload_dir();

				$list = $wp_filesystem->dirlist( $upload_dir['basedir'] . '/siteorigin-widgets/' );

				if ( ! empty( $list ) ) {
					foreach ( $list as $file ) {
						if ( $file['lastmodunix'] < time() - $css_expire || $force_delete ) {
							// Delete the file.
							$wp_filesystem->delete( $upload_dir['basedir'] . '/siteorigin-widgets/' . $file['name'] );

							// Alert other plugins that we've deleted a CSS file.
							do_action( 'siteorigin_widgets_stylesheet_deleted', $file['name'] );
						}
					}
				}
			}

			// Set this transient so we know when to clear all the generated CSS.
			set_transient( 'sow:cleared', true, $css_expire );
		}

		$done = true;
	}

	/**
	 * Register some common scripts used in forms.
	 */
	public function admin_register_scripts() {
		wp_register_script(
			'sowb-pikaday',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/lib/pikaday' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array(),
			'1.5.1'
		);

		wp_register_style(
			'sowb-pikaday',
			plugin_dir_url( __FILE__ ) . 'js/lib/pikaday.css'
		);

		wp_register_script(
			'select2',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/lib/select2' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			'4.1.0-rc.0'
		);

		wp_register_style(
			'select2',
			plugin_dir_url( __FILE__ ) . 'css/lib/select2.css'
		);
	}

	/**
	 * Handler for activating and deactivating widgets.
	 *
	 * @action wp_ajax_so_widgets_bundle_manage
	 */
	public function admin_ajax_manage_handler() {
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'manage_so_widget' ) ) {
			wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 403 );
		}

		if ( ! current_user_can( apply_filters( 'siteorigin_widgets_admin_menu_capability', 'manage_options' ) ) ) {
			wp_die( __( 'Insufficient permissions.', 'so-widgets-bundle' ), 403 );
		}

		if ( empty( $_POST['widget'] ) ) {
			wp_die( __( 'Invalid post.', 'so-widgets-bundle' ), 400 );
		}

		if ( ! empty( $_POST['active'] ) ) {
			$this->activate_widget( $_POST['widget'] );
		} else {
			$this->deactivate_widget( $_POST['widget'] );
		}

		// Send a kind of dummy response.
		wp_send_json( array( 'done' => true ) );
	}

	/**
	 * Handler for displaying the Widget settings form.
	 *
	 * @action wp_ajax_so_widgets_setting_form
	 */
	public function admin_ajax_settings_form() {
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'display-widget-form' ) ) {
			wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 403 );
		}

		if ( ! current_user_can( apply_filters( 'siteorigin_widgets_admin_menu_capability', 'manage_options' ) ) ) {
			wp_die( __( 'Insufficient permissions.', 'so-widgets-bundle' ), 403 );
		}

		$widget_objects = $this->get_widget_objects();

		$widget_path = empty( $_GET['id'] ) ? false : wp_normalize_path( WP_CONTENT_DIR ) . $_GET['id'];

		$widget_object = empty( $widget_objects[ $widget_path ] ) ? false : $widget_objects[ $widget_path ];

		if ( empty( $widget_object ) || ! $widget_object->has_form( 'settings' ) ) {
			wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 400 );
		}

		unset( $widget_object->widget_options['has_preview'] );

		$action_url = admin_url( 'admin-ajax.php' );
		$action_url = add_query_arg( array(
			'id' => $_GET['id'],
			'action' => 'so_widgets_setting_save',
		), $action_url );
		$action_url = wp_nonce_url( $action_url, 'save-widget-settings' );

		$value = $widget_object->get_global_settings();

		?>
		<form method="post" action="<?php echo esc_url( $action_url ); ?>" target="so-widget-settings-save">
			<?php $widget_object->form( $value, 'settings' ); ?>
		</form>
		<?php

		wp_die();
	}

	/**
	 * Handler for saving the widget settings.
	 *
	 * @action wp_ajax_so_widgets_setting_save
	 */
	public function admin_ajax_settings_save() {
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'save-widget-settings' ) ) {
			wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 403 );
		}

		if ( ! current_user_can( apply_filters( 'siteorigin_widgets_admin_menu_capability', 'manage_options' ) ) ) {
			wp_die( __( 'Insufficient permissions.', 'so-widgets-bundle' ), 403 );
		}

		$widget_objects = $this->get_widget_objects();
		$widget_path = empty( $_GET['id'] ) ? false : wp_normalize_path( WP_CONTENT_DIR ) . $_GET['id'];
		$widget_object = empty( $widget_objects[ $widget_path ] ) ? false : $widget_objects[ $widget_path ];

		if ( empty( $widget_object ) || ! $widget_object->has_form( 'settings' ) ) {
			wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 400 );
		}

		$form_values = array_values( $_POST );
		$form_values = array_shift( $form_values );
		$widget_object->save_global_settings( stripslashes_deep( array_shift( $form_values ) ) );

		wp_send_json_success();
	}

	/**
	 * Add the admin menu page.
	 *
	 * @action admin_menu
	 */
	public function admin_menu_init() {
		add_plugins_page(
			__( 'SiteOrigin Widgets', 'so-widgets-bundle' ),
			__( 'SiteOrigin Widgets', 'so-widgets-bundle' ),
			apply_filters( 'siteorigin_widgets_admin_menu_capability', 'manage_options' ),
			'so-widgets-plugins',
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Display the admin page.
	 */
	public function admin_page() {
		$widgets = $this->get_widgets_list();
		$widget_objects = $this->get_widget_objects();

		if (
			isset( $_GET['widget_action_done'] )
			&& ! empty( $_GET['widget_action'] )
			&& ! empty( $_GET['widget'] )
			&& ! empty( $widgets[ $_GET['widget'] . '/' . $_GET['widget'] . '.php' ] )
		) {
			?>
			<div class="updated">
				<p>
				<?php
				printf(
					__( '%s was %s', 'so-widgets-bundle' ),
					$widgets[ $_GET['widget'] . '/' . $_GET['widget'] . '.php' ]['Name'],
					$_GET['widget_action'] == 'activate' ? __( 'Activated', 'so-widgets-bundle' ) : __( 'Deactivated', 'so-widgets-bundle' )
				);
				?>
				</p>
			</div>
			<?php
		}

		// Enqueue all the admin page scripts.
		foreach ( $widget_objects as $widget ) {
			$widget->enqueue_scripts( 'settings' );
		}

		include plugin_dir_path( __FILE__ ) . 'admin/tpl/admin.php';
	}

	/**
	 * Get JavaScript variables for admin.
	 */
	public function admin_ajax_get_javascript_variables() {
		if ( empty( $_REQUEST['_widgets_nonce'] ) ||
			! wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) {
			wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 403 );
		}

		$widget_class = $_POST['widget'];
		global $wp_widget_factory;

		if ( empty( $wp_widget_factory->widgets[ $widget_class ] ) ) {
			wp_die( __( 'Invalid post.', 'so-widgets-bundle' ), 400 );
		}

		$widget = $wp_widget_factory->widgets[ $widget_class ];

		if ( ! method_exists( $widget, 'get_javascript_variables' ) ) {
			wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 400 );
		}

		$result = $widget->get_javascript_variables();

		wp_send_json( $result );
	}

	/**
	 * Activate a widget.
	 *
	 * @param string $widget_id The ID of the widget that we're activating.
	 * @param bool   $include   Should we include the widget, to make it available in the current request.
	 *
	 * @return bool
	 */
	public function activate_widget( $widget_id, $include = true ) {
		$exists = false;
		$widget_folders = $this->get_widget_folders();
		$widget_id = sanitize_file_name( $widget_id );

		foreach ( $widget_folders as $folder ) {
			if ( ! file_exists( $folder . $widget_id . '/' . $widget_id . '.php' ) ) {
				continue;
			}
			$exists = true;
		}

		if ( ! $exists ) {
			return false;
		}

		// There are times when we activate several widgets at once, so clear the cache.
		wp_cache_delete( 'siteorigin_widgets_active', 'options' );
		$active_widgets = $this->get_active_widgets();
		$active_widgets[ $widget_id ] = true;
		update_option( 'siteorigin_widgets_active', $active_widgets );
		wp_cache_delete( 'active_widgets', 'siteorigin_widgets' );

		// Clear the PB widgets cache.
		if ( defined( 'SITEORIGIN_PANELS_VERSION' ) ) {
			delete_transient( 'siteorigin_panels_widgets' );
			delete_transient( 'siteorigin_panels_widget_dialog_tabs' );
		}

		// If we don't want to include the widget files, then our job here is done.
		if ( ! $include ) {
			return;
		}

		// Now, lets actually include the files
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		foreach ( $widget_folders as $folder ) {
			if ( ! file_exists($folder . $widget_id . '/' . $widget_id . '.php' ) ) {
				continue;
			}
			include_once $folder . $widget_id . '/' . $widget_id . '.php';

			global $wp_widget_factory;
			if ( has_action('widgets_init' ) || ! empty( $wp_widget_factory ) ) {
				SiteOrigin_Widgets_Widget_Manager::single()->widgets_init();
			}
		}

		return true;
	}

	/**
	 * Include a widget that might not have been registered.
	 *
	 * @return bool
	 */
	public function include_widget( $widget_id ) {
		$folders = $this->get_widget_folders();

		foreach ( $folders as $folder ) {
			if ( !file_exists( $folder . $widget_id . '/' . $widget_id . '.php' ) ) {
				continue;
			}
			include_once $folder . $widget_id . '/' . $widget_id . '.php';

			return true;
		}

		return false;
	}

	/**
	 * Deactivate a widget.
	 */
	public function deactivate_widget( $widget_id ) {
		$widget_id = sanitize_file_name( $widget_id );
		$active_widgets = $this->get_active_widgets();
		$active_widgets[ $widget_id ] = false;
		update_option( 'siteorigin_widgets_active', $active_widgets );
		wp_cache_delete( 'active_widgets', 'siteorigin_widgets' );

		// Clear the PB widgets cache.
		if ( defined( 'SITEORIGIN_PANELS_VERSION' ) ) {
			delete_transient( 'siteorigin_panels_widgets' );
			delete_transient( 'siteorigin_panels_widget_dialog_tabs' );
		}
	}

	/**
	 * Gets a list of all available widgets.
	 */
	public function get_widgets_list() {
		$active = $this->get_active_widgets();
		$folders = $this->get_widget_folders();

		$default_headers = array(
			'Name' => 'Widget Name',
			'Description' => 'Description',
			'Author' => 'Author',
			'AuthorURI' => 'Author URI',
			'WidgetURI' => 'Widget URI',
			'VideoURI' => 'Video URI',
			'Documentation' => 'Documentation',
			'HideActivate' => 'Hide Activate',
		);

		$widgets = array();

		foreach ( $folders as $folder ) {
			$files = glob( $folder . '*/*.php' );

			foreach ( $files as $file ) {
				$widget = get_file_data( $file, $default_headers, 'siteorigin-widget' );
				// Skip the file if it's missing a name.
				if ( empty( $widget['Name'] ) ) {
					continue;
				}

				foreach ( array( 'Name', 'Description' ) as $field ) {
					$widget[ $field ] = translate( $widget[ $field ], 'so-widgets-bundle' );
				}

				$f = pathinfo( $file );
				$id = $f['filename'];

				$widget['ID'] = $id;
				$widget['Active'] = ! empty( $active[ $id ] );
				$widget['File'] = $file;
				$widget['HideActivate'] = ! empty( $widget['HideActivate'] );

				$widgets[ $file ] = $widget;
			}
		}

		// Sort the widgets alphabetically.
		uasort( $widgets, array( $this, 'widget_uasort' ) );

		return $widgets;
	}

	/**
	 * Get instances of all the widgets. Even ones that are not active.
	 */
	private function get_widget_objects() {
		$folders = $this->get_widget_folders();

		$widgets = array();
		$manager = SiteOrigin_Widgets_Widget_Manager::single();

		foreach ( $folders as $folder ) {
			$files = glob( wp_normalize_path( $folder ) . '*/*.php' );

			foreach ( $files as $file ) {
				$file = wp_normalize_path( $file );
				include_once $file;

				$widget_class = $manager->get_class_from_path( $file );

				if ( $widget_class && class_exists( $widget_class ) ) {
					$widgets[ $file ] = new $widget_class();
				}
			}
		}

		return $widgets;
	}

	/**
	 * Sorting function to sort widgets by name.
	 *
	 * @return int
	 */
	public function widget_uasort( $widget_a, $widget_b ) {
		return $widget_a['Name'] > $widget_b['Name'] ? 1 : -1;
	}

	/**
	 * Look in Page Builder data for any missing widgets.
	 *
	 * @return mixed
	 *
	 * @action siteorigin_panels_data
	 */
	public function load_missing_widgets( $data ) {
		if ( empty( $data['widgets'] ) ) {
			return $data;
		}

		global $wp_widget_factory;

		foreach ( $data['widgets'] as $widget ) {
			if ( empty( $widget['panels_info']['class'] ) ) {
				continue;
			}

			if ( ! empty( $wp_widget_factory->widgets[ $widget['panels_info']['class'] ] ) ) {
				continue;
			}

			$this->load_missing_widget( false, $widget['panels_info']['class'] );
		}

		return $data;
	}

	/**
	 * Attempt to load a single missing widget.
	 */
	public function load_missing_widget( $the_widget, $class ) {
		// We only want to worry about missing widgets.
		if ( ! empty( $the_widget ) ) {
			return $the_widget;
		}

		if ( preg_match( '/^(SiteOrigin_Widgets|SiteOrigin_Widget)_(\w+)_Widget/', $class, $matches ) ) {
			$name = $matches[2];
			$id = strtolower( implode( '-', array_filter( preg_split( '/(?=[A-Z])/', $name ) ) ) );

			if ( $id == 'contact-form' ) {
				// Handle the special case of the contact form widget, which is incorrectly named.
				$id = 'contact';
			}

			$this->activate_widget( $id, true );
			global $wp_widget_factory;

			if ( ! empty( $wp_widget_factory->widgets[ $class ] ) ) {
				return $wp_widget_factory->widgets[ $class ];
			}
		}

		return $the_widget;
	}

	/**
	 * Add action links.
	 */
	public function plugin_action_links( $links ) {
		if ( isset( $links['edit'] ) ) {
			unset( $links['edit'] );
		}
		$links['manage'] = '<a href="' . admin_url( 'plugins.php?page=so-widgets-plugins' ) . '">' . __( 'Manage Widgets', 'so-widgets-bundle' ) . '</a>';
		$links['support'] = '<a href="https://siteorigin.com/thread/" target="_blank" rel="noopener noreferrer">' . __( 'Support', 'so-widgets-bundle' ) . '</a>';

		if ( apply_filters( 'siteorigin_premium_upgrade_teaser', true ) && ! defined( 'SITEORIGIN_PREMIUM_VERSION' ) ) {
			$links['addons'] = '<a href="https://siteorigin.com/downloads/premium/?featured_plugin=so-widgets-bundle" style="color: #3db634" target="_blank" rel="noopener noreferrer">' . __( 'Addons', 'so-widgets-bundle' ) . '</a>';
		}

		return $links;
	}

	public function register_general_scripts() {
		wp_register_script(
			'sowb-fittext',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/sow.jquery.fittext' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			'1.2',
			true
		);

		wp_register_script(
			'dessandro-imagesLoaded',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/lib/imagesloaded.pkgd' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			'3.2.0',
			true
		);

		wp_register_script(
			'dessandro-packery',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/lib/packery.pkgd' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			'2.1.2',
			true
		);

		wp_register_script(
			'sow-google-map',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/sow.google-map' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			SOW_BUNDLE_VERSION
		);

		wp_register_script(
			'sowb-pikaday',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/lib/pikaday' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array(),
			'1.6.1'
		);

		wp_register_script(
			'sowb-pikaday-jquery',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/lib/pikaday.jquery' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'sowb-pikaday' ),
			'1.6.1'
		);

		wp_register_style(
			'sowb-pikaday',
			plugin_dir_url( __FILE__ ) . 'js/lib/pikaday.css'
		);

		wp_register_script(
			'jquery-fitvids',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/lib/jquery.fitvids' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			1.1
		);

		wp_register_script(
			'select2',
			plugin_dir_url( SOW_BUNDLE_BASE_FILE ) . 'js/lib/select2' . SOW_BUNDLE_JS_SUFFIX . '.js',
			array( 'jquery' ),
			'4.1.0-rc.0'
		);

		wp_register_style(
			'select2',
			plugin_dir_url( __FILE__ ) . 'css/lib/select2.css'
		);
	}

	/**
	 * Ensure active widgets' scripts are enqueued at the right time.
	 */
	public function enqueue_active_widgets_scripts() {
		global $wp_registered_widgets;
		$sidebars_widgets = wp_get_sidebars_widgets();

		if ( empty( $sidebars_widgets ) ) {
			return;
		}

		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( ! empty( $widgets ) && $sidebar !== 'wp_inactive_widgets' ) {
				foreach ( $widgets as $i => $id ) {
					if ( ! empty( $wp_registered_widgets[$id] ) ) {
						$widget = $wp_registered_widgets[$id]['callback'][0];

						if ( ! empty( $widget ) && is_object( $widget ) && is_subclass_of( $widget, 'SiteOrigin_Widget' ) && is_active_widget( false, false, $widget->id_base ) ) {
							/* @var $widget SiteOrigin_Widget */
							$opt_wid = get_option( 'widget_' . $widget->id_base );
							preg_match( '/-([0-9]+$)/', $id, $num_match );

							if ( ! empty( $num_match ) && isset( $num_match[1] ) ) {
								$widget_instance = $opt_wid[ $num_match[1] ];
								$widget->enqueue_frontend_scripts( $widget_instance );
								// TODO: Should be calling modify_instance here before generating the CSS.
								$widget->generate_and_enqueue_instance_styles( $widget_instance );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Enqueue scripts for registered widgets, by calling their form and/or widget functions.
	 *
	 * @param bool $front_end Whether to enqueue scripts for the front end.
	 * @param bool $admin     Whether to enqueue scripts for admin.
	 */
	public function enqueue_registered_widgets_scripts( $front_end = true, $admin = true ) {
		global $wp_widget_factory, $post;
		// Store a reference to the $post global to allow any secondary queries to run without affecting it.
		$global_post = $post;

		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			if ( ! empty( $widget_obj ) && is_object( $widget_obj ) && is_subclass_of( $widget_obj, 'SiteOrigin_Widget' ) ) {
				/* @var $widget_obj SiteOrigin_Widget */
				ob_start();

				if ( $admin ) {
					$widget_obj->enqueue_scripts( 'widget' );
				}

				if ( $front_end ) {
					// Enqueue scripts for previews.
					$widget_obj->enqueue_frontend_scripts( array() );
				}
				ob_clean();
			}
		}

		// Reset the $post global back to what it was before any secondary queries.
		$post = $global_post;
	}

	/**
	 * This removes the 'uploads/siteorigin-widgets' folder from exclusion for CSS aggregation in Autoptimize.
	 *
	 * @return string
	 */
	public function include_widgets_css_in_autoptimize( $excluded, $content ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( ! empty( $excluded ) ) {
			return $excluded;
		}

		$excl = array_map( 'trim', explode( ',', $excluded ) );
		$add = array();
		$uploads_dir = wp_upload_dir();

		foreach ( $excl as $index => $path ) {
			if (
				! empty( $path ) &&
				strpos( $uploads_dir['basedir'], untrailingslashit( $path ) ) !== false
			) {
				// Iterate over items in uploads and add to excluded, except for the 'siteorigin-widgets' folder.
				$excl[ $index ] = '';
				$uploads_items = list_files( $uploads_dir['basedir'], 1, array( 'siteorigin-widgets' ) );

				foreach ( $uploads_items as $item ) {
					$add[] = str_replace( ABSPATH, '', $item );
				}
			}
		}
		$excluded = implode( ',', array_filter( array_merge( $excl, $add ) ) );

		return $excluded;
	}

	public function is_block_editor() {
		$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		// This is for the Gutenberg plugin.
		$is_gutenberg_page = $current_screen != null &&
							 function_exists( 'is_gutenberg_page' ) &&
							 is_gutenberg_page();
		// This is for WP 5 with the integrated block editor.
		$is_block_editor = false;

		if ( ! empty( $current_screen ) && method_exists( $current_screen, 'is_block_editor' ) ) {
			$is_block_editor = $current_screen->is_block_editor();
		}

		return $is_block_editor || $is_gutenberg_page;
	}
}

// Create the initial single.
SiteOrigin_Widgets_Bundle::single();

// Initialize the Meta Box Manager. This is required to prevent a WP 6.7 notice.
$sow_meta_box_manager = null;
function siteorigin_widgets_load_meta_box_manager() {
	global $sow_meta_box_manager;

	// Confirm we haven't already set up the Meta Box Manager.
	if ( ! is_a( $sow_meta_box_manager, 'SiteOrigin_Widget_Meta_Box_Manager' ) ) {
		$sow_meta_box_manager = SiteOrigin_Widget_Meta_Box_Manager::single();
	}
}
add_action( 'init', 'siteorigin_widgets_load_meta_box_manager' );

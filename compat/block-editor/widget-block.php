<?php

class SiteOrigin_Widgets_Bundle_Widget_Block {
	public $widgetAnchor;
	public $widgetBlocks = array();
	public $hasMigrationConsent = false;
	private $so_widgets = array();

	/**
	 * Get the singleton instance
	 *
	 * @return SiteOrigin_Widgets_Bundle_Widget_Block
	 */
	public static function single() {
		static $single;

		return empty( $single ) ? $single = new self() : $single;
	}

	public function __construct() {
		$this->register_widget_block();
		$this->setup_rest_validation();

		if ( get_option( 'sowb_block_migration', false ) ) {
			$this->hasMigrationConsent = true;
		}

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_widget_block_editor_assets' ) );

		add_action( 'wp_ajax_so_widgets_block_migration_notice_consent', array( $this, 'block_migration_consent' ) );
	}

	/**
	 * Setup REST API validation for SiteOrigin widgets.
	 *
	 * This method sets up server-side validation for SiteOrigin widgets
	 * in the REST API. It retrieves all public post types and adds a
	 * REST API pre-insert action for each post type to perform
	 * server-side validation.
	 *
	 * @return void
	 */
	public function setup_rest_validation() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		if ( empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}

		foreach ( $post_types as $post_type ) {
			add_action( 'rest_pre_insert_' . $post_type, array( $this, 'server_side_validation' ), 10, 2 );
		}
	}

	/**
	 * Register SiteOrigin Widget blocks.
	 *
	 * This method registers block types for all SiteOrigin widgets
	 * that have a block name. It also registers a legacy widget block to allow for unmigrated widgets to still be rendered.
	 *
	 * @return void
	 */
	public function register_widget_block() {
		$this->prepare_widget_data();

		foreach( $this->so_widgets as $widget ) {
			if ( empty( $widget['blockName'] ) ) {
				continue;
			}

			register_block_type( 'sowb/' . $widget['blockName'], array(
				'render_callback' => array( $this, 'render_widget_block' ),
			) );
		}

		// Register legacy widget block. This will allow for unmigrated
		// widgets to still be rendered.
		register_block_type( 'sowb/widget-block', array(
			'render_callback' => array( $this, 'legacy_render_widget_block' ),
		) );

		add_filter( 'block_categories_all', array( $this, 'setup_block_category' ), 1, 1 );
	}

	/**
	 * Register a new block category for SiteOrigin widgets.
	 *
	 * @param array $categories - The existing block categories.
	 * @return array - The updated block categories.
	 */
	public function setup_block_category( $categories ) {
		$categories[] = array(
			'slug'  => 'siteorigin',
			'title' => __( 'SiteOrigin', 'so-widgets-bundle' ),
		);
		return $categories;
	}

	/**
	 * Get the icon for a widget.
	 *
	 * This function retrieves the icon for a widget by checking if an icon.svg exists
	 * in the widget's assets directory. If the file exists, it reads the SVG content
	 * directly for inline use in the block editor.
	 *
	 * The icon content can be filtered using the 'siteorigin_widgets_block_icon'
	 * filter.
	 *
	 * @param string $widget_file - The full widget file path.
	 *
	 * @return string - The SVG content of the widget's icon.
	 */
	public static function get_widget_icon( $widget_file ) {
		$icon = '';
		$widget_dir = wp_normalize_path( dirname( $widget_file ) );

		if ( file_exists( $widget_dir . '/assets/icon.svg' ) ) {
			$icon = file_get_contents( $widget_dir . '/assets/icon.svg' );
		}

		$icon = apply_filters(
			'siteorigin_widgets_block_icon',
			$icon,
			$widget_file
		);

		return $icon;
	}

	/**
	 * Convert a comma-separated string of keywords into an array.
	 *
	 * This function takes a comma-separated string of keywords,
	 * trims whitespace, and sanitizes each keyword using sanitize_title.
	 *
	 * @param string $keywords - The comma-separated string of keywords.
	 *
	 * @return array - An array of sanitized keywords.
	 */
	private function keywords_to_array( $keywords ) {
		$keywords = explode( ',', $keywords );
		$keywords = array_map( 'trim', $keywords );
		$keywords = array_map( 'sanitize_title', $keywords );

		return $keywords;
	}

	/**
	 * Prepare and store widget data in the `$so_widgets` property.
	 *
	 * Retrieves all widgets, including inactive SiteOrigin and
	 * third-party widgets, processes their metadata, and stores them
	 * in `$so_widgets`. SiteOrigin widgets are sorted to appear first.
	 *
	 * Widget metadata includes:
	 * - `name`: The name of the widget.
	 * - `class`: The PHP class name of the widget.
	 * - `description`: A brief description of the widget.
	 * - `blockName`: The block name used for registering the widget in the block editor.
	 * - `keywords`: An array of keywords associated with the widget.
	 * - `icon`: The SVG icon for the widget, if available.
	 * - `manuallyRegister`: Indicates if the widget requires manual registration.
	 */
	private function prepare_widget_data() : void {
		if ( ! empty( $this->so_widgets ) ) {
			return;
		}

		$widgets_metadata_list = SiteOrigin_Widgets_Bundle::single()->get_widgets_list();
		$widgets_manager = SiteOrigin_Widgets_Widget_Manager::single();

		$so_widgets = array();

		global $wp_widget_factory;
		$third_party_widgets = array();

		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			if (
				empty( $widget_obj ) ||
				! is_object( $widget_obj ) ||
				! is_subclass_of( $widget_obj, 'SiteOrigin_Widget' )
			) {
				continue;
			}

			$is_so_widget = false;
			$file = '';

			/** @var SiteOrigin_Widget $widget_obj */
			$author = '';
			// Try to find a widget's author from its file metadata, by matching the filename to the ID (which is derived from the filename).
			foreach ( $widgets_metadata_list as $widget_metadata ) {
				if ( $widgets_manager->get_class_from_path( wp_normalize_path( $widget_metadata['File'] ) ) == $class ) {
					$author = $widget_metadata['Author'];
					if ( ! empty( $widget_metadata['Description'] ) ) {
						$description = $widget_metadata['Description'];
					}

					$keywords = ! empty( $widget_metadata['Keywords'] ) ? self::keywords_to_array( $widget_metadata['Keywords'] ) : array();

					$file = $widget_metadata['File'];

					break;
				}
			}

			// Ensure every widget has a description.
			if ( empty( $description ) ) {
				$description = __( 'No description available.', 'so-widgets-bundle' );
			}

			$block_name = strtolower( str_replace( '_', '-', $class ) );

			// For SiteOrigin authored widgets, display the widget's name directly. For third-party widgets, append the author's name to the widget name to avoid confusion when multiple widgets have the same name.
			if (
				preg_match( '/^SiteOrigin /', $widget_obj->name ) == 1 &&
				$author == 'SiteOrigin'
			) {
				$widget_name = $widget_obj->name;
				$is_so_widget = true;
			} else {
				$widget_name = sprintf( __( '%s by %s', 'so-widgets-bundle' ), $widget_obj->name, $author );
			}

			$widget_data = array(
				'name' => esc_html( $widget_name ),
				'class' => esc_html( $class ),
				'description' => esc_html( $description ),
				'blockName' => esc_html( $block_name ),
				'keywords' => ! empty( $keywords ) ? $keywords : array(),
				'icon' => ! empty( $file ) ? self::get_widget_icon( $file ) : '',
			);

			if ( $is_so_widget ) {
				if ( strpos( $class, 'SiteOrigin_Widget' ) === 0 ) {
					$widget_data['manuallyRegister'] = true;
				}

				$so_widgets[] = $widget_data;
			} else {
				$third_party_widgets[] = $widget_data;
			}
		}

		// Sort the list of widgets so SiteOrigin widgets are at the top and then third party widgets.
		sort( $so_widgets );
		sort( $third_party_widgets );

		$this->so_widgets = array_merge( $so_widgets, $third_party_widgets );
	}

	public function enqueue_widget_block_editor_assets() {
		$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

		wp_enqueue_script(
			'sowb-register-widget-blocks',
			plugins_url( 'register-widget-blocks' . SOW_BUNDLE_JS_SUFFIX . '.js', __FILE__ ),
			array(
				'wp-blocks',
				'wp-i18n',
			),
			SOW_BUNDLE_VERSION
		);

		// Use the centralized icon system for the bundle default icon.
		$bundle_icon_path = plugin_dir_path( SOW_BUNDLE_BASE_FILE ) . 'base/css/img/bundle-icon.svg';
		$default_icon = file_exists( $bundle_icon_path ) ? file_get_contents( $bundle_icon_path ) : '';

		// Apply the same filter as the centralized system for consistency.
		$default_icon = apply_filters( 'siteorigin_widgets_block_icon', $default_icon, $bundle_icon_path );

		wp_enqueue_script(
			'sowb-widget-block',
			plugins_url( 'widget-block' . SOW_BUNDLE_JS_SUFFIX . '.js', __FILE__ ),
			array(
				'sowb-register-widget-blocks',
				// The WP 5.8 Widget Area requires a specific editor script to be used.
				is_object( $current_screen ) && $current_screen->base == 'widgets' ? 'wp-edit-widgets' : 'wp-editor',
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-components',
				'wp-compose',
				'wp-data',
			),
			SOW_BUNDLE_VERSION
		);

		wp_enqueue_style(
			'sowb-widget-block',
			plugins_url( 'widget-block.css', __FILE__ )
		);

		$this->prepare_widget_data();

		wp_localize_script(
			'sowb-widget-block',
			'sowbBlockEditorAdmin',
			array(
				'widgets' => $this->so_widgets,
				'restUrl' => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'consent' => $this->hasMigrationConsent,
				'migrationNotice' => wp_create_nonce( 'so_block_migration_consent' ),
				'categoryIcon' => plugins_url( 'assets/icon.svg', __FILE__ ),
				'defaultIcon' => $default_icon,
				'legacyNotice' => sprintf(
					__( 'For improved block navigation, individual SiteOrigin Widget Blocks are now available. The multi-select SiteOrigin Widget Block will be automatically converted sitewide to the new individual SiteOrigin Widget Block format on page save; this action requires your consent to proceed. %sFind out more about this migration%s.', 'so-widgets-bundle' ),
					'<a href="https://siteorigin.com/smarter-blocks-smoother-workflow-individual-siteorigin-widget-blocks-arrive" target="_blank" rel="noopener noreferrer">',
					'</a>'
				),
			)
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'sowb-widget-block', 'so-widgets-bundle' );
		}

		$so_widgets_bundle = SiteOrigin_Widgets_Bundle::single();
		// This is to ensure necessary scripts can be enqueued for previews.
		$so_widgets_bundle->register_general_scripts();
		$so_widgets_bundle->enqueue_registered_widgets_scripts();
	}

	public function add_widget_id( $id, $instance, $widget ) {
		return $this->widgetAnchor;
	}

	private function wpml_render_check() {
		$current_page_id = get_the_ID();
		return defined( 'ICL_LANGUAGE_CODE' ) &&
		is_numeric(
			apply_filters(
				'wpml_object_id',
				$current_page_id,
				get_post_type( $current_page_id ),
				false,
				ICL_LANGUAGE_CODE
			)
		);
	}

	/**
	 * Generate a HTML notice for an invalid Block widget class.
	 *
	 * @param string|null $widget_class The widget class name. Defaults to null.
	 *
	 * @return string The HTML notice.
	 */
	private function return_invalid_widget_class_notice( $widget_class = '' ) : string {
		// If the widget class isn't empty, add a space before it.
		if ( ! empty( $widget_class ) ) {
			$widget_class = ' ' . esc_html( $widget_class );
		}

		return
			'<div>' .
				sprintf(
					__( 'Invalid widget class%s. Please make sure the widget has been activated in %sSiteOrigin Widgets%s.', 'so-widgets-bundle' ),
					$widget_class,
					'<a href="' . esc_url( admin_url( 'plugins.php?page=so-widgets-plugins' ) ) . '">',
					'</a>'
				)
			 . '</div>';
	}

	/**
	 * Find the widget class by its block name.
	 *
	 * This function searches through the prepared widget data to find
	 * the class associated with a given block name. If the block name
	 * starts with 'sowb/', it removes that prefix before searching.
	 *
	 * @param string $block_name The block name to search for.
	 *
	 * @return string|false The widget class if found, false otherwise.
	 */
	private function find_widget_class_by_block_name( $block_name ) {
		$this->prepare_widget_data();

		// If the block_name starts with 'sowb/', remove it.
		if ( strpos( $block_name, 'sowb/' ) === 0 ) {
			$block_name = substr( $block_name, 5 );
		}

		foreach( $this->so_widgets as $widget ) {
			if ( $widget['blockName'] === $block_name ) {
				return $widget['class'];
			}
		}

		return false;
	}

	/**
	 * Retrieve the widget instance for a given class.
	 *
	 * Attempts to fetch the widget from `$wp_widget_factory`.
	 * If not found, it uses the Widget Bundle's `load_missing_widget` method.
	 *
	 * If the widget class is invalid, it tries to find a valid class using the
	 * block name and recursively calls itself. Returns an error notice
	 * if no valid widget is found.
	 *
	 * @param string $widget_class The widget class name.
	 * @param string $block_name The block name associated with the widget.
	 * This is used as a fallback.
	 *
	 * @return SiteOrigin_Widget|string The widget instance or an error notice.
	 */
	private function get_block_widget( $widget_class, $block_name ) {
		global $wp_widget_factory;

		$widget = ! empty( $wp_widget_factory->widgets[ $widget_class ] ) ?
			$wp_widget_factory->widgets[ $widget_class ] :
			false;

		// Attempt to activate the widget if it's not already active.
		if ( empty( $widget ) ) {
			$widget = SiteOrigin_Widgets_Bundle::single()->load_missing_widget(
				false,
				$widget_class
			);
		}

		// If we can't find a valid SiteOrigin widget class, we can't render it.
		if (
			empty( $widget ) ||
			! is_object( $widget ) ||
			! is_subclass_of( $widget, 'SiteOrigin_Widget' )
		) {
			// Maybe the widget class is invalid. Try finding it using its block name.
			$found_widget_class = $this->find_widget_class_by_block_name(
				$block_name
			);

			if ( $found_widget_class !== $widget_class ) {
				// We found a different widget class, try returning that widget instead.
				return $this->get_block_widget(
					$found_widget_class,
					$block_name
				);
			}

			return $this->return_invalid_widget_class_notice( $widget_class );
		}

		return $widget;
	}

	/**
	 * Determine if a valid widget class exists in block content.
	 *
	 * @param array $block_content The block content to check.
	 *
	 * @return bool True if a valid widget class exists, false otherwise.
	 */
	private function has_valid_widget_class( $block_content ): bool {
		if (
			! is_array( $block_content ) ||
			! isset( $block_content['widgetClass'] ) ||
			empty( $block_content['widgetClass'] )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Render the widget block for legacy compatibility.
	 *
	 * This function checks if the block content has a widget class.
	 * If not, it returns a notice prompting the user to select a widget type.
	 * Otherwise, it calls the `render_widget_block` method to render the widget.
	 *
	 * @param array $block_content The block content to render.
	 * @param array $block The block data.
	 * @param object $instance The widget instance data.
	 *
	 * @return string The rendered widget block content or a notice.
	 */
	public function legacy_render_widget_block( $block_content, $block, $instance ) {
		if (
			! $this->has_valid_widget_class( $block_content ) &&
			substr( $instance->parsed_block['blockName'], 0, 5 ) !== 'sowb/'
		) {
			return '<div>' .
				__( "You need to select a widget type before you'll see anything here. :)", 'so-widgets-bundle' ) .
				'</div>';
		}

		return $this->render_widget_block(
			$block_content,
			$block,
			$instance
		);
	}

	/**
	 * Render the widget block.
	 *
	 * This function renders the widget block by checking if the widget class is set.
	 * If not, it attempts to find the widget class by its block name.
	 * It then retrieves the widget instance and renders it with the provided instance data.
	 * If the widget class is invalid or not found, it returns an error notice.
	 *
	 * @param array $block_content The block content to render.
	 * @param array $block The block data.
	 * @param object $instance The widget instance data.
	 *
	 * @return string The rendered widget block content or an error notice.
	 */
	public function render_widget_block( $block_content, $block, $instance ) {
		if ( ! $this->has_valid_widget_class( $block_content ) ) {
			$block_content['widgetClass'] = $this->find_widget_class_by_block_name( $instance->name );

			if ( ! $this->has_valid_widget_class( $block_content ) ) {
				return $this->return_invalid_widget_class_notice();
			}
		}

		$widget = $this->get_block_widget(
			$block_content['widgetClass'],
			$instance->name
		);

		if ( ! is_object( $widget ) ) {
			return $this->return_invalid_widget_class_notice( $block_content['widgetClass'] );
		}

		// Support for Additional CSS classes.
		$add_custom_class_name = function ( $class_names ) use ( $block_content ) {
			if ( ! empty( $block_content['className'] ) ) {
				$class_names = array_merge( $class_names, explode( ' ', $block_content['className'] ) );
			}

			return $class_names;
		};

		$GLOBALS['SITEORIGIN_WIDGET_BLOCK_RENDER'] = true;
		$instance = $block_content['widgetData'];
		add_filter( 'siteorigin_widgets_wrapper_classes_' . $widget->id_base, $add_custom_class_name );

		ob_start();

		$always_render_widget_list = array(
			'SiteOrigin_Widget_PostCarousel_Widget',
			'SiteOrigin_Widgets_ContactForm_Widget',
			'SiteOrigin_Widget_Blog_Widget',
		);

		/*
		* Generate widget markup if:
		* - No pre-generated widgetMarkup exists.
		* - widgetMarkup contains "No widget preview available".
		* - POST data exists (widget settings likely changed).
		* - Widget is in always_render_widget_list.
		* - Widget excluded via siteorigin_widgets_block_exclude_widget filter.
		* - Active WPML translation exists.
		*/
		if (
			(
				empty( $block_content['widgetMarkup'] ) ||
				// Does widgetMarkup contain the string No widget preview available?
				strpos( $block_content['widgetMarkup'], __( 'No widget preview available.', 'so-widgets-bundle' ) ) !== false
			) ||
			! empty( $_POST ) ||
			in_array( $block_content['widgetClass'], $always_render_widget_list ) ||
			apply_filters( 'siteorigin_widgets_block_exclude_widget', false, $block_content['widgetClass'], $instance ) ||
			$this->wpml_render_check()
		) {
			// Add anchor to widget wrapper.
			if ( ! empty( $block_content['anchor'] ) ) {
				$this->widgetAnchor = $block_content['anchor'];
				add_filter( 'siteorigin_widgets_wrapper_id_' . $widget->id_base, array( $this, 'add_widget_id' ), 10, 3 );
			}

			/* @var $widget SiteOrigin_Widget */
			$instance = $widget->update( $instance, $instance );
			$widget->widget( array(
				'before_widget' => '',
				'after_widget' => '',
				'before_title' => '<h3 class="widget-title">',
				'after_title' => '</h3>',
			), $instance );

			if ( ! empty( $block_content['anchor'] ) ) {
				remove_filter( 'siteorigin_widgets_wrapper_id_' . $widget->id_base, array( $this, 'add_widget_id' ), 10 );
			}
		} else {
			$widget->generate_and_enqueue_instance_styles( $instance );
			$widget->enqueue_frontend_scripts( $instance );

			// Check if this widget uses any icons that need to be enqueued.
			if ( ! empty( $block_content['widgetIcons'] ) ) {
				$widget_icon_families = apply_filters( 'siteorigin_widgets_icon_families', array() );

				foreach ( $block_content['widgetIcons'] as $icon_font ) {
					if ( ! wp_style_is( $icon_font ) ) {
						$font_family = explode( 'siteorigin-widget-icon-font-', $icon_font )[1];
						wp_enqueue_style( $icon_font, $widget_icon_families[ $font_family ]['style_uri'] );
					}
				}
			}
			echo $block_content['widgetMarkup'];
		}

		$rendered_widget = ob_get_clean();
		remove_filter( 'siteorigin_widgets_wrapper_classes_' . $widget->id_base, $add_custom_class_name );
		unset( $GLOBALS['SITEORIGIN_WIDGET_BLOCK_RENDER'] );
		return $rendered_widget;
	}

	public function server_side_validation( $prepared_post, $request ) {
		if ( empty( $prepared_post->post_content ) ) {
			return $prepared_post;
		}

		$blocks = parse_blocks( $prepared_post->post_content );
		if ( empty( $blocks ) ) {
			return $prepared_post;
		}

		foreach( $blocks as &$block ) {
			$block = $this->sanitize_blocks( $block, true );
		}
		$prepared_post->post_content = serialize_blocks( $blocks );

		return $prepared_post;
	}

	public function sanitize_blocks( $block ) {
		if ( is_wp_error( $block ) ) {
			return rest_ensure_response( $block );
		}

		if (
			! empty( $block['blockName'] ) &&
			$block['blockName'] === 'sowb/'
		) {
			$block = $this->sanitize_block( $block );
		}

		if (
			is_array( $block['innerBlocks'] ) &&
			! empty( $block['innerBlocks'] )
		) {
			foreach( $block['innerBlocks'] as $i => $inner ) {
				$block['innerBlocks'][$i] = $this->sanitize_blocks( $inner );
			}
		}

		return $block;
	}

	public function sanitize_block( $block ) {
		if (
			empty( $block['attrs'] ) ||
			empty( $block['attrs']['widgetClass'] )
		) {
			return $block;
		}

		$rendered_widget = $this->get_widget_preview( $block['attrs'], false );
		if ( is_wp_error( $rendered_widget ) ) {
			return rest_ensure_response( $rendered_widget );
		}

		if ( empty( $rendered_widget ) ) {
			return new WP_Error( 'rest_invalid_param', __( 'Invalid Widgets Bundle data', 'so-widgets-bundle' ), array( 'status' => 400 ) );
		}

		$block['attrs'] = $rendered_widget;
		return $block;
	}

	public function get_widget_preview( $block, $just_html = true ) {
		$widget_class = $block['widgetClass'];
		$widget_data = $block['widgetData'];

		$widget = SiteOrigin_Widgets_Widget_Manager::get_widget_instance( $widget_class );
		// Attempt to activate the widget if it's not already active.
		if ( ! empty( $widget_class ) && empty( $widget ) ) {
			$widget = SiteOrigin_Widgets_Bundle::single()->load_missing_widget( false, $widget_class );
		}

		// This ensures styles are added inline.
		add_filter( 'siteorigin_widgets_is_preview', '__return_true' );
		$GLOBALS[ 'SO_WIDGETS_BUNDLE_PREVIEW_RENDER' ] = true;

		$valid_widget_class = ! empty( $widget ) &&
							  is_object( $widget ) &&
							  is_subclass_of( $widget, 'SiteOrigin_Widget' );

		if ( $valid_widget_class && ! empty( $widget_data ) ) {
			ob_start();
			// Add anchor to widget wrapper.
			if ( ! empty( $block['anchor'] ) ) {
				$this->widgetAnchor = $block['anchor'];
				add_filter( 'siteorigin_widgets_wrapper_id_' . $widget->id_base, array( $this, 'add_widget_id' ), 10, 3 );
			}
			/* @var $widget SiteOrigin_Widget */
			$instance = $widget->update( $widget_data, $widget_data );
			$widget->widget( array(), $instance );
			$rendered_widget = array();
			$rendered_widget['html'] = ob_get_clean();

			if ( ! empty( $block['anchor'] ) ) {
				remove_filter( 'siteorigin_widgets_wrapper_id_' . $widget->id_base, array( $this, 'add_widget_id' ), 10 );
			}

			// Check if this widget loaded any icons, and if it has, store them.
			$styles = wp_styles();

			if ( ! empty( $styles->queue ) ) {
				$rendered_widget['widgetIcons'] = array();

				foreach ( $styles->queue as $style ) {
					if ( strpos( $style, 'siteorigin-widget-icon-font' ) !== false ) {
						$rendered_widget['widgetIcons'][] = $style;
					}
				}
			}
		} else {
			if ( empty( $valid_widget_class ) ) {
				$rendered_widget = new WP_Error(
					400,
					'Invalid or missing widget class: ' . $widget_class,
					array(
						'status' => 400,
					)
				);
			} elseif ( empty( $widget_data ) ) {
				$rendered_widget = new WP_Error(
					400,
					'Unable to render preview. Invalid or missing widget data.',
					array(
						'status' => 400,
					)
				);
			}
		}

		unset( $GLOBALS['SO_WIDGETS_BUNDLE_PREVIEW_RENDER'] );

		if ( $just_html || is_wp_error( $rendered_widget ) ) {
			return $rendered_widget;
		}

		// If there's a style tag, we can't set set widgetMarkup.
		if ( strpos( $rendered_widget['html'], '<style' ) !== false ) {
			$rendered_widget['widgetMarkup'] = '';
		} else {
			$rendered_widget['widgetMarkup'] = $rendered_widget['html'];
		}

		return array(
			'widgetClass' => $widget_class,
			'widgetData' => $widget_data,
			'widgetMarkup' => $rendered_widget['widgetMarkup'],
			'html' => $rendered_widget['html'],
			'widgetIcons' => isset( $rendered_widget['css'] ) ? $rendered_widget['widgetIcons'] : array(),
		);
	}

	public function block_migration_consent() {
		if (
			! empty( $_POST['nonce'] ) &&
			! wp_verify_nonce( $_REQUEST['nonce'], 'so_block_migration_consent' )
		) {
			die();
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die();
		}

		update_option(
			'sowb_block_migration',
			(int) get_current_user_id(),
			false
		);
	}
}

SiteOrigin_Widgets_Bundle_Widget_Block::single();

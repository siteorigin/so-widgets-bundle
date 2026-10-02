<?php

require_once plugin_dir_path( __FILE__ ) . 'widget-describer.php';

/**
 * Widgets Bundle abilities for the WordPress Abilities API (WP 6.9+).
 *
 * Registers two read-only abilities:
 *
 *   - sowb/widget-list      Lists the active Widgets Bundle widgets.
 *   - sowb/widget-describe  Describes one active widget's form as a JSON schema.
 *
 * Both abilities resolve only widgets that are active and already loaded.
 * They never activate a widget, include a widget file, or change site
 * configuration. Both require the edit_posts capability.
 *
 * Contract for later abilities:
 *
 * - This class owns the `so-widgets-bundle` ability category and the
 *   `sowb/widget-describe` ability. Do not register either again elsewhere:
 *   a duplicate registration fails with _doing_it_wrong().
 * - The output keys of both abilities and the error codes
 *   `sowb_cannot_read_widgets` and `sowb_widget_unavailable` are stable.
 *   Add keys only. Never rename or remove them.
 *
 * @api
 */
class SiteOrigin_Widgets_Abilities {
	const CATEGORY = 'so-widgets-bundle';

	/**
	 * Get the singleton instance.
	 *
	 * @return SiteOrigin_Widgets_Abilities
	 */
	public static function single(): self {
		static $single;

		if ( empty( $single ) ) {
			$single = new self();
		}

		return $single;
	}

	public function __construct() {
		// Categories must exist before the abilities that reference them.
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_ability_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register the Widgets Bundle ability category.
	 */
	public function register_ability_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label' => __( 'SiteOrigin Widgets Bundle', 'so-widgets-bundle' ),
				'description' => __( 'Discover and describe SiteOrigin Widgets Bundle widgets.', 'so-widgets-bundle' ),
			)
		);
	}

	/**
	 * Register the Widgets Bundle abilities.
	 */
	public function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$meta = array(
			'annotations' => array(
				'readonly' => true,
				'destructive' => false,
				'idempotent' => true,
			),
			'show_in_rest' => true,
			'mcp' => array( 'public' => true ),
		);

		$entry_properties = array(
			'id' => array(
				'type' => 'string',
				'description' => __( 'Widget id, for example hero.', 'so-widgets-bundle' ),
			),
			'class' => array(
				'type' => 'string',
				'description' => __( 'Widget PHP class.', 'so-widgets-bundle' ),
			),
			'name' => array(
				'type' => 'string',
				'description' => __( 'Widget name.', 'so-widgets-bundle' ),
			),
			'description' => array(
				'type' => 'string',
				'description' => __( 'Widget description.', 'so-widgets-bundle' ),
			),
			'block_name' => array(
				'type' => array( 'string', 'null' ),
				'description' => __( 'Block name of the widget, or null when the widget has no registered block.', 'so-widgets-bundle' ),
			),
		);
		$entry_required = array( 'id', 'class', 'name', 'description', 'block_name' );

		wp_register_ability(
			'sowb/widget-list',
			array(
				'label' => __( 'List widgets', 'so-widgets-bundle' ),
				'description' => __( 'Lists the active SiteOrigin Widgets Bundle widgets with their id, PHP class, name, description and block name.', 'so-widgets-bundle' ),
				'category' => self::CATEGORY,
				'input_schema' => array(
					'type' => 'object',
					'additionalProperties' => false,
					'default' => array(),
				),
				'output_schema' => array(
					'type' => 'object',
					'properties' => array(
						'widgets' => array(
							'type' => 'array',
							'items' => array(
								'type' => 'object',
								'properties' => $entry_properties,
								'required' => $entry_required,
							),
						),
					),
					'required' => array( 'widgets' ),
				),
				'execute_callback' => array( $this, 'widget_list' ),
				'permission_callback' => array( $this, 'can_read_widgets' ),
				'meta' => $meta,
			)
		);

		wp_register_ability(
			'sowb/widget-describe',
			array(
				'label' => __( 'Describe widget', 'so-widgets-bundle' ),
				'description' => __( 'Describes the form of one active SiteOrigin Widgets Bundle widget as a JSON schema. Each field carries x-sowb-field-type with its raw field type; text fields carry x-sowb-text.', 'so-widgets-bundle' ),
				'category' => self::CATEGORY,
				'input_schema' => array(
					'type' => 'object',
					'properties' => array(
						'widget' => array(
							'type' => 'string',
							'minLength' => 1,
							'description' => __( 'Widget id (e.g. hero), PHP class, or sowb/ block name, as returned by sowb/widget-list.', 'so-widgets-bundle' ),
						),
					),
					'required' => array( 'widget' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type' => 'object',
					'properties' => array_merge(
						$entry_properties,
						array(
							'schema' => array(
								'type' => 'object',
								'description' => __( 'JSON schema of the widget form.', 'so-widgets-bundle' ),
							),
						)
					),
					'required' => array_merge( $entry_required, array( 'schema' ) ),
				),
				'execute_callback' => array( $this, 'widget_describe' ),
				'permission_callback' => array( $this, 'can_read_widgets' ),
				'meta' => $meta,
			)
		);
	}

	/**
	 * Check that the current user can read widget information.
	 *
	 * @return true|WP_Error
	 */
	public function can_read_widgets() {
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return new WP_Error(
			'sowb_cannot_read_widgets',
			__( 'Sorry, you are not allowed to read SiteOrigin widgets.', 'so-widgets-bundle' )
		);
	}

	/**
	 * Build the catalog of active, already loaded Widgets Bundle widgets.
	 *
	 * A widget is listed only when it is active in the Widgets Bundle settings
	 * and its class is already loaded. A loaded class alone is not enough: a
	 * widget can load another widget's class without that widget being active.
	 *
	 * @return array[] Entries of { id, class, name, description, block_name|null }.
	 */
	public function get_active_widgets(): array {
		if ( ! class_exists( 'SiteOrigin_Widgets_Bundle' ) ) {
			return array();
		}

		$manager = SiteOrigin_Widgets_Widget_Manager::single();
		$block_registry = class_exists( 'WP_Block_Type_Registry' ) ? WP_Block_Type_Registry::get_instance() : null;
		$widgets = array();

		foreach ( SiteOrigin_Widgets_Bundle::single()->get_widgets_list() as $entry ) {
			if ( empty( $entry['Active'] ) ) {
				continue;
			}

			$class = $manager->get_class_from_path( wp_normalize_path( $entry['File'] ) );

			if (
				empty( $class ) ||
				! class_exists( $class, false ) ||
				! SiteOrigin_Widgets_Widget_Manager::get_widget_instance( $class ) instanceof SiteOrigin_Widget
			) {
				continue;
			}

			$block_name = 'sowb/' . strtolower( str_replace( array( '_', '\\' ), '-', $class ) );

			if ( empty( $block_registry ) || ! $block_registry->is_registered( $block_name ) ) {
				$block_name = null;
			}

			$widgets[] = array(
				'id' => (string) $entry['ID'],
				'class' => $class,
				'name' => (string) $entry['Name'],
				'description' => (string) $entry['Description'],
				'block_name' => $block_name,
			);
		}

		return $widgets;
	}

	/**
	 * Execute sowb/widget-list.
	 *
	 * @param array $input Unused.
	 *
	 * @return array|WP_Error { widgets: entry[] }
	 */
	public function widget_list( $input = array() ) {
		$permission = $this->can_read_widgets();

		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		return array(
			'widgets' => $this->get_active_widgets(),
		);
	}

	/**
	 * Execute sowb/widget-describe.
	 *
	 * Resolves the widget by exact id, class or block name from the active
	 * widget catalog only.
	 *
	 * @param array $input { widget: string }
	 *
	 * @return array|WP_Error The catalog entry plus { schema }, or WP_Error
	 *                        'sowb_widget_unavailable' (404).
	 */
	public function widget_describe( $input ) {
		$permission = $this->can_read_widgets();

		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$widget = is_array( $input ) && isset( $input['widget'] ) && is_string( $input['widget'] ) ? trim( $input['widget'] ) : '';

		if ( $widget !== '' ) {
			foreach ( $this->get_active_widgets() as $entry ) {
				if (
					$widget !== $entry['id'] &&
					$widget !== $entry['class'] &&
					$widget !== $entry['block_name']
				) {
					continue;
				}

				$instance = SiteOrigin_Widgets_Widget_Manager::get_widget_instance( $entry['class'] );

				if ( ! $instance instanceof SiteOrigin_Widget ) {
					break;
				}

				$entry['schema'] = SiteOrigin_Widgets_Widget_Describer::single()->get_schema( $instance );

				return $entry;
			}
		}

		return new WP_Error(
			'sowb_widget_unavailable',
			__( 'This widget is not an active SiteOrigin Widgets Bundle widget. Call sowb/widget-list for the available widgets. A site administrator can activate widgets at Plugins > SiteOrigin Widgets.', 'so-widgets-bundle' ),
			array( 'status' => 404 )
		);
	}
}

SiteOrigin_Widgets_Abilities::single();

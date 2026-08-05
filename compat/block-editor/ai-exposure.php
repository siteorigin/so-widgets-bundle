<?php

/**
 * SiteOrigin Widgets Bundle — AI Exposure.
 *
 * Public API — premium-addon-facing. Provides the shared recursive walk over
 * standalone `sowb/*` widget blocks in a post's content, the shared reader
 * consumed byte-identically by the `sowb/widget-get` ability and the read-only
 * REST route below. Core ships ZERO AI vendor logic: no API keys, model calls,
 * or prompts.
 *
 * Route:    GET /wp-json/sowb/v1/posts/<id>/widgets
 * Auth:     requires `edit_post` on <id>.
 * READ-ONLY. No write surface here; writes go exclusively through the
 * `sowb/widget-update` ability (compat/block-editor/abilities.php), which
 * routes through the untrusted sanitize chokepoint
 * (SiteOrigin_Widgets_Bundle_Widget_Block::sanitize_widget_block_untrusted()).
 *
 * Response shape (committed public API):
 *   {
 *     "post_id": int,
 *     "widget_count": int,
 *     "unscanned_refs": bool,
 *     "widgets": [ { "widget_index": int, "block_name": string,
 *                    "widget_class": string|null, "widget_name": string|null,
 *                    "active": bool, "anchor": string|null,
 *                    "class_name": string|null, "widget_data": {...} }, ... ]
 *   }
 *
 * Targeting scope: the target post's own content only. The walk recurses into
 * the serialized innerBlocks of container blocks (Group, Columns, Cover,
 * Query Loop templates, ...). `core/block` (reusable block) references are
 * NEVER dereferenced — their presence is reported via `unscanned_refs`. FSE
 * templates are out of scope.
 */
class SiteOrigin_Widgets_Bundle_AI_Exposure {
	/**
	 * @var SiteOrigin_Widgets_Bundle_AI_Exposure
	 */
	private static $single;

	/**
	 * Get the singleton instance.
	 *
	 * @return SiteOrigin_Widgets_Bundle_AI_Exposure
	 */
	public static function single() {
		if ( empty( self::$single ) ) {
			self::$single = new self();
		}

		return self::$single;
	}

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the read-only AI exposure REST route.
	 *
	 * Public API — premium-addon-facing. The namespace `sowb/v1` and the route
	 * path `/posts/(?P<id>\d+)/widgets` are load-bearing; a premium addon
	 * binds to them. Do not rename.
	 */
	public function register_routes() {
		register_rest_route(
			'sowb/v1',
			'/posts/(?P<id>\d+)/widgets',
			array(
				'methods'             => WP_REST_Server::READABLE, // Read-only — no write surface.
				'callback'            => array( $this, 'rest_get_widgets' ),
				'permission_callback' => array( $this, 'rest_get_widgets_permission' ),
				'args'                => array(
					'id' => array(
						'description'       => __( 'Post ID to read widget blocks from.', 'so-widgets-bundle' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => array( $this, 'validate_post_id' ),
					),
				),
			)
		);
	}

	/**
	 * @param mixed $value The route id argument.
	 *
	 * @return bool
	 */
	public function validate_post_id( $value ) {
		return is_numeric( $value ) && (int) $value > 0;
	}

	/**
	 * Permission check for reading a post's widget blocks.
	 *
	 * Authorization, not just authentication: the caller must be able to edit
	 * the TARGET post to read its stored widget data. Deliberately narrower
	 * than the blanket `edit_posts` check used by the stateless form/preview
	 * routes — a contributor must not read other authors' private content.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return true|WP_Error
	 */
	public function rest_get_widgets_permission( $request ) {
		$post_id = (int) $request['id'];

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'rest_cannot_read_widgets',
				__( 'Sorry, you are not allowed to read the widgets of this post.', 'so-widgets-bundle' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * REST callback. Delegates to the same shared reader the widget-get
	 * ability uses, so the two outputs can never drift.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_get_widgets( $request ) {
		$post_id = (int) $request['id'];

		// Defense in depth for direct in-process callers.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'rest_cannot_read_widgets',
				__( 'Sorry, you are not allowed to read the widgets of this post.', 'so-widgets-bundle' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$result = $this->read_widgets( $post_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Shared reader consumed byte-identically by the widget-get ability and
	 * the REST route. Never throws.
	 *
	 * @param int $post_id The target post ID.
	 *
	 * @return array|WP_Error The response shape documented in the file header,
	 *                        or WP_Error 'sowb_widgets_post_not_found' (404).
	 */
	public function read_widgets( $post_id ) {
		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			return new WP_Error(
				'sowb_widgets_post_not_found',
				__( 'Post not found.', 'so-widgets-bundle' ),
				array( 'status' => 404 )
			);
		}

		$entries = $this->get_qualifying_widget_blocks( $post );

		global $wp_widget_factory;
		$widgets = array();

		foreach ( $entries as $entry ) {
			$widget_class = $entry['widget_class'];
			$active = ! empty( $widget_class ) &&
					  ! empty( $wp_widget_factory->widgets[ $widget_class ] );
			$widget_name = $active && ! empty( $wp_widget_factory->widgets[ $widget_class ]->name ) ?
				$wp_widget_factory->widgets[ $widget_class ]->name :
				null;

			$widgets[] = array(
				'widget_index' => $entry['widget_index'],
				'block_name' => $entry['block_name'],
				'widget_class' => $widget_class,
				'widget_name' => $widget_name,
				'active' => $active,
				'anchor' => $entry['anchor'],
				'class_name' => $entry['class_name'],
				'widget_data' => $entry['widget_data'],
			);
		}

		return array(
			'post_id' => (int) $post_id,
			'widget_count' => count( $widgets ),
			'unscanned_refs' => $this->post_has_unscanned_refs( $post ),
			'widgets' => $widgets,
		);
	}

	/**
	 * The shared recursive walk. Both the reader (labelling) and the
	 * widget-update writer (targeting) consume THIS method on the same post,
	 * which is the guarantee that a widget_index from widget-get can never
	 * resolve to a different block at update time.
	 *
	 * Qualification is purely STRUCTURAL — it never depends on runtime state
	 * such as the active-widget registry — so indices cannot drift between
	 * requests: a block qualifies iff its blockName begins with `sowb/` AND it
	 * is not a legacy `sowb/widget-block` placeholder without a widgetClass.
	 * Widget-class RESOLUTION (which can vary with activation state) happens
	 * per-entry after indexing and never shifts indices.
	 *
	 * @param WP_Post $post The post whose content to walk.
	 *
	 * @return array[] Entries of shape { widget_index: int, path: int[],
	 *                 block_name: string, widget_class: string|null,
	 *                 widget_data: array, anchor: string|null,
	 *                 class_name: string|null }, depth-first document order.
	 *                 Empty array when the content has no blocks (empty,
	 *                 classic HTML, shortcodes-only) — parse_blocks() is never
	 *                 run on non-block content.
	 */
	public function get_qualifying_widget_blocks( $post ) {
		if ( empty( $post->post_content ) || ! has_blocks( $post->post_content ) ) {
			return array();
		}

		$blocks = parse_blocks( $post->post_content );
		$entries = array();
		$has_refs = false;

		$this->walk_blocks( $blocks, array(), $entries, $has_refs );

		return $entries;
	}

	/**
	 * Whether the post's content contains `core/block` (reusable block)
	 * references, which the walk deliberately never dereferences.
	 *
	 * @param WP_Post $post The post whose content to check.
	 *
	 * @return bool False when the content has no blocks at all.
	 */
	public function post_has_unscanned_refs( $post ) {
		if ( empty( $post->post_content ) || ! has_blocks( $post->post_content ) ) {
			return false;
		}

		$blocks = parse_blocks( $post->post_content );
		$entries = array();
		$has_refs = false;

		$this->walk_blocks( $blocks, array(), $entries, $has_refs );

		return $has_refs;
	}

	/**
	 * Internal recursive collector.
	 *
	 * Descends the serialized innerBlocks of every NON-qualifying block
	 * (Group, Columns, Cover, Query Loop templates, ...). Does not descend
	 * into qualifying `sowb/` blocks (leaf dynamic blocks). `core/block`
	 * references set the refs flag and are otherwise ignored.
	 *
	 * @param array $blocks Blocks as returned by parse_blocks().
	 * @param array $path_prefix Index chain to reach $blocks from the root.
	 * @param array $entries Collected entries (by reference).
	 * @param bool  $has_refs Whether a core/block ref was seen (by reference).
	 */
	private function walk_blocks( $blocks, $path_prefix, &$entries, &$has_refs ) {
		foreach ( $blocks as $index => $block ) {
			$block_name = empty( $block['blockName'] ) ? '' : $block['blockName'];
			$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

			if ( $block_name === 'core/block' ) {
				$has_refs = true;
				continue;
			}

			$is_sowb = strpos( $block_name, 'sowb/' ) === 0;
			$qualifies = $is_sowb &&
						 ! ( $block_name === 'sowb/widget-block' && empty( $attrs['widgetClass'] ) );

			if ( $qualifies ) {
				$path = $path_prefix;
				$path[] = $index;

				$widget_class = null;

				if ( class_exists( 'SiteOrigin_Widgets_Bundle_Widget_Block' ) ) {
					$widget_class = SiteOrigin_Widgets_Bundle_Widget_Block::single()->resolve_widget_class( $attrs, $block_name );
				} elseif ( ! empty( $attrs['widgetClass'] ) ) {
					$widget_class = $attrs['widgetClass'];
				}

				$entries[] = array(
					'widget_index' => count( $entries ),
					'path' => $path,
					'block_name' => $block_name,
					'widget_class' => $widget_class,
					'widget_data' => isset( $attrs['widgetData'] ) && is_array( $attrs['widgetData'] ) ? $attrs['widgetData'] : array(),
					'anchor' => isset( $attrs['anchor'] ) && $attrs['anchor'] !== '' ? $attrs['anchor'] : null,
					'class_name' => isset( $attrs['className'] ) && $attrs['className'] !== '' ? $attrs['className'] : null,
				);

				// Qualifying sowb blocks are leaf dynamic blocks — do not descend.
				continue;
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$path = $path_prefix;
				$path[] = $index;
				$this->walk_blocks( $block['innerBlocks'], $path, $entries, $has_refs );
			}
		}
	}
}

SiteOrigin_Widgets_Bundle_AI_Exposure::single();

<?php

/**
 * SiteOrigin Widgets Bundle — Abilities API exposure.
 *
 * Public API — premium-addon-facing. Registers WordPress Abilities API
 * abilities so the AI ecosystem can discover and use standalone SiteOrigin
 * widget blocks in post content:
 *
 *   - sowb/widget-get    (readonly) — lists a post's standalone widget blocks
 *                         with stable widget_index targeting.
 *   - sowb/widget-update (write)    — replaces one widget block's instance,
 *                         routed through the untrusted sanitize chokepoint
 *                         (single update() pass + forced kses floor +
 *                         widgetMarkup regeneration); ambiguity is declined,
 *                         never guessed.
 *   - sowb/widget-describe (readonly) — translates a widget's form_options()
 *                         into a JSON-schema description of its editable
 *                         instance fields.
 *
 * Core ships ZERO AI vendor logic: no API keys, model calls, or prompts. An
 * ability here is capability registration against existing sanitized seams —
 * exposure, like the read-only REST route in ai-exposure.php. The premium
 * addon will later IMPLEMENT the AI behaviour that CALLS these abilities.
 */
class SiteOrigin_Widgets_Bundle_Abilities {
	/**
	 * @var SiteOrigin_Widgets_Bundle_Abilities
	 */
	private static $single;

	/**
	 * Get the singleton instance.
	 *
	 * @return SiteOrigin_Widgets_Bundle_Abilities
	 */
	public static function single() {
		if ( empty( self::$single ) ) {
			self::$single = new self();
		}

		return self::$single;
	}

	public function __construct() {
		// Categories must be registered on the categories-init hook, BEFORE the
		// abilities-init hook (an ability references its category at registration).
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_ability_category' ) );

		// Abilities must be registered on the documented init hook; registering
		// outside it triggers _doing_it_wrong() and the registration fails.
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register the Widgets Bundle ability category.
	 *
	 * Guarded for environments without the Abilities API, same as
	 * register_abilities(), so the plugin never fatals on WP < 6.9 or without
	 * the Abilities API plugin.
	 */
	public function register_ability_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			'so-widgets-bundle',
			array(
				'label'       => __( 'SiteOrigin Widgets Bundle', 'so-widgets-bundle' ),
				'description' => __( 'Read and update standalone SiteOrigin widget blocks in post content.', 'so-widgets-bundle' ),
			)
		);
	}

	/**
	 * Register the Widgets Bundle abilities.
	 *
	 * Guarded for environments without the Abilities API (WP < 6.9, or the API
	 * plugin absent): bail early rather than fatal.
	 */
	public function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'sowb/widget-get',
			array(
				'label'               => __( 'Get SiteOrigin widget blocks', 'so-widgets-bundle' ),
				'description'         => __( "Lists the standalone SiteOrigin widget blocks in a post's content, in document order, including blocks nested inside container blocks such as Group or Columns. Each entry carries a stable widget_index — pass it to widget-update to rewrite that specific widget. widget_data is the stored instance verbatim. unscanned_refs reports whether the post contains reusable-block references (core/block), whose contents are never scanned; edit the reusable block itself instead.", 'so-widgets-bundle' ),
				'category'            => 'so-widgets-bundle',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'Post ID to read widget blocks from.', 'so-widgets-bundle' ),
							'minimum'     => 1,
						),
					),
					'required'             => array( 'post_id' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'        => array( 'type' => 'integer' ),
						'widget_count'   => array( 'type' => 'integer' ),
						'unscanned_refs' => array( 'type' => 'boolean' ),
						'widgets'        => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'widget_index' => array( 'type' => 'integer' ),
									'block_name'   => array( 'type' => 'string' ),
									'widget_class' => array( 'type' => array( 'string', 'null' ) ),
									'widget_name'  => array( 'type' => array( 'string', 'null' ) ),
									'active'       => array( 'type' => 'boolean' ),
									'anchor'       => array( 'type' => array( 'string', 'null' ) ),
									'class_name'   => array( 'type' => array( 'string', 'null' ) ),
									'widget_data'  => array( 'type' => 'object' ),
								),
							),
						),
					),
				),
				'permission_callback' => array( $this, 'widget_get_permission' ),
				'execute_callback'    => array( $this, 'widget_get' ),
				'meta'                => array(
					'readonly'     => true,
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'sowb/widget-update',
			array(
				'label'               => __( 'Update a SiteOrigin widget block', 'so-widgets-bundle' ),
				'description'         => __( "Replaces the instance data of ONE standalone SiteOrigin widget block, selected by widget_index (from widget-get). widget_data must be the COMPLETE replacement instance — it is sanitized through the widget's own sanitizer, unconditionally kses-floored regardless of the caller's capabilities, and the block's cached markup is regenerated from the floored result. When a post has multiple widget blocks, widget_index is required; if it is missing or out of range the call declines as 'widget-ambiguous' rather than guessing. This ability never deletes widgets and never changes a block's widget type.", 'so-widgets-bundle' ),
				'category'            => 'so-widgets-bundle',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'post_id'      => array(
							'type'        => 'integer',
							'description' => __( 'Post ID containing the widget block to update.', 'so-widgets-bundle' ),
							'minimum'     => 1,
						),
						'widget_data'  => array(
							'type'        => 'object',
							'description' => __( 'The complete replacement widget instance, as read from widget-get.', 'so-widgets-bundle' ),
						),
						'widget_index' => array(
							'type'        => 'integer',
							'description' => __( 'The 0-based index (from widget-get) of the widget block to write. Optional for a single-widget post (defaults to 0); required when the post has multiple widget blocks.', 'so-widgets-bundle' ),
							'minimum'     => 0,
						),
						'widget_class' => array(
							'type'        => 'string',
							'description' => __( 'Optional assertion: the widget class the caller believes it is updating. Declined on mismatch with the target block. Never used to change the block type.', 'so-widgets-bundle' ),
						),
					),
					'required'             => array( 'post_id', 'widget_data' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'      => array( 'type' => 'integer' ),
						'updated'      => array( 'type' => 'boolean' ),
						'widget_index' => array( 'type' => array( 'integer', 'null' ) ),
						'status'       => array(
							'type' => 'string',
							'enum' => array( 'ok', 'widget-ambiguous', 'unsupported', 'not-found' ),
						),
						'message'      => array( 'type' => 'string' ),
						'widget_data'  => array( 'type' => 'object' ),
					),
				),
				'permission_callback' => array( $this, 'widget_update_permission' ),
				'execute_callback'    => array( $this, 'widget_update' ),
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'sowb/widget-describe',
			array(
				'label'               => __( 'Describe a SiteOrigin widget', 'so-widgets-bundle' ),
				'description'         => __( "Describes a SiteOrigin widget's editable instance fields as a JSON schema, so a caller can construct a valid widget_data payload for widget-update. Accepts a widget PHP class name or a sowb/ block name. Fields marked x-sowb-text (text, textarea, tinymce) are the intended targets for content rewriting. State-dependent option lists (posts, taxonomies) are not enumerated.", 'so-widgets-bundle' ),
				'category'            => 'so-widgets-bundle',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'widget' => array(
							'type'        => 'string',
							'description' => __( 'Widget PHP class name (e.g. SiteOrigin_Widget_Testimonial_Widget) or block name (e.g. sowb/siteorigin-widget-testimonial-widget).', 'so-widgets-bundle' ),
						),
					),
					'required'             => array( 'widget' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'widget_class' => array( 'type' => 'string' ),
						'block_name'   => array( 'type' => 'string' ),
						'name'         => array( 'type' => 'string' ),
						'description'  => array( 'type' => 'string' ),
						'schema'       => array( 'type' => 'object' ),
					),
				),
				'permission_callback' => array( $this, 'widget_describe_permission' ),
				'execute_callback'    => array( $this, 'widget_describe' ),
				'meta'                => array(
					'readonly'     => true,
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Permission check for sowb/widget-get.
	 *
	 * Authorization, not just authentication: the caller must be able to edit
	 * the target post to read its stored widget data.
	 *
	 * @param array $input Ability input — expects post_id.
	 *
	 * @return bool|WP_Error
	 */
	public function widget_get_permission( $input ) {
		$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'sowb_cannot_read_widgets',
				__( 'Sorry, you are not allowed to read the widgets of this post.', 'so-widgets-bundle' )
			);
		}

		return true;
	}

	/**
	 * Execute sowb/widget-get.
	 *
	 * Delegates to the shared reader the REST route also serves, so the two
	 * outputs can never drift. Never throws.
	 *
	 * @param array $input Ability input — expects post_id.
	 *
	 * @return array|WP_Error
	 */
	public function widget_get( $input ) {
		$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;

		// Defense in depth for direct in-process callers: the Abilities API
		// runs the permission callback, but nothing stops in-process code from
		// invoking the execute callback directly.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'sowb_cannot_read_widgets',
				__( 'Sorry, you are not allowed to read the widgets of this post.', 'so-widgets-bundle' )
			);
		}

		return SiteOrigin_Widgets_Bundle_AI_Exposure::single()->read_widgets( $post_id );
	}

	/**
	 * Permission check for sowb/widget-update.
	 *
	 * @param array $input Ability input — expects post_id.
	 *
	 * @return bool|WP_Error
	 */
	public function widget_update_permission( $input ) {
		$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'sowb_cannot_update_widget',
				__( 'Sorry, you are not allowed to update the widgets of this post.', 'so-widgets-bundle' )
			);
		}

		return true;
	}

	/**
	 * Execute sowb/widget-update.
	 *
	 * Replaces one widget block's instance. The write is surgical: only the
	 * target block's attributes are mutated; sibling blocks flow through
	 * serialize_blocks() untouched. The replacement instance is origin-
	 * untrusted regardless of the credential carrying the request — it routes
	 * through sanitize_widget_block_untrusted() (single update() pass, forced
	 * kses floor, markup regeneration) before persisting. Never throws;
	 * declines are structured status arrays, and a WP_Error is returned only
	 * from the capability re-check.
	 *
	 * @param array $input Ability input — expects post_id, widget_data;
	 *                     optional widget_index, widget_class.
	 *
	 * @return array|WP_Error
	 */
	public function widget_update( $input ) {
		$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
		$widget_index = isset( $input['widget_index'] ) && is_numeric( $input['widget_index'] ) ?
			(int) $input['widget_index'] :
			null;

		// Defense in depth for direct in-process callers.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'sowb_cannot_update_widget',
				__( 'Sorry, you are not allowed to update the widgets of this post.', 'so-widgets-bundle' )
			);
		}

		// Normalize and validate the replacement instance. Present-but-wrong
		// type is declined rather than coerced to array() — silently
		// collapsing would wipe the widget.
		$widget_data = isset( $input['widget_data'] ) ? $input['widget_data'] : null;

		// Full-depth: a nested stdClass inside an array-shaped instance must
		// also be coerced, or it would sail past the array-recursing floor.
		if ( is_object( $widget_data ) || is_array( $widget_data ) ) {
			$widget_data = self::normalize_widget_data_deep( $widget_data );
		}

		if ( ! is_array( $widget_data ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'unsupported', __( 'The widget_data must be provided as a complete object.', 'so-widgets-bundle' ) );
		}

		if ( empty( $widget_data ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'unsupported', __( 'The widget_data must be the complete replacement instance; this ability does not delete widgets.', 'so-widgets-bundle' ) );
		}

		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'not-found', __( 'Post not found.', 'so-widgets-bundle' ) );
		}

		if ( wp_is_post_revision( $post ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'unsupported', __( 'Revisions cannot be targeted.', 'so-widgets-bundle' ) );
		}

		$exposure = SiteOrigin_Widgets_Bundle_AI_Exposure::single();

		if ( empty( $post->post_content ) || ! has_blocks( $post->post_content ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'unsupported', __( "This post's content does not contain blocks.", 'so-widgets-bundle' ) );
		}

		$entries = $exposure->get_qualifying_widget_blocks( $post );

		if ( empty( $entries ) ) {
			$message = $exposure->post_has_unscanned_refs( $post ) ?
				__( "No targetable SiteOrigin widget blocks in this post's content. It contains reusable-block references (core/block), which cannot be targeted; edit the reusable block itself.", 'so-widgets-bundle' ) :
				__( 'No SiteOrigin widget blocks found in this post.', 'so-widgets-bundle' );

			return $this->update_result( $post_id, false, $widget_index, 'unsupported', $message );
		}

		// Ambiguity rules — decline, never guess.
		$count = count( $entries );

		if ( $count === 1 ) {
			if ( $widget_index !== null && $widget_index !== 0 ) {
				return $this->update_result( $post_id, false, $widget_index, 'widget-ambiguous', __( 'This post has a single widget block; widget_index must be 0 or omitted.', 'so-widgets-bundle' ) );
			}

			$widget_index = 0;
		} else {
			if ( $widget_index === null || $widget_index < 0 || $widget_index >= $count ) {
				return $this->update_result(
					$post_id,
					false,
					$widget_index,
					'widget-ambiguous',
					sprintf(
						__( 'This post has %1$d widget blocks; a valid widget_index is required. Valid indices: 0-%2$d.', 'so-widgets-bundle' ),
						$count,
						$count - 1
					)
				);
			}
		}

		$entry = $entries[ $widget_index ];

		// The widget class always comes from the TARGET block; the input
		// widget_class is an optional assertion, never a selector.
		$target_class = $entry['widget_class'];

		if ( empty( $target_class ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'unsupported', __( "The target block's widget type could not be determined; the widget may not be installed.", 'so-widgets-bundle' ) );
		}

		if (
			! empty( $input['widget_class'] ) &&
			strcasecmp( (string) $input['widget_class'], $target_class ) !== 0
		) {
			return $this->update_result(
				$post_id,
				false,
				$widget_index,
				'unsupported',
				sprintf(
					__( 'widget_class does not match the target block (expected %s).', 'so-widgets-bundle' ),
					$target_class
				)
			);
		}

		if ( ! class_exists( 'SiteOrigin_Widgets_Bundle_Widget_Block' ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'unsupported', __( 'The widget block subsystem is not loaded.', 'so-widgets-bundle' ) );
		}

		// Surgical write: descend the shared walk's path chain by reference so
		// only the target block's attrs are mutated.
		$blocks = parse_blocks( $post->post_content );
		$target = &$blocks;

		foreach ( $entry['path'] as $i => $key ) {
			if ( $i === 0 ) {
				$target = &$blocks[ $key ];
			} else {
				$target = &$target['innerBlocks'][ $key ];
			}
		}

		if ( ! isset( $target['attrs'] ) || ! is_array( $target['attrs'] ) ) {
			$target['attrs'] = array();
		}

		$target['attrs']['widgetClass'] = $target_class;
		$target['attrs']['widgetData'] = $widget_data;

		$sanitized = SiteOrigin_Widgets_Bundle_Widget_Block::single()->sanitize_widget_block_untrusted( $target['attrs'] );

		if ( is_wp_error( $sanitized ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'unsupported', $sanitized->get_error_message() );
		}

		$target['attrs'] = $sanitized;
		unset( $target );

		// wp_update_post() runs wp_unslash() over its input; without wp_slash()
		// every backslash serialize_blocks() emits in JSON-encoded attributes
		// would be stripped, corrupting all block content.
		$result = wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => wp_slash( serialize_blocks( $blocks ) ),
			),
			true
		);

		if ( empty( $result ) || is_wp_error( $result ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'unsupported', __( 'The widget could not be saved.', 'so-widgets-bundle' ) );
		}

		return $this->update_result(
			$post_id,
			true,
			$widget_index,
			'ok',
			'',
			isset( $sanitized['widgetData'] ) && is_array( $sanitized['widgetData'] ) ? $sanitized['widgetData'] : array()
		);
	}

	/**
	 * Permission check for sowb/widget-describe.
	 *
	 * Post-agnostic: describe reads code-defined widget schemas, not post
	 * content, so a general editing capability suffices — a deliberate
	 * difference from get/update's per-post `edit_post` checks.
	 *
	 * @param array $input Ability input.
	 *
	 * @return bool|WP_Error
	 */
	public function widget_describe_permission( $input ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'sowb_cannot_describe_widgets',
				__( 'Sorry, you are not allowed to describe widgets.', 'so-widgets-bundle' )
			);
		}

		return true;
	}

	/**
	 * Execute sowb/widget-describe. Never throws.
	 *
	 * @param array $input Ability input — expects widget (class or block name).
	 *
	 * @return array|WP_Error Describe payload, or WP_Error
	 *                        'sowb_widget_not_found' / 'sowb_cannot_describe_widgets'.
	 */
	public function widget_describe( $input ) {
		// Defense in depth for direct in-process callers.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'sowb_cannot_describe_widgets',
				__( 'Sorry, you are not allowed to describe widgets.', 'so-widgets-bundle' )
			);
		}

		$widget = isset( $input['widget'] ) ? (string) $input['widget'] : '';

		return SiteOrigin_Widgets_Bundle_Widget_Describer::single()->describe( $widget );
	}

	/**
	 * Build the widget-update result payload (shared by success and declines).
	 *
	 * The persisted widget_data is returned on success so the caller learns
	 * what sanitization changed (unknown keys stripped, HTML floored) without
	 * a follow-up get.
	 *
	 * @param int      $post_id The target post ID.
	 * @param bool     $updated Whether the write persisted.
	 * @param int|null $widget_index The resolved target index, when known.
	 * @param string   $status One of ok|widget-ambiguous|unsupported|not-found.
	 * @param string   $message Human-readable decline reason; empty on success.
	 * @param array    $widget_data The persisted post-sanitize instance.
	 *
	 * @return array
	 */
	private function update_result( $post_id, $updated, $widget_index, $status, $message, $widget_data = array() ) {
		return array(
			'post_id' => (int) $post_id,
			'updated' => (bool) $updated,
			'widget_index' => $widget_index,
			'status' => $status,
			'message' => $message,
			'widget_data' => $widget_data,
		);
	}

	/**
	 * Full-depth object-to-array coercion for an inbound widget instance.
	 *
	 * A widget instance is one widget's tree of scalars and arrays, so
	 * full-depth coercion cannot corrupt a legitimate structure — and it fully
	 * closes the stdClass gap: kses_deep() recurses arrays only, so an
	 * object-shaped subtree would otherwise sail past the floor unfloored.
	 * Direct casts, no JSON round-trip (encoding-lossless).
	 *
	 * @param mixed $value The inbound value.
	 *
	 * @return mixed
	 */
	public static function normalize_widget_data_deep( $value ) {
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::normalize_widget_data_deep( $item );
			}
		}

		return $value;
	}
}

SiteOrigin_Widgets_Bundle_Abilities::single();

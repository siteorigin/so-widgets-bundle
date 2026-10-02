<?php

/**
 * Widget block abilities for the WordPress Abilities API (WP 6.9+).
 *
 * Registers two abilities for the standalone SiteOrigin widget blocks in a
 * post's content:
 *
 *   - sowb/widget-get     Lists a post's widget blocks, each with a stable
 *                         widget_index. Read-only.
 *   - sowb/widget-update  Changes fields of one widget block. The fields are a
 *                         patch that merges into the stored instance, applied
 *                         through
 *                         SiteOrigin_Widgets_Bundle_Widget_Block::sanitize_widget_block_untrusted().
 *                         Only draft and pending posts are written. Ambiguous
 *                         targets are declined, never guessed.
 *
 * Both require edit_post on the target post. The ability category belongs to
 * SiteOrigin_Widgets_Abilities.
 *
 * Contract: the output keys, the status values of sowb/widget-update and the
 * error codes `sowb_cannot_read_widgets` and `sowb_cannot_update_widget` are
 * stable. Add keys only. Never rename or remove them.
 *
 * @api
 */
class SiteOrigin_Widgets_Block_Abilities {
	/**
	 * Get the singleton instance.
	 *
	 * @return SiteOrigin_Widgets_Block_Abilities
	 */
	public static function single(): self {
		static $single;

		if ( empty( $single ) ) {
			$single = new self();
		}

		return $single;
	}

	public function __construct() {
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register the widget block abilities.
	 */
	public function register_abilities() {
		if (
			! function_exists( 'wp_register_ability' ) ||
			! class_exists( 'SiteOrigin_Widgets_Abilities' )
		) {
			return;
		}

		wp_register_ability(
			'sowb/widget-get',
			array(
				'label' => __( 'Get widget blocks', 'so-widgets-bundle' ),
				'description' => __( "Lists the standalone SiteOrigin widget blocks in a post's content, in document order, including blocks nested inside container blocks such as Group or Columns. Each entry carries a stable widget_index. Pass it to sowb/widget-update to change that widget. widget_data is the stored instance. unscanned_refs reports whether the post contains reusable block references (core/block), whose contents are never scanned; edit the reusable block itself instead.", 'so-widgets-bundle' ),
				'category' => SiteOrigin_Widgets_Abilities::CATEGORY,
				'input_schema' => array(
					'type' => 'object',
					'properties' => array(
						'post_id' => array(
							'type' => 'integer',
							'description' => __( 'Post ID to read widget blocks from.', 'so-widgets-bundle' ),
							'minimum' => 1,
						),
					),
					'required' => array( 'post_id' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type' => 'object',
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
						'widget_count' => array( 'type' => 'integer' ),
						'unscanned_refs' => array( 'type' => 'boolean' ),
						'widgets' => array(
							'type' => 'array',
							'items' => array(
								'type' => 'object',
								'properties' => array(
									'widget_index' => array( 'type' => 'integer' ),
									'block_name' => array( 'type' => 'string' ),
									'widget_class' => array( 'type' => array( 'string', 'null' ) ),
									'widget_name' => array( 'type' => array( 'string', 'null' ) ),
									'active' => array( 'type' => 'boolean' ),
									'anchor' => array( 'type' => array( 'string', 'null' ) ),
									'class_name' => array( 'type' => array( 'string', 'null' ) ),
									'widget_data' => array( 'type' => 'object' ),
								),
							),
						),
					),
				),
				'permission_callback' => array( $this, 'widget_get_permission' ),
				'execute_callback' => array( $this, 'widget_get' ),
				'meta' => array(
					'annotations' => array(
						'readonly' => true,
						'destructive' => false,
						'idempotent' => true,
					),
					'show_in_rest' => true,
					'mcp' => array( 'public' => true ),
				),
			)
		);

		wp_register_ability(
			'sowb/widget-update',
			array(
				'label' => __( 'Update a widget block', 'so-widgets-bundle' ),
				'description' => __( "Changes fields of ONE standalone SiteOrigin widget block, selected by widget_index (from sowb/widget-get). widget_data is a patch: send only the fields you change. Omitted fields keep their stored values. Sections merge by field, and repeater items merge by index; send [] to clear a repeater. Call sowb/widget-describe for the field names. Every string you send is sanitized as post content, whatever your capabilities, and square brackets are stored as full-width brackets, so shortcodes never run. Only draft and pending posts can be updated. When a post has multiple widget blocks, widget_index is required; if it is missing or out of range the call declines as 'widget-ambiguous' rather than guessing. This ability never deletes widgets and never changes a block's widget type.", 'so-widgets-bundle' ),
				'category' => SiteOrigin_Widgets_Abilities::CATEGORY,
				'input_schema' => array(
					'type' => 'object',
					'properties' => array(
						'post_id' => array(
							'type' => 'integer',
							'description' => __( 'Post ID containing the widget block to update.', 'so-widgets-bundle' ),
							'minimum' => 1,
						),
						'widget_data' => array(
							'type' => 'object',
							'description' => __( 'The fields to change. Omitted fields keep their stored values.', 'so-widgets-bundle' ),
						),
						'widget_index' => array(
							'type' => 'integer',
							'description' => __( 'The 0-based index (from sowb/widget-get) of the widget block to update. Optional for a post with one widget block; required when the post has multiple widget blocks.', 'so-widgets-bundle' ),
							'minimum' => 0,
						),
						'widget_class' => array(
							'type' => 'string',
							'description' => __( 'Optional assertion: the widget class the caller believes it is updating. Declined on mismatch with the target block. Never used to change the block type.', 'so-widgets-bundle' ),
						),
					),
					'required' => array( 'post_id', 'widget_data' ),
					'additionalProperties' => false,
				),
				'output_schema' => array(
					'type' => 'object',
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
						'updated' => array( 'type' => 'boolean' ),
						'widget_index' => array( 'type' => array( 'integer', 'null' ) ),
						'status' => array(
							'type' => 'string',
							'enum' => array(
								'ok',
								'widget-ambiguous',
								'unsupported',
								'not-found',
								'widget-unavailable',
								'post-status-not-allowed',
								'post-locked',
								'post-changed',
								'readback-failed',
							),
						),
						'message' => array( 'type' => 'string' ),
						'widget_data' => array( 'type' => 'object' ),
					),
				),
				'permission_callback' => array( $this, 'widget_update_permission' ),
				'execute_callback' => array( $this, 'widget_update' ),
				'meta' => array(
					// Destructive: an update can overwrite and clear fields.
					'annotations' => array(
						'readonly' => false,
						'destructive' => true,
						'idempotent' => false,
					),
					'show_in_rest' => true,
					'mcp' => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Get the post ID from ability input.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return int
	 */
	private function get_post_id( $input ) {
		return is_array( $input ) && isset( $input['post_id'] ) && is_numeric( $input['post_id'] ) ? (int) $input['post_id'] : 0;
	}

	/**
	 * Permission check for sowb/widget-get: the caller must be able to edit
	 * the target post.
	 *
	 * @param array $input Ability input, expects post_id.
	 *
	 * @return true|WP_Error
	 */
	public function widget_get_permission( $input ) {
		if ( ! current_user_can( 'edit_post', $this->get_post_id( $input ) ) ) {
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
	 * @param array $input Ability input, expects post_id.
	 *
	 * @return array|WP_Error
	 */
	public function widget_get( $input ) {
		$permission = $this->widget_get_permission( $input );

		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		return SiteOrigin_Widgets_Bundle_AI_Exposure::single()->read_widgets( $this->get_post_id( $input ) );
	}

	/**
	 * Permission check for sowb/widget-update: the caller must be able to
	 * edit the target post.
	 *
	 * @param array $input Ability input, expects post_id.
	 *
	 * @return true|WP_Error
	 */
	public function widget_update_permission( $input ) {
		if ( ! current_user_can( 'edit_post', $this->get_post_id( $input ) ) ) {
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
	 * The write is surgical: only the target block's attributes change, and
	 * sibling blocks pass through serialize_blocks() unchanged. Declines are
	 * results with `updated: false`, and the post is not written. A WP_Error
	 * is returned only by the capability check.
	 *
	 * @param array $input Ability input, expects post_id and widget_data;
	 *                     optional widget_index and widget_class.
	 *
	 * @return array|WP_Error
	 */
	public function widget_update( $input ) {
		$permission = $this->widget_update_permission( $input );

		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$post_id = $this->get_post_id( $input );
		$widget_index = isset( $input['widget_index'] ) && is_numeric( $input['widget_index'] ) ?
			(int) $input['widget_index'] :
			null;

		$widget_data = isset( $input['widget_data'] ) ? $input['widget_data'] : null;

		if ( ! is_array( $widget_data ) && ! is_object( $widget_data ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'unsupported', __( 'The widget_data must be an object with the fields to change.', 'so-widgets-bundle' ) );
		}

		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'not-found', __( 'Post not found.', 'so-widgets-bundle' ) );
		}

		if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'unsupported', __( 'Revisions cannot be targeted.', 'so-widgets-bundle' ) );
		}

		$decline = $this->check_post_state( $post, $widget_index );

		if ( ! empty( $decline ) ) {
			return $decline;
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

		// Ambiguity rules: decline, never guess.
		$count = count( $entries );

		if ( $count === 1 ) {
			if ( $widget_index !== null && $widget_index !== 0 ) {
				return $this->update_result( $post_id, false, $widget_index, 'widget-ambiguous', __( 'This post has a single widget block; widget_index must be 0 or omitted.', 'so-widgets-bundle' ) );
			}

			$widget_index = 0;
		} elseif ( $widget_index === null || $widget_index < 0 || $widget_index >= $count ) {
			return $this->update_result(
				$post_id,
				false,
				$widget_index,
				'widget-ambiguous',
				sprintf(
					/* translators: 1: number of widget blocks, 2: highest valid index. */
					__( 'This post has %1$d widget blocks; a valid widget_index is required. Valid indices: 0-%2$d.', 'so-widgets-bundle' ),
					$count,
					$count - 1
				)
			);
		}

		$entry = $entries[ $widget_index ];

		// The widget class always comes from the target block. The input
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
					/* translators: %s: widget class of the target block. */
					__( 'widget_class does not match the target block (expected %s).', 'so-widgets-bundle' ),
					$target_class
				)
			);
		}

		$canonical_class = null;

		foreach ( SiteOrigin_Widgets_Abilities::single()->get_active_widgets() as $catalog_entry ) {
			if ( strcasecmp( $catalog_entry['class'], $target_class ) === 0 ) {
				$canonical_class = $catalog_entry['class'];
				break;
			}
		}

		if ( empty( $canonical_class ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'widget-unavailable', $this->get_unavailable_message() );
		}

		if ( ! class_exists( 'SiteOrigin_Widgets_Bundle_Widget_Block' ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'unsupported', __( 'The widget block subsystem is not loaded.', 'so-widgets-bundle' ) );
		}

		// Surgical write: descend the shared walk's path chain by reference so
		// only the target block's attrs are changed. The path comes from
		// get_qualifying_widget_blocks() over the same content parsed here; the
		// isset guards decline rather than raise a notice if the two diverge.
		$original_content = $post->post_content;
		$blocks = parse_blocks( $original_content );
		$target = &$blocks;

		foreach ( $entry['path'] as $i => $key ) {
			if ( $i === 0 ) {
				if ( ! isset( $blocks[ $key ] ) ) {
					return $this->update_result( $post_id, false, $widget_index, 'unsupported', __( 'The target widget block could not be located.', 'so-widgets-bundle' ) );
				}

				$target = &$blocks[ $key ];
			} else {
				if ( ! isset( $target['innerBlocks'][ $key ] ) ) {
					return $this->update_result( $post_id, false, $widget_index, 'unsupported', __( 'The target widget block could not be located.', 'so-widgets-bundle' ) );
				}

				$target = &$target['innerBlocks'][ $key ];
			}
		}

		$attrs = isset( $target['attrs'] ) && is_array( $target['attrs'] ) ? $target['attrs'] : array();
		$attrs['widgetClass'] = $canonical_class;

		$sanitized = SiteOrigin_Widgets_Bundle_Widget_Block::single()->sanitize_widget_block_untrusted( $attrs, $widget_data );

		if ( is_wp_error( $sanitized ) ) {
			unset( $target );

			if ( $sanitized->get_error_code() === 'sowb_widget_unavailable' ) {
				return $this->update_result( $post_id, false, $widget_index, 'widget-unavailable', $this->get_unavailable_message() );
			}

			return $this->update_result( $post_id, false, $widget_index, 'unsupported', $sanitized->get_error_message() );
		}

		$target['attrs'] = $sanitized;
		unset( $target );

		// Re-read the post with caches cleared, and decline if it changed
		// since it was parsed, or if its status or lock changed.
		clean_post_cache( $post->ID );
		wp_cache_delete( $post->ID, 'post_meta' );
		$fresh = get_post( $post->ID );

		if ( empty( $fresh ) || $fresh->post_content !== $original_content ) {
			return $this->update_result( $post_id, false, $widget_index, 'post-changed', __( 'The post changed while the widget was being updated. Read the widgets again and retry.', 'so-widgets-bundle' ) );
		}

		$decline = $this->check_post_state( $fresh, $widget_index );

		if ( ! empty( $decline ) ) {
			return $decline;
		}

		$pre_write_count = $count;
		$target_path = $entry['path'];
		$target_block_name = $entry['block_name'];

		// wp_update_post() runs wp_unslash() over its input; without wp_slash()
		// every backslash serialize_blocks() emits in JSON-encoded attributes
		// would be stripped, corrupting all block content.
		$result = wp_update_post(
			array(
				'ID' => $post->ID,
				'post_content' => wp_slash( serialize_blocks( $blocks ) ),
			),
			true
		);

		if ( empty( $result ) || is_wp_error( $result ) ) {
			return $this->update_result( $post_id, false, $widget_index, 'unsupported', __( 'The widget could not be saved.', 'so-widgets-bundle' ) );
		}

		// Report what was stored, not what was sent: save filters, such as
		// the content KSES for users without unfiltered_html, can change it.
		clean_post_cache( $post->ID );
		$saved = get_post( $post->ID );
		$saved_entries = empty( $saved ) ? array() : $exposure->get_qualifying_widget_blocks( $saved );
		$saved_entry = isset( $saved_entries[ $widget_index ] ) ? $saved_entries[ $widget_index ] : null;

		if (
			empty( $saved_entry ) ||
			count( $saved_entries ) !== $pre_write_count ||
			$saved_entry['path'] !== $target_path ||
			$saved_entry['block_name'] !== $target_block_name ||
			$saved_entry['widget_class'] !== $canonical_class
		) {
			return $this->update_result( $post_id, true, $widget_index, 'readback-failed', __( 'The post was saved, but the updated widget could not be found in the saved content. Another plugin may have changed the content on save.', 'so-widgets-bundle' ) );
		}

		return $this->update_result( $post_id, true, $widget_index, 'ok', '', $saved_entry['widget_data'] );
	}

	/**
	 * Decline a post whose status does not allow updates, or that another
	 * user has open in the editor.
	 *
	 * Publishing is a human action, so only draft and pending posts are
	 * written, even when edit_post would allow more.
	 *
	 * @param WP_Post  $post         The post.
	 * @param int|null $widget_index The target index, when known.
	 *
	 * @return array|null A decline result, or null when the post can be written.
	 */
	private function check_post_state( $post, $widget_index ) {
		if ( ! in_array( get_post_status( $post ), array( 'draft', 'pending' ), true ) ) {
			return $this->update_result( $post->ID, false, $widget_index, 'post-status-not-allowed', __( 'Only draft and pending posts can be updated.', 'so-widgets-bundle' ) );
		}

		if ( ! function_exists( 'wp_check_post_lock' ) ) {
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}

		if ( wp_check_post_lock( $post->ID ) ) {
			return $this->update_result( $post->ID, false, $widget_index, 'post-locked', __( 'Another user is editing this post.', 'so-widgets-bundle' ) );
		}

		return null;
	}

	/**
	 * The message for a widget that is not active.
	 *
	 * @return string
	 */
	private function get_unavailable_message() {
		return __( 'This widget is not an active SiteOrigin Widgets Bundle widget. Call sowb/widget-list for the available widgets. A site administrator can activate widgets at Plugins > SiteOrigin Widgets.', 'so-widgets-bundle' );
	}

	/**
	 * Build a sowb/widget-update result.
	 *
	 * @param int      $post_id      The target post ID.
	 * @param bool     $updated      Whether the post was written.
	 * @param int|null $widget_index The target index, when known.
	 * @param string   $status       The status.
	 * @param string   $message      The reason for a decline; empty on success.
	 * @param array    $widget_data  The stored widget data, on success.
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
			'widget_data' => empty( $widget_data ) ? new stdClass() : $widget_data,
		);
	}
}

SiteOrigin_Widgets_Block_Abilities::single();

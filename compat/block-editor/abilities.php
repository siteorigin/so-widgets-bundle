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
}

SiteOrigin_Widgets_Bundle_Abilities::single();

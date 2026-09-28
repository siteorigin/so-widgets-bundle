<?php
/*
Plugin Name: SOWB E2E Probe
Description: Test-only helpers for the Widgets Bundle end-to-end tests. Never ship or activate this on a real site.
Version: 1.0.0
Author: SiteOrigin
License: GPL3
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [sowb_e2e_probe] counts every run and prints a marker, so a test can tell
 * whether a shortcode ran.
 */
add_shortcode(
	'sowb_e2e_probe',
	function () {
		update_option( 'sowb_e2e_probe_runs', (int) get_option( 'sowb_e2e_probe_runs', 0 ) + 1, false );

		return '<span class="sowb-e2e-probe">SOWB-E2E-PROBE-RAN</span>';
	}
);

/**
 * Whether any string in a value contains a marker.
 */
function sowb_e2e_probe_contains( $value, $marker ) {
	if ( is_string( $value ) ) {
		return strpos( $value, $marker ) !== false;
	}

	if ( is_array( $value ) || is_object( $value ) ) {
		foreach ( (array) $value as $item ) {
			if ( sowb_e2e_probe_contains( $item, $marker ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Markers in a widget instance trigger test behaviour in the sanitize
 * filter: SOWB_E2E_NESTED_OBJECT adds an object value, and SOWB_E2E_THROW
 * throws.
 */
add_filter(
	'siteorigin_widgets_sanitize_instance',
	function ( $instance ) {
		if ( sowb_e2e_probe_contains( $instance, 'SOWB_E2E_THROW' ) ) {
			throw new RuntimeException( 'SOWB_E2E_THROW internal detail' );
		}

		if ( is_array( $instance ) && sowb_e2e_probe_contains( $instance, 'SOWB_E2E_NESTED_OBJECT' ) ) {
			$instance['sowb_e2e_obj'] = (object) array(
				'a' => '<script>x()</script>[sowb_e2e_probe]',
				'b' => new ArrayObject(
					array(
						'c' => '&#91;sowb_e2e_probe]<img src=x onerror=y()>',
					)
				),
			);
		}

		return $instance;
	},
	99
);

/**
 * Remove every sowb/ block whose widgetData contains a marker, at any depth.
 *
 * @return array The remaining blocks.
 */
function sowb_e2e_probe_drop_blocks( $blocks, $marker ) {
	$kept = array();

	foreach ( $blocks as $block ) {
		if (
			! empty( $block['blockName'] ) &&
			strpos( $block['blockName'], 'sowb/' ) === 0 &&
			isset( $block['attrs']['widgetData'] ) &&
			sowb_e2e_probe_contains( $block['attrs']['widgetData'], $marker )
		) {
			continue;
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			// innerContent holds one null placeholder per inner block, in order.
			$inner_content = array();
			$inner_index = 0;

			foreach ( $block['innerContent'] as $chunk ) {
				if ( $chunk !== null ) {
					$inner_content[] = $chunk;
					continue;
				}

				$inner = $block['innerBlocks'][ $inner_index++ ];
				$remaining = sowb_e2e_probe_drop_blocks( array( $inner ), $marker );

				if ( ! empty( $remaining ) ) {
					$inner_content[] = null;
				}
			}

			$block['innerBlocks'] = sowb_e2e_probe_drop_blocks( $block['innerBlocks'], $marker );
			$block['innerContent'] = $inner_content;
		}

		$kept[] = $block;
	}

	return $kept;
}

/**
 * A real save filter that removes widget blocks carrying the
 * SOWB_E2E_DROP_BLOCK marker, as another plugin changing content on save
 * might.
 */
add_filter(
	'wp_insert_post_data',
	function ( $data ) {
		$content = wp_unslash( $data['post_content'] );

		if ( strpos( $content, 'SOWB_E2E_DROP_BLOCK' ) === false ) {
			return $data;
		}

		$blocks = sowb_e2e_probe_drop_blocks( parse_blocks( $content ), 'SOWB_E2E_DROP_BLOCK' );
		$data['post_content'] = wp_slash( serialize_blocks( $blocks ) );

		return $data;
	},
	99
);

add_action(
	'rest_api_init',
	function () {
		$admin_only = function () {
			return current_user_can( 'manage_options' );
		};

		register_rest_route(
			'sowb-e2e/v1',
			'/probe-runs',
			array(
				array(
					'methods' => 'GET',
					'permission_callback' => $admin_only,
					'callback' => function () {
						return array( 'runs' => (int) get_option( 'sowb_e2e_probe_runs', 0 ) );
					},
				),
				array(
					'methods' => 'DELETE',
					'permission_callback' => $admin_only,
					'callback' => function () {
						update_option( 'sowb_e2e_probe_runs', 0, false );

						return array( 'runs' => 0 );
					},
				),
			)
		);

		// Insert a post with raw content, bypassing the REST save path.
		register_rest_route(
			'sowb-e2e/v1',
			'/seed',
			array(
				'methods' => 'POST',
				'permission_callback' => $admin_only,
				'callback' => function ( WP_REST_Request $request ) {
					$post = array(
						'post_title' => (string) $request->get_param( 'title' ),
						'post_status' => (string) $request->get_param( 'status' ),
						'post_author' => (int) $request->get_param( 'author' ),
						'post_type' => $request->get_param( 'type' ) ? (string) $request->get_param( 'type' ) : 'post',
						'post_content' => wp_slash( (string) $request->get_param( 'content' ) ),
					);

					if ( $request->get_param( 'date' ) ) {
						$post['post_date'] = (string) $request->get_param( 'date' );
					}

					$id = wp_insert_post( $post, true );

					if ( is_wp_error( $id ) ) {
						return $id;
					}

					return array( 'id' => $id );
				},
			)
		);

		register_rest_route(
			'sowb-e2e/v1',
			'/blocks/(?P<id>\d+)',
			array(
				'methods' => 'GET',
				'permission_callback' => $admin_only,
				'callback' => function ( WP_REST_Request $request ) {
					clean_post_cache( (int) $request['id'] );
					$post = get_post( (int) $request['id'] );

					if ( empty( $post ) ) {
						return new WP_Error( 'sowb_e2e_not_found', 'Post not found.', array( 'status' => 404 ) );
					}

					return array(
						'content' => $post->post_content,
						'status' => $post->post_status,
						'blocks' => parse_blocks( $post->post_content ),
					);
				},
			)
		);

		// Run sowb/widget-update in process, with object input, and report
		// the global state left behind.
		register_rest_route(
			'sowb-e2e/v1',
			'/execute-update',
			array(
				'methods' => 'POST',
				'permission_callback' => $admin_only,
				'callback' => function ( WP_REST_Request $request ) {
					$body = json_decode( $request->get_body(), false );
					$input = isset( $body->input ) ? (array) $body->input : array();
					$id_base = isset( $body->id_base ) ? (string) $body->id_base : '';

					$ob_before = ob_get_level();
					$result = wp_get_ability( 'sowb/widget-update' )->execute( $input );
					$ob_after = ob_get_level();

					return array(
						'result' => is_wp_error( $result ) ? array( 'error' => $result->get_error_code() ) : $result,
						'preview_flag_set' => isset( $GLOBALS['SO_WIDGETS_BUNDLE_PREVIEW_RENDER'] ),
						'anchor_filter' => $id_base !== '' && has_filter( 'siteorigin_widgets_wrapper_id_' . $id_base ) !== false,
						'ob_delta' => $ob_after - $ob_before,
					);
				},
			)
		);
	}
);

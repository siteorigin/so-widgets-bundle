<?php

/**
 * Filter a query created from the post selector field into an array that will work properly with get_posts
 *
 * @param bool $exclude_current whether to exclude the current post or not.
 *
 * @return mixed
 */
function siteorigin_widget_post_selector_process_query( $query, $exclude_current = true ) {
	$query = wp_parse_args(
		$query,
		array(
			'post_status' => 'publish',
			'posts_per_page' => 10,
		)
	);

	if ( ! empty( $query['post_type'] ) ) {
		if ( $query['post_type'] == '_all' ) {
			$query['post_type'] = siteorigin_widget_post_selector_all_post_types();
		}
		$query['post_type'] = strpos( $query['post_type'], ',' ) !== false ? explode( ',', $query['post_type'] ) : $query['post_type'];
	}

	if ( ! empty( $query['post_type'] ) && $query['post_type'] == 'attachment' && $query['post_status'] == 'publish' ) {
		$query['post_status'] = 'inherit';
	}

	if ( ! empty( $query['post__in'] ) ) {
		$query['post__in'] = explode( ',', $query['post__in'] );
		$query['post__in'] = array_map( 'intval', $query['post__in'] );
	}

	if ( ! empty( $query['tax_query'] ) ) {
		$tax_queries = explode( ',', $query['tax_query'] );

		$query['tax_query'] = array();
		$query['tax_query']['relation'] = isset( $query['tax_query_relation'] ) ? $query['tax_query_relation'] : 'OR';

		foreach ( $tax_queries as $tq ) {
			if ( strpos( $tq, ':' ) !== false ) {
				list( $tax, $term ) = explode( ':', $tq );
			} else {
				// There's no separator, try using the previous $tax.
				$tax = empty( $tax ) ? 'category' : $tax;
				$term = $tq;
			}

			if ( empty( $tax ) || empty( $term ) ) {
				continue;
			}
			$query['tax_query'][] = array(
				'taxonomy' => $tax,
				'field' => 'slug',
				'terms' => $term,
			);
		}
	}

	if ( isset( $query['date_type'] ) && $query['date_type'] == 'relative' ) {
		$date_query_rel = json_decode(
			stripslashes( $query['date_query_relative'] ),
			true
		);
		$value_after = new DateTime(
			$date_query_rel['from']['value'] . ' ' . $date_query_rel['from']['unit'] . ' ago'
		);
		$value['after'] = $value_after->format( 'Y-m-d' );
		$value_before = new DateTime(
			$date_query_rel['to']['value'] . ' ' . $date_query_rel['to']['unit'] . ' ago'
		);
		$value['before'] = $value_before->format( 'Y-m-d' );
		$query['date_query'] = $value;
		unset( $query['date_type'] );
		unset( $query['date_query_relative'] );
	} elseif ( ! empty( $query['date_query'] ) ) {
		$query['date_query'] = json_decode( stripslashes( $query['date_query'] ), true );
	}

	if ( ! empty( $query['date_query'] ) ) {
		$query['date_query']['inclusive'] = true;
	}

	if ( ! empty( $query['sticky'] ) ) {
		switch( $query['sticky'] ) {
			case 'ignore':
				$query['ignore_sticky_posts'] = 1;
				break;

			case 'only':
				$post_in = empty( $query['post__in'] ) ? array() : $query['post__in'];
				$query['post__in'] = array_merge( $post_in, get_option( 'sticky_posts' ) );
				break;

			case 'exclude':
				$query['post__not_in'] = get_option( 'sticky_posts' );
				break;
		}
		unset( $query['sticky'] );
	}

	if ( ! empty( $query['additional'] ) ) {
		$query = wp_parse_args( $query['additional'], $query );
		unset( $query['additional'] );

		// If post_not_in is set, we need to convert it to an array to avoid issues with the query.
		if ( ! empty( $query['post__not_in'] ) && !is_array( $query['post__not_in'] ) ) {
			$query['post__not_in'] = explode( ',', $query['post__not_in'] );
			$query['post__not_in'] = array_map( 'intval', $query['post__not_in'] );
		}
	}

	if ( $exclude_current && get_the_ID() ) {
		if ( ! isset( $query['post__not_in'] ) ) {
			$query['post__not_in'] = array();
		}
		$query['post__not_in'][] = get_the_ID();
	}

	return apply_filters( 'siteorigin_widgets_posts_selector_query', $query );
}

/**
 * Just return a comma separated list of all available post types.
 *
 * @return string
 */
function siteorigin_widget_post_selector_all_post_types() {
	$post_types = array();

	foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $id => $type ) {
		$post_types[] = $id;
	}

	return implode( ',', $post_types );
}

/**
 * Tells us how many posts this query has
 *
 * @return int
 */
function siteorigin_widget_post_selector_count_posts( $query ) {
	$query = siteorigin_widget_post_selector_process_query( $query );
	$posts = new WP_Query( $query );

	return $posts->found_posts;
}

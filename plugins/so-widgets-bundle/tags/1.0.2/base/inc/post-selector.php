<?php

function siteorigin_widget_post_selector_process_query($query){
	$query = wp_parse_args($query);

	if(!empty($query['post_type'])) {
		if($query['post_type'] == '_all') $query['post_type'] = siteorigin_widget_post_selector_all_post_types();
		$query['post_type'] = explode(',', $query['post_type']);
	}

	if(!empty($query['post__in'])) {
		$query['post__in'] = explode(',', $query['post__in']);
		array_map('intval', $query['post__in']);
	}

	if(!empty($query['tax_query'])) {
		$tax_queries = explode(',', $query['tax_query']);

		$query['tax_query'] = array();
		foreach($tax_queries as $tq) {
			list($tax, $term) = explode(':', $tq);

			if( empty($tax) || empty($term) ) continue;
			$query['tax_query'][] = array(
				'taxonomy' => $tax,
				'field' => 'slug',
				'terms' => $term
			);
		}
	}

	return $query;
}

function siteorigin_widget_post_selector_form_fields(){
	$return = array();

	// The post type field
	$return['post_type'] = '';
	$return['post_type'] .= '<label><span>' . __('Post Type', 'siteorigin-widgets') . '</span>';
	$return['post_type'] .= '<select name="post_type">';
	$return['post_type'] .= '<option value="_all">' . __('All', 'siteorigin-widgets') . '</option>';
	foreach( get_post_types( array( 'public' => true  ), 'objects' ) as $id => $type ) {
		if(!empty($type->labels->name)) {
			$post_types[$id] = $type->labels->name;
			$return['post_type'] .= '<option value="' . $id . '">' . $type->labels->name . '</option>';
		}
	}
	$return['post_type'] .= '</select></label>';

	// The field for specifying individual posts
	$return['post__in'] = '';
	$return['post__in'] .= '<label><span>' . __('Post In', 'siteorigin-widgets') . '</span>';
	$return['post__in'] .= '<input type="text" name="post__in" class="" />';
	$return['post__in'] .= ' <a href="#" class="sow-select-posts button button-secondary">' . __('Select Posts', 'siteorigin-widget') . '</a>';
	$return['post__in'] .= '</label>';

	// The taxonomy field
	$return['tax_query'] = '';
	$return['tax_query'] .= '<label><span>' . __('Taxonomies', 'siteorigin-widgets') . '</span>';
	$return['tax_query'] .= '<input type="text" name="tax_query" class="" placeholder="search" />';
	$return['tax_query'] .= '</label>';


	// The order by field
	$return['orderby'] = '';
	$return['orderby'] .= '<label><span>' . __('Order By', 'siteorigin-widgets') . '</span>';
	$return['orderby'] .= '<select name="orderby">';
	$orderby = array(
		'none' => __('No Order', 'siteorigin-widgets'),
		'ID' => __('Post ID', 'siteorigin-widgets'),
		'author' => __('Author', 'siteorigin-widgets'),
		'title' => __('Title', 'siteorigin-widgets'),
		'date' => __('Published Date', 'siteorigin-widgets'),
		'modified' => __('Modified Date', 'siteorigin-widgets'),
		'parent' => __('By Parent', 'siteorigin-widgets'),
		'rand' => __('Random Order', 'siteorigin-widgets'),
		'comment_count' => __('Comment Count', 'siteorigin-widgets'),
		'menu_order' => __('Menu Order', 'siteorigin-widgets'),
		'meta_value' => __('By Meta Value', 'siteorigin-widgets'),
		'meta_value_num' => __('By Numeric Meta Value', 'siteorigin-widgets'),
		'post__in' => __('By Include Order', 'siteorigin-widgets'),
	);
	foreach($orderby as $id => $v) {
		$return['orderby'] .= '<option value="' . $id . '">' . $v . '</option>';
	}
	$return['orderby'] .= '</select>';
	$return['orderby'] .= '<input type="hidden" name="order" />';
	$return['orderby'] .= '<span class="sow-order-button sow-order-button-desc"></span>';
	$return['orderby'] .= '</label>';

	$return['posts_per_page'] = '';
	$return['posts_per_page'] .= '<label><span>' . __('Posts Per Page', 'siteorigin-widgets') . '</span>';
	$return['posts_per_page'] .= '<input type="number" name="posts_per_page" class="" />';
	$return['posts_per_page'] .= '</label>';

	return $return;
}

/**
 * Just return a comma separated list of all available post types.
 *
 * @return string
 */
function siteorigin_widget_post_selector_all_post_types(){
	$post_types = array();
	foreach( get_post_types( array( 'public' => true  ), 'objects' ) as $id => $type ) {
		$post_types[] = $id;
	}

	return implode(',', $post_types);
}

/**
 * Tells us how many posts this query has
 *
 * @param $query
 * @return int
 */
function siteorigin_widget_post_selector_count_posts($query){
	if( empty($query) ) return 0;

	$query = wp_parse_args(
		siteorigin_widget_post_selector_process_query($query),
		array(
			'post_status' => 'publish',
			'posts_per_page' => 10,
		)
	);
	$posts = new WP_Query($query);
	return $posts->found_posts;
}

/**
 * The get posts ajax action
 */
function siteorigin_widget_post_selector_get_posts_action(){
	$query = stripslashes( $_POST['query'] );
	$query = wp_parse_args(
		siteorigin_widget_post_selector_process_query($query),
		array(
			'post_status' => 'publish',
			'posts_per_page' => 100,
		)
	);

	if(!empty($_POST['ignore_pagination'])) {
		$query['posts_per_page'] = 100;
	}

	$posts = new WP_Query($query);

	// Create the result
	$result = array(
		'found_posts' => $posts->found_posts,
		'posts' => array()
	);

	foreach($posts->posts as $post) {
		$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ) );

		$result['posts'][] = array(
			'title' => $post->post_title,
			'id' => $post->ID,
			'thumbnail' => !empty($thumbnail) ? $thumbnail[0] : plugin_dir_url(__FILE__).'../css/img/thumbnail-placeholder.png',
		);
	}

	header('content-type: application/json');
	echo json_encode($result);

	exit();
}
add_action('wp_ajax_sow_get_posts', 'siteorigin_widget_post_selector_get_posts_action');

/**
 * The action handler for searching posts by title
 */
function siteorigin_widget_post_selector_post_search_action(){
	$term = !empty($_GET['term']) ? stripslashes($_GET['term']) : '';

	$results = array();
	if( !empty($term)){
		$r = new WP_Query( array('s' => $term, 'post_status' => 'publish') );
		foreach($r->posts as $post) {
			$thumbnail = wp_get_attachment_image_src($post->ID);

			$results[] = array(
				'label' => $post->post_title,
				'value' => $post->ID,
			);
		}
	}

	header('content-type:application/json');
	echo json_encode($results);
	exit();

}
add_action('wp_ajax_sow_search_posts', 'siteorigin_widget_post_selector_post_search_action');

/**
 * The action handler for searching taxonomy terms
 */
function siteorigin_widget_post_selector_search_taxonomy_terms(){
	global $wpdb;
	$term = !empty($_GET['term']) ? stripslashes($_GET['term']) : '';
	$term = trim($term, '%');

	$return = array();

	$query = $wpdb->prepare("
		SELECT terms.term_id, terms.slug, terms.name, termtaxonomy.taxonomy
		FROM $wpdb->terms AS terms
		JOIN $wpdb->term_taxonomy AS termtaxonomy ON terms.term_id = termtaxonomy.term_id
		WHERE
			terms.name LIKE '%s'
	", '%'.$term.'%');

	foreach($wpdb->get_results($query) as $result) {
		$return[] = array(
			'label' => $result->taxonomy.': '.$result->name,
			'value' => $result->taxonomy.':'.$result->slug,
		);
	}

	header('content-type:application/json');
	echo json_encode($return);
	exit();
}
add_action('wp_ajax_sow_search_terms', 'siteorigin_widget_post_selector_search_taxonomy_terms');
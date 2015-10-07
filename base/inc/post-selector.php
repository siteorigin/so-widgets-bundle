<?php

function siteorigin_widget_post_selector_enqueue_admin_scripts() {
	if ( ! wp_script_is( 'siteorigin-widget-admin-posts-selector' ) ) {

		wp_enqueue_style( 'siteorigin-widget-admin-posts-selector', plugin_dir_url(SOW_BUNDLE_BASE_FILE).'base/css/post-selector.css', array(), SOW_BUNDLE_VERSION );
		wp_enqueue_script( 'siteorigin-widget-admin-posts-selector', plugin_dir_url(SOW_BUNDLE_BASE_FILE).'base/js/posts-selector' . SOW_BUNDLE_JS_SUFFIX . '.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-autocomplete', 'underscore', 'backbone' ), SOW_BUNDLE_VERSION, true );

		wp_localize_script( 'siteorigin-widget-admin-posts-selector', 'sowPostsSelectorTpl', array(
			'ajaxurl' => wp_nonce_url( admin_url('admin-ajax.php'), 'widgets_action', '_widgets_nonce' ),
			'modal' => file_get_contents( plugin_dir_path(SOW_BUNDLE_BASE_FILE).'base/tpl/posts-selector/modal.html' ),
			'postSummary' => file_get_contents( plugin_dir_path(SOW_BUNDLE_BASE_FILE).'base/tpl/posts-selector/post.html' ),
			'foundPosts' => '<div class="sow-post-count-message">' . sprintf( __('This query returns <a href="#" class="preview-query-posts">%s posts</a>.', 'so-widgets-bundle'), '<%= foundPosts %>') . '</div>',
			'fields' => siteorigin_widget_post_selector_form_fields(),
			'selector' => file_get_contents( plugin_dir_path(SOW_BUNDLE_BASE_FILE).'base/tpl/posts-selector/selector.html' ),
		) );

		wp_localize_script( 'siteorigin-widget-admin-posts-selector', 'sowPostsSelectorVars', array(
			'modalTitle' => __('Select posts', 'so-widgets-bundle'),
		) );
	}
}

function siteorigin_widget_post_selector_admin_form_field( $value, $field_name ) {
	?>
	<input type="hidden" value="<?php echo esc_attr( $value ) ?>" name="<?php echo $field_name ?>" class="siteorigin-widget-input" />
	<a href="#" class="sow-select-posts button button-secondary">
		<span class="sow-current-count"><?php echo esc_html( siteorigin_widget_post_selector_count_posts( $value ) )?></span>
		<?php esc_html_e( 'Build posts query', 'so-widgets-bundle' ) ?>
	</a>
	<?php
}

function siteorigin_widget_post_selector_process_query($query){
	$query = wp_parse_args($query,
		array(
			'post_status' => 'publish',
			'posts_per_page' => 10,
		)
	);

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

	if ( ! empty( $query['sticky'] ) ) {
		switch($query['sticky']){
			case 'ignore' :
				$query['ignore_sticky_posts'] = 1;
				break;
			//TODO: Revisit this. Not sure if it makes sense to have this as an option in a separate dropdown, but am
			//TODO: trying to stay as close as possible to Page Builder Post Loop widget post selection options.
			//TODO: It's probably better in the long run to make this work well and just cope with issues that come up in
			//TODO: Page Builder Post Loop migrations until it dies.
			case 'only' :
				$post_in = empty( $query['post__in'] ) ? array() : $query['post__in'];
				$query['post__in'] = array_merge( $post_in, get_option( 'sticky_posts' ) );
				break;
			case 'exclude' :
				$query['post__not_in'] = get_option( 'sticky_posts' );
				break;
		}
		unset( $query['sticky'] );
	}

	if ( ! empty( $query['additional'] ) ) {
		$query = wp_parse_args( $query['additional'], $query );
		unset( $query['additional'] );
	}

	return $query;
}

function siteorigin_widget_post_selector_form_fields(){
	$return = array();

	// The post type field
	$return['post_type'] = '';
	$return['post_type'] .= '<label><span>' . __('Post type', 'so-widgets-bundle') . '</span>';
	$return['post_type'] .= '<select name="post_type">';
	$return['post_type'] .= '<option value="_all">' . __('All', 'so-widgets-bundle') . '</option>';
	foreach( get_post_types( array( 'public' => true  ), 'objects' ) as $id => $type ) {
		if(!empty($type->labels->name)) {
			$post_types[$id] = $type->labels->name;
			$return['post_type'] .= '<option value="' . $id . '">' . $type->labels->name . '</option>';
		}
	}
	$return['post_type'] .= '</select></label>';

	// The field for specifying individual posts
	$return['post__in'] = '';
	$return['post__in'] .= '<label><span>' . __('Post in', 'so-widgets-bundle') . '</span>';
	$return['post__in'] .= '<input type="text" name="post__in" class="" />';
	$return['post__in'] .= ' <a href="#" class="sow-select-posts button button-secondary">' . __('Select posts', 'so-widgets-bundle') . '</a>';
	$return['post__in'] .= '</label>';

	// The taxonomy field
	$return['tax_query'] = '';
	$return['tax_query'] .= '<label><span>' . __('Taxonomies', 'so-widgets-bundle') . '</span>';
	$return['tax_query'] .= '<input type="text" name="tax_query" class="" placeholder="search" />';
	$return['tax_query'] .= '</label>';


	// The order by field
	$return['orderby'] = '';
	$return['orderby'] .= '<label><span>' . __('Order by', 'so-widgets-bundle') . '</span>';
	$return['orderby'] .= '<select name="orderby">';
	$orderby = array(
		'none' => __('No order', 'so-widgets-bundle'),
		'ID' => __('Post ID', 'so-widgets-bundle'),
		'author' => __('Author', 'so-widgets-bundle'),
		'title' => __('Title', 'so-widgets-bundle'),
		'date' => __('Published date', 'so-widgets-bundle'),
		'modified' => __('Modified date', 'so-widgets-bundle'),
		'parent' => __('By parent', 'so-widgets-bundle'),
		'rand' => __('Random order', 'so-widgets-bundle'),
		'comment_count' => __('Comment count', 'so-widgets-bundle'),
		'menu_order' => __('Menu order', 'so-widgets-bundle'),
		'meta_value' => __('By meta value', 'so-widgets-bundle'),
		'meta_value_num' => __('By numeric meta value', 'so-widgets-bundle'),
		'post__in' => __('By include order', 'so-widgets-bundle'),
	);
	foreach($orderby as $id => $v) {
		$return['orderby'] .= '<option value="' . $id . '">' . $v . '</option>';
	}
	$return['orderby'] .= '</select>';
	$return['orderby'] .= '<input type="hidden" name="order" />';
	$return['orderby'] .= '<span class="sow-order-button sow-order-button-desc"></span>';
	$return['orderby'] .= '</label>';

	$return['posts_per_page'] = '';
	$return['posts_per_page'] .= '<label><span>' . __('Posts per page', 'so-widgets-bundle') . '</span>';
	$return['posts_per_page'] .= '<input type="number" name="posts_per_page" class="" />';
	$return['posts_per_page'] .= '</label>';


	$return['sticky'] = '';
	$return['sticky'] .= '<label><span>' . __('Sticky posts', 'so-widgets-bundle') . '</span>';
	$return['sticky'] .= '<select name="sticky">';
	$sticky = array(
		'' => __('Default', 'so-widgets-bundle'),
		'ignore' => __('Ignore sticky', 'so-widgets-bundle'),
		'exclude' => __('Exclude sticky', 'so-widgets-bundle'),
		'only' => __('Include sticky', 'so-widgets-bundle'),
	);
	foreach($sticky as $id => $v) {
		$return['sticky'] .= '<option value="' . $id . '">' . $v . '</option>';
	}
	$return['sticky'] .= '</select></label>';

	$return['additional'] = '';
	$return['additional'] .= '<label><span>' . __('Additional', 'so-widgets-bundle') . '</span>';
	$return['additional'] .= '<input type="text" name="additional" class="" />';
	$return['additional'] .= '<small>' . __('Additional query arguments. See <a href="http://codex.wordpress.org/Function_Reference/query_posts" target="_blank">query_posts</a>.', 'so-widgets-bundle') . '</small>';
	$return['additional'] .= '</label>';

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
//	if( empty($query) ) return 0;

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
	if ( empty( $_REQUEST['_widgets_nonce'] ) || !wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) return;
	$query = stripslashes( $_POST['query'] );
	$query = wp_parse_args(
		siteorigin_widget_post_selector_process_query($query),
		array(
			'post_status' => 'publish',
			'posts_per_page' => 10,
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
			'editUrl' => admin_url( 'post.php?post=' . $post->ID . '&action=edit')
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
	if ( empty( $_REQUEST['_widgets_nonce'] ) || !wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) return;
	$term = !empty($_GET['term']) ? stripslashes($_GET['term']) : '';
	$type = !empty($_GET['type']) ? stripslashes($_GET['type']) : '_all';
	if($type == '_all') $type = explode(',', siteorigin_widget_post_selector_all_post_types());

	$results = array();
	$r = new WP_Query( array('s' => $term, 'post_status' => 'publish', 'posts_per_page' => 20, 'post_type' => $type) );
	foreach($r->posts as $post) {
//			$thumbnail = wp_get_attachment_image_src($post->ID);

		$results[] = array(
			'label' => $post->post_title,
			'value' => $post->ID,
		);
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
	if ( empty( $_REQUEST['_widgets_nonce'] ) || !wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) return;
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
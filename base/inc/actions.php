<?php

/**
 * Action for displaying the widget preview.
 */
function siteorigin_widget_preview_widget_action() {
	if (
		empty( $_REQUEST['_widgets_nonce'] ) ||
		! wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' )
	) {
		wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 403 );
	} else if ( empty( $_POST['class'] ) ) {
		wp_die( __( 'Invalid widget.', 'so-widgets-bundle' ), 400 );
	}

	// Get the widget from the widget factory
	global $wp_widget_factory;
	$widget_class = str_replace('\\\\', '\\', $_POST['class']);
	
	$widget = ! empty( $wp_widget_factory->widgets[ $widget_class ] ) ? $wp_widget_factory->widgets[ $widget_class ] : false;

	if( ! is_a( $widget, 'SiteOrigin_Widget' ) ) {
		wp_die( __( 'Invalid post.', 'so-widgets-bundle' ), 400 );
	}

	$instance = json_decode( stripslashes_deep($_POST['data']), true);
	/* @var $widget SiteOrigin_Widget */
	$instance = $widget->update( $instance, $instance );
	$instance['is_preview'] = true;

	// The theme stylesheet will change how the button looks
	wp_enqueue_style( 'theme-css', get_stylesheet_uri(), array(), rand( 0, 65536 ) );
	wp_enqueue_style( 'so-widget-preview', siteorigin_widgets_url( 'base/css/preview.css' ), array(), rand( 0,65536 ) );

	$sowb = SiteOrigin_Widgets_Bundle::single();
	$sowb->register_general_scripts();
	
	do_action( 'siteorigin_widgets_render_preview_' . $widget->id_base, $widget );

	ob_start();
	$widget->widget( array(
		'before_widget' => '',
		'after_widget' => '',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	), $instance);
	$widget_html = ob_get_clean();

	// Print all the scripts and styles
	?>
	<html>
	<head>
		<title><?php _e('Widget Preview', 'so-widgets-bundle') ?></title>
		<?php
		wp_print_scripts();
		wp_print_styles();
		?>
	</head>
	<body>
	<?php // A lot of themes use entry-content as their main content wrapper ?>
	<div class="entry-content">
		<?php echo $widget_html ?>
	</div>
	</body>
	</html>

	<?php
	wp_die();
}
add_action('wp_ajax_so_widgets_preview', 'siteorigin_widget_preview_widget_action');

/**
 * Action to handle searching posts
 */
function siteorigin_widget_action_search_posts() {
	if ( empty( $_REQUEST['_widgets_nonce'] ) || ! wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) {
		wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 403 );
	}

	// Get all public post types, besides attachments
	$post_types = (array) get_post_types( array(
		'public' => true,
	) );

	if ( ! empty( $_REQUEST['postTypes'] ) ) {
		$post_types = array_intersect( explode( ',', $_REQUEST['postTypes'] ), $post_types );
	} else {
		unset( $post_types['attachment'] );
	}

	$post_types = apply_filters( 'siteorigin_widgets_search_posts_post_types', $post_types );

	global $wpdb;
	if( !empty($_GET['query']) ) {
		$query = "AND post_title LIKE '%" . esc_sql( $_GET['query'] ) . "%'";
	}
	else {
		$query = '';
	}

	$post_types = "'" . implode("', '", array_map( 'esc_sql', $post_types ) ) . "'";

	$results = $wpdb->get_results( "
		SELECT ID AS 'value', post_title AS label, post_type AS 'type'
		FROM {$wpdb->posts}
		WHERE
			post_type IN ( {$post_types} ) AND post_status = 'publish' {$query}
		ORDER BY post_modified DESC
		LIMIT 20
	", ARRAY_A );
	
	wp_send_json( apply_filters( 'siteorigin_widgets_search_posts_results', $results ) );
}
add_action('wp_ajax_so_widgets_search_posts', 'siteorigin_widget_action_search_posts');

/**
 * Action to handle searching taxonomy terms.
 */
function siteorigin_widget_action_search_terms() {
	if ( empty( $_REQUEST['_widgets_nonce'] ) || ! wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) {
		wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 403 );
	}
	
	global $wpdb;
	$term = ! empty($_GET['term']) ? stripslashes($_GET['term']) : '';
	$term = trim($term, '%');

	$query = $wpdb->prepare("
		SELECT terms.term_id, terms.slug AS 'value', terms.name AS 'label', termtaxonomy.taxonomy AS 'type'
		FROM $wpdb->terms AS terms
		JOIN $wpdb->term_taxonomy AS termtaxonomy ON terms.term_id = termtaxonomy.term_id
		WHERE
			terms.name LIKE '%s'
		LIMIT 20
	", '%' . esc_sql( $term ) . '%');

	$results = array();

	foreach ( $wpdb->get_results( $query ) as $result ) {
		$results[] = array(
			'value' => $result->type . ':' . $result->value,
			'label' => $result->label,
			'type' => $result->type,
		);
	}

	wp_send_json( $results );
}
add_action('wp_ajax_so_widgets_search_terms', 'siteorigin_widget_action_search_terms');

/**
 * Action for getting the number of posts returned by a query.
 */
function siteorigin_widget_get_posts_count_action() {

	if ( empty( $_REQUEST['_widgets_nonce'] ) || ! wp_verify_nonce( $_REQUEST['_widgets_nonce'], 'widgets_action' ) ) {
		wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 403 );
	}

	$query = stripslashes( $_POST['query'] );
	
	wp_send_json( array( 'posts_count' => siteorigin_widget_post_selector_count_posts( $query ) ) );
}

add_action( 'wp_ajax_sow_get_posts_count', 'siteorigin_widget_get_posts_count_action' );


function siteorigin_widget_remote_image_search(){
	if( empty( $_GET[ '_sononce' ] ) || ! wp_verify_nonce( $_GET[ '_sononce' ], 'so-image' ) ) {
		wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 403 );
	}

	if( empty( $_GET['q'] ) ) {
		wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 400 );
	}

	// Send the query to stock search server
	$url = add_query_arg( array(
		'q' => $_GET[ 'q' ],
		'page' => !empty( $_GET[ 'page' ] ) ? intval( $_GET[ 'page' ] ) : 1,
	), 'http://stock.siteorigin.com/wp-admin/admin-ajax.php?action=image_search' );

	$result = wp_remote_get( $url, array(
		'timeout' => 20,
	) );

	if( ! is_wp_error( $result ) ) {
		$result = json_decode( $result['body'], true );
		if( !empty( $result['items'] ) ) {
			foreach( $result['items'] as & $r ) {
				if( !empty( $r['full_url'] ) ) {
					$r['import_signature'] = md5( $r['full_url'] . '::' . NONCE_SALT );
				}
			}
		}
		wp_send_json( $result );
	}
	else {
		$result = array(
			'error' => true,
			'message' => $result->get_error_message()
		);
		wp_send_json_error( $result );
	}

}
add_action('wp_ajax_so_widgets_image_search', 'siteorigin_widget_remote_image_search');

function siteorigin_widget_image_import(){
	if( empty( $_GET[ '_sononce' ] ) || ! wp_verify_nonce( $_GET[ '_sononce' ], 'so-image' ) ) {
		$result = array(
			'error' => true,
			'message' => __( 'Nonce error', 'so-widgets-bundle' ),
		);
	}
	else if(
		empty( $_GET['import_signature'] ) ||
		empty( $_GET['full_url'] ) ||
		md5( $_GET['full_url'] . '::' . NONCE_SALT ) !== $_GET['import_signature']
	) {
		$result = array(
			'error' => true,
			'message' => __( 'Signature error', 'so-widgets-bundle' ),
		);
	}
	else {
		// Fetch the image
		$src = media_sideload_image( $_GET['full_url'], $_GET['post_id'], null, 'src' );
		if( is_wp_error( $src ) ) {
			$result = array(
				'error' => true,
				'message' => $src->get_error_code(),
			);
		}
		else {
			global $wpdb;
			$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $src ) );
			if( !empty( $attachment ) ) {
				$thumb_src = wp_get_attachment_image_src( $attachment[0], 'thumbnail' );
				$result = array(
					'error' => false,
					'attachment_id' => $attachment[0],
					'thumb' => $thumb_src[0]
				);
			}
			else {
				$result = array(
					'error' => true,
					'message' => __( 'Attachment error', 'so-widgets-bundle' ),
				);
			}
		}
	}

	// Return the result
	wp_send_json( $result );
}
add_action('wp_ajax_so_widgets_image_import', 'siteorigin_widget_image_import');

/**
 * Action to handle a user dismissing a teaser notice.
 */
function siteorigin_widgets_dismiss_widget_action(){
	if( empty( $_GET[ '_wpnonce' ] ) || ! wp_verify_nonce( $_GET[ '_wpnonce' ], 'dismiss-widget-teaser' ) ) {
		wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 403 );
	}
	if( empty( $_GET[ 'widget' ] ) ) {
		wp_die( __( 'Invalid request.', 'so-widgets-bundle' ), 400 );
	}

	$dismissed = get_user_meta( get_current_user_id(), 'teasers_dismissed', true );
	if( empty( $dismissed ) ) {
		$dismissed = array();
	}

	$dismissed[ $_GET[ 'widget' ] ] = true;

	update_user_meta( get_current_user_id(), 'teasers_dismissed', $dismissed );
	
	wp_die();
}
add_action( 'wp_ajax_so_dismiss_widget_teaser', 'siteorigin_widgets_dismiss_widget_action' );

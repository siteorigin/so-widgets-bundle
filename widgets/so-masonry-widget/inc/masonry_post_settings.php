<?php

/**
 * Add the image sizes used by the masonry widget.
 */
function sow_masonry_register_image_sizes(){
	add_image_size( 'so-masonry-size-11', 280, 280, true );
	add_image_size( 'so-masonry-size-12', 280, 560, true );
	add_image_size( 'so-masonry-size-21', 560, 280, true );
	add_image_size( 'so-masonry-size-22', 560, 560, true );
}
add_action('init', 'sow_masonry_register_image_sizes');

/**
 * Add the image sizes used by the masonry widget.
 */
function sow_masonry_save_post($post_id){
	if(empty($_POST['_so_masonry_nonce']) || !wp_verify_nonce( $_POST['_so_masonry_nonce'], 'save' ) ) return;
	if(!current_user_can('edit_post', $post_id)) return;

	$settings = array_map( 'stripslashes', $_POST['masonry_post'] );
	update_post_meta( $post_id, 'sow_masonry_settings', $settings );
}
add_action( 'save_post', 'sow_masonry_save_post' );

function sow_masonry_add_meta_boxes(){
	$masonry_post_types = get_option( 'sow_masonry_post_types', array('post') );

	foreach ($masonry_post_types as $screen) {
		add_meta_box(
			'sow_masonry_metabox',
			__( 'Masonry Widget Settings', 'siteorigin-widgets' ),
			'sow_masonry_meta_box_render',
			$screen,
			'side'
		);
	}
}

function sow_masonry_get_settings($post_id){
	$settings = (array) get_post_meta( $post_id, 'sow_masonry_settings', true );
	$settings = wp_parse_args($settings, array(
		'size' => '11',
	));
	return $settings;
}

function sow_masonry_meta_box_render($post){
	$settings = sow_masonry_get_settings($post->ID);

	?>
	<table class="form-table">
		<tbody>
		<tr>
			<th scope="row"><?php _e('Brick Size', 'siteorigin-widgets') ?></th>
			<td>
				<select name="masonry_post[size]">
					<option value="11" <?php selected( '11', $settings['size'] ) ?>><?php _e( '1 by 1', 'so-masonry' ) ?></option>
					<option value="12" <?php selected( '12', $settings['size'] ) ?>><?php _e( '1 by 2', 'so-masonry' ) ?></option>
					<option value="21" <?php selected( '21', $settings['size'] ) ?>><?php _e( '2 by 1', 'so-masonry' ) ?></option>
					<option value="22" <?php selected( '22', $settings['size'] ) ?>><?php _e( '2 by 2', 'so-masonry' ) ?></option>
				</select>
			</td>
		</tr>
		</tbody>
	</table>
	<?php
	wp_nonce_field('save', '_so_masonry_nonce');
}

add_action( 'add_meta_boxes', 'sow_masonry_add_meta_boxes' );
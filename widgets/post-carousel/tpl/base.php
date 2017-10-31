<?php
/**
 * @var array $args
 * @var string $title
 * @var WP_Query $posts
 * @var string $default_thumbnail
 * @var string $storage_hash
 */

?>

<?php if($posts->have_posts()) : ?>
	<div class="sow-carousel-title">
		<?php if( ! empty( $title ) ) echo $args['before_title'] . esc_html( $title ) . $args['after_title'] ?>

		<a href="#" class="sow-carousel-next" title="<?php esc_attr_e('Next', 'so-widgets-bundle') ?>"></a>
		<a href="#" class="sow-carousel-previous" title="<?php esc_attr_e('Previous', 'so-widgets-bundle') ?>"></a>
	</div>

	<div class="sow-carousel-container<?php if( is_rtl() ) echo ' js-rtl' ?>">

		<a href="#" class="sow-carousel-previous" title="<?php esc_attr_e('Previous', 'so-widgets-bundle') ?>"></a>

		<a href="#" class="sow-carousel-next" title="<?php esc_attr_e('Next', 'so-widgets-bundle') ?>"></a>

		<div class="sow-carousel-wrapper"
		     data-found-posts="<?php echo esc_attr($posts->found_posts) ?>"
		     data-ajax-url="<?php echo sow_esc_url( wp_nonce_url( admin_url('admin-ajax.php'), 'widgets_action', '_widgets_nonce' ) ) ?>"
			>
			<ul class="sow-carousel-items">
				<?php include plugin_dir_path( __FILE__ ) . 'carousel-post-loop.php' ?>
			</ul>
		</div>
	</div>
	<input type="hidden" name="instance_hash" value="<?php echo esc_attr( $storage_hash ) ?>"/>
<?php endif; ?>

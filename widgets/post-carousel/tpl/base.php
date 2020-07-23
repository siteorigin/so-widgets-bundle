<?php
/**
 * @var array $args
 * @var string $title
 * @var WP_Query $posts
 * @var string $default_thumbnail
 * @var boolean $loop_posts
 * @var string $storage_hash
 */

?>

<?php if(! empty( $posts ) && $posts->have_posts() ) : ?>
	<div class="sow-carousel-title<?php if ( ! empty( $title ) ) echo ' has-title'; ?>">
		<?php if( ! empty( $title ) ) echo $args['before_title'] . esc_html( $title ) . $args['after_title'] ?>

		<div class="sow-carousel-navigation">
			<a href="#" class="sow-carousel-next" title="<?php esc_attr_e('Next', 'so-widgets-bundle') ?>" aria-label="<?php esc_attr_e( 'Next Posts', 'so-widgets-bundle') ?>" role="button"></a>
			<a href="#" class="sow-carousel-previous" title="<?php esc_attr_e('Previous', 'so-widgets-bundle') ?>" aria-label="<?php esc_attr_e( 'Previous Posts', 'so-widgets-bundle') ?>" role="button"></a>
		</div>

	</div>

	<div class="sow-carousel-container">

		<a href="#" class="sow-carousel-previous" title="<?php esc_attr_e('Previous', 'so-widgets-bundle') ?>" aria-label="<?php esc_attr_e( 'Previous Posts', 'so-widgets-bundle') ?>" role="button"></a>

		<a href="#" class="sow-carousel-next" title="<?php esc_attr_e('Next', 'so-widgets-bundle') ?>" aria-label="<?php esc_attr_e( 'Next Posts', 'so-widgets-bundle') ?>" role="button"></a>

		<div class="sow-carousel-wrapper"
		     data-post-count="<?php echo esc_attr($posts->found_posts) ?>"
		     data-loop-posts-enabled="<?php echo esc_attr( $loop_posts ) ?>"
		     data-ajax-url="<?php echo sow_esc_url( wp_nonce_url( admin_url('admin-ajax.php'), 'widgets_action', '_widgets_nonce' ) ) ?>"
		     data-page="1"
		     data-fetching="false"
		     data-dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>"
		>
			<div class="sow-carousel-items">
				<?php include plugin_dir_path( __FILE__ ) . 'carousel-post-loop.php' ?>
			</div>
		</div>
	</div>
	<input type="hidden" name="instance_hash" value="<?php echo esc_attr( $storage_hash ) ?>"/>
<?php endif; ?>

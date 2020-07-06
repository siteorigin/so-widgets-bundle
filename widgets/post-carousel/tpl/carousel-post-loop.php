<?php
/**
 * @var WP_Query $posts
 * @var string $default_thumbnail
 */
while($posts->have_posts()) : $posts->the_post(); ?>
	<div class="sow-carousel-item">
		<div class="sow-carousel-thumbnail">
			<?php if( has_post_thumbnail() ) : $img = wp_get_attachment_image_src(get_post_thumbnail_id(), $instance['image_size']); ?>
				<a href="<?php the_permalink() ?>" style="background-image: url(<?php echo sow_esc_url($img[0]) ?>)" aria-labelledby="sow-carousel-id-<?php echo the_ID(); ?>">
					<span class="overlay"></span>
				</a>
			<?php else : ?>
				<a href="<?php the_permalink() ?>" class="sow-carousel-default-thumbnail"
				<?php echo $link_target == 'new' ? 'target="_blank" rel="noopener noreferrer"': ''; ?>
				<?php echo ! empty( $default_thumbnail ) ?
				'style="background-image: url('. sow_esc_url( $default_thumbnail ) .')"' : '' ?> aria-labelledby="sow-carousel-id-<?php echo the_ID(); ?>"><span class="overlay"></span></a>
			<?php endif; ?>
		</div>
		<h3><a href="<?php the_permalink() ?>" id="sow-carousel-id-<?php echo the_ID(); ?>"><?php the_title() ?></a></h3>
	</div>
<?php endwhile; wp_reset_postdata(); ?>

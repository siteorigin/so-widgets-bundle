
<div class="so-masonry-container <?php if( $responsive ) echo 'responsive' ?>">
	<?php while($posts->have_posts()) : $posts->the_post(); ?>
		<div class="masonry-brick <?php echo esc_attr('size-'. $this->get_brick_size(get_the_ID(), $instance) . ' ' . (!has_post_thumbnail() ? 'no-thumbnail' : '')) ?>">
			<div class="post-information">
				<h2><a href="<?php the_permalink() ?>"><?php the_title() ?></h2></a>
				<div class="entry-meta">
					<?php
					printf( __( 'Posted on <a href="%1$s" title="%2$s" rel="bookmark"><time class="entry-date" datetime="%3$s">%4$s</time></a><span class="byline"> by <span class="author vcard"><a class="url fn n" href="%5$s" title="%6$s" rel="author">%7$s</a></span></span>', 'so-masonry' ),
						esc_url( get_permalink() ),
						esc_attr( get_the_time() ),
						esc_attr( get_the_date( 'c' ) ),
						esc_html( get_the_date() ),
						esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
						esc_attr( sprintf( __( 'View all posts by %s', 'so-masonry' ), get_the_author() ) ),
						get_the_author()
					);
					?>
				</div><!-- .entry-meta -->
			</div>

			<a href="<?php the_permalink() ?>" class="thumbnail-link">
				<?php if(has_post_thumbnail()) : ?>
					<?php echo get_the_post_thumbnail(get_the_ID(), 'so-masonry-size-'.$settings['size']) ?>
				<?php endif; ?>
			</a>
		</div>
	<?php endwhile; wp_reset_postdata(); ?>
</div>
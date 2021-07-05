<?php if ( ! empty( $posts ) && $posts->have_posts() ) : ?>
	<?php if ( ! empty( $instance['title'] ) ) echo $args['before_title'] . $instance['title'] . $args['after_title'] ?>
	<div class="sow-blog sow-blog-layout-standard sow-blog-columns-<?php echo esc_attr( $settings['column-count'] ); ?>">
		<?php while( $posts->have_posts() ) : $posts->the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<div class="sow-blog-header-container">
					<?php $this->post_featured_image( $settings ); ?>
					<header class="entry-header">
						<?php
						the_title(
							'<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">',
							'</a></h2>'
						);
						?>
						<div class="entry-meta">
							<?php $this->post_meta( $settings ); ?>
						</div>
					</header>
				</div>

				<div class="entry-content">
					<?php
						if ( $settings['content'] == 'full' ) {
							the_content();
						} else {
							$this->generate_excerpt( $settings );
						}
					?>
				</div>
			</article>
		<?php endwhile; ?>
		<?php $this->paginate_links( $settings, $posts ); ?>
	</div>
<?php endif; ?>
<?php wp_reset_postdata(); ?>

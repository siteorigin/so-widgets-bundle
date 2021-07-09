<?php if ( ! empty( $posts ) && $posts->have_posts() ) : ?>
	<div class="sow-blog sow-blog-layout-portfolio">
		<?php if ( ! empty( $instance['title'] ) ) echo $args['before_title'] . $instance['title'] . $args['after_title']; ?>
		<?php if ( $settings['categories'] ) : ?>
			<?php if ( ! is_wp_error( $template_settings['terms'] ) ) : ?>
				<div class="sow-portfolio-filter-terms">
					<button data-filter="*" class="active"><?php echo esc_html__( 'All', 'siteorigin-corp' ); ?></button>
					<?php foreach ( $template_settings['terms'] as $tax_term ) : ?>
						<button data-filter=".<?php echo $tax_term->slug; ?>"><?php echo $tax_term->slug; ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<div class="sow-blog-columns" style="position: relative;">
			<?php while ( $posts->have_posts() ) : $posts->the_post(); ?>
				<?php
				// If this post doesn't have a thumbnail, skip it.
				if ( ! has_post_thumbnail() ) {
					continue;
				}
				$types = null;
				if ( $settings['categories'] ) {
					$terms = $this->portfolio_get_terms( $instance, get_the_ID() );
					if ( ! is_wp_error( $terms ) ) {
						$filtering_links = array();

						if ( $terms ) {
							foreach ( $terms as $term ) {
								$filtering_links[] = $term->slug;
							}
						}

						$filtering = join( ', ', $filtering_links );
						$types = $filtering ? join( ' ', $filtering_links ) : ' ';
					}
				}
				?>
				<article id="post-<?php the_ID(); ?> <?php echo $types; ?>" <?php post_class( 'sow-portfolio-item ' . $types ); ?>>
					<div class="entry-thumbnail">
						<a href="<?php the_permalink(); ?>">
							<div class="entry-overlay"></div>
							<div class="entry-content">
								<?php the_title( '<h2 class="entry-title">', '</h2>' ); ?>
								<?php if ( $settings['categories'] ) : ?>
								<div class="entry-divider"></div>
								<span class="entry-project-type"><?php echo $filtering; ?></span>
								<?php endif; ?>
							</div>
							<?php the_post_thumbnail(); ?>
						</a>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
	<?php $this->paginate_links( $settings, $posts ); ?>
	</div>
<?php endif; ?>
<?php wp_reset_postdata(); ?>

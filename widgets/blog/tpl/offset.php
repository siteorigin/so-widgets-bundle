<article id="post-<?php the_ID(); ?>" <?php post_class( 'sow-blog-columns' ); ?>>
	<div class="sow-blog-entry-offset">
		<?php if ( $settings['author'] ) : ?>
			<div class="entry-author-avatar">
				<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>">
					<?php echo get_avatar( get_the_author_meta( 'ID' ), 70 ); ?>
				</a>
			</div>
			<div class="entry-author-link">
				<span class="meta-text"><?php esc_html_e( 'Written by', 'so-widgets-bundle' ); ?></span>
				<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>">
					<?php echo get_the_author(); ?>
				</a>
			</div>
		<?php endif; ?>

		<?php if ( $settings['categories'] ) : ?>
			<div class="entry-categories">
				<span class="meta-text"><?php esc_html_e( 'Posted in', 'so-widgets-bundle' ); ?></span>
				<?php the_category( ', ', '', '' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( comments_open() && $settings['comment_count'] ) : ?>
			<div class="entry-comments">
				<span class="meta-text"><?php esc_html_e( 'Comments', 'so-widgets-bundle' ); ?></span>
				<?php
				echo comments_popup_link(
					esc_html__( 'Post a comment', 'so-widgets-bundle' ),
					esc_html__( '1 Comment', 'so-widgets-bundle' ),
					esc_html__( '% Comments', 'so-widgets-bundle' )
				);
				?>
			</div>
		<?php endif; ?>
	</div>
	<div class="sow-blog-entry">
		<?php $this->post_featured_image( $settings ); ?>
		<div class="sow-blog-content-wrapper">
			<header class="entry-header">
				<?php
				the_title(
					'<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">',
					'</a></h2>'
				);
				if ( ! empty( $template_settings['time_string'] ) ) {
					$time_string = sprintf( $template_settings['time_string'],
						esc_attr( get_the_date( DATE_W3C ) ),
						esc_html( get_the_date( $template_settings['date_format'] ) ),
						esc_attr( get_the_modified_date( DATE_W3C ) ),
						esc_html( get_the_modified_date( $template_settings['date_format'] ) )
					);
					$template_settings['time_string'] = sprintf(
						/* translators: %s: post date. */
						esc_html_x( 'Posted on %s', 'post date', 'so-widgets-bundle' ),
						'<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $time_string . '</a>'
					);
					?>
					<div class="entry-meta">
						<?php
						echo '<span class="posted-on">';
						printf(
							/* translators: %s: post date. */
							esc_html_x( 'Posted on %s', 'post date', 'so-widgets-bundle' ),
							'<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $time_string . '</a>'
						);
						echo '</span>';
						?>
					</div>
				<?php } ?>
			</header>

			<div class="entry-content">
				<?php
					if ( $settings['content'] == 'full' ) {
						the_content();
					} else {
						$this->generate_excerpt( $settings );
					}
				?>
			</div>
		</div>
	</div>
</article>

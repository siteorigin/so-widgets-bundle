<article id="post-<?php the_ID(); ?>" <?php post_class( 'sow-blog-columns' ); ?> style="display: flex; flex-wrap: wrap; justify-content: space-between; margin: 0 0 40px;">
	<div class="sow-blog-entry-offset" style="box-sizing: border-box; padding-right: 30px; width: 22%;">
		<?php if ( $settings['author'] ) { ?>
			<?php if ( function_exists( 'coauthors_posts_links' ) ) { ?>
				<?php $coauthors = get_coauthors(); ?>
				<span class="sow-meta-text">
					<?php echo esc_html( _n( 'Author', 'Authors', count( $coauthors ), 'so-widgets-bundle' ) ); ?>	
				</span>
				<?php foreach ( $coauthors as $author ) { ?>
					<div class="sow-entry-author-avatar">
						<a href="<?php echo get_author_posts_url( $author->ID ); ?>">
							<?php echo get_avatar( $author->ID, 70 ); ?>
						</a>
					</div>
					<div class="sow-entry-author-link">
						<?php echo coauthors_posts_links_single( $author ); ?>
					</div>
				<?php } ?>
			<?php } else { ?>
				<div class="sow-entry-author-avatar">
					<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>">
						<?php echo get_avatar( get_the_author_meta( 'ID' ), 70 ); ?>
					</a>
				</div>
				<div class="sow-entry-author-link">
					<span class="sow-meta-text"><?php esc_html_e( 'Written by', 'so-widgets-bundle' ); ?></span>
					<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>">
						<?php echo get_the_author(); ?>
					</a>
				</div>
			<?php } ?>
		<?php } ?>

		<?php if ( $settings['categories'] ) { ?>
			<div class="sow-entry-categories">
				<span class="sow-meta-text"><?php esc_html_e( 'Posted in', 'so-widgets-bundle' ); ?></span>
				<?php the_category( ', ', '', '' ); ?>
			</div>
		<?php } ?>

		<?php if ( ! empty( $settings['tags'] ) && has_tag() ) { ?>
			<div class="sow-entry-tags">
				<span class="sow-meta-text"><?php esc_html_e( 'Tags', 'so-widgets-bundle' ); ?></span>
				<?php the_tags( '' ); ?>
			</div>
		<?php } ?>

		<?php if ( comments_open() && $settings['comment_count'] ) { ?>
			<div class="sow-entry-comments">
				<span class="sow-meta-text"><?php esc_html_e( 'Comments', 'so-widgets-bundle' ); ?></span>
				<?php
				echo comments_popup_link(
					esc_html__( 'Post a comment', 'so-widgets-bundle' ),
					esc_html__( '1 Comment', 'so-widgets-bundle' ),
					esc_html__( '% Comments', 'so-widgets-bundle' )
				);
				?>
			</div>
		<?php } ?>
	</div>
	<div class="sow-blog-entry" style="width: 78%;">
		<?php SiteOrigin_Widget_Blog_Widget::post_featured_image( $settings ); ?>
		<div class="sow-blog-content-wrapper" style="padding: 25px 30px 33px;">
			<header class="sow-entry-header" style="margin-bottom: 20px;">
				<?php
				SiteOrigin_Widget_Blog_Widget::generate_post_title( $settings );
				if ( ! empty( $template_settings['time_string'] ) ) {
					$time_string = sprintf(
						$template_settings['time_string'],
						esc_attr( get_the_date( DATE_W3C ) ),
						esc_html( get_the_date( $template_settings['date_format'] ) ),
						esc_attr( get_the_modified_date( DATE_W3C ) ),
						esc_html( get_the_modified_date( $template_settings['date_format'] ) )
					);
					?>
					<div class="sow-entry-meta">
						<?php
						echo '<span class="sow-posted-on">';
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

			<div class="sow-entry-content">
				<?php
				if ( $settings['content'] == 'full' ) {
					the_content();
				} else {
					SiteOrigin_Widget_Blog_Widget::generate_excerpt( $settings );
				}
				?>
			</div>
		</div>
	</div>
</article>

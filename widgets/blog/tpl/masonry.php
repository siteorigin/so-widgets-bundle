<article id="post-<?php the_ID(); ?>" <?php post_class( 'sow-masonry-item' ); ?>>
	<?php SiteOrigin_Widget_Blog_Widget::post_featured_image( $settings, true ); ?>
	<div class="sow-blog-content-wrapper">
		<header class="sow-entry-header">
			<?php
			the_title(
				'<h2 class="sow-entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">',
				'</a></h2>'
			);
			?>
			<div class="sow-entry-meta">
				<?php SiteOrigin_Widget_Blog_Widget::post_meta( $settings ); ?>
			</div>
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
</article>

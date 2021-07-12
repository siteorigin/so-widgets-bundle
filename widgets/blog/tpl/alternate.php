<?php $thumbnail_class = ! $settings['featured_image'] || ! has_post_thumbnail() ? 'sow-no-thumbnail' : ''; ?>
<article id="post-<?php the_ID(); ?>" <?php post_class( "sow-blog-columns $thumbnail_class" ); ?>>
	<?php $this->post_featured_image( $settings ); ?>
	<div class="sow-blog-content-wrapper">
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
</article>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'sow-blog-columns' ); ?> style="display: flex; justify-content: space-between; margin-bottom: 30px;">
	<?php SiteOrigin_Widget_Blog_Widget::post_featured_image( $settings ); ?>
	<div class="sow-blog-content-wrapper">
		<header class="sow-entry-header" style="margin-bottom: 18px;">
			<?php SiteOrigin_Widget_Blog_Widget::generate_post_title( $settings ); ?>
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

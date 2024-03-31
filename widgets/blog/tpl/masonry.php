<article id="post-<?php the_ID(); ?>" <?php post_class( 'sow-masonry-item' ); ?>>
	<?php SiteOrigin_Widget_Blog_Widget::post_featured_image( $settings ); ?>
	<div class="sow-blog-content-wrapper" style="padding: 25px 30px 33px;">
		<header class="sow-entry-header">
			<?php SiteOrigin_Widget_Blog_Widget::generate_post_title( $settings ); ?>
			<div class="sow-entry-meta">
				<?php SiteOrigin_Widget_Blog_Widget::post_meta( $settings ); ?>
			</div>
		</header>

		<?php SiteOrigin_Widget_Blog_Widget::output_content( $settings ); ?>
	</div>
</article>

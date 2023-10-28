<article id="post-<?php the_ID(); ?>" <?php post_class( 'sow-blog-columns' ); ?> style="display: flex; justify-content: space-between; margin-bottom: 30px;">
	<?php SiteOrigin_Widget_Blog_Widget::post_featured_image( $settings ); ?>
	<div class="sow-blog-content-wrapper">
		<header class="sow-entry-header">
			<?php SiteOrigin_Widget_Blog_Widget::generate_post_title( $settings ); ?>
			<div class="sow-entry-meta">
				<?php SiteOrigin_Widget_Blog_Widget::post_meta( $settings ); ?>
			</div>
		</header>

		<?php SiteOrigin_Widget_Blog_Widget::output_content( $settings, 18 ); ?>
	</div>
</article>

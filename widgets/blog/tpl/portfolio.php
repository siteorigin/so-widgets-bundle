<?php
$types = null;

if ( $settings['categories'] || $template_settings['filter_categories'] ) {
	$terms = SiteOrigin_Widget_Blog_Widget::portfolio_get_terms( $instance, get_the_ID() );

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
	<div class="sow-entry-thumbnail">
		<a href="<?php the_permalink(); ?>" class="sow-entry-link-overlay">&nbsp;</a>
		<span class="sow-entry-overlay">&nbsp;</span>
		<div class="sow-entry-content">
			<?php
			the_title(
				'<' . $settings['tag'] . ' class="sow-entry-title" style="margin: 0 0 5px;">',
				'</' . $settings['tag'] . '>'
			);
			
			if ( $settings['categories'] ) {
				?>
				<div class="sow-entry-divider"></div>
				<span class="sow-entry-project-type"><?php echo $filtering; ?></span>
			<?php } ?>
		</div>
		<?php
		if ( ! has_post_thumbnail() ) {
			echo apply_filters( 'siteorigin_widgets_blog_featured_image_fallback', false, $settings );
		} else {
			the_post_thumbnail( 'sow-blog-portfolio' );
		}
		?>
	</div>
</article>

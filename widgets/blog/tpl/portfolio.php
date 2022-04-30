<?php
$types = null;
if ( $settings['categories'] ) {
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
		<a href="<?php the_permalink(); ?>">
			<div class="sow-entry-overlay"></div>
			<div class="sow-entry-content">
				<?php the_title( '<h2 class="sow-entry-title">', '</h2>' ); ?>
				<?php if ( $settings['categories'] ) : ?>
					<div class="sow-entry-divider"></div>
					<span class="sow-entry-project-type"><?php echo $filtering; ?></span>
				<?php endif; ?>
			</div>
			<?php the_post_thumbnail( 'sow-blog-portfolio' ); ?>
		</a>
	</div>
</article>

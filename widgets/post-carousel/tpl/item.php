<?php
while ( $settings['posts']->have_posts() ) {
	$settings['posts']->the_post();
	?>
	<div class="sow-carousel-item" tabindex="-1" style="float: left;">
		<div class="sow-carousel-thumbnail">
			<?php
			if ( has_post_thumbnail() ) {
				$img = siteorigin_widgets_get_attachment_image_src( get_post_thumbnail_id(), $settings['image_size'], $settings['default_thumbnail'] );
				?>
				<a
					href="<?php the_permalink(); ?>"
					<?php echo $settings['link_target'] == 'new' ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
					style="background-image: url( <?php echo sow_esc_url( $img[0] ); ?> )"
					aria-labelledby="sow-carousel-id-<?php echo the_ID(); ?>"
					tabindex="-1"
				>
					<span class="overlay"></span>
				</a>
			<?php } else { ?>
				<a
					href="<?php the_permalink(); ?>"
					class="sow-carousel-default-thumbnail"
					<?php echo $settings['link_target'] == 'new' ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
					<?php echo ! empty( $settings['default_thumbnail'] ) ? 'style="background-image: url(' . sow_esc_url( $settings['default_thumbnail'] ) . ')"' : ''; ?>
					aria-labelledby="sow-carousel-id-<?php echo the_ID(); ?>"
					tabindex="-1"
				>
					<span class="overlay"></span>
				</a>
			<?php } ?>
		</div>
		<<?php echo esc_attr( $settings['item_title_tag'] ); ?> class="sow-carousel-item-title">
			<a
				href="<?php the_permalink(); ?>"
				id="sow-carousel-id-<?php echo the_ID(); ?>"
				<?php echo $settings['link_target'] == 'new' ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
				tabindex="-1"
			>

				<?php echo esc_html( get_the_title() ); ?>
			</a>
		</<?php echo esc_attr( $settings['item_title_tag'] ); ?>>
	</div>
<?php
}
wp_reset_postdata();

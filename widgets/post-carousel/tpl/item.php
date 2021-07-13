<?php
while( $settings['posts']->have_posts() ) :
	$settings['posts']->the_post();
	?>
	<div class="sow-carousel-item" tabindex="-1">
		<div class="sow-carousel-thumbnail">
			<?php
			if ( has_post_thumbnail() ) :
				$img = wp_get_attachment_image_src( get_post_thumbnail_id(), $settings['image_size'] );
				?>
				<a
				href="<?php the_permalink() ?>"
					<?php echo $settings['link_target'] == 'new' ? 'target="_blank" rel="noopener noreferrer"' : '' ?>
					style="background-image: url( <?php echo sow_esc_url( $img[0] ); ?> )"
					aria-labelledby="sow-carousel-id-<?php echo the_ID(); ?>"
					tabindex="-1"
				>
					<span class="overlay"></span>
				</a>
			<?php else : ?>
				<a
					href="<?php the_permalink() ?>"
					class="sow-carousel-default-thumbnail"
					<?php echo $settings['link_target'] == 'new' ? 'target="_blank" rel="noopener noreferrer"': ''; ?>
					<?php echo ! empty( $settings['default_thumbnail'] ) ?'style="background-image: url(' . sow_esc_url( $settings['default_thumbnail'] ) . ')"' : ''; ?>
					aria-labelledby="sow-carousel-id-<?php echo the_ID(); ?>"
					tabindex="-1"
				>
					<span class="overlay"></span>
				</a>
			<?php endif; ?>
		</div>
		<h3>
			<a
				href="<?php the_permalink(); ?>"
				id="sow-carousel-id-<?php echo the_ID(); ?>"
				<?php echo $settings['link_target'] == 'new' ? 'target="_blank" rel="noopener noreferrer"': ''; ?>
				tabindex="-1"
			>
			<?php the_title(); ?>
			</a>
		</h3>
	</div>
<?php
endwhile;
wp_reset_postdata();

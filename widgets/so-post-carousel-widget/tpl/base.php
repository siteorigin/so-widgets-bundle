<?php
$query = siteorigin_widget_post_selector_process_query( $instance['posts'] );
$the_query = new WP_Query( $query );
?>

<?php if($the_query->have_posts()) : ?>
	<div class="sow-carousel-title">
		<?php echo $args['before_title'] . esc_html($instance['title']) . $args['after_title'] ?>

		<a href="#" class="sow-carousel-next" title="<?php esc_attr_e('Next', 'siteorigin-widgets') ?>"></a>
		<a href="#" class="sow-carousel-previous" title="<?php esc_attr_e('Previous', 'siteorigin-widgets') ?>"></a>
	</div>

	<div class="sow-carousel-container">

		<a href="#" class="sow-carousel-previous" title="<?php esc_attr_e('Previous', 'siteorigin-widgets') ?>"></a>

		<a href="#" class="sow-carousel-next" title="<?php esc_attr_e('Next', 'siteorigin-widgets') ?>"></a>

		<div class="sow-carousel-wrapper"
		     data-query="<?php echo esc_attr($instance['posts']) ?>"
		     data-found-posts="<?php echo esc_attr($the_query->found_posts) ?>"
		     data-ajax-url="<?php echo sow_esc_url( wp_nonce_url( admin_url('admin-ajax.php'), 'widgets_action', '_widgets_nonce' ) ) ?>"
			>
			<ul class="sow-carousel-items">
				<?php while($the_query->have_posts()) : $the_query->the_post(); ?>
					<li class="sow-carousel-item">
						<div class="sow-carousel-thumbnail">
							<?php if( has_post_thumbnail() ) : $img = wp_get_attachment_image_src(get_post_thumbnail_id(), 'sow-carousel-default'); ?>
								<a href="<?php the_permalink() ?>" style="background-image: url(<?php echo sow_esc_url($img[0]) ?>)">
									<span class="overlay"></span>
								</a>
							<?php else : ?>
								<a href="<?php the_permalink() ?>" class="sow-carousel-default-thumbnail"><span class="overlay"></span></a>
							<?php endif; ?>
						</div>
						<h3><a href="<?php the_permalink() ?>"><?php the_title() ?></a></h3>
					</li>
				<?php endwhile; wp_reset_postdata(); ?>
			</ul>
		</div>
	</div>
<?php endif; ?>
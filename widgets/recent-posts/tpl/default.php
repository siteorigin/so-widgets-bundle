<?php
if ( $query->have_posts() ) {
	do_action( 'siteorigin_widgets_recent_posts_title_before', $instance );

	if ( ! empty( $instance['title'] ) ) {
		echo $args['before_title'] . wp_kses_post( $instance['title'] ) . $args['after_title'];
	}

	do_action( 'siteorigin_widgets_recent_posts_loop_before', $instance );
	?>
	<ul class="sow-recent-posts">
		<?php
		while( $query->have_posts() ) {
			$query->the_post();
			?>
			<li class="sow-recent-posts-item">
				<div class="sow-recent-posts-item-inner">
					<?php do_action( 'siteorigin_widgets_recent_posts_item_start', $instance ); ?>
					<?php SiteOrigin_Widget_Recent_Posts_Widget::featured_image( $settings ); ?>

					<div class="sow-recent-posts-item-hgroup">
						<?php
						SiteOrigin_Widget_Recent_Posts_Widget::post_title( $settings );
						SiteOrigin_Widget_Recent_Posts_Widget::post_date( $settings );
						SiteOrigin_Widget_Recent_Posts_Widget::content( $settings );
						SiteOrigin_Widget_Recent_Posts_Widget::read_more( $settings );
						?>
					</div>
					<?php do_action( 'siteorigin_widgets_recent_posts_item_end', $instance ); ?>
				</div>
			</li>
			<?php
		}
		wp_reset_postdata();
		?>
	</ul>
	<?php
	do_action( 'siteorigin_widgets_recent_posts_loop_after', $instance );
}

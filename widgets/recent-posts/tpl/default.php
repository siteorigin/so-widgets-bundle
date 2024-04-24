<?php
$processed_query = siteorigin_widget_post_selector_process_query( $instance['query'] );
$query = new WP_Query( $processed_query );

// Loop through the posts and do something with them.
if ( $query->have_posts() ) {
	do_action( 'siteorigin_widgets_recent_posts_before', $instance );
	?>
	<ul class="sow-recent-posts">
		<?php
		while( $query->have_posts() ) {
			$query->the_post();
			?>
			<li class="sow-recent-posts-item">
				<?php do_action( 'siteorigin_widgets_recent_posts_item_start', $instance ); ?>
				<span class="sow-recent-posts-title">
					<?php if ( ! empty( $instance['link_title'] ) ) { ?>
						<a
							href="<?php echo esc_url( get_the_permalink() ); ?>"
							<?php echo ! empty( $instance['new_window'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
						>
					<?php } ?>
					<?php the_title() ?>
					<?php if ( ! empty( $instance['link_title'] ) ) { ?>
						</a>
					<?php } ?>
				</span>

				<?php if ( ! empty( $instance['date'] ) ) { ?>
					<span class="sow-recent-posts-date">
						<?php $date_format = isset( $instance['date_format'] ) ? $instance['date_format'] : null; ?>
						<time
							class="published"
							datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"
							aria-label="<?php esc_attr_e( 'Published on:', 'so-widgets-bundle' ); ?>"
						>
							<?php echo esc_html( get_the_date( $date_format ) ); ?>
						</time>

						<time
							class="updated"
							datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"
							aria-label="<?php esc_attr_e( 'Last updated on:', 'so-widgets-bundle' ); ?>"
						>
							<?php echo esc_html( get_the_modified_date( $date_format ) ); ?>
						</time>
					</span>
				<?php } ?>
				<?php do_action( 'siteorigin_widgets_recent_posts_item_end', $instance ); ?>
			</li>
		<?php
		}
		wp_reset_postdata();
		?>
	</ul>
	<?php
	do_action( 'siteorigin_widgets_recent_posts_after', $instance );
}

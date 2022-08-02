<?php if ( ! empty( $posts ) && $posts->have_posts() ) : ?>
	<?php
	if ( ! empty( $instance['title'] ) ) :
		echo $args['before_title'] . $instance['title'] . $args['after_title'];
	endif;

	// Set up moretag override when the full content is set to output.
	if ( $settings['content'] == 'full' && $settings['read_more'] ) :
		set_query_var( 'siteorigin_blog_read_more', ! empty( $settings['read_more_text'] ) ? $settings['read_more_text'] : __( 'Continue reading', 'so-widgets-bundle' ) );

		add_filter( 'the_content_more_link', array( $this, 'alter_read_more_link' ) );
	endif;
	?>
	<div
		class="sow-blog sow-blog-layout-<?php echo esc_attr( $instance['template'] ); ?>"
		data-template="<?php echo esc_attr( $instance['template'] ); ?>"
		data-settings="<?php echo esc_attr( json_encode( $settings ) ); ?>"
		data-paged="<?php echo esc_attr( $posts->query['paged'] ); ?>"
		data-total-pages="<?php echo esc_attr( $posts->max_num_pages ); ?>"
		data-hash="<?php echo esc_attr( $storage_hash ); ?>"
	>
		<?php
		if (
			$settings['categories'] &&
			$instance['template'] == 'portfolio' &&
			! is_wp_error( $template_settings['terms'] )
		) :
		?>
			<div class="sow-portfolio-filter-terms" style="margin-bottom: 25px;">
				<button data-filter="*" class="active" style="background: none; margin-right: 34px; padding: 0 0 6px;">
					<?php echo esc_html__( 'All', 'so-widgets-bundle' ); ?>		
				</button>
				<?php foreach ( $template_settings['terms'] as $tax_term ) : ?>
					<button data-filter=".<?php echo $tax_term->slug; ?>" style="background: none; box-shadow: none; margin-right: 34px; padding: 0 0 6px;">
						<?php echo $tax_term->slug; ?>	
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="sow-blog-posts">
			<?php while ( $posts->have_posts() ) : $posts->the_post(); ?>
				<?php include plugin_dir_path( __FILE__ ) . $instance['template'] . '.php'; ?>
			<?php endwhile; ?>
		</div>
		<?php $this->paginate_links( $settings, $posts, $instance ); ?>
	</div>
	<?php
	if ( $settings['read_more'] ) :
		remove_filter( 'the_content_more_link', array( $this, 'alter_read_more_link' ) );
	endif;
	?>
<?php endif; ?>
<?php wp_reset_postdata(); ?>

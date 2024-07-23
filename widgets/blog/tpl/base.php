<?php if ( ! empty( $posts ) && $posts->have_posts() ) { ?>
	<?php if ( ! empty( $instance['title'] ) ) { ?>
		<div class="sow-blog-title">
			<?php
			echo $args['before_title'] . wp_kses_post( $instance['title'] ) . $args['after_title'];
			?>
		</div>
		<?php
	}

	$this->override_read_more( $settings );
	?>
	<div
		class="sow-blog sow-blog-layout-<?php echo esc_attr( $instance['template'] ); ?>"
		data-template="<?php echo esc_attr( $instance['template'] ); ?>"
		data-settings="<?php echo esc_attr( json_encode( $settings ) ); ?>"
		data-paged="<?php echo esc_attr( $posts->query['paged'] ); ?>"
		data-paging-id="<?php echo esc_attr( $instance['paged_id'] ); ?>"
		data-total-pages="<?php echo esc_attr( $this->total_pages( $posts ) ); ?>"
		data-hash="<?php echo esc_attr( $storage_hash ); ?>"
	>
		<?php
		do_action( 'siteorigin_widgets_blog_output_before', $settings );

		if (
			$instance['template'] == 'portfolio' &&
			$template_settings['filter_categories'] &&
			! is_wp_error( $template_settings['terms'] )
		) {
			?>
			<div class="sow-portfolio-filter-terms" style="margin-bottom: 25px;">
				<button data-filter="*" class="active" style="background: none; margin-right: 34px; padding: 0 0 6px;">
					<?php esc_html_e( 'All', 'so-widgets-bundle' ); ?>
				</button>
				<?php foreach ( $template_settings['terms'] as $tax_term ) { ?>
					<button data-filter=".<?php echo esc_attr( $tax_term->slug ); ?>" style="background: none; box-shadow: none; margin-right: 34px; padding: 0 0 6px;">
						<?php echo esc_html( $tax_term->slug ); ?>
					</button>
				<?php } ?>
			</div>
		<?php } ?>

		<?php $template = SiteOrigin_Widget_Blog_Widget::get_template( $instance ); ?>
		<?php if ( ! empty( $template ) ) { ?>
			<div class="sow-blog-posts">
				<?php
				while ( $posts->have_posts() ) {
					$posts->the_post();
					include $template;
				}
				?>
			</div>
			<?php $this->paginate_links( $settings, $posts, $instance ); ?>
		<?php } ?>
		<?php do_action( 'siteorigin_widgets_blog_output_after', $settings ); ?>
	</div>
	<?php $this->override_read_more( $settings ); ?>
<?php } ?>
<?php wp_reset_postdata(); ?>

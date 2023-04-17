<div class="sow-author-box">
	<?php do_action( 'siteorigin_widgets_author_box_before', $instance ); ?>
	<?php if ( $show_avatar ) { ?>
		<div class="sow-author-box-avatar">
			<?php do_action( 'siteorigin_widgets_author_box_avatar_above', $instance ); ?>
			<?php if ( $link_avatar ) { ?>
				<a href="<?php echo esc_urL( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
			<?php
			}

			echo get_avatar(
				get_the_author_meta( 'ID' ),
				apply_filters( 'siteorigin_widgets_avatar_size', 100 )
			);

			if ( $link_avatar ) {
			?>
				</a>
			<?php } ?>
			<?php do_action( 'siteorigin_widgets_author_box_avatar_below', $instance ); ?>
		</div>
	<?php } ?>
	<div class="sow-author-box-description">
		<?php do_action( 'siteorigin_widgets_author_box_description_above', $instance ); ?>

		<h4 class="sow-author-box-title">
			<small class="sow-author-box-info">
				<?php if ( $link_name ) { ?>
					<a href="<?php echo esc_urL( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
				<?php
				}
				echo get_the_author();

				if ( $link_name ) {
					?>
					</a>
				<?php } ?>

				<?php if ( $link_all_posts ) { ?>
					<a href="<?php echo esc_urL( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
						<?php echo __( sprintf( 'View posts by %s', get_the_author() ), 'so-widgets-bundle' ); ?> 
					</a>
				<?php } ?>
			</small>

			<?php do_action( 'siteorigin_widgets_author_box_description_inline', $instance ); ?>
		</h4>

		<?php if ( $author_bio ) { ?>
			<div class="sow-author-box-bio">
		<?php do_action( 'siteorigin_widgets_author_box_description_bio_before', $instance ); ?>
				<?php echo wp_kses_post( get_the_author_meta( 'description' ), null ); ?>
			</div>
			<?php do_action( 'siteorigin_widgets_author_box_description_bio_after', $instance ); ?>
		<?php } ?>

		<?php do_action( 'siteorigin_widgets_author_box_description_below', $instance ); ?>
	</div>
	<?php do_action( 'siteorigin_widgets_author_box_after', $instance ); ?>
</div>

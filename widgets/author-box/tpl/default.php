<div class="sow-author-box">
	<?php if ( $show_avatar ) { ?>
		<div class="sow-author-box-avatar">
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
		</div>
	<?php } ?>
	<div class="sow-author-box-description">
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
		</h4>
		<?php if ( $author_bio ) { ?>
			<div class="sow-author-box-bio">
				<?php echo wp_kses_post( get_the_author_meta( 'description' ), null ); ?>
			</div>
		<?php } ?>
	</div>
</div>

<div class="sow-author-box">
	<?php do_action( 'siteorigin_widgets_author_box_before', $instance ); ?>
	<?php if ( $show_avatar ) { ?>
		<?php $avatar_image_size = apply_filters( 'siteorigin_widgets_avatar_size', (int) $avatar_image_size ); ?>
		<div class="sow-author-box-avatar" style="max-width: <?php echo esc_attr( $avatar_image_size ); ?>px;">
			<?php do_action( 'siteorigin_widgets_author_box_avatar_above', $instance ); ?>
			<?php if ( $link_avatar ) { ?>
				<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
			<?php
			}

			echo get_avatar(
				get_the_author_meta( 'ID' ),
				$avatar_image_size * 2
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

		<div class="sow-author-box-title-wrapper">
			<h4 class="sow-author-box-title">
				<?php if ( $link_name ) { ?>
					<a href="<?php echo esc_urL( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"  class="sow-author-box-author">
					<?php
				}
				echo get_the_author();

				if ( $link_name ) {
					?>
					</a>
				<?php } ?>
			</h4>

			<?php
			ob_start();
			do_action( 'siteorigin_widgets_author_box_description_inline', $instance );
			$inline_html_action = ob_get_clean();
			?>
			<?php if ( ! empty( $inline_html_action ) ) { ?>
				<div class="sow-author-box-inline-title">
					<?php echo $inline_html_action; ?>
				</div>
			<?php } ?>
		</div>

		<div class="sow-author-box-info">
			<?php if ( $link_all_posts ) { ?>
				<a href="<?php echo esc_urL( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" class="sow-author-box-author-all">
					<?php esc_html_e( sprintf( 'View posts by %s', get_the_author() ), 'so-widgets-bundle' ); ?>
				</a>
			<?php } ?>
		</div>

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

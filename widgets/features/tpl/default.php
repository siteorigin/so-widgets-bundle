<?php
if ( empty( $instance['features'] ) ) {
	return;
}
?>
<ul
	class="sow-features-list
	<?php
	if ( $instance['responsive'] ) {
		echo 'sow-features-responsive';
	}
	?>
">

	<?php
	foreach ( $instance['features'] as $i => $feature ) {
		$link_overlay = ! empty( $instance['link_feature'] ) &&
		! empty( $feature['more_url'] );

		$right_left_read_more = ! empty( $feature['more_text'] ) &&
		(
			empty( $instance['more_text_bottom_align'] ) ||
			(
				$feature['container_position'] == 'right' ||
				$feature['container_position'] == 'left'
			)
		);
		?>
		<li
			class="sow-features-feature sow-icon-container-position-<?php echo esc_attr( $feature['container_position'] ); ?>"
			style="display: flex; flex-direction: <?php echo $this->get_feature_flex_direction( $feature['container_position'], ! empty( $instance['more_text_bottom_align'] ) ); ?>; width: <?php echo esc_attr( $feature_width ); ?>;"
		>
			<?php if ( $link_overlay ) { ?>
				<a
					href="<?php echo sow_esc_url( $feature['more_url'] ); ?>"
					<?php echo (bool) $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
					class="sow-features-feature-linked-column"
				>
					<span class="so-sr-only">
						<?php echo wp_kses_post( $feature['more_text'] ); ?>
					</span>
				</a>
			<?php } ?>

			<?php if ( $right_left_read_more ) { ?>
				<div class="sow-features-feature-right-left-container" style="display: flex; flex-direction: inherit;">
				<?php
			}

			if (
				! empty( $feature['more_url'] ) &&
				$instance['icon_link'] &&
				! $link_overlay
			) { ?>
				<a
					href="<?php echo sow_esc_url( $feature['more_url'] ); ?>"
					<?php echo (bool) $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
				>
			<?php } ?>
			<div
				class="sow-icon-container <?php
				echo ! empty( $instance['container_shape'] ) ?
					'sow-container-' . esc_attr( $instance['container_shape'] ) :
					'sow-container-none';
				?>"
				style="color: <?php echo esc_attr( $feature['container_color'] ); ?>; "
				<?php echo ! empty( $feature['icon_title'] ) ? 'title="' . esc_attr( $feature['icon_title'] ) . '"' : ''; ?>
			>
				<?php
				$icon_styles = array();

				if ( ! empty( $feature['icon_image'] ) || ! empty( $feature['icon_image_fallback'] ) ) {
					$size = empty( $feature['icon_image_size'] ) ? 'thumbnail' : $feature['icon_image_size'];
					$attachment = siteorigin_widgets_get_attachment_image_src(
						$feature['icon_image'],
						$size,
						! empty( $feature['icon_image_fallback'] ) ? $feature['icon_image_fallback'] : false
					);

					if ( ! empty( $attachment ) ) {
						$icon_styles[] = 'background-image: url(' . sow_esc_url( $attachment[0] ) . ')';

						if ( ! empty( $instance['icon_size'] ) ) {
							$icon_styles[] = 'font-size: ' . (int) $instance['icon_size'] . esc_attr( $instance['icon_size_unit'] );
						}

						?>
						<div class="sow-icon-image" style="<?php echo implode( '; ', $icon_styles ); ?>"></div>
						<?php
					}
				} else {
					if ( ! empty( $instance['icon_size'] ) ) {
						$icon_styles[] = 'font-size: ' . (int) $instance['icon_size'] . esc_attr( $instance['icon_size_unit'] );
					}

					if ( ! empty( $feature['icon_color'] ) ) {
						$icon_styles[] = 'color: ' . esc_attr( $feature['icon_color'] );
					}

					echo siteorigin_widget_get_icon( $feature['icon'], $icon_styles );
				}
				?>
			</div>
			<?php
			if (
				! empty( $feature['more_url'] ) &&
				$instance['icon_link'] &&
				! $link_overlay
			) {
				?>
				</a>
				<?php
			}
			?>

			<div class="textwidget">
				<?php if ( $right_left_read_more ) { ?>
					<div class="sow-features-feature-content">
				<?php } ?>

				<?php if ( ! empty( $feature['title'] ) ) { ?>
					<<?php echo esc_html( $tag ); ?> class="sow-features-feature-title">
						<?php
						if (
							! empty( $feature['more_url'] ) &&
							$instance['title_link'] &&
							! $link_overlay
						) {
						?>
							<a
								href="<?php echo sow_esc_url( $feature['more_url'] ); ?>"
								<?php
								echo (bool) $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : '';
								?>
							>
							<?php
						}

						echo wp_kses_post( $feature['title'] );

						if (
							! empty( $feature['more_url'] ) &&
							$instance['title_link'] &&
							! $link_overlay
						) {
								?>
							</a>
							<?php
						}
						?>
					</<?php echo esc_html( $tag ); ?>>
				<?php } ?>

				<?php if ( ! empty( $feature['text'] ) ) { ?>
					<div class="sow-features-feature-text">
					<?php echo wp_kses_post( do_shortcode( $feature['text'] ) ); ?>
					</div>
					<?php
				}

				if (
					$right_left_read_more &&
					! $link_overlay
				) {
					?>
					</div>
					<p class="sow-more-text">
						<?php
						if ( ! empty( $feature['more_url'] ) ) {
							echo '<a href="' . sow_esc_url( $feature['more_url'] ) . '" ' . ( (bool) $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : '' ) . '>';
						}
						?>
						<?php echo wp_kses_post( $feature['more_text'] ); ?>
						<?php
						if ( ! empty( $feature['more_url'] ) ) {
							echo '</a>';
						}
						?>
					</p>
				<?php } ?>
			</div>
			<?php if ( $right_left_read_more ) { ?>
				</div>
			<?php } ?>
			<?php
			if (
				! empty( $feature['more_text'] ) &&
				! empty( $instance['more_text_bottom_align'] ) &&
				(
					$feature['container_position'] == 'top' ||
					$feature['container_position'] == 'bottom'
				) &&
				! $link_overlay
			) {
				?>
				<p class="sow-more-text">
					<?php
					if ( ! empty( $feature['more_url'] ) ) {
						echo '<a href="' . sow_esc_url( $feature['more_url'] ) . '" ' . ( (bool) $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : '' ) . '>';
					}

					echo wp_kses_post( $feature['more_text'] );

					if ( ! empty( $feature['more_url'] ) ) {
						echo '</a>';
					}
					?>
				</p>
			<?php } ?>
		</li>

	<?php } ?>
</ul>

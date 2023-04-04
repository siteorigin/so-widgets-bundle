<?php
$per_row = ! empty( $instance['per_row'] ) ? $instance['per_row'] : 3;

if ( ! empty( $instance['features'] ) ) {
	$last_row = floor( ( count( $instance['features'] ) - 1 ) / $per_row );
}
?>

<div class="sow-features-list <?php if ( $instance['responsive'] ) {
	echo 'sow-features-responsive';
} ?>">

	<?php if ( isset( $instance['features'] ) ) { ?>
		<?php foreach ( $instance['features'] as $i => $feature ) { ?>
			<?php
			$right_left_read_more = ! empty( $feature['more_text'] ) &&
			(
				empty( $instance['more_text_bottom_align'] ) ||
				(
					$feature['container_position'] == 'right' ||
					$feature['container_position'] == 'left'
				)
			);
			?>
			<div
				class="sow-features-feature sow-icon-container-position-<?php echo esc_attr( $feature['container_position'] ); ?> <?php if ( floor( $i / $per_row ) == $last_row ) echo 'sow-features-feature-last-row'; ?>"
				style="display: flex; flex-direction: <?php echo $this->get_feature_flex_direction( $feature['container_position'], ! empty( $instance['more_text_bottom_align'] ) ); ?>; width: <?php echo round( 100 / $per_row, 3 ); ?>%;"
			>
			<?php if ( $right_left_read_more ) { ?>
				<div class="sow-features-feature-right-left-container" style="display: flex; flex-direction: inherit;">
			<?php } ?>

				<?php if ( ! empty( $feature['more_url'] ) && $instance['icon_link'] && empty( $instance['link_feature'] ) ) { ?>
					<a
						href="<?php echo sow_esc_url( $feature['more_url'] ); ?>"
						<?php echo $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
					>
				<?php } ?>
				<div
					class="sow-icon-container <?php echo ! empty( $instance['container_shape'] ) ? 'sow-container-' . esc_attr( $instance['container_shape'] ) : 'sow-container-none'; ?>"
					style="color: <?php echo esc_attr( $feature['container_color'] ); ?>; "
					<?php echo ! empty( $feature['icon_title'] ) ? 'title="' . esc_attr( $feature['icon_title'] ) . '"' : ''; ?>>
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

					?><div class="sow-icon-image" style="<?php echo implode( '; ', $icon_styles ); ?>"></div><?php
				}
			} else {
				if ( ! empty( $instance['icon_size'] ) ) {
					$icon_styles[] = 'font-size: ' . (int) $instance['icon_size'] . esc_attr( $instance['icon_size_unit'] );
				}

				if ( ! empty( $feature['icon_color'] ) ) {
					$icon_styles[] = 'color: ' . $feature['icon_color'];
				}

				echo siteorigin_widget_get_icon( $feature['icon'], $icon_styles );
			}
			?>
				</div>
				<?php if ( ! empty( $feature['more_url'] ) && $instance['icon_link'] && empty( $instance['link_feature'] ) ) { ?>
					</a>
				<?php } ?>

				<div class="textwidget">
					<?php if ( $right_left_read_more ) { ?>
						<div class="sow-features-feature-content">
					<?php } ?>

					<?php if ( ! empty( $feature['title'] ) ) { ?>
						<<?php echo esc_html( $instance['fonts']['title_options']['tag'] ); ?>>
							<?php if ( ! empty( $feature['more_url'] ) && $instance['title_link'] && empty( $instance['link_feature'] ) ) { ?>
								<a
									href="<?php echo sow_esc_url( $feature['more_url'] ); ?>"
									<?php echo $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
								>
							<?php } ?>

							<?php echo wp_kses_post( $feature['title'] ); ?>
							<?php if ( ! empty( $feature['more_url'] ) && $instance['title_link'] && empty( $instance['link_feature'] ) ) { ?>
								</a>
							<?php } ?>
						</<?php echo esc_html( $instance['fonts']['title_options']['tag'] ); ?>>
					<?php } ?>

					<?php if ( ! empty( $feature['text'] ) ) { ?>
						<?php echo do_shortcode( $feature['text'] ); ?>
					<?php } ?>

					<?php if ( $right_left_read_more ) { ?>
						</div>
						<p class="sow-more-text">
							<?php if ( ! empty( $feature['more_url'] ) ) {
								echo '<a href="' . sow_esc_url( $feature['more_url'] ) . '" ' . ( $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : '' ) . '>';
							} ?>
							<?php echo wp_kses_post( $feature['more_text'] ); ?>
							<?php if ( ! empty( $feature['more_url'] ) ) {
								echo '</a>';
							} ?>
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
					)
				) { ?>
					<p class="sow-more-text">
						<?php if ( ! empty( $feature['more_url'] ) ) {
							echo '<a href="' . sow_esc_url( $feature['more_url'] ) . '" ' . ( $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : '' ) . '>';
						} ?>
						<?php echo wp_kses_post( $feature['more_text'] ); ?>
						<?php if ( ! empty( $feature['more_url'] ) ) {
							echo '</a>';
						} ?>
					</p>
				<?php } ?>

				<?php if ( ! empty( $instance['link_feature'] ) && ! empty( $feature['more_url'] ) ) { ?>
					<a
						href="<?php echo sow_esc_url( $feature['more_url'] ); ?>"
						<?php echo $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
						class="sow-features-feature-linked-column"
					>
						&nbsp;
					</a>
				<?php } ?>
			</div>

		<?php } ?>
	<?php } ?>

</div>

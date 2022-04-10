<?php
if ( ! empty( $instance['features'] ) ) {
	$last_row = floor( ( count( $instance['features'] ) - 1 ) / $instance['per_row'] );
}
?>

<div class="sow-features-list <?php if( $instance['responsive'] ) echo 'sow-features-responsive'; ?>">

	<?php if( isset( $instance['features'] ) ) : ?>
		<?php foreach( $instance['features'] as $i => $feature ) : ?>
			<div
				class="sow-features-feature sow-icon-container-position-<?php echo esc_attr( $feature['container_position'] ) ?> <?php if (  floor( $i / $instance['per_row'] ) == $last_row ) echo 'sow-features-feature-last-row'; ?>"
				style="display: flex; flex-direction: <?php echo $this->get_feature_flex_direction( $feature['container_position'] ); ?>; float: left; width: <?php echo round( 100 / $instance['per_row'], 3 ); ?>%;"
			>

				<?php if ( ! empty( $feature['more_url'] ) && $instance['icon_link'] && empty( $instance['link_feature'] ) ) : ?>
					<a
						href="<?php echo sow_esc_url( $feature['more_url'] ); ?>"
						<?php echo $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
					>
				<?php endif; ?>
				<div
					class="sow-icon-container <?php echo !empty($instance['container_shape']) ? 'sow-container-' . esc_attr($instance['container_shape']) : 'sow-container-none'?>"
                    style="color: <?php echo esc_attr($feature['container_color']) ?>; "
					<?php  echo ( ! empty( $feature['icon_title'] ) ? 'title="' . esc_attr( $feature['icon_title'] ) . '"' : '' ); ?>>
					<?php
					$icon_styles = array();
					if( !empty($feature['icon_image']) || !empty($feature['icon_image_fallback']) ) {
						$size = empty( $feature['icon_image_size'] ) ? 'thumbnail' : $feature['icon_image_size'];
						$attachment = siteorigin_widgets_get_attachment_image_src(
							$feature['icon_image'],
							$size,
							! empty( $feature['icon_image_fallback'] ) ? $feature['icon_image_fallback'] : false
						);
						if(!empty($attachment)) {
							$icon_styles[] = 'background-image: url(' . sow_esc_url($attachment[0]) . ')';
							if ( ! empty( $instance['icon_size'] ) ) {
								$icon_styles[] = 'font-size: ' . (int) $instance['icon_size'] . esc_attr( $instance['icon_size_unit'] );
							}

							?><div class="sow-icon-image" style="<?php echo implode('; ', $icon_styles) ?>"></div><?php
						}
					}
					else {
						if ( ! empty( $instance['icon_size'] ) ) {
							$icon_styles[] = 'font-size: '. (int) $instance['icon_size'] . esc_attr( $instance['icon_size_unit'] );
						}

						if(!empty($feature['icon_color'])) $icon_styles[] = 'color: '.$feature['icon_color'];

						echo siteorigin_widget_get_icon($feature['icon'], $icon_styles);
					}
					?>
				</div>
				<?php if ( !empty( $feature['more_url'] ) && $instance['icon_link'] && empty( $instance['link_feature'] ) ) : ?>
					</a>
				<?php endif; ?>

				<div class="textwidget">
					<?php if(!empty($feature['title'])) : ?>
						<<?php echo esc_html( $instance['title_tag'] ); ?>>
							<?php if ( ! empty( $feature['more_url'] ) && $instance['title_link'] && empty( $instance['link_feature'] ) ) : ?>
								<a
									href="<?php echo sow_esc_url( $feature['more_url'] ); ?>"
									<?php echo $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
								>
							<?php endif; ?>

							<?php echo wp_kses_post( $feature['title'] ) ?>
							<?php if ( !empty( $feature['more_url'] ) && $instance['title_link'] && empty( $instance['link_feature'] ) ) : ?>
								</a>
							<?php endif; ?>
						</<?php echo esc_html( $instance['title_tag'] ); ?>>
					<?php endif; ?>

					<?php if(!empty($feature['text'])) : ?>
						<?php echo do_shortcode( $feature['text'] ); ?>
					<?php endif; ?>

					<?php if(!empty($feature['more_text'])) : ?>
						<p class="sow-more-text">
							<?php if( !empty( $feature['more_url'] ) ) echo '<a href="' . sow_esc_url( $feature['more_url'] ) . '" ' . ( $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : '' ) . '>'; ?>
							<?php echo wp_kses_post( $feature['more_text'] ) ?>
							<?php if( !empty( $feature['more_url'] ) ) echo '</a>'; ?>
						</p>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $instance['link_feature'] ) && ! empty( $feature['more_url'] ) ) : ?>
					<a
						href="<?php echo sow_esc_url( $feature['more_url'] ); ?>"
						<?php echo $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
						class="sow-features-feature-linked-column"
					>
						&nbsp;
					</a>
				<?php endif; ?>
			</div>

		<?php endforeach; ?>
	<?php endif; ?>

</div>

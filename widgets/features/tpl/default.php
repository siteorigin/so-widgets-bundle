<?php
$last_row = floor( ( count($instance['features']) - 1 ) / $instance['per_row'] );
?>

<div class="sow-features-list <?php if( $instance['responsive'] ) echo 'sow-features-responsive'; ?>">

	<?php if( isset( $instance['features'] ) ) : ?>
		<?php foreach( $instance['features'] as $i => $feature ) : ?>

			<div class="sow-features-feature sow-icon-container-position-<?php echo esc_attr( $feature['container_position'] ) ?> <?php if(  floor( $i / $instance['per_row'] ) == $last_row ) echo 'sow-features-feature-last-row' ?>" style="width: <?php echo round( 100 / $instance['per_row'], 3 ) ?>%">

				<?php if( !empty( $feature['more_url'] ) && $instance['icon_link'] ) echo '<a href="' . sow_esc_url( $feature['more_url'] ) . '" ' . ( $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : '' ) . '>'; ?>
				<div
					class="sow-icon-container <?php echo !empty($instance['container_shape']) ? 'sow-container-' . esc_attr($instance['container_shape']) : 'sow-container-none'?>"
                    style="color: <?php echo esc_attr($feature['container_color']) ?>; "
					<?php  echo ( ! empty( $feature['icon_title'] ) ? 'title="' . esc_attr( $feature['icon_title'] ) . '"' : '' ); ?>>
					<?php
					$icon_styles = array();
					if( !empty($feature['icon_image']) ) {
						$size = empty( $feature['icon_image_size'] ) ? 'thumbnail' : $feature['icon_image_size'];
						$attachment = wp_get_attachment_image_src( $feature['icon_image'], $size );
						if(!empty($attachment)) {
							$icon_styles[] = 'background-image: url(' . sow_esc_url($attachment[0]) . ')';
							if(!empty($instance['icon_size'])) $icon_styles[] = 'font-size: '.intval($instance['icon_size']) . esc_attr( $instance['icon_size_unit'] );

							?><div class="sow-icon-image" style="<?php echo implode('; ', $icon_styles) ?>"></div><?php
						}
					}
					else {
						if(!empty($instance['icon_size'])) $icon_styles[] = 'font-size: '.intval($instance['icon_size']) . esc_attr( $instance['icon_size_unit'] );
						if(!empty($feature['icon_color'])) $icon_styles[] = 'color: '.$feature['icon_color'];

						echo siteorigin_widget_get_icon($feature['icon'], $icon_styles);
					}
					?>
				</div>
				<?php if( !empty( $feature['more_url'] ) && $instance['icon_link'] ) echo '</a>'; ?>

				<div class="textwidget">
					<?php if(!empty($feature['title'])) : ?>
						<h5>
							<?php if( !empty( $feature['more_url'] ) && $instance['title_link'] ) echo '<a href="' . sow_esc_url( $feature['more_url'] ) . '" ' . ( $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : '' ) . '>'; ?>
							<?php echo wp_kses_post( $feature['title'] ) ?>
							<?php if( !empty( $feature['more_url'] ) && $instance['title_link'] ) echo '</a>'; ?>
						</h5>
					<?php endif; ?>

					<?php if(!empty($feature['text'])) : ?>
						<?php echo wp_kses_post( $feature['text'] ) ?>
					<?php endif; ?>

					<?php if(!empty($feature['more_text'])) : ?>
						<p class="sow-more-text">
							<?php if( !empty( $feature['more_url'] ) ) echo '<a href="' . sow_esc_url( $feature['more_url'] ) . '" ' . ( $instance['new_window'] ? 'target="_blank" rel="noopener noreferrer"' : '' ) . '>'; ?>
							<?php echo wp_kses_post( $feature['more_text'] ) ?>
							<?php if( !empty( $feature['more_url'] ) ) echo '</a>'; ?>
						</p>
					<?php endif; ?>
				</div>
			</div>

		<?php endforeach; ?>
	<?php endif; ?>

</div>

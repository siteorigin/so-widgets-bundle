<?php
$last_row = floor( ( count($instance['features']) - 1 ) / $instance['per_row'] );
?>

<div class="sow-features-list <?php if( $instance['responsive'] ) echo 'sow-features-responsive'; ?>">

	<?php foreach( $instance['features'] as $i => $feature ) : ?>

		<?php if( $i % $instance['per_row'] == 0 && $i != 0 ) : ?>
			<div class="sow-features-clear"></div>
		<?php endif; ?>

		<div class="sow-features-feature <?php if(  floor( $i / $instance['per_row'] ) == $last_row ) echo 'sow-features-feature-last-row' ?>" style="width: <?php echo round( 100 / $instance['per_row'], 3 ) ?>%">

			<?php if( !empty( $feature['more_url'] ) && $instance['icon_link'] ) echo '<a href="' . esc_url( $feature['more_url'] ) . '" ' . ( $instance['new_window'] ? 'target="_blank"' : '' ) . '>'; ?>
			<div class="sow-icon-container sow-container-<?php echo esc_attr($instance['container_shape']) ?>" style="font-size: <?php echo intval($instance['container_size']) ?>px; color: <?php echo esc_attr($feature['container_color']) ?>;">
				<?php
				if( !empty($feature['icon_image']) ) {
					$attachment = wp_get_attachment_image_src($feature['icon_image']);
					if(!empty($attachment)) {
						$icon_styles[] = 'background-image: url(' . esc_url($attachment[0]) . ')';
						if(!empty($instance['icon_size'])) $icon_styles[] = 'font-size: '.intval($instance['icon_size']).'px';

						?><div class="sow-icon-image" style="<?php echo implode('; ', $icon_styles) ?>"></div><?php
					}
				}
				else {
					$icon_styles = array();
					if(!empty($instance['icon_size'])) $icon_styles[] = 'font-size: '.intval($instance['icon_size']).'px';
					if(!empty($feature['icon_color'])) $icon_styles[] = 'color: '.$feature['icon_color'];

					echo siteorigin_widget_get_icon($feature['icon'], $icon_styles);
				}
				?>
			</div>
			<?php if( !empty( $feature['more_url'] ) && $instance['icon_link'] ) echo '</a>'; ?>

			<div class="textwidget">
				<?php if(!empty($feature['title'])) : ?>
					<h5>
						<?php if( !empty( $feature['more_url'] ) && $instance['title_link'] ) echo '<a href="' . esc_url( $feature['more_url'] ) . '" ' . ( $instance['new_window'] ? 'target="_blank"' : '' ) . '>'; ?>
						<?php echo wp_kses_post( $feature['title'] ) ?>
						<?php if( !empty( $feature['more_url'] ) && $instance['title_link'] ) echo '</a>'; ?>
					</h5>
				<?php endif; ?>

				<?php if(!empty($feature['text'])) : ?>
					<p><?php echo wp_kses_post( $feature['text'] ) ?></p>
				<?php endif; ?>

				<?php if(!empty($feature['more_text'])) : ?>
					<p class="sow-more-text">
						<?php if( !empty( $feature['more_url'] ) ) echo '<a href="' . esc_url( $feature['more_url'] ) . '" ' . ( $instance['new_window'] ? 'target="_blank"' : '' ) . '>'; ?>
						<?php echo wp_kses_post( $feature['more_text'] ) ?>
						<?php if( !empty( $feature['more_url'] ) ) echo '</a>'; ?>
					</p>
				<?php endif; ?>
			</div>
		</div>

	<?php endforeach; ?>

</div>
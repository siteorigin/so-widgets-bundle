<?php
$settings = array(
	'pagination' => true,
	'speed' => $instance['speed'],
	'timeout' => $instance['timeout'],
);

if( empty($instance['frames']) ) return;

?>

<div tabindex="0" class="sow-slider-base <?php if( wp_is_mobile() ) echo 'sow-slider-is-mobile' ?> <?php if( !empty($instance['nav_color']) && $instance['nav_color'] == 'black' ) echo 'sow-slider-nav-black' ?>" style="display: none">

	<ul class="sow-slider-images" data-settings="<?php echo esc_attr(json_encode($settings)) ?>">
		<?php
		foreach($instance['frames'] as $frame) {
			if( empty($frame['background_image']) ) $background_image = false;
			else $background_image = wp_get_attachment_image_src($frame['background_image'], 'full');

			if( empty($frame['foreground_image']) ) $foreground_image = false;
			else $foreground_image = wp_get_attachment_image_src($frame['foreground_image'], 'full');

			?>
			<li
				class="sow-slider-image sow-slider-image-<?php echo $frame['background_image_type'] ?>"
				style="<?php if(!empty($background_image[0]) && (!empty($foreground_image)) || !empty($frame['background_videos']) ) echo 'background-image: url('.$background_image[0].');' ?>">
				<?php
				if( !empty( $frame['foreground_image'] ) ) {
					?>
					<div class="sow-slider-image-container">
						<div class="sow-slider-image-wrapper" style="max-width: <?php echo intval($foreground_image[1]) ?>px; ">
							<?php
							if(!empty($frame['url'])) echo '<a href="' . sow_esc_url($frame['url']) . '">';
							echo wp_get_attachment_image($frame['foreground_image'], 'full');
							if(!empty($frame['url'])) echo '</a>';
							?>
						</div>
					</div>
					<?php

					// Render the background videos
					$this->video_code( $frame['background_videos'], array('sow-background-element') );
				}
				else {
					// We need to find another background
					if(!empty($frame['url'])) echo '<a href="' . sow_esc_url($frame['url']) . '" ' . ( !empty($frame['new_window']) ? 'target="_blank"' : '' ) . '>';

					if( !empty($frame['background_videos']) ){
						$this->video_code($frame['background_videos'], array('sow-full-element'));
					}
					else {
						// Lets use the background image
						echo wp_get_attachment_image($frame['background_image'], 'full');
					}

					if(!empty($frame['url'])) echo '</a>';
				}
				?>
			</li>
			<?php
		}
		?>
	</ul>

	<ol class="sow-slider-pagination">
		<?php foreach($instance['frames'] as $i => $frame) : ?>
			<li><a href="#" data-goto="<?php echo $i ?>"><?php echo $i+1 ?></a></li>
		<?php endforeach; ?>
	</ol>

	<div class="sow-slide-nav sow-slide-nav-next">
		<a href="#" data-goto="next" data-action="next">
			<em class="sow-sld-icon-<?php echo sanitize_html_class( $instance['nav_style'] ) ?>-right"></em>
		</a>
	</div>

	<div class="sow-slide-nav sow-slide-nav-prev">
		<a href="#" data-goto="previous" data-action="prev">
			<em class="sow-sld-icon-<?php echo sanitize_html_class( $instance['nav_style'] ) ?>-left"></em>
		</a>
	</div>

</div>

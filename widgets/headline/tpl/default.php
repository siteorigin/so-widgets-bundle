<div class="sow-headline-container <?php if ( $instance['fittext'] ) {
	;
} ?>">
	<?php
	foreach ( $order as $item ) {
		unset( $text );

		switch( $item ) {
			case 'headline':
				case 'headline':
					$text = $headline;
					$tag = $headline_tag;
					$destination_url = $headline_destination_url;
					$new_window = $headline_new_window;
					$class = 'sow-headline';
				case 'sub_headline':
					if ( ! isset( $text ) ) {
						$text = $sub_headline;
						$tag = $sub_headline_tag;
						$destination_url = $sub_headline_destination_url;
						$new_window = $sub_headline_new_window;
						$class = 'sow-sub-headline';
					}

					if ( ! empty( $text ) ) {
						?>
						<<?php echo esc_attr( $tag ); ?> class="<?php echo $class; ?>">
						<?php
						if ( ! empty( $destination_url ) ) { ?>
							<a href="<?php echo sow_esc_url( $destination_url ); ?>" <?php echo (bool) $new_window ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
								<?php
						}

						echo wp_kses_post( $text );

						if ( ! empty( $destination_url ) ) {
							echo '</a>';
						}
						?>
						</<?php echo esc_attr( $tag ); ?>>
						<?php
					}
				break;

			case 'divider':
				if ( $has_divider ) {
					?>
					<div class="decoration">
						<div class="decoration-inside"></div>
					</div>
					<?php
				}
				break;
		}
	}
	?>
</div>

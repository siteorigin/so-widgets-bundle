<div class="sow-headline-container <?php if( $instance['fittext'] ) ?>">
	<?php
	foreach( $order as $item ) {
		switch( $item ) {
			case 'headline' :
				if( !empty( $headline ) ) {
					echo "<$headline_tag class='sow-headline'>";

					if( !empty( $headline_destination_url ) ): ?>
						<a href="<?php echo sow_esc_url( $headline_destination_url ) ?>" <?php echo $headline_new_window ? 'target="_blank"' : '' ?>>
					<?php
					endif;

					echo wp_kses_post( $headline );
					if( !empty( $headline_destination_url ) ) echo '</a>';
					echo "</$headline_tag>";
				}
				break;

			case 'divider' :
				if( $has_divider ) {
					?>
					<div class="decoration">
						<div class="decoration-inside"></div>
					</div>
					<?php
				}
				break;

			case 'sub_headline' :
				if( !empty( $sub_headline ) ) {
					echo "<$sub_headline_tag class='sow-sub-headline'>";

					if( !empty( $sub_headline_destination_url ) ): ?>
						<a href="<?php echo sow_esc_url( $sub_headline_destination_url ) ?>" <?php echo $sub_headline_new_window ? 'target="_blank"' : '' ?>>
					<?php
					endif;

					echo wp_kses_post( $sub_headline );
					if( !empty( $sub_headline_destination_url ) ) echo '</a>';
					echo "</$sub_headline_tag>";
				}
				break;
		}
	}
	?>
</div>
<div class="sow-headline-container <?php if( $instance['fittext'] ) ?>">
	<?php
	foreach( $order as $item ) {
		switch( $item ) {
			case 'headline' :
				if( !empty( $headline ) ) {
					echo '<' . $headline_tag . ' class="sow-headline">' . wp_kses_post( $headline ) . '</' . $headline_tag . '>';
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
					echo '<' . $sub_headline_tag . ' class="sow-sub-headline">' . wp_kses_post( $sub_headline ) . '</' . $sub_headline_tag . '>';
				}
				break;
		}
	}
	?>
</div>
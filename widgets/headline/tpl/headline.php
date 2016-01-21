<div id="sow-headline-container" class="sow-headline">

	<?php if ( !empty( $headline ) ) : ?>
		<<?php echo $headline_tag ?>><?php echo $headline ?></<?php echo $headline_tag ?>>
	<?php endif; ?>

	<?php if ( $has_divider ) : ?>
		<div class="decoration">
			<div class="decoration-inside"></div>
		</div>
	<?php endif; ?>

	<?php if ( !empty( $sub_headline ) ) : ?>
		<<?php echo $sub_headline_tag ?>><?php echo $sub_headline ?></<?php echo $sub_headline_tag ?>>
	<?php endif; ?>

</div>
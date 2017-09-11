<?php
/**
 * @var array $panels
 */

?>
<div>
	<div class="sow-accordion">
	<?php foreach ( $panels as $panel ) : ?>
		<div class="sow-accordion-panel<?php if ( $panel['initial_state'] == 'open' ) echo ' sow-accordion-panel-open'; ?>">
			<div class="sow-accordion-panel-header">
				<?php echo esc_html( $panel['title'] ); ?>
			</div>
			<div class="sow-accordion-panel-content">
				<?php echo wp_kses_post( $panel['content'] ); ?>
			</div>
		</div>
	<?php endforeach; ?>
	</div>
</div>

<?php
/**
 * @var array $panels
 * @var string $icon_open
 * @var string $icon_close
 */

?>
<div>
	<div class="sow-accordion">
	<?php foreach ( $panels as $panel ) : ?>
		<div class="sow-accordion-panel<?php if ( $panel['initial_state'] == 'open' ) echo ' sow-accordion-panel-open'; ?>">
			<div class="sow-accordion-panel-header">
				<?php echo $panel['before_title']; ?>
				<?php echo wp_kses_post( $panel['title'] ); ?>
				<?php echo $panel['after_title']; ?>
				<div class="sow-accordion-open-button">
					<?php echo siteorigin_widget_get_icon( $icon_open ); ?>
				</div>
				<div class="sow-accordion-close-button">
					<?php echo siteorigin_widget_get_icon( $icon_close ); ?>
				</div>
			</div>
			<div class="sow-accordion-panel-content">
				<?php echo wp_kses_post( $panel['content'] ); ?>
			</div>
		</div>
	<?php endforeach; ?>
	</div>
</div>
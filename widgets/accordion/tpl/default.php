<?php
/**
 * @var array $instance
 * @var array $panels
 * @var string $icon_open
 * @var string $icon_close
 */

if( !empty( $instance['title'] ) ) {
	echo $args['before_title'] . $instance['title'] . $args['after_title'];
}
?>
<div>
	<div class="sow-accordion">
	<?php foreach ( $panels as $panel ) : ?>
		<div class="sow-accordion-panel<?php if ( $panel['initial_state'] == 'open' ) echo ' sow-accordion-panel-open'; ?>"
			 data-anchor="<?php echo sanitize_title_with_dashes( $panel['anchor'] ); ?>">
			<div class="sow-accordion-panel-header">
				<div class="sow-accordion-title">
					<?php echo $panel['before_title']; ?>
					<?php echo wp_kses_post( $panel['title'] ); ?>
					<?php echo $panel['after_title']; ?>
				</div>
				<div class="sow-accordion-open-close-button">
					<div class="sow-accordion-open-button">
						<?php echo siteorigin_widget_get_icon( $icon_open ); ?>
					</div>
					<div class="sow-accordion-close-button">
						<?php echo siteorigin_widget_get_icon( $icon_close ); ?>
					</div>
				</div>
			</div>
			<div class="sow-accordion-panel-content">
				<div class="sow-accordion-panel-border">
					<?php $this->render_panel_content( $panel, $instance ); ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
	</div>
</div>

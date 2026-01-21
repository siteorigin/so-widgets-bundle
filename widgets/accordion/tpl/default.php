<?php
/**
 * @var array  $instance
 * @var array  $panels
 * @var string $icon_open
 * @var string $icon_close
 * @var string $title_tag
 * @var int    $title_level
 * @var bool   $title_has_native_heading
 */
if ( ! empty( $instance['title'] ) ) {
	echo $args['before_title'] . wp_kses_post( $instance['title'] ) . $args['after_title'];
}
?>
<div>
	<div class="sow-accordion">
	<?php foreach ( $panels as $panel ) { ?>
		<div class="sow-accordion-panel
		<?php
		if ( $panel['initial_state'] == 'open' ) {
			echo ' sow-accordion-panel-open';
		}
			?>
			"
				data-anchor-id="<?php echo esc_attr( sanitize_title( $panel['anchor'] ) ); ?>">
					<div class="sow-accordion-panel-header-container"<?php if ( ! $title_has_native_heading ) { ?> role="heading" aria-level="<?php echo esc_attr( $title_level ); ?>"<?php } ?>>
					<div class="sow-accordion-panel-header" tabindex="0" role="button" id="accordion-label-<?php echo sanitize_title_with_dashes( $panel['anchor'] ); ?>" aria-controls="accordion-content-<?php echo sanitize_title_with_dashes( $panel['anchor'] ); ?>" aria-expanded="<?php echo $panel['initial_state'] == 'open' ? 'true' : 'false'; ?>">
						<<?php echo esc_attr( $title_tag ); ?> class="sow-accordion-title <?php echo empty( $panel['after_title'] ) ? 'sow-accordion-title-icon-left' : 'sow-accordion-title-icon-right'; ?>">
							<?php echo $panel['before_title']; ?>
							<?php echo wp_kses_post( $panel['title'] ); ?>
							<?php echo $panel['after_title']; ?>
						</<?php echo esc_attr( $title_tag ); ?>>
						<div class="sow-accordion-open-close-button">
							<div class="sow-accordion-open-button">
								<?php echo siteorigin_widget_get_icon( $icon_open ); ?>
							</div>
							<div class="sow-accordion-close-button">
								<?php echo siteorigin_widget_get_icon( $icon_close ); ?>
							</div>
						</div>
					</div>
				</div>

			<div
				class="sow-accordion-panel-content"
				role="region"
				aria-labelledby="accordion-label-<?php echo sanitize_title_with_dashes( $panel['anchor'] ); ?>"
				id="accordion-content-<?php echo sanitize_title_with_dashes( $panel['anchor'] ); ?>"
				<?php
				if ( $panel['initial_state'] == 'closed' ) {
					echo 'style="display: none;"';
				}
				?>
			>
				<div class="sow-accordion-panel-border">
					<?php $this->render_panel_content( $panel, $instance ); ?>
				</div>
			</div>
		</div>
	<?php } ?>
	</div>
</div>

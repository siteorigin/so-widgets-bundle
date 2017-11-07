<?php
/**
 * @var array $args
 * @var array $instance
 * @var array $tabs
 * @var array $initial_tab_index
 */

echo $args['before_title'] . $instance['title'] . $args['after_title'];
?>
<div class="sow-tabs">
	<div class="sow-tabs-tab-container">
	<?php foreach ( $tabs as $i => $tab ) : ?>
		<div class="sow-tabs-tab<?php if ( $i == $initial_tab_index ) echo ' sow-tabs-tab-selected'; ?>"
			 data-anchor="<?php echo sanitize_title_with_dashes( $tab['anchor'] ); ?>">
			<div class="sow-tabs-title">
				<?php echo $tab['before_title']; ?>
				<?php echo wp_kses_post( $tab['title'] ); ?>
				<?php echo $tab['after_title']; ?>
			</div>
		</div>
	<?php endforeach; ?>
	</div>
	
	<div class="sow-tabs-panel-container">
	<?php foreach ( $tabs as $i => $tab ) : ?>
		<div class="sow-tabs-panel">
			<div class="sow-tabs-panel-content">
				<?php $this->render_panel_content( $tab, $instance ); ?>
			</div>
		</div>
	<?php endforeach; ?>
	</div>
</div>

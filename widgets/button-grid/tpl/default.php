<?php
if ( ! empty( $instance['buttons'] ) ) {
	global $wp_widget_factory;
	?>
	<div class="sow-buttons-grid">
		<?php
		$the_widget = $wp_widget_factory->widgets['SiteOrigin_Widget_Button_Widget'];
		foreach ( $instance['buttons'] as $button ) {
			$the_widget->widget( array(), $button['widget'] );
		}
		?>
	</div>
	<?php
}

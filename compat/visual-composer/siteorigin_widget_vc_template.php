<?php
if ( empty( $atts ) ) {
	return;
}
$widget_settings = $this->get_widget_settings( $atts );
$this->render_widget( $widget_settings['widget_class'], $widget_settings['widget_data'] );

<?php
$widget_settings = $this->get_widget_settings( $atts );
$instance = $this->update_widget( $widget_settings['widget_class'], $widget_settings['widget_data'] );
$this->render_widget( $widget_settings['widget_class'], $widget_settings['widget_data'] );
?>

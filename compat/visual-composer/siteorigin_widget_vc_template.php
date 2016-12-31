<?php
$widget_settings = json_decode( $atts['so_widget_data'], true );
$this->render_widget( $widget_settings['widget_class'], $widget_settings['widget_data'] );
?>

<div
	class="sow-google-map-canvas"
	style="height:<?php echo intval($height) ?>px;"
	id="map-canvas-<?php echo esc_attr($map_id) ?>"
<?php foreach( $map_data as $key => $val ) : ?>
	<?php if ( ! empty( $val ) ) : ?>
	data-<?php echo $key . '="' . esc_attr( $val ) . '"'?>
	<?php endif ?>
<?php endforeach; ?>
></div>
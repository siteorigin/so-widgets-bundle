<?php
/**
 * @var $map_id
 * @var $map_data
 * @var $height
 * @var $fallback_image_data
 */
?>

<div class="sow-google-map-canvas"
     style="height:<?php echo intval( $height ) ?>px;"
     id="map-canvas-<?php echo esc_attr( $map_id ) ?>"
     data-options="<?php echo esc_attr( json_encode( $map_data ) ) ?>"
     data-fallback-image="<?php echo esc_attr( json_encode( $fallback_image_data ) ); ?>"></div>

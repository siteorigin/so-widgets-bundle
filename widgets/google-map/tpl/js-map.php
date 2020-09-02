<?php
/**
 * @var $map_id
 * @var $map_data
 * @var $height
 * @var $fallback_image_data
 */
?>
<?php if ( $map_consent ) : ?>
	<div class="sow-google-map-consent">
		<?php echo wp_kses_post( $map_consent_notice ); ?>

		<button class="btn button"><?php echo esc_html( $map_consent_btn_text ); ?></button>
	</div>
<?php endif; ?>

<div class="sow-google-map-canvas"
     style="height:<?php echo intval( $height ) ?>px; <?php echo ( $map_consent ) ? 'display: none;' : ''; ?>"
     id="map-canvas-<?php echo esc_attr( $map_id ) ?>"
     data-options="<?php echo esc_attr( json_encode( $map_data ) ) ?>"
     data-fallback-image="<?php echo esc_attr( json_encode( $fallback_image_data ) ); ?>"></div>

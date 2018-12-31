<?php
/**
 * @var $destination_url
 * @var $new_window
 * @var $src_url
 * @var $fallback_image_data
 */
?>

<?php if( !empty( $destination_url ) ): ?>
<a href="<?php echo sow_esc_url( $destination_url ) ?>" <?php echo $new_window ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
<?php endif; ?>

<img
	class="sowb-google-map-static"
	border="0"
	src="<?php echo sow_esc_url( $src_url ) ?>"
	data-fallback-image="<?php echo esc_attr( json_encode( $fallback_image_data ) ); ?>"
	onerror="this.sowbLoadError = true;">

<?php
if( !empty( $destination_url ) ) echo '</a>';

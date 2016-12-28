<?php if( !empty( $destination_url ) ): ?>
<a href="<?php echo sow_esc_url( $destination_url ) ?>" <?php echo $new_window ? 'target="_blank"' : '' ?>>
<?php endif; ?> 

<img
	border="0"
	src="<?php echo sow_esc_url( $src_url ) ?>">

<?php
if( !empty( $destination_url ) ) echo '</a>';
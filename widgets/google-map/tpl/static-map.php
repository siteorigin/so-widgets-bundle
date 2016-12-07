<?php
if( !empty( $destination_url ) ) echo '<a href="' . $destination_url . '" ' . ( $new_window ? 'target="_blank"' : '' ) . '>'; ?>

<img
	border="0"
	src="<?php echo $src_url ?>">

<?php
if( !empty( $destination_url ) ) echo '</a>';
<?php
if ( empty( $file ) ) {
	echo __( 'Unable to display Lottie Player.', 'so-widgets-bundle' );

	return;
}
?>

<div class="sow-lottie-player" <?php if ( ! empty( $url ) ) { ?>style="position: relative;"<?php } ?>>
	<?php if ( ! empty( $url ) ) { ?>
		<?php $bottom = ! empty( $attributes['controls'] ) ? '35px' : 0; ?>
		<a
			href="<?php echo sow_esc_url( $url ); ?>"
			style="position: absolute; top: 0; right: 0; left: 0; bottom: <?php echo $bottom; ?>; z-index: 1;"
			<?php echo $new_window ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
		>
			&nbsp;
		</a>
	<?php } ?>
	<lottie-player
		class="sow-lottie-player"
		<?php
		foreach ( $attributes as $name => $value ) {
			if ( ! empty( $value ) ) {
				if ( $value === true ) {
					echo "$name ";
				} else {
					echo $name . '="' . esc_attr( $value ) . '" ';
				}
			}
		}
		?>
		src="<?php echo sow_esc_url( $file ); ?>"
	>
	</lottie-player>
</div>

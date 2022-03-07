<?php
if ( empty( $file ) ) {
	echo __( 'Unable to display Lottie Player.', 'so-widgets-bundle' );
	return;
}
?>

<?php if ( ! empty( $url ) ) : ?>
<a
	href="<?php echo sow_esc_url( $url ); ?>"
	<?php echo $new_window ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
>
<?php endif; ?>
	<lottie-player
		<?php
		foreach ( $attributes as $name => $value) {
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

<?php if ( ! empty( $url ) ) : ?>
	</a>
<?php endif; ?>

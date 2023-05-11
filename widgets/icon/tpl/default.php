<?php
/**
 * @var $icon
 * @var $url
 * @var $new_window
 */
?>

<div class="sow-icon-container sow-icon">
	<?php if ( ! empty( $url ) ) { ?>
		<a href="<?php echo sow_esc_url( $url ); ?>" <?php if ( ! empty( $new_window ) ) {
			echo 'target="_blank" rel="noopener noreferrer"';
		} ?>>
	<?php } ?>
		<?php echo siteorigin_widget_get_icon( $icon, false, $title ); ?>
	<?php if ( ! empty( $url ) ) { ?>
		</a>
	<?php } ?>
</div>

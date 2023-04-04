<?php
/**
 * @var $images array
 * @var $max_height int
 * @var $max_width int
 * @var $title_position string
 */
?>
<?php if ( ! empty( $images ) ) { ?>
	<div
		class="sow-image-grid-wrapper"
		<?php if ( ! empty( $max_width ) ) {
			echo 'data-max-width="' . (int) $max_width . '"';
		} ?>
		<?php if ( ! empty( $max_height ) ) {
			echo 'data-max-height="' . (int) $max_height . '"';
		} ?>
	>
		<?php foreach ( $images as $image ) { ?>
			<div class="sow-image-grid-image">
				<?php if ( ! empty( $title_position ) && ! empty( $image['title'] ) && $title_position == 'above' ) { ?>
					<div class="image-title">
						<?php echo wp_kses_post( $image['title'] ); ?>
					</div>
				<?php } ?>
				<?php if ( ! empty( $image['url'] ) ) { ?>
					<a href="<?php echo sow_esc_url( $image['url'] ); ?>"
					<?php foreach ( $image['link_attributes'] as $att => $val ) { ?>
						<?php if ( ! empty( $val ) ) { ?>
							<?php echo $att . '="' . esc_attr( $val ) . '" '; ?>
						<?php } ?>
					<?php } ?>>
				<?php } ?>
				<?php echo $image['image_html']; ?>
				<?php if ( ! empty( $image['url'] ) ) { ?>
					</a>
				<?php } ?>
				<?php if ( ! empty( $title_position ) && ! empty( $image['title'] ) && $title_position == 'below' ) { ?>
					<div class="image-title">
						<?php echo wp_kses_post( $image['title'] ); ?>
					</div>
				<?php } ?>
			</div>
		<?php } ?>
	</div>
<?php } ?>

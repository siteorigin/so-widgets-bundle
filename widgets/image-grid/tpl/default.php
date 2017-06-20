<?php
/**
 * @var $images array
 * @var $max_height int
 * @var $max_width int
 * @var $attachment_size string
 */
?>
<?php if( ! empty( $images ) ) : ?>
	<div class="sow-image-grid-wrapper"
		<?php if( !empty( $max_width ) ) echo 'data-max-width="' . intval( $max_width ) . '"' ?>
		<?php if( !empty( $max_height ) ) echo 'data-max-height="' . intval( $max_height ) . '"' ?>>
		<?php foreach( $images as $image ) : ?>
			<div class="sow-image-grid-image">
				<?php if ( ! empty( $image['url'] ) ) : ?>
					<a href="<?php echo sow_esc_url( $image['url'] ) ?>"
					<?php foreach( $image['link_attributes'] as $att => $val ) : ?>
						<?php if ( ! empty( $val ) ) : ?>
							<?php echo $att.'="' . esc_attr( $val ) . '" '; ?>
						<?php endif; ?>
					<?php endforeach; ?>>
				<?php endif; ?>
				<?php echo wp_get_attachment_image( $image['image'], $attachment_size, false, array(
					'title' => $image['title']
				) );?>
				<?php if ( ! empty( $image['url'] ) ) : ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

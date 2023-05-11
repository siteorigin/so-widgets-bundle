<?php
/**
 * @var $design
 * @var $settings
 * @var $testimonials
 */
if ( ! empty( $instance['title'] ) ) {
	echo $args['before_title'] . $instance['title'] . $args['after_title'];
}
$this->caret_svg();
?>
<div class="sow-testimonials">
	<?php foreach ( $testimonials as $testimonial ) { ?>
		<?php
		$url = $testimonial['url'];
		$new_window = $testimonial['new_window'];
		$location = $testimonial['location'];
		$image_id = $testimonial['image'];
		$fallback_image_id = ! empty( $testimonial['image_fallback'] ) ? $testimonial['image_fallback'] : false;
		$has_image = ! empty( $image_id ) || ! empty( $fallback_image_id );
		$link_location = ! empty( $url );
		$link_name = $has_image && ! empty( $url );
		$link_image = $has_image && ! empty( $url );
		?>
		<div class="sow-testimonial-wrapper <?php echo $this->testimonial_wrapper_class( $design ); ?>">
			<div class="sow-testimonial">
				<?php if ( strpos( $design['layout'], '_above' ) !== false ) { ?>
					<div class="sow-testimonial-text">
						<?php echo wp_kses_post( $testimonial['text'] ); ?>
					</div>
				<?php } ?>

				<div class="sow-testimonial-user">
					<?php if ( $has_image ) { ?>
					<div class="sow-image-wrapper sow-image-wrapper-shape-<?php echo $design['image']['image_shape']; ?>">
						<?php if ( $link_image ) { ?>
						<a href="<?php echo sow_esc_url( $url ); ?>" <?php if ( ! empty( $new_window ) ) {
							echo 'target="_blank" rel="noopener noreferrer"';
						} ?>>
						<?php } ?>
						<?php echo $this->testimonial_user_image( $image_id, $design, $fallback_image_id ); ?>
						<?php if ( $link_image ) { ?>
						</a>
						<?php } ?>
					</div>
					<?php } ?>

					<div class="sow-text">
						<?php if ( $link_name ) { ?>
						<a href="<?php echo sow_esc_url( $url ); ?>" <?php if ( ! empty( $new_window ) ) {
							echo 'target="_blank" rel="noopener noreferrer"';
						} ?>>
						<?php } ?>
							<span class="sow-testimonial-name"><strong><?php echo esc_html( $testimonial['name'] ); ?></strong></span>
						<?php if ( $link_name ) { ?>
						</a>
						<?php } ?>
						<?php if ( $link_location ) { ?>
							<a href="<?php echo sow_esc_url( $url ); ?>" <?php if ( ! empty( $new_window ) ) {
								echo 'target="_blank" rel="noopener noreferrer"';
							} ?>>
						<?php } ?>
						<?php if ( ! empty( $location ) ) { ?>
							<span class="sow-testimonial-location"><?php echo esc_html( $location ); ?></span>
						<?php } ?>
						<?php if ( $link_location ) { ?>
							</a>
						<?php } ?>
					</div>
				</div>

				<?php if ( strpos( $design['layout'], '_above' ) === false ) { ?>
					<div class="sow-testimonial-text">
						<?php echo wp_kses_post( $testimonial['text'] ); ?>
					</div>
				<?php } ?>
			</div>
		</div>
	<?php } ?>
</div>

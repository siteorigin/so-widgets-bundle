<?php
/**
 * @var $design
 * @var $settings
 * @var $testimonials
 */
?>
<?php $this->caret_svg() ?>
<div class="sow-testimonials">
	<?php foreach( $testimonials as $testimonial ) : ?>
		<div class="sow-testimonial-wrapper <?php echo $this->testimonial_wrapper_class($design) ?>">
			<div class="sow-testimonial">
				<?php if( strpos($design['layout'], '_above') !== false ) : ?>
					<div class="sow-testimonial-text">
						<?php echo wp_kses_post( $testimonial['text'] ) ?>
					</div>
				<?php endif; ?>

				<div class="sow-testimonial-user">
					<div class="sow-image-wrapper">
						<?php echo $this->testimonial_user_image($testimonial, $design) ?>
					</div>
					<div class="sow-text">
						<strong><?php echo esc_html( $testimonial['name'] ) ?></strong>
						<a><?php echo esc_html( $testimonial['location'] ) ?></a>
					</div>

					<?php // $this->testimonial_pointer($design) ?>
				</div>

				<?php if( strpos($design['layout'], '_above') === false ) : ?>
					<div class="sow-testimonial-text">
						<?php echo wp_kses_post( $testimonial['text'] ) ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
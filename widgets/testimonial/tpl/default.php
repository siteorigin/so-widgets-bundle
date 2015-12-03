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
					<?php $sow_user_image = $this->testimonial_user_image($testimonial, $design); ?>
          <?php if( !empty( $sow_user_image ) ){ ?>
					<div class="sow-image-wrapper">
						<?php echo $sow_user_image ?>
					</div>
					<?php } ?>
					<div class="sow-text">
						<strong><?php echo esc_html( $testimonial['name'] ) ?></strong>
						<?php if( !empty( $testimonial['location'] ) ){ ?>
						<?php   if( empty( $testimonial['url'] ) ){ ?>
						<?php echo esc_html( $testimonial['location'] ) ?>
						<?php   }elseif( $testimonial['new_window'] ){ ?>
						<a href="<?php echo sow_esc_url( $testimonial['url'] ); ?>" target="_blank"><?php echo esc_html( $testimonial['location'] ) ?></a>
						<?php   }else{ ?>
						<a href="<?php echo sow_esc_url( $testimonial['url'] ); ?>"><?php echo esc_html( $testimonial['location'] ) ?></a>
						<?php   }
						      } ?>
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
<?php if ( ! empty( $image_url ) ) : ?>
<div class="testimonial-image-wrapper">
	<img src="<?php echo esc_url( $image_url ) ?>" />
</div>
<?php endif; ?>

<div class="text">
	<?php echo wpautop( wp_kses_post( $testimonial ) ); ?>
	<h5 class="testimonial-name">
		<?php if( $has_url ) : ?><a href="<?php echo esc_url( $url ) ?>" <?php if( $new_window ) : ?>target="_blank"<?php endif; ?>><?php endif; ?>
			<?php echo esc_html( $name ) ?>
		<?php if( $has_url ) : ?></a><?php endif; ?>
	</h5>
	<small class="testimonial-location"><?php echo esc_html( $location ) ?></small>
</div>
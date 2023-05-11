<div class="sow-form-field sow-form-field-captcha">
	<?php if ( $instance['design']['labels']['position'] != 'below' ) { ?>
		<label
			class="sow-form-field-label-<?php echo esc_attr( $instance['design']['labels']['position'] != 'inside' ? $instance['design']['labels']['position'] : 'above' ); ?>"
			for="really-simple-captcha-<?php echo esc_attr( $instance_hash ); ?>">
			<strong><?php _e( 'Captcha', 'so-widgets-bundle' ); ?></strong>
		</label>
	<?php } ?>

	<?php if ( ! empty( $template_vars['really_simple_spam_error'] ) ) { ?>
		<div class="sow-error">
			<?php echo esc_html( $template_vars['really_simple_spam_error'] ); ?>
		</div>
	<?php } ?>

	<img
		src="<?php echo esc_url( plugins_url() . '/really-simple-captcha/tmp/' . $really_simple_spam_image ); ?>"
		width="<?php echo esc_attr( $really_simple_spam->img_size[0] ); ?>"
		height="<?php echo esc_attr( $really_simple_spam->img_size[1] ); ?>"
	>
	<span class="sow-field-container">
		<input type="text" name="really-simple-captcha-<?php echo esc_attr( $instance_hash ); ?>" id="really-simple-captcha-<?php echo esc_attr( $instance_hash ); ?>" value="" class="sow-text-field">
	</span>
	<?php if ( $instance['design']['labels']['position'] == 'below' ) { ?>
		<label class="sow-form-field-label-left" for="really-simple-captcha-<?php echo esc_attr( $instance_hash ); ?>">
			<strong><?php _e( 'Captcha', 'so-widgets-bundle' ); ?></strong>
		</label>
	<?php } ?>
	<input type="hidden" name="really-simple-captcha-prefix-<?php echo esc_attr( $instance_hash ); ?>" value="<?php echo esc_attr( $really_simple_spam_prefix ); ?>" />
</div>

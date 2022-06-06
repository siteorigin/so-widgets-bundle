<div class="sow-form-field sow-form-field-captcha">
	<label class="sow-form-field-label-above" for="really-simple-captcha-<?php echo esc_attr( $instance_hash ); ?>">
		<strong>Captcha</strong>
	</label>
	<img
		src="<?php echo esc_url( plugins_url() . '/really-simple-captcha/tmp/' . $really_simple_spam_image ); ?>"
		width="<?php echo esc_attr( $really_simple_spam->img_size[0] ); ?>"
		height="<?php echo esc_attr( $really_simple_spam->img_size[1] ); ?>"
	>
	<span class="sow-field-container">
		<input type="text" name="really-simple-captcha-<?php echo esc_attr( $instance_hash ); ?>" id="really-simple-captcha-<?php echo esc_attr( $instance_hash ); ?>" value="" class="sow-text-field">
	</span>
	<input type="hidden" name="really-simple-captcha-prefix-<?php echo esc_attr( $instance_hash ); ?>" value="<?php echo esc_attr( $really_simple_spam_prefix ) ?>" />
</div>

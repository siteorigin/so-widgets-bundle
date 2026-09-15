<?php
$captcha_name = $this->name_from_label( 'really-simple-captcha' );

// Resolve the label position the same way render_form_fields() does, so the
// captcha lines up with the fields above it and never emits a bare class name
// when the stored value is missing or malformed.
$captcha_label_position = $this->design_setting( $instance, 'labels', 'position' );

if ( ! in_array( $captcha_label_position, array( 'above', 'below', 'left', 'right', 'inside' ), true ) ) {
	$captcha_label_position = 'above';
}
?>

<div class="sow-form-field sow-form-field-captcha">
	<?php if ( $captcha_label_position != 'below' ) { ?>
		<label
			class="sow-form-field-label-<?php echo esc_attr( $captcha_label_position != 'inside' ? $captcha_label_position : 'above' ); ?>"
			for="<?php echo esc_attr( $captcha_name ); ?>"
		>
			<strong>
				<?php echo esc_html__( 'Captcha', 'so-widgets-bundle' ); ?>
			</strong>
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
		<input
			type="text"
			name="<?php echo esc_attr( $captcha_name ); ?>"
			id="<?php echo esc_attr( $captcha_name ); ?>"
			value=""
			class="sow-text-field"
		>
	</span>
	<?php if ( $captcha_label_position == 'below' ) { ?>
		<label
			class="sow-form-field-label-left"
			for="<?php echo esc_attr( $captcha_name ); ?>"
		>
			<strong>
				<?php echo esc_html__( 'Captcha', 'so-widgets-bundle' ); ?>
			</strong>
		</label>
	<?php } ?>
	<input
		type="hidden"
		name="<?php echo esc_attr( $this->name_from_label( 'really-simple-captcha-prefix' ) ); ?>"
		value="<?php echo esc_attr( $really_simple_spam_prefix ); ?>"
	/>
</div>

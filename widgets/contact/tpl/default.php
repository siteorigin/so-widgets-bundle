<?php
$result = $this->contact_form_action( $instance, $instance_hash );

// Display the title
if( $instance['display_title'] ) {
	echo $args['before_title'] . $instance['title'] . $args['after_title'];
}

if( $result['status'] == 'success' ) {
	// Display the success message
	?>
	<div class="sow-contact-form-success" id="contact-form-<?php echo esc_attr( $instance_hash ) ?>">
		<?php echo wp_kses_post( wpautop( $instance['settings']['success_message'] ) ) ?>
	</div>
	<?php
}
else {
	$recaptcha_config = $instance['spam']['recaptcha'];
	$use_recaptcha = $recaptcha_config['use_captcha'] && ! empty( $recaptcha_config['site_key'] ) && ! empty( $recaptcha_config['secret_key'] );
	if ( $use_recaptcha ) {
		wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js');
	}
	?>
	<form action="<?php echo add_query_arg( false, false ) ?>#contact-form-<?php echo esc_attr( substr( $instance_hash, 0, 4 ) ) ?>" method="POST" class="sow-contact-form" id="contact-form-<?php echo esc_attr( substr( $instance_hash, 0, 4 ) ) ?>">

		<?php if( !empty($result['errors']['_general']) ) : ?>
			<ul class="sow-error">
				<?php foreach( $result['errors']['_general'] as $type => $message ) : ?>
					<li><?php echo esc_html( $message ) ?></li>
				<?php endforeach ?>
			</ul>
		<?php endif ?>

		<?php $this->render_form_fields( $instance['fields'], $result['errors'], $instance ) ?>
		<input type="hidden" name="instance_hash" value="<?php echo esc_attr($instance_hash) ?>" />

		<?php if( $use_recaptcha ) : ?>
			<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $instance['spam']['recaptcha']['site_key'] ) ?>" data-callback="soOnCaptchaSuccess"></div>

			<script type="application/javascript">
				jQuery(function ($) {
					// Ensure we're getting the right submit input in case there are multiple widgets on a page.
					var $input = $("<?php echo '.js-sow-submit-' . $instance['_sow_form_id'] ?>");
					window.soOnCaptchaSuccess = function (response) {
						if(response) {
							$input.prop('disabled', false);
						}
					};
				});
			</script>
		<?php endif; ?>

		<div class="sow-submit-wrapper <?php if( $instance['design']['submit']['styled'] ) echo 'sow-submit-styled' ?>">
			<input type="submit" value="<?php echo esc_attr( $instance['settings']['submit_text'] ) ?>"
				   class="sow-submit <?php echo 'js-sow-submit-' . $instance['_sow_form_id'] ?>" <?php if( $use_recaptcha ) echo 'disabled="true"'; ?>>
		</div>
	</form>
	<?php
}
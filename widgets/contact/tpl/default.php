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
	if( $instance['spam']['recaptcha']['use_captcha'] ) {
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

		<?php if( $instance['spam']['recaptcha']['use_captcha'] ) : ?>
			<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $instance['spam']['recaptcha']['site_key'] ) ?>"></div>
		<?php endif; ?>

		<div class="sow-submit-wrapper <?php if( $instance['design']['submit']['styled'] ) echo 'sow-submit-styled' ?>">
			<input type="submit" value="<?php echo esc_attr( $instance['settings']['submit_text'] ) ?>" class="sow-submit">
		</div>
	</form>
	<?php
}
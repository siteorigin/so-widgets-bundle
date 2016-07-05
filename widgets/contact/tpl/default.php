<?php
$result = $this->contact_form_action( $instance, $instance_hash );

// Display the title
if( $instance['display_title'] ) {
	echo $args['before_title'] . $instance['title'] . $args['after_title'];
}
$short_hash = substr( $instance_hash, 0, 4 );
if( $result['status'] == 'success' ) {
	// Display the success message
	?>
	<div class="sow-contact-form-success" id="contact-form-<?php echo esc_attr( $short_hash ) ?>">
		<?php echo wp_kses_post( wpautop( $instance['settings']['success_message'] ) ) ?>
	</div>
	<?php
}
else {
	$recaptcha_config = $instance['spam']['recaptcha'];
	$use_recaptcha = $recaptcha_config['use_captcha'] && ! empty( $recaptcha_config['site_key'] ) && ! empty( $recaptcha_config['secret_key'] );

	$settings = null;
	if( $use_recaptcha ) {
		$settings = array(
			'sitekey' => $recaptcha_config['site_key'],
			'theme'   => $recaptcha_config['theme'],
			'type'    => $recaptcha_config['type'],
			'size'    => $recaptcha_config['size']
		);
	}
	?>
	<form action="<?php echo esc_url( add_query_arg( false, false ) ) ?>#contact-form-<?php echo esc_attr( $short_hash ) ?>" method="POST" class="sow-contact-form" id="contact-form-<?php echo esc_attr( $short_hash ) ?>">

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
			<div class="sow-recaptcha"
				 data-config="<?php echo esc_attr( json_encode( $settings ) ) ?>"></div>
		<?php endif; ?>

		<div class="sow-submit-wrapper <?php if( $instance['design']['submit']['styled'] ) echo 'sow-submit-styled' ?>">
			<input type="submit" value="<?php echo esc_attr( $instance['settings']['submit_text'] ) ?>"
				   class="sow-submit">
		</div>
	</form>
	<?php
}

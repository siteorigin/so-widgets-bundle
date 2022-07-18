<?php
// Display the title.
if ( $instance['display_title'] && !empty( $instance['title'] ) ) {
	echo $args['before_title'] . $instance['title'] . $args['after_title'];
}
$short_hash = substr( $instance_hash, 0, 4 );
if ( $result['status'] == 'success' ) {
	// Display the success message
	?>
	<div class="sow-contact-form-success" id="contact-form-<?php echo esc_attr( $short_hash ) ?>">
		<?php echo $instance['settings']['success_message']; ?>
	</div>
	<?php
}
else {
	if ( $recaptcha && ! empty( $recaptcha_v2 ) ) {
		$settings = array(
			'sitekey' => $recaptcha_config['site_key'],
			'theme'   => $recaptcha_config['theme'],
			'type'    => $recaptcha_config['type'],
			'size'    => $recaptcha_config['size']
		);
	}
	?>
	<form action="#contact-form-<?php echo esc_attr( $short_hash ); ?>"
		method="POST" class="sow-contact-form" id="contact-form-<?php echo esc_attr( $short_hash ) ?>">

		<?php if ( ! empty( $result['errors']['_general'] ) ) : ?>
			<ul class="sow-error">
				<?php foreach ( $result['errors']['_general'] as $type => $message ) : ?>
					<li><?php echo esc_html( $message ) ?></li>
				<?php endforeach ?>
			</ul>
		<?php endif ?>

		<?php $this->render_form_fields( $instance['fields'], $result['errors'], $instance ) ?>
		<input type="hidden" name="instance_hash" value="<?php echo esc_attr( $instance_hash ) ?>" />
		<?php wp_nonce_field( '_contact_form_submit' ) ?>

		<?php if ( $recaptcha ) : ?>
			<div class="sow-recaptcha"
				<?php if ( ! empty( $recaptcha_v2 ) ) : ?>
					data-config="<?php echo esc_attr( json_encode( $recaptcha_v2 ) ) ?>"
				<?php endif; ?>
			></div>
		<?php endif; ?>

		<?php
		if ( ! empty( $really_simple_spam ) ) {
			if ( $really_simple_spam == 'missing' ) {
				echo __( 'Unable to detect Really Simple CAPTCHA plugin.', 'so-widgets-bundle' );
			} else {
				require( 'simple.php' );
			}
		}
		?>
		<div class="sow-submit-wrapper <?php if ( $instance['design']['submit']['styled'] ) echo 'sow-submit-styled'; ?>">

		<button class="sow-submit<?php if ( $recaptcha && empty( $recaptcha_v2 ) ) echo ' g-recaptcha'; ?>"
			<?php foreach ( $submit_attributes as $name => $val ) echo $name . '="' . esc_attr( $val ) . '" ' ?>
			<?php if ( ! empty( $onclick ) ) echo 'onclick="' . esc_js( $onclick ) . '"'; ?>
		>
			<?php echo esc_attr( $instance['settings']['submit_text'] ) ?>
		</button>

		</div>
	</form>
	<?php
}

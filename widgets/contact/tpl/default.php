<?php
// Display the title.
if ( $instance['display_title'] && ! empty( $instance['title'] ) ) {
	echo $args['before_title'] . wp_kses_post( $instance['title'] ) . $args['after_title'];
}
$short_hash = substr( $instance_hash, 0, 4 );

if ( is_array( $result ) && $result['status'] == 'success' ) {
	// Display the success message
	?>
	<div class="sow-contact-form-success" id="contact-form-<?php echo esc_attr( $short_hash ); ?>">
		<?php echo wp_kses_post( apply_filters( 'siteorigin_widgets_contact_success_message', $instance['settings']['success_message'] ) ); ?>
	</div>
	<?php
} else {
	if ( $recaptcha && ! empty( $recaptcha_v2 ) ) {
		$settings = array(
			'sitekey' => $recaptcha_config['site_key'],
			'theme'   => $recaptcha_config['theme'],
			'type'    => $recaptcha_config['type'],
			'size'    => $recaptcha_config['size'],
		);
	}
	$global_settings = $this->get_global_settings();
	?>
	<form
		action="<?php echo esc_url( add_query_arg( null, null ) ); ?>"
		method="POST"
		class="sow-contact-form<?php echo ! empty( $global_settings['scrollto'] ) && ! empty( $result ) ? ' sow-contact-submitted' : ''; ?>"
		id="contact-form-<?php echo esc_attr( $short_hash ); ?>"
	>

		<?php if ( ! empty( $result['errors']['_general'] ) ) { ?>
			<ul class="sow-error">
				<?php foreach ( $result['errors']['_general'] as $type => $message ) { ?>
					<li><?php echo esc_html( $message ); ?></li>
				<?php } ?>
			</ul>
		<?php } ?>

		<?php
		$this->render_form_fields( $instance['fields'], $result, $instance );

		if ( $template_vars['honeypot'] ) {
			?>
			<input type="text" name="sow-<?php echo esc_attr( $instance['_sow_form_id'] ); ?>" class="sow-text-field" style="display: none !important; visibility: hidden !important;" autocomplete="off" aria-hidden="true">
		<?php } ?>

		<?php if ( $recaptcha ) { ?>
			<div class="sow-recaptcha"
				<?php if ( ! empty( $recaptcha_v2 ) ) { ?>
					data-config="<?php echo esc_attr( json_encode( $recaptcha_v2 ) ); ?>"
				<?php } ?>
			></div>
			<?php
		}

		if ( ! empty( $really_simple_spam ) ) {
			if ( $really_simple_spam == 'missing' ) {
				esc_html_e( 'Unable to detect Really Simple CAPTCHA plugin.', 'so-widgets-bundle' );
			} else {
				require 'simple.php';
			}
		}

		do_action( 'siteorigin_widgets_contact_before_submit', $instance, $result );
		?>

		<div class="sow-submit-wrapper <?php if ( $instance['design']['submit']['styled'] ) {
			echo 'sow-submit-styled';
		} ?>">

			<button
				type="submit"
				class="sow-submit<?php
				if ( $recaptcha && empty( $recaptcha_v2 ) ) {
					echo ' g-recaptcha';
				}
				?>"
				<?php
				foreach ( $submit_attributes as $name => $val ) {
					echo siteorigin_sanitize_attribute_key( $name ) . '="' . esc_attr( $val ) . '" ';
				}

				if ( ! empty( $onclick ) ) {
					echo 'onclick="' . siteorigin_widget_onclick( $onclick ) . '"';
				}
				?>
			>
				<?php echo esc_html( $instance['settings']['submit_text'] ); ?>
			</button>
		</div>
		<?php do_action( 'siteorigin_widgets_contact_after_submit', $instance, $result ); ?>
		<input type="hidden" name="instance_hash" value="<?php echo esc_attr( $instance_hash ); ?>" />
		<?php wp_nonce_field( '_contact_form_submit' ); ?>
	</form>
	<?php
}

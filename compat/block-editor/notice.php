<div class="notice notice-info is-dismissible so-widgets-block-migration-notice">
	<p>
		<?php echo esc_html( $message ); ?>
	</p>

	<div class="wporg-notice__actions">
		<?php if ( $is_admin ) { ?>
			<button type="button" class="button button-primary notice-consent">
				<?php esc_html__( 'Placeholder consent button', 'so-widgets-bundle' ); ?>
			</button>
		<?php } ?>

		<a href="#" target="_blank" rel="noopener noreferrer" class="button button-secondary">
			<?php echo esc_html__( 'Learn more', 'so-widgets-bundle' ); ?>
		</a>
	</div>

	<button type="button" class="notice-dismiss">
		<span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'so-widgets-bundle' ); ?></span>
	</button>
</div>

<?php if ( ! empty( $settings['posts'] ) && $settings['posts']->have_posts() ) : ?>
	<div
		style="overflow: hidden; max-width: 100%; <?php echo ! empty( $settings['height'] ) ? esc_attr( $settings['height'] ) : ''; ?>"
	>
		<?php $this->render_template( $settings, $args ); ?>
		<input type="hidden" name="instance_hash" value="<?php echo esc_attr( $storage_hash ); ?>"/>
	</div>
<?php endif; ?>

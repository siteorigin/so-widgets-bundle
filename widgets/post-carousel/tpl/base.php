<?php if ( ! empty( $settings['posts'] ) && $settings['posts']->have_posts() ) { ?>
	<div
		class="sow-post-carousel-wrapper <?php echo ! empty( $settings['theme'] ) ? 'sow-post-carousel-theme-' . esc_attr( $settings['theme'] ) : ''; ?>"
		style="overflow: hidden; max-width: 100%; <?php echo ! empty( $settings['height'] ) ? esc_attr( $settings['height'] ) : ''; ?>"
	>
		<?php $this->render_template( $settings, $args ); ?>
		<input type="hidden" name="instance_hash" value="<?php echo esc_attr( $storage_hash ); ?>"/>
	</div>
<?php } ?>

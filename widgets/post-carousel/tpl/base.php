<?php if ( ! empty( $settings['posts'] ) && $settings['posts']->have_posts() ) : ?>
	<div class="sow-post-carousel-wrapper" style="overflow: hidden; max-width: 100%;">
		<?php $this->render_template( $settings, $args ); ?>
		<input type="hidden" name="instance_hash" value="<?php echo esc_attr( $storage_hash ); ?>"/>
	</div>
<?php endif; ?>

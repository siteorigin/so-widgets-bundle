<?php
if ( ! empty( $settings['posts'] ) && $settings['posts']->have_posts() ) :
	$this->render_template( $settings );
	?>
	<input type="hidden" name="instance_hash" value="<?php echo esc_attr( $storage_hash ); ?>"/>
	<?php
endif;

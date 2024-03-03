<?php if ( ! empty( $instance['title'] ) ) {
	echo $args['before_title'] . wp_kses_post( $instance['title'] ) . $args['after_title'];
} ?>

<div class="siteorigin-widget-tinymce textwidget">
	<?php echo $text; ?>
</div>

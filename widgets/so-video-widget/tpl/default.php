<?php echo $args['before_title'] . esc_html($instance['title']) . $args['after_title'] ?>

<div>
<?php if ( $host_type == 'self' || $video_type == 'youtube' || $video_type == 'vimeo' ) : ?>
	<video width="640" height="360" id="<?php echo $player_id ?>" preload="none"
	       poster="<?php echo esc_attr( $poster ) ?>"
		<?php if( ! empty( $poster ) ) echo 'poster="' . $poster . '"' ?>
		<?php if( $skin_class != 'default' ) echo 'class="mejs-' . $skin_class . '"' ?>>
		<source type="video/<?php echo $video_type ?>" src="<?php echo esc_attr( $src ) ?>" />
	</video>
<?php else : ?>
	<?php echo wp_oembed_get( $src ); ?>
<?php endif; ?>
</div>

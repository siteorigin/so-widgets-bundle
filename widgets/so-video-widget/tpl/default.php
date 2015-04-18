<?php echo $args['before_title'] . esc_html($instance['title']) . $args['after_title'] ?>

<div>
<?php if ( $is_skinnable_video_host ) : ?>
	<video width="640" height="360" id="<?php echo $player_id ?>" preload="none"
		<?php if( ! empty( $autoplay ) ) echo 'autoplay="' . $autoplay . '"' ?>
		<?php if( ! empty( $poster ) ) echo 'poster="' . $poster . '"' ?>
		<?php if( $skin_class != 'default' ) echo 'class="mejs-' . $skin_class . '"' ?>>
		<source type="video/<?php echo $video_type ?>" src="<?php echo esc_attr( $src ) ?>" />
	</video>
<?php else : ?>
	<?php echo wp_oembed_get( $src ); ?>
<?php endif; ?>
</div>

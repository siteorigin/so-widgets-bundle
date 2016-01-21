<?php
if( !empty($instance['title']) ) echo $args['before_title'] . esc_html($instance['title']) . $args['after_title'];

$video_args = array(
	'id' => $player_id,
	'class' => 'sow-video-widget',
	'preload' => 'auto',
	'style' => 'width:100%;height:100%;',
);
if( $autoplay ) $video_args['autoplay'] = 1;
if( ! empty( $poster ) ) $video_args['poster'] = esc_url( $poster );
if( $skin_class != 'default' ) $video_args['class'] = 'mejs-'.$skin_class;
?>

<div class="sow-video-wrapper">
	<?php if ( $is_skinnable_video_host ) : ?>
		<video <?php foreach( $video_args as $k => $v ) echo $k.'="' . $v . '" '; ?>>
			<source type="<?php echo esc_attr($video_type) ?>" src="<?php echo esc_url( $src ) ?>" />
		</video>
	<?php else : ?>
		<?php echo $this->get_video_oembed( $src, $autoplay ); ?>
	<?php endif; ?>
</div>

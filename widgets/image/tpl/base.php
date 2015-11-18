<?php

$src = siteorigin_widgets_get_attachment_image_src(
	$instance['image'],
	$instance['size'],
	!empty($instance['image_fallback']) ? $instance['image_fallback'] : false
);

if( !empty($src) ) {
	$attr = array(
		'src' => $src[0],
	);

	if(!empty($src[1])) $attr['width'] = $src[1];
	if(!empty($src[2])) $attr['height'] = $src[2];
}

$styles = array();
$classes = array('so-widget-image');

if(!empty($instance['title'])) $attr['title'] = $instance['title'];
if(!empty($instance['alt'])) $attr['alt'] = $instance['alt'];
if(!empty($instance['bound'])) {
	$styles[] = 'max-width:100%';
	$styles[] = 'height:auto';
}
if(!empty($instance['full_width'])) {
	$styles[] = 'width:100%';
}
$styles[] = 'display:block';
?>

<?php if(!empty($instance['url'])) : ?><a href="<?php echo sow_esc_url($instance['url']) ?>" <?php if($instance['new_window']) echo 'target="_blank"' ?>><?php endif; ?>
	<img <?php foreach($attr as $n => $v) echo $n.'="' . esc_attr($v) . '" ' ?> class="<?php echo esc_attr( implode(' ', $classes) ) ?>" <?php if( !empty($styles) ) echo 'style="'.implode('; ', $styles).'"'; ?> />
<?php if(!empty($instance['url'])) : ?></a><?php endif; ?>
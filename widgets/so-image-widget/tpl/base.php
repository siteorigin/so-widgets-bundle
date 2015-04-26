<?php
$src = wp_get_attachment_image_src($instance['image'], $instance['size']);
if( !empty($src) ) {
	$attr = array(
		'src' => $src[0],
		'width' => $src[1],
		'height' => $src[2],
	);
}
else if( !empty( $instance['image_fallback'] ) ) {
	$attr = array(
		'src' => esc_url($instance['image_fallback']),
	);
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
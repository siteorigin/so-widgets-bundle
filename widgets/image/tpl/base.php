<?php
/**
 * @var $title
 * @var $title_position
 * @var $image
 * @var $size
 * @var $image_fallback
 * @var $alt
 * @var $url
 * @var $new_window
 */
?>

<?php if( $title_position == 'above' ) : ?>
	<?php echo $args['before_title']; ?>
	<h2> <?php echo $title ?> </h2>
	<?php echo $args['after_title']; ?>
<?php endif; ?>

<?php
$src = siteorigin_widgets_get_attachment_image_src(
	$image,
	$size,
	$image_fallback
);

$attr = array();
if( !empty($src) ) {
	$attr = array(
		'src' => $src[0],
	);

	if(!empty($src[1])) $attr['width'] = $src[1];
	if(!empty($src[2])) $attr['height'] = $src[2];
	if (function_exists('wp_get_attachment_image_srcset')) {
		$attr['srcset'] = wp_get_attachment_image_srcset($image, $size);
 	}
}

$classes = array('so-widget-image');

if(!empty($title)) $attr['title'] = $title;
if(!empty($alt)) $attr['alt'] = $alt;

?>
<div class="sow-image-container">
<?php if(!empty($url)) : ?><a href="<?php echo sow_esc_url($url) ?>" <?php if($new_window) echo 'target="_blank"' ?>><?php endif; ?>
	<img <?php foreach($attr as $n => $v) echo $n.'="' . esc_attr($v) . '" ' ?> class="<?php echo esc_attr( implode(' ', $classes) ) ?>"/>
<?php if(!empty($url)) : ?></a><?php endif; ?>
</div>

<?php if( $title_position == 'below' ) : ?>
	<?php echo $args['before_title']; ?>
	<h2> <?php echo $title ?> </h2>
	<?php echo $args['after_title']; ?>
<?php endif; ?>

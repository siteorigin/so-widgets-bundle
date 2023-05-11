<?php
/**
 * @var $title
 * @var $title_position
 * @var $url
 * @var $link_attributes
 * @var $link_title
 * @var $new_window
 * @var $attributes
 * @var $classes
 */
?>

<?php
if ( $title_position == 'above' ) {
	echo $args['before_title'];

	if ( $link_title && ! empty( $url ) ) {
		echo $this->generate_anchor_open( $url, $link_attributes ) . $title . '</a>';
	} else {
		echo $title;
	}
	echo $args['after_title'];
}
?>

<div class="sow-image-container">
	<?php if ( ! empty( $url ) ) {
		$this->generate_anchor_open( $url, $link_attributes );
	} ?>
	<img <?php foreach ( $attributes as $n => $v ) {
		if ( $n === 'alt' || ! empty( $v ) ) {
			echo $n . '="' . esc_attr( $v ) . '" ';
		}
	} ?>
		class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"/>
	<?php if ( ! empty( $url ) ) { ?></a><?php } ?>
</div>

<?php
if ( $title_position == 'below' ) {
	echo $args['before_title'];

	if ( $link_title && ! empty( $url ) ) {
		echo $this->generate_anchor_open( $url, $link_attributes ) . $title . '</a>';
	} else {
		echo $title;
	}
	echo $args['after_title'];
}
?>

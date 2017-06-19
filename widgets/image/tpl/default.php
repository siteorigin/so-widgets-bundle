<?php
/**
 * @var $title
 * @var $title_position
 * @var $url
 * @var $link_attributes
 * @var $new_window
 * @var $attributes
 * @var $classes
 */
?>

<?php if( $title_position == 'above' ) : ?>
	<?php echo $args['before_title'] . wp_kses_post( $title ) . $args['after_title']; ?>
<?php endif; ?>

<?php

?>
<div class="sow-image-container">
<?php if ( ! empty( $url ) ) : ?><a href="<?php echo sow_esc_url( $url ) ?>" <?php foreach( $link_attributes as $att => $val ) if ( ! empty( $val ) ) : echo $att.'="' . esc_attr( $val ) . '" '; endif; ?>><?php endif; ?>
	<img <?php foreach( $attributes as $n => $v ) if ( ! empty( $v ) ) : echo $n.'="' . esc_attr( $v ) . '" '; endif; ?>
		class="<?php echo esc_attr( implode(' ', $classes ) ) ?>"/>
<?php if ( ! empty( $url ) ) : ?></a><?php endif; ?>
</div>

<?php if( $title_position == 'below' ) : ?>
	<?php echo $args['before_title'] . wp_kses_post( $title ) . $args['after_title']; ?>
<?php endif; ?>

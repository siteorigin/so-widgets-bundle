<div class="sow-carousel-title<?php if ( ! empty( $settings['title'] ) ) echo ' has-title'; ?>">
	<?php
	if ( ! empty( $settings['title'] ) ) {
		echo $settings['args']['before_title'] . esc_html( $settings['title'] ) . $settings['args']['after_title'];

	}
	?>

	<div class="sow-carousel-navigation">
		<a href="#" class="sow-carousel-next" title="<?php esc_attr_e( 'Next', 'so-widgets-bundle' ); ?>" aria-label="<?php esc_attr_e( 'Next Posts', 'so-widgets-bundle' ); ?>" role="button"></a>
		<a href="#" class="sow-carousel-previous" title="<?php esc_attr_e( 'Previous', 'so-widgets-bundle' ); ?>" aria-label="<?php esc_attr_e( 'Previous Posts', 'so-widgets-bundle' ); ?>" role="button"></a>
	</div>

</div>

<div class="sow-carousel-container">
	<div class="sow-carousel-wrapper"
	     data-dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>"
	     <?php
	     foreach( $settings['attributes'] as $n => $v ) {
	     	if ( ! empty( $n ) ) {
	     		echo 'data-' . $n . '="' . esc_attr( $v ) . '" ';
	     	}
	     }
	     ?>
	>
		<div class="sow-carousel-items">
			<?php include $settings['item_template']; ?>
		</div>
	</div>
</div>

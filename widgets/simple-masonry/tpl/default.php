<?php
/**
 * @var $args array
 * @var $items array
 * @var $layouts array
 */
?>

<?php if ( ! empty( $instance['widget_title'] ) ) {
	echo $args['before_title'] . $instance['widget_title'] . $args['after_title'];
} ?>

<?php if ( $preloader_enabled ) { ?>
	<div class="sow-masonry-grid-preloader"><div></div><div></div><div></div><div></div></div>
<?php } ?>
<div class="sow-masonry-grid"
	 data-layouts="<?php echo esc_attr( json_encode( $layouts ) ); ?>" data-layout-origin-left="<?php esc_attr_e( $layout_origin_left ); ?>" <?php echo ! empty( $preloader_enabled ) ? 'style="opacity: 0;"' : ''; ?>>
	<?php
	if ( ! empty( $items ) ) {
		foreach ( $items as $item ) {
			$src   = wp_get_attachment_image_src( $item['image'], 'full' );
			$src   = empty( $src ) ? '' : $src[0];
			$title = empty( $item['title'] ) ? '' : $item['title'];
			$url   = empty( $item['url'] ) ? '' : $item['url'];
			?>
			<div class="sow-masonry-grid-item" data-col-span="<?php echo esc_attr( $item['column_span'] ); ?>"
			     data-row-span="<?php echo esc_attr( $item['row_span'] ); ?>">
				<?php if ( ! empty( $url ) ) { ?>
					<a href="<?php echo sow_esc_url( $url ); ?>"
					<?php foreach ( $item['link_attributes'] as $att => $val ) { ?>
						<?php if ( ! empty( $val ) ) { ?>
							<?php echo $att . '="' . esc_attr( $val ) . '" '; ?>
						<?php } ?>
					<?php } ?>>
				<?php } ?>

				<?php
				if (
					! empty( $instance['title'] ) &&
					! empty( $item['title'] ) &&
					! empty( $instance['title']['display'] ) &&
					$instance['title']['position'] == 'above'
				) {
					?>
					<span class="image-title">
						<?php echo wp_kses_post( $item['title'] ); ?>
					</span>
				<?php } ?>

				<?php
				$loading_val = function_exists( 'wp_get_loading_attr_default' ) ? wp_get_loading_attr_default( 'the_content' ) : 'lazy';
				echo siteorigin_widgets_get_attachment_image(
					$item['image'],
					'full',
					! empty( $item['image_fallback'] ) ? $item['image_fallback'] : '',
					array(
						'title' => esc_attr( $title ),
						'class' => 'sow-masonry-grid-image',
						'loading' => $preloader_enabled ? false : $loading_val,
					)
				);
				?>

				<?php
				if (
					! empty( $instance['title'] ) &&
					! empty( $item['title'] ) &&
					! empty( $instance['title']['display'] ) &&
					$instance['title']['position'] == 'below'
				) {
					?>
					<span class="image-title">
						<?php echo wp_kses_post( $item['title'] ); ?>
					</span>
				<?php } ?>

				<?php if ( ! empty( $url ) ) { ?>
					</a>
				<?php } ?>
			</div>
			<?php
		}
	}
?>

</div>

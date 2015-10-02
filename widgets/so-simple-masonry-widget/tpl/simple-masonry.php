<div class="sow-masonry-grid"
	 data-settings="<?php echo esc_attr( json_encode( siteorigin_widgets_underscores_to_camel_case( $packery_settings ) ) ) ?>">
	<?php
	foreach($instance['items'] as $item) {
		$src = wp_get_attachment_image_src( $item['image'], 'large' );
		$src = empty( $src ) ? '' : $src[0];
		$title = empty( $item['title'] ) ? '' : $item['title'];
		$url = empty( $item['url'] ) ? '' : $item['url'];
		$new_window = ! empty( $item['new_window'] );

		if( $item['column_span'] > $packery_settings['num_columns'] ) {
			$col_span = $packery_settings['num_columns'];
		}
		elseif( $item['column_span'] < 1 ) {
			$col_span = 1;
		}
		else {
			$col_span = $item['column_span'];
		}

		if( $item['row_span'] > $packery_settings['num_columns'] ) {
			$row_span = $packery_settings['num_columns'];
		}
		elseif( $item['row_span'] < 1 ) {
			$row_span = 1;
		}
		else {
			$row_span = $item['row_span'];
		}
		?>
		<div class="sow-masonry-grid-item" data-col-span="<?php echo esc_attr( $col_span ) ?>"
			 data-row-span="<?php echo esc_attr( $row_span ) ?>">
			<?php if( !empty( $url ) ) : ?>
			<a href="<?php echo sow_esc_url( $url ) ?>" <?php if( $new_window ) { ?>target="_blank" <?php } ?>>
			<?php endif; ?>

				<?php echo wp_get_attachment_image( $item['image'], 'large', false, array( 'title' => esc_attr( $title ) ) ); ?>

			<?php if( !empty( $url ) ) : ?>
			</a>
			<?php endif; ?>
		</div>
		<?php
	}
	?>

</div>

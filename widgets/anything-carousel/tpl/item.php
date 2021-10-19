<?php foreach ( $settings['items'] as $item ) : ?>
	<div class="sow-carousel-item" tabindex="-1">
		<?php if ( ! empty( $item['title'] ) ) : ?>
			<<?php echo esc_attr( $settings['item_title_tag'] ); ?> class="sow-carousel-item-title"><?php echo esc_html( $item['title'] ); ?></<?php echo esc_attr( $settings['item_title_tag'] ); ?>>
		<?php endif; ?>

		<div class="sow-carousel-content">
			<?php $this->render_item_content( $item, $instance ); ?>
		</div>
	</div>
	<?php
endforeach;

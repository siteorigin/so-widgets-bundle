<div class="social-media-button-container">
	<?php foreach( $networks as $network ) :
		$classes = array();
		if( !empty($instance['design']['hover']) ) $classes[] = 'ow-button-hover';
		$classes[] = "sow-social-media-button-" . sanitize_html_class( $network['name'] );
		$classes[] = "sow-social-media-button";
		$button_attributes = array(
			'class' => esc_attr(implode(' ', $classes))
		);
		if(!empty($instance['design']['new_window'])) $button_attributes['target'] = '_blank';
		if ( ! empty( $network['url'] ) ) $button_attributes['href'] = sow_esc_url( $network['url'] );
		?>

		<a <?php foreach($button_attributes as $name => $val) echo $name . '="' . $val . '" ' ?>>
			<span>
				<?php if( !empty( $network['is_custom'] ) ) echo '<!-- premium-' . $network['name'] . ' -->'; ?>
				<?php echo siteorigin_widget_get_icon( $network['icon_name'] ); ?>
				<?php if( !empty( $network['is_custom'] ) ) echo '<!-- endpremium -->'; ?>
			</span>
		</a>
	<?php endforeach; ?>
</div>
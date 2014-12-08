<div class="wrap">
	<h2><?php _e('SiteOrigin Widgets', 'siteorigin-widgets'); ?></h2>

	<div id="siteorigin-widgets-bundle">
		<?php foreach( $widgets as $id => $widget ): ?>
			<div class="so-widget-wrap">
				<div class="so-widget so-widget-is-<?php echo $widget['Active'] ? 'active' : 'inactive' ?>">

					<?php
					$banner = '';
					if( file_exists( plugin_dir_path( $widget['File'] ) . 'assets/banner.svg' ) ) {
						$banner = plugin_dir_url( $widget['File'] ) . 'assets/banner.svg';
					}
					$banner = apply_filters('siteorigin_widgets_widget_banner', $banner, $widget);
					?>

					<img src="<?php echo esc_url( !empty($banner) ? $banner : plugin_dir_url(__FILE__) . '../banners/default.png' ) ?>" />

					<div class="so-widget-text">
						<label class="switch">
							<input class="switch-input" type="checkbox" <?php checked( $widget['Active'] ) ?> data-url="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=so_widgets_bundle_manage&widget='.$widget['ID']), 'manage_so_widget' ) ?>">
							<span class="switch-label" data-on="<?php _e('On', 'siteorigin-widgets') ?>" data-off="<?php _e('Off', 'siteorigin-widgets') ?>"></span>
							<span class="switch-handle"></span>
						</label>

						<h4><?php echo esc_html($widget['Name']); ?></h4>
						<p class="so-widget-description">
							<?php echo $widget['Description'] ?>
						</p>
					</div>

				</div>
			</div>
		<?php endforeach; ?>

	</div>

</div>
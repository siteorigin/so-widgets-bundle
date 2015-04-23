<div class="wrap" id="sow-widgets-page">
	<div class="page-banner">

		<span class="icon">
			<img src="<?php echo plugin_dir_url(__FILE__) ?>../images/icon-back.png" class="icon-back" width="50" height="43">
			<img src="<?php echo plugin_dir_url(__FILE__) ?>../images/icon-gear.png" class="icon-gear" width="26" height="26">
			<img src="<?php echo plugin_dir_url(__FILE__) ?>../images/icon-front.png" class="icon-front" width="50" height="43">
		</span>
		<h1><?php _e('SiteOrigin Widgets Bundle', 'siteorigin-widgets') ?></h1>

		<!--
		<div id="sow-widget-search">
			<input type="search" placeholder="<?php esc_attr_e('Search Widgets', 'siteorigin-widgets') ?>" />
		</div>
		-->
	</div>

	<ul class="page-nav">
		<li class="active"><a href="#all"><?php _e('All', 'siteorigin-widgets') ?></a></li>
		<li><a href="#enabled"><?php _e('Enabled', 'siteorigin-widgets') ?></a></li>
		<li><a href="#disabled"><?php _e('Disabled', 'siteorigin-widgets') ?></a></li>
	</ul>


	<div id="widgets-list">

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

					<img src="<?php echo sow_esc_url( !empty($banner) ? $banner : plugin_dir_url(__FILE__) . '../../banners/default.png' ) ?>" />

					<div class="so-widget-text">
						<label class="switch">
							<span class="dashicons dashicons-yes"></span>
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

	<div class="developers-link">
		<?php _e('Developers - create your own widgets for the Widgets Bundle.', 'siteorigin-widgets') ?>
		<a href="https://siteorigin.com/docs/widgets-bundle/" target="_blank"><?php _e('Read More', 'siteorigin-widgets') ?></a>.
	</div>

</div>
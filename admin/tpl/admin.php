<div class="wrap" id="sow-widgets-page">
	<div class="page-banner">

		<span class="icon">
			<img src="<?php echo plugin_dir_url(__FILE__) ?>../images/icon-back.png" class="icon-back" width="50" height="43">
			<img src="<?php echo plugin_dir_url(__FILE__) ?>../images/icon-gear.png" class="icon-gear" width="26" height="26">
			<img src="<?php echo plugin_dir_url(__FILE__) ?>../images/icon-front.png" class="icon-front" width="50" height="43">
		</span>
		<h1><?php _e('SiteOrigin Widgets Bundle', 'siteorigin-widgets') ?></h1>

		<div id="sow-widget-search">
			<input type="search" placeholder="<?php esc_attr_e('Filter Widgets', 'siteorigin-widgets') ?>" />
		</div>
	</div>

	<ul class="page-nav">
		<li class="active"><a href="#all"><?php _e('All', 'siteorigin-widgets') ?></a></li>
		<li><a href="#enabled"><?php _e('Enabled', 'siteorigin-widgets') ?></a></li>
		<li><a href="#disabled"><?php _e('Disabled', 'siteorigin-widgets') ?></a></li>
	</ul>


	<div id="widgets-list">

		<?php foreach( $widgets as $id => $widget ): ?>
			<div class="so-widget-wrap">
				<div class="so-widget so-widget-is-<?php echo $widget['Active'] ? 'active' : 'inactive' ?>" data-id="<?php echo esc_attr( $widget['ID'] ) ?>">

					<?php
					$banner = '';
					if( file_exists( plugin_dir_path( $widget['File'] ) . 'assets/banner.svg' ) ) {
						$banner = plugin_dir_url( $widget['File'] ) . 'assets/banner.svg';
					}
					$banner = apply_filters('siteorigin_widgets_widget_banner', $banner, $widget);
					?>
					<div class="so-widget-banner" data-seed="<?php echo esc_attr( substr( md5($widget['ID']), 0, 6 ) ) ?>">
						<?php if( !empty($banner) ) : ?>
							<img src="<?php echo esc_url($banner) ?>" />
						<?php endif; ?>
					</div>

					<div class="so-widget-text">

						<div class="so-widget-active-indicator"><?php _e('Active', 'siteorigin-widgets') ?></div>

						<h3><?php echo esc_html( $widget['Name'] ); ?></h3>

						<div class="so-widget-description">
							<?php echo esc_html( $widget['Description'] ) ?>
						</div>

						<?php if( !empty( $widget['Author'] ) ) : ?>
							<div class="so-widget-byline">
								By
								<strong>
								<?php
									if( !empty($widget['AuthorURI']) ) echo '<a href="' . esc_url( $widget['AuthorURI'] ) . '" target="_blank">';
									echo esc_html( $widget['Author'] );
									if( !empty($widget['AuthorURI']) ) echo '</a>';
								?>
								</strong>
							</div>
						<?php endif; ?>

						<div class="so-widget-toggle-active">
							<button class="button-secondary so-widget-activate" data-status="1"><?php esc_html_e( 'Activate', 'siteorigin-widgets' ) ?></button>
							<button class="button-secondary so-widget-deactivate" data-status="0"><?php esc_html_e( 'Deactivate', 'siteorigin-widgets' ) ?></button>
						</div>

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
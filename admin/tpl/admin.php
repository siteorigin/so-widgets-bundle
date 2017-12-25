<div class="wrap" id="sow-widgets-page">
	<div class="page-banner">

		<span class="icon">
			<img src="<?php echo siteorigin_widgets_url( 'admin/images/icon-back.png' )?>" class="icon-back" width="50" height="43">
			<img src="<?php echo siteorigin_widgets_url( 'admin/images/icon-gear.png' ) ?>" class="icon-gear" width="26" height="26">
			<img src="<?php echo siteorigin_widgets_url( 'admin/images/icon-front.png' ) ?>" class="icon-front" width="50" height="43">
		</span>
		<h1><?php _e('SiteOrigin Widgets Bundle', 'so-widgets-bundle') ?></h1>

		<div id="sow-widget-search">
			<input type="search" placeholder="<?php esc_attr_e('Filter Widgets', 'so-widgets-bundle') ?>" />
		</div>
	</div>

	<ul class="page-nav">
		<li class="active"><a href="#all"><?php _e('All', 'so-widgets-bundle') ?></a></li>
		<li><a href="#enabled"><?php _e('Enabled', 'so-widgets-bundle') ?></a></li>
		<li><a href="#disabled"><?php _e('Disabled', 'so-widgets-bundle') ?></a></li>
	</ul>


	<div id="widgets-list">

		<?php
		foreach( $widgets as $file => $widget ): 
			$file = wp_normalize_path( $file );
			?>
			<div class="so-widget-wrap">
				<div class="so-widget so-widget-is-<?php echo $widget['Active'] ? 'active' : 'inactive' ?>" data-id="<?php echo esc_attr( $widget['ID'] ) ?>">

					<?php
					$banner = '';
					$widget_dir = dirname( $file );
					if( file_exists( $widget_dir . '/assets/banner.svg' ) ) {
						$banner = str_replace( wp_normalize_path( WP_CONTENT_DIR ), content_url(), $widget_dir ) . '/assets/banner.svg';
					}
					$banner = apply_filters('siteorigin_widgets_widget_banner', $banner, $widget);
					?>
					<div class="so-widget-banner" data-seed="<?php echo esc_attr( substr( md5($widget['ID']), 0, 6 ) ) ?>">
						<?php if( !empty( $banner ) ) : ?>
							<img src="<?php echo esc_url($banner) ?>" />
						<?php endif; ?>
					</div>

					<div class="so-widget-text">

						<div class="so-widget-active-indicator"><?php _e('Active', 'so-widgets-bundle') ?></div>

						<h3><?php echo esc_html( $widget['Name'] ); ?></h3>

						<div class="so-widget-description">
							<?php echo esc_html( $widget['Description'] ) ?>
						</div>

						<?php if( !empty( $widget['Author'] ) ) : ?>
							<div class="so-widget-byline">
								By
								<strong>
								<?php
									if( !empty($widget['AuthorURI']) ) echo '<a href="' . esc_url( $widget['AuthorURI'] ) . '" target="_blank" rel="noopener noreferrer">';
									echo esc_html( $widget['Author'] );
									if( !empty($widget['AuthorURI']) ) echo '</a>';
								?>
								</strong>
							</div>
						<?php endif; ?>

						<div class="so-widget-toggle-active">
							<button class="button-secondary so-widget-activate" data-status="1"><?php esc_html_e( 'Activate', 'so-widgets-bundle' ) ?></button>
							<button class="button-secondary so-widget-deactivate" data-status="0"><?php esc_html_e( 'Deactivate', 'so-widgets-bundle' ) ?></button>
						</div>

						<?php
						/** @var SiteOrigin_Widget $widget_object */
						$widget_object = !empty( $widget_objects[ $file ] ) ? $widget_objects[ $file ] : false;
						if( !empty( $widget_object ) && $widget_object->has_form( 'settings' ) ) {
							$rel_path = str_replace( wp_normalize_path( WP_PLUGIN_DIR ), '', $file );
							
							$form_url = add_query_arg( array(
									'id' => $rel_path,
									'action' => 'so_widgets_setting_form',
								),
								admin_url( 'admin-ajax.php' )
							);
							$form_url = wp_nonce_url( $form_url, 'display-widget-form' );

							?>
							<button class="button-secondary so-widget-settings" data-form-url="<?php echo esc_url( $form_url ) ?>">
								<?php esc_html_e( 'Settings', 'so-widgets-bundle' ) ?>
							</button>
							<?php
						}
						?>
					</div>

				</div>
			</div>
		<?php endforeach; ?>

	</div>

	<div class="developers-link">
		<?php _e('Developers - create your own widgets for the Widgets Bundle.', 'so-widgets-bundle') ?>
		<a href="https://siteorigin.com/docs/widgets-bundle/" target="_blank" rel="noopener noreferrer"><?php _e('Read More', 'so-widgets-bundle') ?></a>.
	</div>

	<div id="sow-settings-dialog">
		<div class="so-overlay"></div>

		<div class="so-title-bar">
			<h3 class="so-title"><?php _e( 'Widget Settings', 'so-widgets-bundle' ) ?></h3>
			<a class="so-close">
				<span class="so-dialog-icon"></span>
			</a>
		</div>

		<div class="so-content so-loading">
		</div>

		<div class="so-toolbar">
			<div class="so-buttons">
				<button class="button-primary so-save"><?php _e( 'Save', 'so-widgets-bundle' ) ?></button>
			</div>
		</div>
	</div>

	<iframe id="so-widget-settings-save" name="so-widget-settings-save"></iframe>

</div>

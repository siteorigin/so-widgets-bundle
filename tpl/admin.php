<div class="wrap">
	<h2><?php _e('SiteOrigin Widgets', 'siteorigin-widgets'); ?></h2>

	<div id="siteorigin-widgets-bundle">
		<?php foreach( $widgets as $id => $widget ): ?>
			<div class="so-widget-wrap">
				<div class="so-widget so-widget-is-<?php echo !$widget['Active'] ? 'inactive' : 'active' ?>">

					<?php if( $widget['Active'] ) : ?><div class="so-widgets-active-banner"><?php _e('Activated', 'siteorigin-widgets') ?></div><?php endif; ?>

					<img src="<?php echo plugin_dir_url(__FILE__).'../banners/'.$widget['ID'].'.svg' ?>" />

					<div class="so-widget-text">

						<?php if( !$widget['Active'] ) : ?>
							<a href="<?php echo wp_nonce_url( add_query_arg( array( 'widget_action' => 'activate', 'widget' => $widget['ID'] ) ), 'siteorigin_widget_action' ) ?>" class="so-widget-action-link so-widget-action-activate"><?php _e('Activate', 'siteorigin-widgets') ?></a>
						<?php else : ?>
							<a href="<?php echo wp_nonce_url( add_query_arg( array( 'widget_action' => 'deactivate', 'widget' => $widget['ID'] ) ), 'siteorigin_widget_action') ?>" class="so-widget-action-link so-widget-action-deactivate"><?php _e('Deactivate', 'siteorigin-widgets') ?></a>
						<?php endif; ?>

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
<div
	class="sow-cta-base"
	<?php echo apply_filters( 'siteorigin_widgets_cta_base', '', $instance ); ?>
>
	<?php do_action( 'siteorigin_widgets_cta_before_wrapper', $instance ); ?>

	<div class="sow-cta-wrapper">

		<div class="sow-cta-text">
			<h4 class="sow-cta-title"><?php echo wp_kses_post( $instance['title'] ); ?></h4>
			<h5 class="sow-cta-subtitle"><?php echo wp_kses_post( $instance['sub_title'] ); ?></h5>
		</div>

		<?php $this->sub_widget( 'SiteOrigin_Widget_Button_Widget', $args, $instance['button'] ); ?>

	</div>

	<?php do_action( 'siteorigin_widgets_cta_after_wrapper', $instance ); ?>
</div>

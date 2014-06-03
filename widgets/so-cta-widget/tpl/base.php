<div class="sow-cta-base">

	<div class="sow-cta-wrapper">

		<div class="sow-cta-text">
			<h4><?php echo wp_kses_post( $instance['title'] ) ?></h4>
			<h5><?php echo wp_kses_post( $instance['sub_title'] ) ?></h5>
		</div>

		<?php $this->sub_widget('SiteOrigin_Widget_Button_Widget', $args, $instance['button']) ?>

	</div>

</div>
<div class="sow-cta-base">

	<div class="sow-cta-wrapper">

		<div class="sow-cta-text">
			<?php if ( ! empty( $title ) ) { ?>
				<<?php echo $title_tag; ?> class="sow-cta-title">
					<?php echo wp_kses_post( $title ); ?>
				</<?php echo $title_tag; ?>>
			<?php } ?>

			<?php if ( ! empty( $sub_title ) ) { ?>
				<<?php echo $sub_title_tag; ?> class="sow-cta-subtitle">
					<?php echo wp_kses_post( $sub_title ); ?>
				</<?php echo $sub_title_tag; ?>>
			<?php } ?>
		</div>

		<?php $this->sub_widget( 'SiteOrigin_Widget_Button_Widget', $args, $button ); ?>

	</div>

	<?php do_action( 'siteorigin_widgets_cta_after_wrapper', $instance ); ?>

</div>

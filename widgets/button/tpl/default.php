<?php
/**
 * @var array  $button_attributes
 * @var string $href
 * @var string $onclick
 * @var string $align
 * @var string $icon_image_url
 * @var string $icon
 * @var string $icon_color
 * @var string $text
 */

$add_anchor = ! empty( $href ) ||
	apply_filters( 'siteorigin_widgets_button_always_add_anchor', true );
?>
<div class="ow-button-base ow-button-align-<?php echo esc_attr( $align ); ?>">
	<?php if ( $add_anchor ) { ?>
		<a
		<?php if ( ! empty( $href ) ) { ?>
			href="<?php echo sow_esc_url( do_shortcode( $href ) ); ?>"
			<?php
		}
	} else {
		?>
		<div
	<?php } ?>
		<?php
		foreach ( $button_attributes as $name => $val ) {
			echo siteorigin_sanitize_attribute_key( $name ) . '="' . esc_attr( $val ) . '" ';
		}

		if ( ! empty( $on_click ) ) {
			echo 'onclick="' . siteorigin_widget_onclick( $on_click ) . '"';
		} ?>
	>
		<span>
			<?php
			if ( ! empty( $icon_image_url ) ) {
				?><div class="sow-icon-image" style="<?php echo 'background-image: url(' . sow_esc_url( $icon_image_url ) . ')'; ?>"></div><?php
			} else {
				$icon_styles = array();

				if ( ! empty( $icon_color ) ) {
					$icon_styles[] = 'color: ' . esc_attr( $icon_color );
				}
				echo siteorigin_widget_get_icon( $icon, $icon_styles );
			}
			?>

			<?php echo wp_kses_post( $text ); ?>
		</span>
	<?php if ( $add_anchor ) { ?>
		</a>
	<?php } else { ?>
		</div>
	<?php } ?>
</div>

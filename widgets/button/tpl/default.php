<?php
/**
 * @var array $button_attributes
 * @var string $href
 * @var string $onclick
 * @var string $align
 * @var string $icon_image_url
 * @var string $icon
 * @var string $icon_color
 * @var string $text
 */

?>
<div class="ow-button-base ow-button-align-<?php echo esc_attr( $align ) ?>">
	<a href="<?php echo sow_esc_url( $href ) ?>" <?php foreach( $button_attributes as $name => $val ) echo $name . '="' . esc_attr( $val ) . '" ' ?>
		<?php if ( ! empty( $onclick ) ) echo 'onclick="' . esc_js( $onclick ) . '"'; ?>>
		<span>
			<?php
				if( ! empty( $icon_image_url ) ) {
                    ?><div class="sow-icon-image" style="<?php echo 'background-image: url(' . sow_esc_url( $icon_image_url ) . ')' ?>"></div><?php
				}
				else {
					$icon_styles = array();
					if ( ! empty( $icon_color ) ) $icon_styles[] = 'color: ' . $icon_color;
					echo siteorigin_widget_get_icon( $icon, $icon_styles );
				}
			?>

			<?php echo wp_kses_post( $text ) ?>
		</span>
	</a>
</div>

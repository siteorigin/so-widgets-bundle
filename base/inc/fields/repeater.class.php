<?php

/**
 * Class SiteOrigin_Widget_Field_Repeater
 */
class SiteOrigin_Widget_Field_Repeater extends SiteOrigin_Widget_Field_Container_Base {

	/**
	 *  A default label for each repeated item.
	 *
	 * @access protected
	 * @var string
	 */
	protected $item_name;
	/**
	 * This associative array describes how the repeater may retrieve the item labels from HTML elements as they are
	 * updated. The options are:
	 *  - selector string A JQuery selector which is used to find an element from which to retrieve the item label.
	 *  - update_event string The JavaScript event on which to bind and update the item label.
	 *  - value_method string The JavaScript function which should be used to retrieve the item label from an element.
	 *
	 * @access protected
	 * @var array
	 */
	protected $item_label;
	/**
	 * The maximum number of repeated items to display before adding a scrollbar to the repeater.
	 *
	 * @access protected
	 * @var int
	 */
	protected $scroll_count;
    /**
     * The maximum number of repeated items
     *
     * @access protected
     * @var int
     */
    protected $max_items;
	/**
	 * Whether or not items may be added to or removed from this repeater by user interaction.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $readonly;

	protected function render_field( $value, $instance ) {
		if( !isset( $this->fields ) || empty( $this->fields ) ) return;
		$container = array( 'name' => $this->base_name, 'type' => 'repeater' );
		$item_label = isset( $this->item_label ) ? $this->item_label : null;
        $max_items = isset( $this->max_items ) ? $this->max_items : null;
		if ( ! empty( $item_label ) ) {
			// convert underscore naming convention to camelCase for javascript and encode as json string
			$item_label = wp_parse_args( $item_label, array(
				'update_event' => 'change',
				'value_method' => 'val'
			) );
			$item_label = siteorigin_widgets_underscores_to_camel_case( $item_label );
			$item_label = json_encode( $item_label );
		}
		if( empty( $this->item_name ) ) $this->item_name = __( 'Item', 'so-widgets-bundle' );
		?>
		<div class="siteorigin-widget-field-repeater"
		     data-item-name="<?php echo esc_attr( $this->item_name ) ?>"
		     data-repeater-name="<?php echo esc_attr( $this->base_name ) ?>"
             data-max-items="<?php echo esc_attr( $this->max_items ) ?>"
		     data-element-name="<?php echo esc_attr( $this->element_name ) ?>"
			<?php echo ! empty( $item_label ) ? 'data-item-label="' . esc_attr( $item_label ) . '"' : '' ?>
			<?php echo ! empty( $this->scroll_count ) ? 'data-scroll-count="' . esc_attr( $this->scroll_count ) . '"' : '' ?>
			<?php if( ! empty( $this->readonly ) ) echo 'readonly' ?>>
			<div class="siteorigin-widget-field-repeater-top">
				<div class="siteorigin-widget-field-repeater-expand"></div>
				<h3><?php echo esc_html( $this->label ) ?> <?php if($max_items !== null) : echo '(Maximum : '.$max_items.')'; endif; ?></h3>
			</div>
			<div class="siteorigin-widget-field-repeater-items">
				<?php
				if( !empty( $value ) ) {
					foreach( $value as $v ) {
						?>
						<div class="siteorigin-widget-field-repeater-item ui-draggable">
							<div class="siteorigin-widget-field-repeater-item-top">
								<div class="siteorigin-widget-field-expand"></div>
								<?php if( empty( $this->readonly ) ) : ?>
									<div class="siteorigin-widget-field-copy" <?php if(count($value) === $max_items) : ?>style="display: none;"<?php endif; ?>></div>
									<div class="siteorigin-widget-field-remove"></div>
								<?php endif; ?>
								<h4><?php echo esc_html( $this->item_name ) ?></h4>
							</div>
							<div class="siteorigin-widget-field-repeater-item-form">
								<?php
								$this->create_and_render_sub_fields( $v, $container );
								?>
							</div>
						</div>
						<?php
					}
				}
				?>
			</div>
			<?php if( empty( $this->readonly ) ) : ?>
				<div class="siteorigin-widget-field-repeater-add <?php if(count($value) === $max_items) : ?>is-hidden<?php endif; ?>" <?php if(count($value) === $max_items) : ?>style="display: none;"<?php endif; ?>><?php esc_html_e( 'Add', 'so-widgets-bundle' ) ?></div>
			<?php endif; ?>
			<?php
			ob_start();
			$this->create_and_render_sub_fields( null, $container, true );
			$rpt_fields = ob_get_clean();
			$rpt_fields = preg_replace( '/\s+name\s*=\s*/', ' data-name=', $rpt_fields );
			?>
			<div class="siteorigin-widget-field-repeater-item-html" style="display: none;">
				<?php echo $rpt_fields; ?>
			</div>
		</div>
		<?php
	}

	protected function render_field_label( $value, $instance ) {
		// Empty override. This field renders it's own label in the render_field() function.
	}

	/**
	 * Go over the items in the repeater and sanitize each one using the container sanitization function.
	 *
	 * @param mixed $value
	 *
	 * @return array|mixed
	 */
	function sanitize_field_input( $value, $instance ){
		if( empty($value) ) return array();

		foreach( $value as &$el ) {
			$el = parent::sanitize_field_input( $el, $instance );
		}

		return $value;
	}
}

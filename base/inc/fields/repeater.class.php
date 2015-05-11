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
	 * Whether or not items may be added to or removed from this repeater by user interaction.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $readonly;

	public function __construct( $base_name, $element_id, $element_name, $field_options, SiteOrigin_Widget $for_widget, $parent_container = array() ) {
		parent::__construct( $base_name, $element_id, $element_name, $field_options, $for_widget, $parent_container );

		if( isset( $field_options['item_name'] ) ) $this->item_name = $field_options['item_name'];
		if( isset( $field_options['item_label'] ) ) $this->item_label = $field_options['item_label'];
		if( isset( $field_options['scroll_count'] ) ) $this->scroll_count = $field_options['scroll_count'];
		if( isset( $field_options['readonly'] ) ) $this->readonly = $field_options['readonly'];
	}

	protected function render_field( $value, $instance ) {
		if( !isset( $this->sub_field_options ) || empty( $this->sub_field_options ) ) return;
		$container = array( 'name' => $this->base_name, 'type' => 'repeater' );
		ob_start();
		$this->create_and_render_sub_fields( null, $container, true );
		$html = ob_get_clean();

		$this->javascript_variables['repeaterHTML'] = $html;
		$item_label = isset( $this->item_label ) ? $this->item_label : null;
		if ( ! empty( $item_label ) ) {
			// convert underscore naming convention to camelCase for javascript and encode as json string
			$item_label = $this->underscores_to_camel_case( $item_label );
			$item_label = json_encode( $item_label );
		}
		if( empty( $this->item_name ) ) $this->item_name = __( 'Item', 'siteorigin-widgets' );
		?>
		<div class="siteorigin-widget-field-repeater"
		     data-item-name="<?php echo esc_attr( $this->item_name ) ?>"
		     data-repeater-name="<?php echo esc_attr( $this->base_name ) ?>"
		     data-element-name="<?php echo esc_attr( $this->element_name ) ?>"
			<?php echo ! empty( $item_label ) ? 'data-item-label="' . esc_attr( $item_label ) . '"' : '' ?>
			<?php echo ! empty( $this->scroll_count ) ? 'data-scroll-count="' . esc_attr( $this->scroll_count ) . '"' : '' ?>
			<?php if( ! empty( $this->readonly ) ) echo 'readonly' ?>>
			<div class="siteorigin-widget-field-repeater-top">
				<div class="siteorigin-widget-field-repeater-expend"></div>
				<h3><?php echo $this->label ?></h3>
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
									<div class="siteorigin-widget-field-remove"></div>
								<?php endif; ?>
								<h4><?php echo esc_html( $this->item_name ) ?></h4>
							</div>
							<div class="siteorigin-widget-field-repeater-item-form">
								<?php
								$this->create_and_render_sub_fields( $v );
								?>
							</div>
						</div>
						<?php
					}
				}
				?>
			</div>
			<?php if( empty( $this->readonly ) ) : ?>
				<div class="siteorigin-widget-field-repeater-add"><?php _e('Add', 'siteorigin-widgets') ?></div>
			<?php endif; ?>
		</div>
		<?php
	}

	protected function render_field_label() {
		// Empty override. This field renders it's own label in the render_field() function.
	}
}
<?php

/**
 * Class SiteOrigin_Widget_Field_Repeater
 */
class SiteOrigin_Widget_Field_Repeater extends SiteOrigin_Widget_Field {

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
	 * The set of fields to be repeated together as one item. This should contain any combination of other field types,
	 * even repeaters and sections.
	 *
	 * @access protected
	 * @var array
	 */
	protected $fields;
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
	/**
	 * The set of field classes to be rendered together as one item.
	 *
	 * @var array
	 */
	private $sub_fields;
	/**
	 * Reference to the containing widget required for creating subfields.
	 *
	 * @access private
	 * @var SiteOrigin_Widget
	 */
	private $for_widget;
	/**
	 * An array of field names of parent repeaters.
	 *
	 * @var array
	 */
	private $parent_repeater;

	public function __construct( $base_name, $element_id, $element_name, $options, SiteOrigin_Widget $for_widget, $parent_repeater = array() ) {
		parent::__construct( $base_name, $element_id, $element_name, $options );

		if( isset( $options['item_name'] ) ) $this->item_name = $options['item_name'];
		if( isset( $options['item_label'] ) ) $this->item_label = $options['item_label'];
		if( isset( $options['fields'] ) ) $this->fields = $options['fields'];
		if( isset( $options['scroll_count'] ) ) $this->scroll_count = $options['scroll_count'];
		if( isset( $options['readonly'] ) ) $this->readonly = $options['readonly'];

		$this->for_widget = $for_widget;
		$this->parent_repeater = $parent_repeater;
	}

	protected function render_field( $value, $instance ) {
		if( !isset( $this->fields ) || empty( $this->fields ) ) return;
		// Figure out how to get repeater HTML
//		ob_start();
		$this->parent_repeater[] = $this->base_name;
//		foreach( $this->fields as $sub_field_name => $sub_field_options ) {
//			$element_id = $this->so_get_field_id( $sub_field_name );
//			$element_name = $this->so_get_field_name( $sub_field_name );
//			$field = SiteOrigin_Widget_Field_Factory::create_field( $sub_field_name, $element_id, $element_name, $sub_field_options );
//			$field->render( isset( $value[$sub_field_name] ) ? $value[$sub_field_name] : null );
//			$this->sub_fields[$sub_field_name] = $field;
//		}
//		$html = ob_get_clean();
//
//		$this->repeater_html[$this->base_name] = $html;

		$item_label = isset( $this->item_label ) ? $this->item_label : null;
		if ( ! empty( $item_label ) ) {
			// convert underscore naming convention to camelCase for javascript and encode as json string
			$item_label = $this->underscores_to_camel_case( $item_label );
			$item_label = json_encode( $item_label );
		}
		if( empty( $this->item_name ) ) $this->item_name = __( 'Item', 'siteorigin-widgets' );
		?>
		<div class="siteorigin-widget-field-repeater" data-item-name="<?php echo esc_attr( $this->item_name ) ?>" data-repeater-name="<?php echo esc_attr( $this->base_name ) ?>" <?php echo ! empty( $item_label ) ? 'data-item-label="' . esc_attr( $item_label ) . '"' : '' ?> <?php echo ! empty( $this->scroll_count ) ? 'data-scroll-count="' . esc_attr( $this->scroll_count ) . '"' : '' ?> <?php if( ! empty( $this->readonly ) ) echo 'readonly' ?>>
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
								foreach($this->fields as $sub_field_name => $sub_field_options) {
									$field = SiteOrigin_Widget_Field_Factory::create_field( $sub_field_name, $sub_field_options, $this->for_widget, $this->parent_repeater );
									$field->render( isset( $value[$sub_field_name] ) ? $value[$sub_field_name] : null );
									$this->sub_fields[$sub_field_name] = $field;
								}
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

	protected function sanitize_field_input( $value ) {

	}

}
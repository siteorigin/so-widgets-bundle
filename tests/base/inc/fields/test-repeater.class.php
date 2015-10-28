<?php

class SiteOrigin_Widget_Field_RepeaterTest extends WP_UnitTestCase {

	private $field_options;

	/* @var $stub_widget SiteOrigin_Widget */
	private $stub_widget;

	/* @var $field SiteOrigin_Widget_Field_Base */
	private $field;

	private $field_output;

	function setUp() {
		$this->field_options = array(
			'type' => 'repeater',
			'label' => 'A repeater field',
			'item_name' => 'Repeat item',
			'item_label' => array(
				'selector'     => "[id*='field_one']",
				'update_event' => 'change',
				'value_method' => 'val'),
			'scroll_count' => 5,
			'readonly' => true,
			'fields' => array(
				'field_one' => array(
					'type' => 'text'
				)
			),
		);

		$this->stub_widget = $this->getMockBuilder( 'SiteOrigin_Widget' )
		                          ->disableOriginalConstructor()
		                          ->getMockForAbstractClass();

		/* @var $field SiteOrigin_Widget_Field_Repeater */
		$this->field = new SiteOrigin_Widget_Field_Repeater( 'repeater_field', 'repeater_field_id', 'repeater_field_name', $this->field_options, $this->stub_widget );

		$this->field_output = get_field_render_output( $this->field );
	}

	/**
	 * @test
	 */
	public function render_outputs_repeater_HTML_template() {
		$this->assertContains( 'siteorigin-widget-field-repeater-item-html', $this->field_output );
	}

	/**
	 * @test
	 */
	public function render_outputs_data_item_name_attribute() {
		$this->assertContains( 'data-item-name="Repeat item"', $this->field_output );
	}

	/**
	 * @test
	 */
	public function render_outputs_data_repeater_name_attribute() {
		$this->assertContains( 'data-repeater-name="repeater_field"', $this->field_output );
	}

	/**
	 * @test
	 */
	public function render_outputs_data_element_name_attribute() {
		$this->assertContains( 'data-element-name="repeater_field_name"', $this->field_output );
	}

	/**
	 * @test
	 */
	public function render_outputs_data_item_label_attribute() {
		$this->assertContains( 'data-item-label="{&quot;updateEvent&quot;:&quot;change&quot;,&quot;valueMethod&quot;:&quot;val&quot;,&quot;selector&quot;:&quot;[id*=&#039;field_one&#039;]&quot;}', $this->field_output );
	}

	/**
	 * @test
	 */
	public function render_outputs_data_scroll_count_attribute() {
		$this->assertContains( 'data-scroll-count="5"', $this->field_output );
	}

	/**
	 * @test
	 */
	public function render_outputs_readonly_attribute() {
		$this->assertContains( 'readonly', $this->field_output );
	}

	/**
	 * @test
	 */
	public function render_does_not_output_add_button_when_readonly() {
		$this->assertNotContains( '<div class="siteorigin-widget-field-repeater-add">Add</div>', $this->field_output );
	}

}

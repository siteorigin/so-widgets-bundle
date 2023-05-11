<?php

abstract class SiteOrigin_Widget_ContactForm_Field_Base {
	/**
	 * The options for this field. Used when enqueueing styles and scripts and rendering the field.
	 *
	 * @var array
	 */
	protected $options;
	private $type;

	public function __construct( $options ) {
		$this->options = $options;
		$this->init();
	}

	private function init() {
		$this->initialize( $this->options );
	}

	protected function initialize( $options ) {
	}

	abstract protected function render_field( $options );

	public function render() {
		$this->render_field( $this->options );
	}

	public static function add_custom_attrs( $type ) {
		$attr = apply_filters( 'siteorigin_widgets_contact_field_attr', array(), $type );

		foreach ( $attr as $k => $v ) {
			echo esc_attr( $k ) . '="' . esc_attr( $v ) . '" ';
		}
	}
}

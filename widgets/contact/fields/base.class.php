<?php
abstract class SiteOrigin_Widget_ContactForm_Field_Base {

	/**
	 * The options for this field. Used when enqueueing styles and scripts and rendering the field.
	 *
	 * @access protected
	 * @var array
	 */
	protected $options;

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
}

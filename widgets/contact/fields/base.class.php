<?php
abstract class SiteOrigin_Widget_ContactForm_Field_Base {

	public function __construct() {
		$this->init();
	}

	private function init() {
		$this->initialize();
	}

	protected function initialize() {
	}

	abstract protected function render_field( $options );

	public function render( $options ) {
		$this->render_field( $options );
	}
}

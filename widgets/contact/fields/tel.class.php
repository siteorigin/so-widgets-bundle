<?php

class SiteOrigin_Widget_ContactForm_Field_Tel extends SiteOrigin_Widget_ContactForm_Field_Text {
	// Outside of the construct, this class just exists for autoloading purposes, but is the same as the text field.
	public function __construct( $options ) {
		$this->type = 'tel';
		parent::__construct( $options );
	}
}

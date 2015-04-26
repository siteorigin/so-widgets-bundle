<?php

/**
 * The class that other fields most extend
 *
 * Class SiteOrigin_Widget_Field
 */
abstract class SiteOrigin_Widget_Field {

	protected $field_options;

	function __construct( $field_options ){
		$this->field_options = $field_options;
	}

	/**
	 * Generate
	 *
	 * @return mixed
	 */
	abstract public function render( $value );

	/**
	 * The default sanitization function. Even if implemented in a child class, this should still be called.
	 */
	public function sanitize( $value ){

		if( isset($this->field_options['sanitize']) ) {
			// This field also needs some custom sanitization
			switch($this->field_options['sanitize']) {
				case 'url':
					$value = sow_esc_url_raw( $value );
					break;

				case 'email':
					$value = sanitize_email( $value );
					break;

				default:
					// This isn't a built in sanitization. Maybe it's handled elsewhere.
					$value = apply_filters( 'siteorigin_widgets_sanitize_field_' . $this->field_options['sanitize'], $value );
					break;
			}
		}

		return $value;

	}

}

// All the implementing fields will go here
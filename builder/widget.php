<?php

/**
 * A widget that
 *
 * Class SiteOrigin_Widget_Custom_Built_Widget
 */
class SiteOrigin_Widget_Custom_Built_Widget extends SiteOrigin_Widget {

	private $custom_options;

	function __construct( $id, $name, $description, $custom_options ) {
		parent::__construct(
			$id,
			$name,
			array(
				'description' => $description,
			),
			array( ),
			array( ),
			plugin_dir_path( __FILE__ )
		);

		$this->custom_options = $custom_options;
	}

	function initialize_form(){
		// Convert the $custom_options into a form array
		$form = $this->generate_form_array( $this->custom_options[ 'fields' ] );

		return $form;
	}

	private function generate_form_array( $custom_fields ) {
		$fields = array();

		foreach( $custom_fields as $cf ) {
			$cf_args = $cf;
			unset( $cf_args['variable'] );
			unset( $cf_args['sub_fields'] );
			$fields[ $cf[ 'variable' ] ] = $cf_args;

			if( $cf[ 'type' ] == 'repeater' || $cf['type'] == 'section' ) {
				$fields[ $cf[ 'variable' ] ][ 'fields' ] = $this->generate_form_array( $cf['sub_fields'] );
			}
		}

		return $fields;
	}
}
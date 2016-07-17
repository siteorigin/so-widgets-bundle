<?php

/**
 * A widget that
 *
 * Class SiteOrigin_Widget_Custom_Built_Widget
 */
class SiteOrigin_Widget_Custom_Built_Widget extends SiteOrigin_Widget {

	private $custom_options;

	function __construct( $id, $name, $description, $custom_options ) {
		$this->custom_options = $custom_options;

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
	}

	/**
	 * Initialize the form based on the custom_options.
	 *
	 * @return array
	 */
	function initialize_form(){
		// Convert the $custom_options into a form array
		$form = $this->generate_form_array( $this->custom_options[ 'fields' ] );
		if( $this->custom_options['has_title'] ) {
			$form = array_merge( array(
				'title' => array(
					'label' => __( 'Title', 'so-widgets-bundle' ),
					'type' => 'text',
				),
			), $form );
		}

		return $form;
	}

	/**
	 * Initialize the custom widget.
	 */
	function initialize() {
		if( ! empty( $this->custom_options[ 'scripts' ] ) ) {
			// Register the scripts
			$scripts = array();
			foreach( $this->custom_options[ 'scripts' ] as $script ) {
				if( empty( $script['file'] ) ) continue;

				$url = wp_get_attachment_url( $script['file'] );
				$file = get_attached_file( $script['file'] );

				if( empty( $url ) || empty( $file ) ) continue;

				$scripts[] = array(
					$this->id . '-script-' . intval( $script['file'] ),
					$url,
					$script['jquery'] ? array( 'jquery' ) : array( ),
					md5_file( $file )
				);
			}

			if( ! empty( $scripts ) ) {
				$this->register_frontend_scripts( $scripts );
			}
		}

		if( ! empty( $this->custom_options[ 'styles' ] ) ) {
			// Register the styles
			$styles = array();
			foreach( $this->custom_options[ 'styles' ] as $style ) {
				if( empty( $style['file'] ) ) continue;

				$url = wp_get_attachment_url( $style['file'] );
				$file = get_attached_file( $style['file'] );

				if( empty( $url ) || empty( $file ) ) continue;

				$styles[] = array(
					$this->id . '-script-' . intval( $style['file'] ),
					$url,
					$script['jquery'] ? array( 'jquery' ) : array( ),
					md5_file( $file )
				);
			}

			if( ! empty( $styles ) ) {
				$this->register_frontend_scripts( $styles );
			}
		}
	}

	/**
	 * Convert part of the custom_options array into a form array
	 *
	 * @param $custom_fields
	 * @return array
	 */
	private function generate_form_array( $custom_fields ) {
		$fields = array();

		foreach( $custom_fields as $cf ) {
			$cf_args = $cf;
			unset( $cf_args[ 'variable' ] );
			unset( $cf_args[ 'sub_fields' ] );
			$fields[ $cf[ 'variable' ] ] = $cf_args;

			if( $cf[ 'type' ] == 'repeater' || $cf['type'] == 'section' ) {
				$fields[ $cf[ 'variable' ] ][ 'fields' ] = $this->generate_form_array( $cf['sub_fields'] );
			}
		}

		return $fields;
	}

	function get_html_content( $instance, $args, $template_vars, $css_name ){
		$tpl = $this->custom_options[ 'template_code' ];

		// TODO Process the code

		// Add the title field if there is one
		if( $this->custom_options[ 'has_title' ] && !empty( $instance['title'] ) ) {
			$tpl = $args[ 'before_title' ] . $instance['title'] . $args[ 'after_title' ] . $tpl;
		}

		return $tpl;
	}

	function get_less_content( $instance ){
		return $this->custom_options[ 'less_code' ];
	}
}
<?php

/*
Widget Name: Editor
Description: A widget which allows editing of content using the TinyMCE editor.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

class SiteOrigin_Widget_Editor_Widget extends SiteOrigin_Widget {

	function __construct() {

		parent::__construct(
			'sow-editor',
			__('SiteOrigin Editor', 'so-widgets-bundle'),
			array(
				'description' => __('A rich-text, text editor.', 'so-widgets-bundle'),
				'help' => 'https://siteorigin.com/widgets-bundle/editor-widget/'
			),
			array(),
			array(
				'title' => array(
					'type' => 'text',
					'label' => __('Title', 'so-widgets-bundle'),
				),
				'text' => array(
					'type' => 'tinymce',
					'rows' => 20
				),
				'autop' => array(
					'type' => 'checkbox',
					'default' => true,
					'label' => __('Automatically add paragraphs', 'so-widgets-bundle'),
				),
			),
			plugin_dir_path(__FILE__)
		);
	}

	function unwpautop($string) {
		$string = str_replace("\n", "", $string);
		$string = str_replace("<p>", "", $string);
		$string = str_replace(array("<br />", "<br>", "<br/>"), "\n", $string);
		$string = str_replace("</p>", "\n\n", $string);

		return $string;
	}

	public function get_template_variables( $instance, $args ) {
		$instance = wp_parse_args(
			$instance,
			array(  'text' => '' )
		);

		$instance['text'] = $this->unwpautop( $instance['text'] );

		/* @var $field_factory SiteOrigin_Widget_Field_Factory */
		$field_factory = SiteOrigin_Widget_Field_Factory::getInstance();
		$form_options = $this->form_options();
		$field = $field_factory->create_field( $this->id_base, $form_options['text'], $this );
		$instance['text'] = $field->sanitize( $instance['text'] );

		$instance['text'] = apply_filters( 'widget_text', $instance['text'] );

		// Run some known stuff
		if( !empty($GLOBALS['wp_embed']) ) {
			$instance['text'] = $GLOBALS['wp_embed']->autoembed( $instance['text'] );
		}
		if( $instance['autop'] ) {
			$instance['text'] = wpautop( $instance['text'] );
		}
		$instance['text'] = do_shortcode( $instance['text'] );

		return array(
			'text' => $instance['text'],
		);
	}


	function get_template_name($instance) {
		return 'editor';
	}

	function get_style_name($instance) {
		// We're not using a style
		return false;
	}
}

siteorigin_widget_register( 'sow-editor', __FILE__, 'SiteOrigin_Widget_Editor_Widget' );
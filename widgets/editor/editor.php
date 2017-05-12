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
			false,
			plugin_dir_path(__FILE__)
		);
	}

	function get_widget_form() {
		$global_settings = $this->get_global_settings();
		return array(
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
				'default' => $global_settings['autop_default'],
				'label' => __('Automatically add paragraphs', 'so-widgets-bundle'),
			),
		);
	}

	function get_settings_form() {
		return array(
			'autop_default' => array(
				'type'    => 'checkbox',
				'default' => true,
				'label'   => __( 'Enable the <em>Automatically add paragraphs</em> setting by default.', 'so-widgets-bundle' ),
			),
		);
	}

	function unwpautop($string) {
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

		if (function_exists('wp_make_content_images_responsive')) {
			$instance['text'] = wp_make_content_images_responsive( $instance['text'] );
		}

		if (
			// Only run these parts if we're rendering for the frontend
			empty( $GLOBALS[ 'SITEORIGIN_PANELS_CACHE_RENDER' ] ) &&
			empty( $GLOBALS[ 'SITEORIGIN_PANELS_POST_CONTENT_RENDER' ] )
		) {
			// Manual support for Jetpack Markdown module.
			if ( class_exists( 'WPCom_Markdown' ) &&
			     Jetpack::is_module_active( 'markdown' ) &&
			     $instance['text_selected_editor'] == 'html'
			) {
				$markdown_parser = WPCom_Markdown::get_instance();
				$instance['text'] = $markdown_parser->transform( $instance['text'] );
			}

			// Run some known stuff
			if( ! empty( $GLOBALS['wp_embed'] ) ) {
				$instance['text'] = $GLOBALS['wp_embed']->run_shortcode( $instance['text'] );
				$instance['text'] = $GLOBALS['wp_embed']->autoembed( $instance['text'] );
			}

			$instance['text'] = apply_filters( 'widget_text', $instance['text'] );
			$instance['text'] = do_shortcode( shortcode_unautop( $instance['text'] ) );
		}

		if( $instance['autop'] ) {
			$instance['text'] = wpautop( $instance['text'] );
		}

		return array(
			'text' => $instance['text'],
		);
	}


	function get_style_name($instance) {
		// We're not using a style
		return false;
	}
}

siteorigin_widget_register( 'sow-editor', __FILE__, 'SiteOrigin_Widget_Editor_Widget' );

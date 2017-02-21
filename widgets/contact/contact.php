<?php

/*
Widget Name: Contact Form
Description: A light weight contact form builder.
Author: SiteOrigin
Author URI: https://siteorigin.com
*/

class SiteOrigin_Widgets_ContactForm_Widget extends SiteOrigin_Widget {

	function __construct() {

		parent::__construct(
			'sow-contact-form',
			__( 'SiteOrigin Contact Form', 'so-widgets-bundle' ),
			array(
				'description' => __( 'Create a simple contact form for your users to get hold of you.', 'so-widgets-bundle' ),
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	/**
	 * Initialize the contact form widget
	 */
	function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'sow-contact',
					plugin_dir_url( __FILE__ ) . 'js/contact' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' ),
					SOW_BUNDLE_VERSION
				)
			)
		);
		add_filter( 'siteorigin_widgets_sanitize_field_multiple_emails', array( $this, 'sanitize_multiple_emails' ) );
	}

	function get_widget_form() {
		return array(
			'title' => array(
				'type'    => 'text',
				'label'   => __( 'Title', 'so-widgets-bundle' ),
				'default' => __( 'Contact Us', 'so-widgets-bundle' ),
			),

			'display_title' => array(
				'type'  => 'checkbox',
				'label' => __( 'Display title', 'so-widgets-bundle' ),
			),

			'settings' => array(
				'type'   => 'section',
				'label'  => __( 'Settings', 'so-widgets-bundle' ),
				'hide'   => true,
				'fields' => array(
					'to'                               => array(
						'type'        => 'text',
						'label'       => __( 'To email address', 'so-widgets-bundle' ),
						'description' => __( 'Where contact emails will be delivered to.', 'so-widgets-bundle' ),
						'sanitize'    => 'multiple_emails',
					),
					'default_subject'                  => array(
						'type'        => 'text',
						'label'       => __( 'Default subject', 'so-widgets-bundle' ),
						'description' => __( "Subject to use when there isn't one available.", 'so-widgets-bundle' ),
					),
					'subject_prefix'                   => array(
						'type'        => 'text',
						'label'       => __( 'Subject prefix', 'so-widgets-bundle' ),
						'description' => __( 'Prefix added to all incoming email subjects.', 'so-widgets-bundle' ),
					),
					'success_message'                  => array(
						'type'        => 'tinymce',
						'label'       => __( 'Success message', 'so-widgets-bundle' ),
						'description' => __( 'Message to display after message successfully sent.', 'so-widgets-bundle' ),
						'default'     => __( "Thanks for contacting us. We'll get back to you shortly.", 'so-widgets-bundle' )
					),
					'submit_text'                      => array(
						'type'    => 'text',
						'label'   => __( 'Submit button text', 'so-widgets-bundle' ),
						'default' => __( "Contact Us", 'so-widgets-bundle' )
					),
					'required_field_indicator'         => array(
						'type'          => 'checkbox',
						'label'         => __( 'Indicate required fields with asterisk (*)', 'so-widgets-bundle' ),
						'state_emitter' => array(
							'callback' => 'conditional',
							'args'     => array(
								'required_fields[show]: val',
								'required_fields[hide]: ! val'
							),
						)
					),
					'required_field_indicator_message' => array(
						'type'          => 'text',
						'label'         => __( 'Required field indicator message', 'so-widgets-bundle' ),
						'default'       => __( 'Fields marked with * are required', 'so-widgets-bundle' ),
						'state_handler' => array(
							'required_fields[show]' => array( 'show' ),
							'required_fields[hide]' => array( 'hide' ),
						)
					),

				)
			),

			'fields' => array(

				'type'       => 'repeater',
				'label'      => __( 'Fields', 'so-widgets-bundle' ),
				'item_name'  => __( 'Field', 'so-widgets-bundle' ),
				'item_label' => array(
					'selector' => "[id*='label']",
				),
				'fields'     => array(

					'type' => array(
						'type'          => 'select',
						'label'         => __( 'Field Type', 'so-widgets-bundle' ),
						'options'       => array(
							'name'       => __( 'Name', 'so-widgets-bundle' ),
							'email'      => __( 'Email', 'so-widgets-bundle' ),
							'subject'    => __( 'Subject', 'so-widgets-bundle' ),
							'text'       => __( 'Text', 'so-widgets-bundle' ),
							'textarea'   => __( 'Text Area', 'so-widgets-bundle' ),
							'select'     => __( 'Dropdown Select', 'so-widgets-bundle' ),
							'checkboxes' => __( 'Checkboxes', 'so-widgets-bundle' ),
							'radio'      => __( 'Radio', 'so-widgets-bundle' ),
						),
						'state_emitter' => array(
							'callback' => 'select',
							'args'     => array( 'field_type_{$repeater}' ),
						)
					),

					'label' => array(
						'type'  => 'text',
						'label' => __( 'Label', 'so-widgets-bundle' ),
					),

					'description' => array(
						'type'        => 'text',
						'label'       => __( 'Description', 'so-widgets-bundle' ),
						'description' => __( 'This text will appear small beneath the input field.', 'so-widgets-bundle' ),
					),

					'required' => array(
						'type'   => 'section',
						'label'  => __( 'Required Field', 'so-widgets-bundle' ),
						'fields' => array(
							'required'        => array(
								'type'        => 'checkbox',
								'label'       => __( 'Required field', 'so-widgets-bundle' ),
								'description' => __( 'Is this field required?', 'so-widgets-bundle' ),
							),
							'missing_message' => array(
								'type'        => 'text',
								'label'       => __( 'Missing message', 'so-widgets-bundle' ),
								'description' => __( 'Error message to display if this field is missing.', 'so-widgets-bundle' ),
							)
						)
					),

					// This are for select, radio, and checkboxes
					'options'  => array(
						'type'          => 'repeater',
						'label'         => __( 'Options', 'so-widgets-bundle' ),
						'item_name'     => __( 'Option', 'so-widgets-bundle' ),
						'item_label'    => array( 'selector' => "[id*='value']" ),
						'fields'        => array(
							'value' => array(
								'type'  => 'text',
								'label' => __( 'Value', 'so-widgets-bundle' ),
							),
						),

						// These are only required for a few states
						'state_handler' => array(
							'field_type_{$repeater}[select,checkboxes,radio]' => array( 'show' ),
							'_else[field_type_{$repeater}]'                   => array( 'hide' ),
						),
					),
				),
			),

			'spam' => array(
				'type'   => 'section',
				'label'  => __( 'Spam Protection', 'so-widgets-bundle' ),
				'hide'   => true,
				'fields' => array(

					'recaptcha' => array(
						'type'   => 'section',
						'label'  => __( 'reCAPTCHA', 'so-widgets-bundle' ),
						'fields' => array(
							'use_captcha' => array(
								'type'    => 'checkbox',
								'label'   => __( 'Use reCAPTCHA', 'so-widgets-bundle' ),
								'default' => false,
							),
							'site_key'    => array(
								'type'  => 'text',
								'label' => __( 'reCAPTCHA Site Key', 'so-widgets-bundle' ),
							),
							'secret_key'  => array(
								'type'  => 'text',
								'label' => __( 'reCAPTCHA Secret Key', 'so-widgets-bundle' ),
							),
							'theme'       => array(
								'type'    => 'select',
								'label'   => __( 'Theme', 'so-widgets-bundle' ),
								'default' => 'light',
								'options' => array(
									'light' => __( 'Light', 'so-widgets-bundle' ),
									'dark'  => __( 'Dark', 'so-widgets-bundle' ),
								),
							),
							'type'        => array(
								'type'    => 'select',
								'label'   => __( 'Challenge type', 'so-widgets-bundle' ),
								'default' => 'image',
								'options' => array(
									'image' => __( 'Image', 'so-widgets-bundle' ),
									'audio' => __( 'Audio', 'so-widgets-bundle' ),
								),
							),
							'size'        => array(
								'type'    => 'select',
								'label'   => __( 'Size', 'so-widgets-bundle' ),
								'default' => 'normal',
								'options' => array(
									'normal'  => __( 'Normal', 'so-widgets-bundle' ),
									'compact' => __( 'Compact', 'so-widgets-bundle' ),
								),
							),
						)
					),

					'akismet' => array(
						'type'   => 'section',
						'label'  => __( 'Akismet', 'so-widgets-bundle' ),
						'fields' => array(
							'use_akismet' => array(
								'type'    => 'checkbox',
								'label'   => __( 'Use Akismet filtering', 'so-widgets-bundle' ),
								'default' => true,
							),
							'spam_action' => array(
								'type'        => 'select',
								'label'       => __( 'Spam action', 'so-widgets-bundle' ),
								'options'     => array(
									'error' => __( 'Show error message', 'so-widgets-bundle' ),
									'tag'   => __( 'Tag as spam in subject', 'so-widgets-bundle' ),
								),
								'description' => __( 'How to handle submissions that are identified as spam.', 'so-widgets-bundle' ),
								'default'     => 'error',
							),
						)
					),
				)
			),

			'design' => array(
				'type'   => 'section',
				'label'  => __( 'Design', 'so-widgets-bundle' ),
				'hide'   => true,
				'fields' => array(

					'container' => array(
						'type'   => 'section',
						'label'  => __( 'Container', 'so-widgets-bundle' ),
						'fields' => array(
							'background'   => array(
								'type'    => 'color',
								'label'   => __( 'Background color', 'so-widgets-bundle' ),
								'default' => '#f2f2f2',
							),
							'padding'      => array(
								'type'    => 'measurement',
								'label'   => __( 'Padding', 'so-widgets-bundle' ),
								'default' => '10px',
							),
							'border_color' => array(
								'type'    => 'color',
								'label'   => __( 'Border color', 'so-widgets-bundle' ),
								'default' => '#c0c0c0',
							),
							'border_width' => array(
								'type'    => 'measurement',
								'label'   => __( 'Border width', 'so-widgets-bundle' ),
								'default' => '1px',
							),
							'border_style' => array(
								'type'    => 'select',
								'label'   => __( 'Border style', 'so-widgets-bundle' ),
								'default' => 'solid',
								'options' => array(
									'none'   => __( 'None', 'so-widgets-bundle' ),
									'hidden' => __( 'Hidden', 'so-widgets-bundle' ),
									'dotted' => __( 'Dotted', 'so-widgets-bundle' ),
									'dashed' => __( 'Dashed', 'so-widgets-bundle' ),
									'solid'  => __( 'Solid', 'so-widgets-bundle' ),
									'double' => __( 'Double', 'so-widgets-bundle' ),
									'groove' => __( 'Groove', 'so-widgets-bundle' ),
									'ridge'  => __( 'Ridge', 'so-widgets-bundle' ),
									'inset'  => __( 'Inset', 'so-widgets-bundle' ),
									'outset' => __( 'Outset', 'so-widgets-bundle' ),
								)
							),
						)
					),

					'labels' => array(
						'type'   => 'section',
						'label'  => __( 'Field labels', 'so-widgets-bundle' ),
						'fields' => array(
							'font'     => array(
								'type'    => 'font',
								'label'   => __( 'Font', 'so-widgets-bundle' ),
								'default' => 'default',
							),
							'size'     => array(
								'type'    => 'measurement',
								'label'   => __( 'Font size', 'so-widgets-bundle' ),
								'default' => 'default',
							),
							'color'    => array(
								'type'    => 'color',
								'label'   => __( 'Color', 'so-widgets-bundle' ),
								'default' => 'default',
							),
							'position' => array(
								'type'    => 'select',
								'label'   => __( 'Position', 'so-widgets-bundle' ),
								'default' => 'above',
								'options' => array(
									'above'  => __( 'Above', 'so-widgets-bundle' ),
									'below'  => __( 'Below', 'so-widgets-bundle' ),
									'left'   => __( 'Left', 'so-widgets-bundle' ),
									'right'  => __( 'Right', 'so-widgets-bundle' ),
									'inside' => __( 'Inside', 'so-widgets-bundle' ),
								),
							),
							'width'    => array(
								'type'    => 'measurement',
								'label'   => __( 'Width', 'so-widgets-bundle' ),
								'default' => '',
							),
							'align'    => array(
								'type'    => 'select',
								'label'   => __( 'Align', 'so-widgets-bundle' ),
								'default' => 'left',
								'options' => array(
									'left'    => __( 'Left', 'so-widgets-bundle' ),
									'right'   => __( 'Right', 'so-widgets-bundle' ),
									'center'  => __( 'Center', 'so-widgets-bundle' ),
									'justify' => __( 'Justify', 'so-widgets-bundle' ),
								)
							),
						),
					),

					'fields' => array(
						'type'   => 'section',
						'label'  => __( 'Fields', 'so-widgets-bundle' ),
						'fields' => array(
							'font'          => array(
								'type'    => 'font',
								'label'   => __( 'Font', 'so-widgets-bundle' ),
								'default' => 'default',
							),
							'font_size'     => array(
								'type'  => 'measurement',
								'label' => __( 'Font Size', 'so-widgets-bundle' )
							),
							'color'         => array(
								'type'  => 'color',
								'label' => __( 'Text Color', 'so-widgets-bundle' ),
							),
							'margin'        => array(
								'type'  => 'measurement',
								'label' => __( 'Margin', 'so-widgets-bundle' )
							),
							'padding'       => array(
								'type'  => 'measurement',
								'label' => __( 'Padding', 'so-widgets-bundle' )
							),
							'height'        => array(
								'type'  => 'measurement',
								'label' => __( 'Height', 'so-widgets-bundle' )
							),
							'background'    => array(
								'type'  => 'color',
								'label' => __( 'Background', 'so-widgets-bundle' ),
							),
							'border_color'  => array(
								'type'    => 'color',
								'label'   => __( 'Border color', 'so-widgets-bundle' ),
								'default' => '#c0c0c0',
							),
							'border_width'  => array(
								'type'    => 'measurement',
								'label'   => __( 'Border width', 'so-widgets-bundle' ),
								'default' => '1px',
							),
							'border_style'  => array(
								'type'    => 'select',
								'label'   => __( ' Border style', 'so-widgets-bundle' ),
								'default' => 'solid',
								'options' => array(
									'none'   => __( 'None', 'so-widgets-bundle' ),
									'hidden' => __( 'Hidden', 'so-widgets-bundle' ),
									'dotted' => __( 'Dotted', 'so-widgets-bundle' ),
									'dashed' => __( 'Dashed', 'so-widgets-bundle' ),
									'solid'  => __( 'Solid', 'so-widgets-bundle' ),
									'double' => __( 'Double', 'so-widgets-bundle' ),
									'groove' => __( 'Groove', 'so-widgets-bundle' ),
									'ridge'  => __( 'Ridge', 'so-widgets-bundle' ),
									'inset'  => __( 'Inset', 'so-widgets-bundle' ),
									'outset' => __( 'Outset', 'so-widgets-bundle' ),
								)
							),
							'border_radius' => array(
								'type'    => 'slider',
								'label'   => __( 'Border rounding', 'so-widgets-bundle' ),
								'default' => 0,
								'max'     => 50,
								'min'     => 0
							),
						)
					),

					'descriptions' => array(
						'type'   => 'section',
						'label'  => __( 'Field descriptions', 'so-widgets-bundle' ),
						'fields' => array(
							'size'  => array(
								'type'    => 'measurement',
								'label'   => __( 'Size', 'so-widgets-bundle' ),
								'default' => '0.9em',
							),
							'color' => array(
								'type'    => 'color',
								'label'   => __( 'Color', 'so-widgets-bundle' ),
								'default' => '#999999',
							),
							'style' => array(
								'type'    => 'select',
								'label'   => __( 'Style', 'so-widgets-bundle' ),
								'default' => 'italic',
								'options' => array(
									'italic' => __( 'Italic', 'so-widgets-bundle' ),
									'normal' => __( 'Normal', 'so-widgets-bundle' ),
								)
							),
						)
					),

					'errors' => array(
						'type'   => 'section',
						'label'  => __( 'Error messages', 'so-widgets-bundle' ),
						'fields' => array(
							'background'   => array(
								'type'    => 'color',
								'label'   => __( 'Error background color', 'so-widgets-bundle' ),
								'default' => '#fce4e5',
							),
							'border_color' => array(
								'type'    => 'color',
								'label'   => __( 'Error border color', 'so-widgets-bundle' ),
								'default' => '#ec666a',
							),
							'text_color'   => array(
								'type'    => 'color',
								'label'   => __( 'Error text color', 'so-widgets-bundle' ),
								'default' => '#ec666a',
							),
							'padding'      => array(
								'type'    => 'measurement',
								'label'   => __( 'Error padding', 'so-widgets-bundle' ),
								'default' => '5px',
							),
							'margin'       => array(
								'type'    => 'measurement',
								'label'   => __( 'Error margin', 'so-widgets-bundle' ),
								'default' => '10px',
							),
						)
					),

					'submit' => array(
						'type'   => 'section',
						'label'  => __( 'Submit button', 'so-widgets-bundle' ),
						'fields' => array(
							'styled' => array(
								'type'        => 'checkbox',
								'label'       => __( 'Style submit button', 'so-widgets-bundle' ),
								'description' => __( 'Style the button or leave it with default theme styling.', 'so-widgets-bundle' ),
								'default'     => true,
							),

							'background_color'    => array(
								'type'    => 'color',
								'label'   => __( 'Background color', 'so-widgets-bundle' ),
								'default' => '#eeeeee',
							),
							'background_gradient' => array(
								'type'    => 'slider',
								'label'   => __( 'Gradient intensity', 'so-widgets-bundle' ),
								'default' => 10,
							),
							'border_color'        => array(
								'type'    => 'color',
								'label'   => __( 'Border color', 'so-widgets-bundle' ),
								'default' => '#989a9c',
							),
							'border_style'        => array(
								'type'    => 'select',
								'label'   => __( 'Border style', 'so-widgets-bundle' ),
								'default' => 'solid',
								'options' => array(
									'none'   => __( 'None', 'so-widgets-bundle' ),
									'solid'  => __( 'Solid', 'so-widgets-bundle' ),
									'dotted' => __( 'Dotted', 'so-widgets-bundle' ),
									'dashed' => __( 'Dashed', 'so-widgets-bundle' ),
								)
							),
							'border_width'        => array(
								'type'    => 'measurement',
								'label'   => __( 'Border width', 'so-widgets-bundle' ),
								'default' => '1px',
							),
							'border_radius'       => array(
								'type'    => 'slider',
								'label'   => __( 'Border rounding', 'so-widgets-bundle' ),
								'default' => 3,
								'max'     => 50,
								'min'     => 0
							),
							'text_color'          => array(
								'type'    => 'color',
								'label'   => __( 'Text color', 'so-widgets-bundle' ),
								'default' => '#5a5a5a',
							),
							'font_size'           => array(
								'type'    => 'measurement',
								'label'   => __( 'Font size', 'so-widgets-bundle' ),
								'default' => 'default',
							),
							'weight'              => array(
								'type'    => 'select',
								'label'   => __( 'Font weight', 'so-widgets-bundle' ),
								'default' => '500',
								'options' => array(
									'normal' => __( 'Normal', 'so-widgets-bundle' ),
									'500'    => __( 'Semi-bold', 'so-widgets-bundle' ),
									'bold'   => __( 'Bold', 'so-widgets-bundle' ),
								)
							),
							'padding'             => array(
								'type'    => 'measurement',
								'label'   => __( 'Padding', 'so-widgets-bundle' ),
								'default' => '10px',
							),
							'inset_highlight'     => array(
								'type'        => 'slider',
								'label'       => __( 'Inset highlight', 'so-widgets-bundle' ),
								'description' => __( 'The white highlight at the bottom of the button', 'so-widgets-bundle' ),
								'default'     => 50,
								'max'         => 100,
								'min'         => 0
							),
						)
					),

					'focus' => array(
						'type'   => 'section',
						'label'  => __( 'Input focus', 'so-widgets-bundle' ),
						'fields' => array(
							'style' => array(
								'type'    => 'select',
								'label'   => __( 'Style', 'so-widgets-bundle' ),
								'default' => 'solid',
								'options' => array(
									'dotted' => __( 'Dotted', 'so-widgets-bundle' ),
									'dashed' => __( 'Dashed', 'so-widgets-bundle' ),
									'solid'  => __( 'Solid', 'so-widgets-bundle' ),
									'double' => __( 'Double', 'so-widgets-bundle' ),
									'groove' => __( 'Groove', 'so-widgets-bundle' ),
									'ridge'  => __( 'Ridge', 'so-widgets-bundle' ),
									'inset'  => __( 'Inset', 'so-widgets-bundle' ),
									'outset' => __( 'Outset', 'so-widgets-bundle' ),
									'none'   => __( 'None', 'so-widgets-bundle' ),
									'hidden' => __( 'Hidden', 'so-widgets-bundle' ),
								)
							),
							'color' => array(
								'type'    => 'color',
								'label'   => __( 'Color', 'so-widgets-bundle' ),
								'default' => 'default',
							),
							'width' => array(
								'type'    => 'measurement',
								'label'   => __( 'Width', 'so-widgets-bundle' ),
								'default' => '1px',
							),
						),
					),
				),
			),
		);
	}

	function get_form_teaser() {
		if ( ! $this->display_siteorigin_premium_teaser() ) {
			return false;
		}

		$url = add_query_arg( array(
			'featured_addon'  => 'plugin/contact-form-fields',
			'featured_plugin' => 'widgets-bundle'
		), 'https://siteorigin.com/downloads/premium/' );

		return sprintf(
			__( 'Get more form fields for the Contact Form Widget in %s', 'so-widgets-bundle' ),
			'<a href="' . esc_url( $url ) . '" target="_blank">' . __( 'SiteOrigin Premium', 'so-widgets-bundle' ) . '</a>'
		);
	}

	function sanitize_multiple_emails( $value ) {
		$values = explode( ',', $value );
		foreach ( $values as $i => $email ) {
			$values[ $i ] = sanitize_email( $email );
		}

		return implode( ',', $values );
	}

	function modify_instance( $instance ) {
		// Use this to set up an initial version of the
		if ( empty( $instance['settings']['to'] ) ) {
			$current_user               = wp_get_current_user();
			$instance['settings']['to'] = $current_user->user_email;
		}
		if ( empty( $instance['fields'] ) ) {
			$instance['fields'] = array(
				array(
					'type'     => 'name',
					'label'    => __( 'Your Name', 'so-widgets-bundle' ),
					'required' => array(
						'required'        => true,
						'missing_message' => __( 'Please enter your name', 'so-widgets-bundle' ),
					),
				),
				array(
					'type'     => 'email',
					'label'    => __( 'Your Email', 'so-widgets-bundle' ),
					'required' => array(
						'required'        => true,
						'missing_message' => __( 'Please enter a valid email address', 'so-widgets-bundle' ),
					),
				),
				array(
					'type'     => 'subject',
					'label'    => __( 'Subject', 'so-widgets-bundle' ),
					'required' => array(
						'required'        => true,
						'missing_message' => __( 'Please enter a subject', 'so-widgets-bundle' ),
					),
				),
				array(
					'type'     => 'textarea',
					'label'    => __( 'Message', 'so-widgets-bundle' ),
					'required' => array(
						'required'        => true,
						'missing_message' => __( 'Please write something', 'so-widgets-bundle' ),
					),
				),
			);
		}

		return $instance;
	}

	function get_template_variables( $instance, $args ) {
		$vars = array();

		unset( $instance['title'] );
		unset( $instance['display_title'] );
		unset( $instance['design'] );
		unset( $instance['panels_info'] );
		unset( $instance['_sow_form_id'] );

		$vars['instance_hash'] = md5( serialize( $instance ) );

		return $vars;
	}

	function get_less_variables( $instance ) {
		if ( empty( $instance['design']['labels']['font'] ) ) {
			$instance['design']['labels'] = array( 'font' => '' );
		}
		$label_font = siteorigin_widget_get_font( $instance['design']['labels']['font'] );
		$field_font = siteorigin_widget_get_font( $instance['design']['fields']['font'] );

		$label_position = $instance['design']['labels']['position'];
		if ( $label_position != 'left' && $label_position != 'right' ) {
			$label_position = 'default';
		}

		$vars = array(
			// All the container variables.
			'container_background'       => $instance['design']['container']['background'],
			'container_padding'          => $instance['design']['container']['padding'],
			'container_border_color'     => $instance['design']['container']['border_color'],
			'container_border_width'     => $instance['design']['container']['border_width'],
			'container_border_style'     => $instance['design']['container']['border_style'],

			// Field labels
			'label_font_family'          => $label_font['family'],
			'label_font_weight'          => ! empty( $label_font['weight'] ) ? $label_font['weight'] : '',
			'label_font_size'            => $instance['design']['labels']['size'],
			'label_font_color'           => $instance['design']['labels']['color'],
			'label_position'             => $label_position,
			'label_width'                => $instance['design']['labels']['width'],
			'label_align'                => $instance['design']['labels']['align'],

			// Fields
			'field_font_family'          => $field_font['family'],
			'field_font_weight'          => ! empty( $field_font['weight'] ) ? $field_font['weight'] : '',
			'field_font_size'            => $instance['design']['fields']['font_size'],
			'field_font_color'           => $instance['design']['fields']['color'],
			'field_margin'               => $instance['design']['fields']['margin'],
			'field_padding'              => $instance['design']['fields']['padding'],
			'field_height'               => $instance['design']['fields']['height'],
			'field_background'           => $instance['design']['fields']['background'],
			'field_border_color'         => $instance['design']['fields']['border_color'],
			'field_border_width'         => $instance['design']['fields']['border_width'],
			'field_border_style'         => $instance['design']['fields']['border_style'],
			'field_border_radius'        => $instance['design']['fields']['border_radius'] . 'px',

			// Field descriptions
			'description_font_size'      => $instance['design']['descriptions']['size'],
			'description_font_color'     => $instance['design']['descriptions']['color'],
			'description_font_style'     => $instance['design']['descriptions']['style'],

			// The error message styles
			'error_background'           => $instance['design']['errors']['background'],
			'error_border'               => $instance['design']['errors']['border_color'],
			'error_text'                 => $instance['design']['errors']['text_color'],
			'error_padding'              => $instance['design']['errors']['padding'],
			'error_margin'               => $instance['design']['errors']['margin'],

			// The submit button
			'submit_background_color'    => $instance['design']['submit']['background_color'],
			'submit_background_gradient' => $instance['design']['submit']['background_gradient'] . '%',
			'submit_border_color'        => $instance['design']['submit']['border_color'],
			'submit_border_style'        => $instance['design']['submit']['border_style'],
			'submit_border_width'        => $instance['design']['submit']['border_width'],
			'submit_border_radius'       => $instance['design']['submit']['border_radius'] . 'px',
			'submit_text_color'          => $instance['design']['submit']['text_color'],
			'submit_font_size'           => $instance['design']['submit']['font_size'],
			'submit_weight'              => $instance['design']['submit']['weight'],
			'submit_padding'             => $instance['design']['submit']['padding'],
			'submit_inset_highlight'     => $instance['design']['submit']['inset_highlight'] . '%',

			// Input focus styles
			'outline_style'              => $instance['design']['focus']['style'],
			'outline_color'              => $instance['design']['focus']['color'],
			'outline_width'              => $instance['design']['focus']['width'],
		);

		return $vars;
	}

	function get_google_font_fields( $instance ) {
		return array(
			$instance['design']['labels']['font'],
			$instance['design']['fields']['font'],
		);
	}

	static function name_from_label( $label, & $ids ) {
		$it = 0;

		$label = str_replace( ' ', '-', strtolower( $label ) );
		$label = sanitize_html_class( $label );
		do {
			$id = $label . ( $it > 0 ? '-' . $it : '' );
			$it ++;
		} while ( ! empty( $ids[ $id ] ) );
		$ids[ $id ] = true;

		return $id;
	}

	/**
	 * Render the form fields
	 *
	 * @param $fields
	 * @param array $errors
	 * @param $instance
	 */
	function render_form_fields( $fields, $errors = array(), $instance ) {

		$field_ids      = array();
		$label_position = $instance['design']['labels']['position'];

		$indicate_required_fields = $instance['settings']['required_field_indicator'];

		if ( ! empty( $indicate_required_fields ) ) {
			?>
            <p><em><?php echo esc_html( $instance['settings']['required_field_indicator_message'] ) ?></em></p>
			<?php
		}

		foreach ( $fields as $i => $field ) {
			if ( empty( $field['type'] ) ) {
				continue;
			}
			// Using `$instance['_sow_form_id']` to uniquely identify contact form fields across widgets.
			// I.e. if there are many contact form widgets on a page this will prevent field name conflicts.
			$field_name = $this->name_from_label( ! empty( $field['label'] ) ? $field['label'] : $i, $field_ids ) . '-' . $instance['_sow_form_id'];
			$field_id   = 'sow-contact-form-field-' . $field_name;

			$value = '';
			if ( ! empty( $_POST[ $field_name ] ) ) {
				$value = stripslashes_deep( $_POST[ $field_name ] );
			}

			?>
            <div class="sow-form-field sow-form-field-<?php echo sanitize_html_class( $field['type'] ) ?>"><?php

			$label = $field['label'];
			if ( $indicate_required_fields && ! empty( $field['required']['required'] ) ) {
				$label .= '*';
			}
			$is_text_input_field = ( $field['type'] != 'select' && $field['type'] != 'radio' && $field['type'] != 'checkboxes' );
			// label should be rendered before the field, then CSS will do the exact positioning.
			$render_label_before_field = ( $label_position != 'below' && $label_position != 'inside' ) || ( $label_position == 'inside' && ! $is_text_input_field );
			if ( empty( $label_position ) || $render_label_before_field ) {
				$this->render_form_label( $field_id, $label, $label_position );
			}

			$show_placeholder = $label_position == 'inside';

			if ( ! empty( $errors[ $field_name ] ) ) {
				?>
                <div class="sow-error">
					<?php echo wp_kses_post( $errors[ $field_name ] ) ?>
                </div>
				<?php
			}
			?><span class="sow-field-container"><?php
			$class_name = empty( $field['type'] ) ? '' : 'SiteOrigin_Widget_ContactForm_Field_' . ucwords( $field['type'] );
			// This does autoloading if required.
			if ( class_exists( $class_name ) ) {
				/**
				 * @var $contact_field SiteOrigin_Widget_ContactForm_Field_Base
				 */
				$field_input_options = array(
					'field'            => $field,
					'field_id'         => $field_id,
					'field_name'       => $field_name,
					'value'            => $value,
					'show_placeholder' => $show_placeholder,
					'label'            => $label,
				);
				$contact_field       = new $class_name( $field_input_options );
				$contact_field->render();
			} else {
				echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '"  value="' . esc_attr( $value ) . '"  class="sow-text-field" ' . ( $show_placeholder ? 'placeholder="' . esc_attr( $label ) . '"' : '' ) . '/>';
			}
			?></span><?php

			if ( ! empty( $label_position ) && $label_position == 'below' ) {
				$this->render_form_label( $field_id, $label, $instance );
			}

			if ( ! empty( $field['description'] ) ) {
				?>
                <div class="sow-form-field-description">
					<?php echo wp_kses_post( $field['description'] ) ?>
                </div>
				<?php
			}

			?></div><?php
		}
	}

	function render_form_label( $field_id, $label, $position ) {
		if ( ! empty( $label ) ) {
			$label_class = '';
			if ( ! empty( $position ) ) {
				$label_class = ' class="sow-form-field-label-' . $position . '"';
			}
			?><label<?php if ( ! empty( $label_class ) ) {
				echo $label_class;
			} ?> for="<?php echo esc_attr( $field_id ) ?>"><strong><?php echo esc_html( $label ) ?></strong></label>
			<?php
		}
	}

	/**
	 * Ajax action handler to send the form
	 */
	function contact_form_action( $instance, $storage_hash ) {
		if ( empty( $_POST['instance_hash'] ) || $_POST['instance_hash'] != $storage_hash ) {
			return false;
		}
		if ( empty( $instance['fields'] ) ) {
			array(
				'status' => null,
			);
		}

		// Make sure that this action only runs once per instance
		static $send_cache = array();
		$send_cache_hash = md5( serialize( $instance ) . '::' . $storage_hash );
		if ( isset( $send_cache[ $send_cache_hash ] ) ) {
			return $send_cache[ $send_cache_hash ];
		}

		$errors       = array();
		$email_fields = array();
		$post_vars    = stripslashes_deep( $_POST );

		$field_ids = array();
		foreach ( $instance['fields'] as $i => $field ) {
			if ( empty( $field['type'] ) ) {
				continue;
			}
			$field_name = $this->name_from_label( ! empty( $field['label'] ) ? $field['label'] : $i, $field_ids ) . '-' . $instance['_sow_form_id'];
			$value      = ! empty( $post_vars[ $field_name ] ) ? $post_vars[ $field_name ] : '';

			if ( empty( $value ) ) {
				if ( $field['required']['required'] ) {
					// Add in the default subject
					if ( $field['type'] == 'subject' && ! empty( $instance['settings']['default_subject'] ) ) {
						$value = $instance['settings']['default_subject'];
					} else {
						$errors[ $field_name ] = ! empty( $field['required']['missing_message'] ) ? $field['required']['missing_message'] : __( 'Required field', 'so-widgets-bundle' );
						continue;
					}
				} else {
					continue; // Don't process an empty field that's not required
				}
			}

			// Type Validation
			switch ( $field['type'] ) {
				case 'email':
					if ( $value != sanitize_email( $value ) ) {
						$errors[ $field_name ] = __( 'Invalid email address.', 'so-widgets-bundle' );
					}
					$email_fields[ $field['type'] ] = $value;

					break;

				case 'name':
				case 'subject':
					$email_fields[ $field['type'] ] = $value;

					break;

				case 'checkboxes':
					$email_fields['message'][] = array(
						'label' => $field['label'],
						'value' => implode( ', ', $value ),
					);
					break;

				default:
					$email_fields['message'][] = array(
						'label' => $field['label'],
						'value' => $value,
					);
					break;
			}
		}

		// Add in the default subject if no subject field is defined in the form at all
		if ( ! isset( $email_fields['subject'] ) && ! empty( $instance['settings']['default_subject'] ) ) {
			$email_fields['subject'] = $instance['settings']['default_subject'];
		}

		// Add in the default subject prefix
		if ( ! empty( $email_fields['subject'] ) && ! empty( $instance['settings']['subject_prefix'] ) ) {
			$email_fields['subject'] = $instance['settings']['subject_prefix'] . ' ' . $email_fields['subject'];
		}

		// Now we do some email message validation
		if ( empty( $errors ) ) {
			$email_errors = $this->validate_mail( $email_fields );
			// Missing subject input and no default subject set. Revert to using a generic default 'SiteName Contact Form'
			if ( ! isset( $email_fields['subject'] ) && ! empty( $email_errors['subject'] ) ) {
				unset( $email_errors['subject'] );
				$email_fields['subject'] = get_bloginfo() . ' ' . __( 'Contact Form', 'siteorigin-widgets' );
			}
			if ( ! empty( $email_errors ) ) {
				$errors['_general'] = $email_errors;
			}
		}

		// And if we get this far, do some spam filtering and Captcha checking
		if ( empty( $errors ) ) {
			$spam_errors = $this->spam_check( $post_vars, $email_fields, $instance );
			if ( ! empty( $spam_errors ) ) {
				// Now we can decide how we want to handle this spam status
				if ( ! empty( $spam_errors['akismet'] ) && $instance['spam']['akismet']['spam_action'] == 'tag' ) {
					unset( $spam_errors['akismet'] );
					$email_fields['subject'] = '[spam] ' . $email_fields['subject'];
				}
			}

			if ( ! empty( $spam_errors ) ) {
				$errors['_general'] = $spam_errors;
			}
		}

		if ( empty( $errors ) ) {
			// We can send the email
			$success = $this->send_mail( $email_fields, $instance );

			if ( is_wp_error( $success ) ) {
				$errors['_general']['send'] = $success->get_error_message();
			} else if ( ! $success ) {
				$errors['_general']['send'] = __( 'Error sending email, please try again later.', 'so-widgets-bundle' );
			}
		}

		$send_cache[ $send_cache_hash ] = array(
			'status' => empty( $errors ) ? 'success' : 'fail',
			'errors' => $errors
		);

		return $send_cache[ $send_cache_hash ];
	}

	/**
	 * Validate fields of an email message
	 */
	function validate_mail( $email_fields ) {
		$errors = array();
		if ( empty( $email_fields['email'] ) ) {
			$errors['email'] = __( 'A valid email is required', 'so-widgets-bundle' );
		} elseif ( function_exists( 'filter_var' ) && ! filter_var( $email_fields['email'], FILTER_VALIDATE_EMAIL ) ) {
			$errors['email'] = __( 'The email address is invalid', 'so-widgets-bundle' );
		}

		if ( empty( $email_fields['subject'] ) ) {
			$errors['subject'] = __( 'Missing subject', 'so-widgets-bundle' );
		}

		return $errors;
	}

	/**
	 * Check the email for spam
	 *
	 * @param $email_fields
	 * @param $instance
	 *
	 * @return array
	 */
	function spam_check( $post_vars, $email_fields, $instance ) {
		$errors = array();

		$recaptcha_config = $instance['spam']['recaptcha'];
		$use_recaptcha    = $recaptcha_config['use_captcha'] && ! empty( $recaptcha_config['site_key'] ) && ! empty( $recaptcha_config['secret_key'] );
		if ( $use_recaptcha ) {
			$result = wp_remote_post(
				'https://www.google.com/recaptcha/api/siteverify',
				array(
					'body' => array(
						'secret'   => $instance['spam']['recaptcha']['secret_key'],
						'response' => $post_vars['g-recaptcha-response'],
						'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null,
					)
				)
			);

			if ( ! is_wp_error( $result ) && ! empty( $result['body'] ) ) {
				$result = json_decode( $result['body'], true );
				if ( isset( $result['success'] ) && ! $result['success'] ) {
					$errors['recaptcha'] = __( 'Error validating your Captcha response.', 'so-widgets-bundle' );
				}
			}
		}

		if ( $instance['spam']['akismet']['use_akismet'] && class_exists( 'Akismet' ) ) {
			$comment = array();

			$message_text = array();
			foreach ( $email_fields['message'] as $m ) {
				$message_text[] = $m['value'];
			}

			$comment['comment_text']         = $email_fields['subject'] . "\n\n" . implode( "\n\n", $message_text );
			$comment['comment_author']       = ! empty( $email_fields['name'] ) ? $email_fields['name'] : '';
			$comment['comment_author_email'] = $email_fields['email'];
			$comment['comment_post_ID']      = get_the_ID();

			$comment['comment_type'] = 'contact-form';

			$comment['user_ip']      = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null;
			$comment['user_agent']   = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : null;
			$comment['referrer']     = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : null;
			$comment['blog']         = get_option( 'home' );
			$comment['blog_lang']    = get_locale();
			$comment['blog_charset'] = get_option( 'blog_charset' );

			// Pretend to check with Akismet
			$response = Akismet::http_post( Akismet::build_query( $comment ), 'comment-check' );
			$is_spam  = ! empty( $response[1] ) && $response[1] == 'true';

			if ( $is_spam ) {
				$errors['akismet'] = __( 'Unfortunately our system identified your message as spam.', 'so-widgets-bundle' );
			}
		}

		return $errors;
	}

	function send_mail( $email_fields, $instance ) {
		$body = '<strong>From:</strong> <a href="mailto:' . sanitize_email( $email_fields['email'] ) . '">' . esc_html( $email_fields['name'] ) . '</a> &#60;' . sanitize_email( $email_fields['email'] ) . "&#62; \n\n";
		foreach ( $email_fields['message'] as $m ) {
			$body .= '<strong>' . $m['label'] . ':</strong>';
			$body .= "\n";
			$body .= htmlspecialchars( $m['value'] );
			$body .= "\n\n";
		}
		$body = wpautop( trim( $body ) );

		if ( $instance['settings']['to'] == 'ibrossiter@gmail.com' || $instance['settings']['to'] == 'test@example.com' || empty( $instance['settings']['to'] ) ) {
			// Replace default and empty email address.
			// Also replaces the email address that comes from the prebuilt layout directory
			$instance['settings']['to'] = get_option( 'admin_email' );
		}

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $this->sanitize_header( $email_fields['name'] ) . ' <' . sanitize_email( $email_fields['email'] ) . '>',
		);

		// Check if this is a duplicated send
		$hash       = md5( json_encode( array(
			'to'      => $instance['settings']['to'],
			'subject' => $email_fields['subject'],
			'body'    => $body,
			'headers' => $headers
		) ) );
		$hash_check = get_option( 'so_contact_hashes', array() );
		// Remove expired hashes
		foreach ( $hash_check as $h => $t ) {
			if ( $t < time() - 5 * 60 ) {
				unset( $hash_check[ $h ] );
			}
		}

		if ( isset( $hash_check[ $hash ] ) ) {
			// Store the version with the expired hashes removed
			update_option( 'so_contact_hashes', $hash_check, true );

			// This message has already been sent successfully
			return true;
		}

		$mail_success = wp_mail( $instance['settings']['to'], $email_fields['subject'], $body, $headers );
		if ( $mail_success ) {
			$hash_check[ $hash ] = time();
			update_option( 'so_contact_hashes', $hash_check, true );
		}

		return $mail_success;
	}

	/**
	 * Sanitize a value for an email header.
	 *
	 * From Pear Mail https://pear.php.net/package/Mail (BSD Style license - https://pear.php.net/copyright.php).
	 *
	 * @param $value
	 *
	 * @return mixed
	 */
	static function sanitize_header( $value ) {
		return preg_replace( '=((<CR>|<LF>|0x0A/%0A|0x0D/%0D|\\n|\\r)\S).*=i', null, $value );
	}

}

siteorigin_widget_register( 'sow-contact-form', __FILE__, 'SiteOrigin_Widgets_ContactForm_Widget' );

// Tell the autoloader where to look for contactform field classes.
function contactform_fields_class_paths( $class_paths ) {
	$loader = SiteOrigin_Widget_Field_Class_Loader::single();

	$loader->add_class_prefixes(
		apply_filters( 'siteorigin_widgets_contact_form_field_class_prefixes', array(
			'SiteOrigin_Widget_ContactForm_Field_'
		) ),
		'contact-form'
	);

	$loader->add_class_paths(
		apply_filters( 'siteorigin_widgets_contact_form_field_class_paths', array(
			plugin_dir_path( __FILE__ ) . 'fields/'
		) ),
		'contact-form'
	);

	return $class_paths;
}

add_filter( 'init', 'contactform_fields_class_paths' );

<?php
/*
Widget Name: Contact Form
Description: A lightweight contact form builder.
Author: SiteOrigin
Author URI: https://siteorigin.com
Documentation: https://siteorigin.com/widgets-bundle/contact-form-widget/
*/

class SiteOrigin_Widgets_ContactForm_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'sow-contact-form',
			__( 'SiteOrigin Contact Form', 'so-widgets-bundle' ),
			array(
				'description' => __( 'A lightweight contact form builder.', 'so-widgets-bundle' ),
				'help' => 'https://siteorigin.com/widgets-bundle/contact-form-widget/',
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	/**
	 * Initialize the contact form widget
	 */
	public function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'sow-contact',
					plugin_dir_url( __FILE__ ) . 'js/contact' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' ),
					SOW_BUNDLE_VERSION,
				),
			)
		);
		add_filter( 'siteorigin_widgets_sanitize_field_multiple_emails', array( $this, 'sanitize_multiple_emails' ) );
		add_action( 'siteorigin_widgets_enqueue_frontend_scripts_sow-contact-form', array( $this, 'enqueue_widget_scripts' ) );
	}

	public function enqueue_widget_scripts() {
		$global_settings = $this->get_global_settings();
		wp_localize_script(
			'sow-contact',
			'sowContact',
			array(
				'scrollto' => ! empty( $global_settings['scrollto'] ),
				'scrollto_offset' => (int) apply_filters( 'siteorigin_widgets_contact_scrollto_offset', 0 ),
			)
		);
	}

	public function get_widget_form() {
		$form_options = array(
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
						'description' => __( 'Where contact emails will be delivered to. You can send to multiple emails by separating the emails with a comma (,)', 'so-widgets-bundle' ),
						'sanitize'    => 'multiple_emails',
					),
					'from'                               => array(
						'type'        => 'text',
						'label'       => __( 'From email address', 'so-widgets-bundle' ),
						'description' => __( 'It will appear as if emails are sent from this address. Ideally, this should be in the same domain as this server to avoid spam filters.', 'so-widgets-bundle' ),
						'sanitize'    => 'email',
					),
					'default_subject'                  => array(
						'type'        => 'text',
						'label'       => __( 'Default subject', 'so-widgets-bundle' ),
						'description' => __( "Subject to use when there isn't one supplied by the user. If you make use of this option it won't be possible to set the Subject field as required because the default subject will be used as a fallback.", 'so-widgets-bundle' ),
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
						'default'     => __( "Thanks for contacting us. We'll get back to you shortly.", 'so-widgets-bundle' ),
					),
					'submit_text'                      => array(
						'type'    => 'text',
						'label'   => __( 'Submit button text', 'so-widgets-bundle' ),
						'default' => __( 'Contact Us', 'so-widgets-bundle' ),
					),
					'submit_id' => array(
						'type' => 'text',
						'label' => __( 'Button ID', 'so-widgets-bundle' ),
						'description' => __( 'An ID attribute allows you to target this button in JavaScript.', 'so-widgets-bundle' ),
					),
					'on_click' => array(
						'type'        => 'text',
						'label'       => __( 'Onclick', 'so-widgets-bundle' ),
						'description' => __( 'Run this JavaScript when the button is clicked. Ideal for tracking.', 'so-widgets-bundle' ),
					),
					'required_field_indicator'         => array(
						'type'          => 'checkbox',
						'label'         => __( 'Indicate required fields with asterisk (*)', 'so-widgets-bundle' ),
						'state_emitter' => array(
							'callback' => 'conditional',
							'args'     => array(
								'required_fields[show]: val',
								'required_fields[hide]: ! val',
							),
						),
					),
					'required_field_indicator_message' => array(
						'type'          => 'text',
						'label'         => __( 'Required field indicator message', 'so-widgets-bundle' ),
						'default'       => __( 'Fields marked with * are required', 'so-widgets-bundle' ),
						'state_handler' => array(
							'required_fields[show]' => array( 'show' ),
							'required_fields[hide]' => array( 'hide' ),
						),
					),
					'log_ip_address' => array(
						'type' => 'checkbox',
						'label' => __( 'Log IP addresses', 'so-widgets-bundle' ),
						'description' => __( 'List in contact emails, the IP address of the form sender.', 'so-widgets-bundle' ),
						'default' => false,
					),
				),
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
						'type'    => 'select',
						'label'   => __( 'Field Type', 'so-widgets-bundle' ),
						'prompt'  => __( 'Select Field Type', 'so-widgets-bundle' ),
						'options' => array(
							'name'       => __( 'Name', 'so-widgets-bundle' ),
							'email'      => __( 'Email', 'so-widgets-bundle' ),
							'tel'        => __( 'Phone Number', 'so-widgets-bundle' ),
							'number'     => __( 'Number', 'so-widgets-bundle' ),
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
						),
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
								'default'     => __( 'Required field', 'so-widgets-bundle' ),
							),
						),
					),

					'multiple_select' => array(
						'type'  => 'checkbox',
						'label' => __( 'Allow multiple selections', 'so-widgets-bundle' ),
						'state_handler' => array(
							'field_type_{$repeater}[select]' => array( 'show' ),
							'_else[field_type_{$repeater}]' => array( 'hide' ),
						),
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
							'default' => array(
								'type'  => 'checkbox',
								'label' => __( 'Enabled', 'so-widgets-bundle' ),
								'state_handler' => array(
									'field_type_{$repeater}[checkboxes]' => array( 'show' ),
									'_else[field_type_{$repeater}]'      => array( 'hide' ),
								),
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
					'honeypot' => array(
						'type'        => 'checkbox',
						'label'       => __( 'Honeypot', 'so-widgets-bundle' ),
						'default'     => true,
						'description' => __( 'Adds a hidden form field that only bots can see. The form will reject the submission if the hidden field is populated.', 'so-widgets-bundle' ),
					),
					'browser_check' => array(
						'type'        => 'checkbox',
						'label'       => __( 'Browser Check', 'so-widgets-bundle' ),
						'default'     => true,
						'description' => __( 'Runs a check on submission that confirms the submission came from a browser. Requires the user to have JavaScript enabled.', 'so-widgets-bundle' ),
					),
					'recaptcha' => array(
						'type'   => 'section',
						'label'  => __( 'reCAPTCHA', 'so-widgets-bundle' ),
						'fields' => array(
							'use_captcha' => array(
								'type'    => 'radio',
								'label'   => __( 'reCAPTCHA', 'so-widgets-bundle' ),
								'default' => false,
								'options' => array(
									''   => __( 'Disabled', 'so-widgets' ),
									'v2' => __( 'v2', 'so-widgets' ),
									'v3' => __( 'v3', 'so-widgets' ),
								),
								'description' => sprintf(
									__( 'Please make sure you register a new reCAPTCHA key %shere%s.', 'so-widgets-bundle' ),
									'<a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="noopener noreferrer">',
									'</a>'
								),
								'state_emitter' => array(
									'callback' => 'select',
									'args' => array( 'recaptcha_version' ),
								),
							),
							'site_key'    => array(
								'type'  => 'text',
								'label' => __( 'reCAPTCHA v2 Site Key', 'so-widgets-bundle' ),
								'state_handler' => array(
									'recaptcha_version[v2]' => array( 'show' ),
									'_else[recaptcha_version]' => array( 'hide' ),
								),
							),
							'secret_key'  => array(
								'type'  => 'text',
								'label' => __( 'reCAPTCHA v2 Secret Key', 'so-widgets-bundle' ),
								'state_handler' => array(
									'recaptcha_version[v2]' => array( 'show' ),
									'_else[recaptcha_version]' => array( 'hide' ),
								),
							),
							'site_key_v3'    => array(
								'type'  => 'text',
								'label' => __( 'reCAPTCHA v3 Site Key', 'so-widgets-bundle' ),
								'state_handler' => array(
									'recaptcha_version[v3]' => array( 'show' ),
									'_else[recaptcha_version]' => array( 'hide' ),
								),
							),
							'secret_key_v3'  => array(
								'type'  => 'text',
								'label' => __( 'reCAPTCHA v3 Secret Key', 'so-widgets-bundle' ),
								'state_handler' => array(
									'recaptcha_version[v3]' => array( 'show' ),
									'_else[recaptcha_version]' => array( 'hide' ),
								),
							),
							'theme'       => array(
								'type'    => 'select',
								'label'   => __( 'Theme', 'so-widgets-bundle' ),
								'default' => 'light',
								'options' => array(
									'light' => __( 'Light', 'so-widgets-bundle' ),
									'dark'  => __( 'Dark', 'so-widgets-bundle' ),
								),
								'state_handler' => array(
									'recaptcha_version[v2]' => array( 'slideDown' ),
									'_else[recaptcha_version]' => array( 'slideUp' ),
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
								'state_handler' => array(
									'recaptcha_version[v2]' => array( 'slideDown' ),
									'_else[recaptcha_version]' => array( 'slideUp' ),
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
								'state_handler' => array(
									'recaptcha_version[v2]' => array( 'slideDown' ),
									'_else[recaptcha_version]' => array( 'slideUp' ),
								),
							),
						),
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
						),
					),
				),
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
								),
							),
						),
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
								),
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
								'label' => __( 'Font size', 'so-widgets-bundle' ),
							),
							'color'         => array(
								'type'  => 'color',
								'label' => __( 'Text color', 'so-widgets-bundle' ),
							),
							'margin'        => array(
								'type'  => 'measurement',
								'label' => __( 'Margin', 'so-widgets-bundle' ),
							),
							'padding'       => array(
								'type'  => 'measurement',
								'label' => __( 'Padding', 'so-widgets-bundle' ),
							),
							'max_width'    => array(
								'type'    => 'measurement',
								'label'   => __( 'Max width', 'so-widgets-bundle' ),
								'default' => '',
							),
							'height'        => array(
								'type'  => 'measurement',
								'label' => __( 'Height', 'so-widgets-bundle' ),
							),
							'height_textarea' => array(
								'type'  => 'measurement',
								'label' => __( 'Text area height', 'so-widgets-bundle' ),
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
								),
							),
							'border_radius' => array(
								'type'    => 'slider',
								'label'   => __( 'Border rounding', 'so-widgets-bundle' ),
								'default' => 0,
								'max'     => 50,
								'min'     => 0,
							),
						),
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
								),
							),
						),
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
						),
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
								),
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
								'min'     => 0,
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
								),
							),
							'padding'             => array(
								'type'    => 'measurement',
								'label'   => __( 'Padding', 'so-widgets-bundle' ),
								'default' => '10px',
							),
							'width' => array(
								'type'    => 'measurement',
								'label'   => __( 'Width', 'so-widgets-bundle' ),
							),
							'align'    => array(
								'type'    => 'select',
								'label'   => __( 'Align', 'so-widgets-bundle' ),
								'default' => 'left',
								'options' => array(
									'left'    => __( 'Left', 'so-widgets-bundle' ),
									'right'   => __( 'Right', 'so-widgets-bundle' ),
									'center'  => __( 'Center', 'so-widgets-bundle' ),
								),
							),
							'inset_highlight'     => array(
								'type'        => 'slider',
								'label'       => __( 'Inset highlight', 'so-widgets-bundle' ),
								'description' => __( 'The white highlight at the bottom of the button', 'so-widgets-bundle' ),
								'default'     => 50,
								'max'         => 100,
								'min'         => 0,
							),
						),
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
								),
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

	if ( function_exists( 'imagecreatetruecolor' ) ) {
			siteorigin_widgets_array_insert(
				$form_options['spam']['fields'],
				'akismet',
				array(
					'simple' => array(
						'type'   => 'section',
						'label'  => __( 'Really Simple CAPTCHA', 'so-widgets-bundle' ),
						'fields' => array(
							'enabled' => array(
								'type'    => 'checkbox',
								'label'   => __( 'Add Really Simple CAPTCHA', 'so-widgets-bundle' ),
								'description' => sprintf(
									__( 'The %sReally Simple CAPTCHA%s plugin is DSGVO compliant.', 'so-widgets-bundle' ),
									'<a href="https://wordpress.org/plugins/really-simple-captcha/" target="_blank">',
									'</a>'
								),
								'default' => false,
								'state_emitter' => array(
									'callback' => 'conditional',
									'args'     => array(
										'really_simple[show]: val',
										'really_simple[hide]: ! val'
									),
								)
							),
							'background'   => array(
								'type'    => 'color',
								'label'   => __( 'Background color', 'so-widgets-bundle' ),
								'default' => '#ffffff',
								'state_handler' => array(
									'really_simple[show]' => array( 'slideDown' ),
									'really_simple[hide]' => array( 'slideUp' ),
								),
							),
							'color'   => array(
								'type'    => 'color',
								'label'   => __( 'Text color', 'so-widgets-bundle' ),
								'default' => '#000000',
								'state_handler' => array(
									'really_simple[show]' => array( 'slideDown' ),
									'really_simple[hide]' => array( 'slideUp' ),
								),
							),
						)
					),
				)
			);
		}

		return $form_options;
	}

	public function sanitize_multiple_emails( $value ) {
		$values = explode( ',', $value );

		foreach ( $values as $i => $email ) {
			$values[ $i ] = sanitize_email( $email );
		}

		return implode( ',', $values );
	}

	public function modify_instance( $instance ) {
		// Use this to set up an initial version of the
		if ( empty( $instance['settings']['to'] ) || $this->is_dev_email( $instance['settings']['to'] ) ) {
			$current_user = wp_get_current_user();
			$instance['settings']['to'] = $current_user->user_email;
		}

		if ( empty( $instance['settings']['from'] ) || $this->is_dev_email( $instance['settings']['from'] ) ) {
			$instance['settings']['from'] = $this->default_from_address();
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

		// Migrate onclick setting to prevent Wordfence flag.
		if (
			! empty( $instance['settings'] ) &&
			! empty( $instance['settings']['onclick'] )
		) {
			$instance['settings']['on_click'] = $instance['settings']['onclick'];
		}

		// If using an older version of reCAPTCHA settings, migrate.
		if (
			! empty( $instance['spam'] ) &&
			! empty( $instance['spam']['recaptcha'] ) &&
			$instance['spam']['recaptcha']['use_captcha'] === true
		) {
			$instance['spam']['recaptcha']['use_captcha'] = 'v2';
		}

		if ( ! isset( $instance['spam']['honeypot'] ) ) {
			$instance['spam']['honeypot'] = false;
			$instance['spam']['browser_check'] = false;
		}

		return $instance;
	}

	public static function is_recaptcha_enabled( $settings, $use_v3 = false ) {
		return ! empty( $settings['use_captcha'] ) &&
			(
				! $use_v3 ||
				$settings['use_captcha'] == 'v3'
			) &&
			(
				// Check for v2
				(
					! $use_v3 &&
					! empty( $settings['site_key'] ) &&
					! empty( $settings['secret_key'] )
				) ||
				// Check for v3
				(
					! empty( $settings['site_key_v3'] ) &&
					! empty( $settings['secret_key_v3'] )
				)
			);
	}

	public function get_template_variables( $instance, $args ) {
		unset( $instance['title'] );
		unset( $instance['display_title'] );
		unset( $instance['design'] );
		unset( $instance['panels_info'] );

		$template_vars = array(
			'onclick' => ! empty( $instance['settings']['on_click'] ) ? $instance['settings']['on_click'] : '',
		);

		$submit_attributes = array();

		if ( ! empty( $instance['spam']['browser_check'] ) ) {
			$submit_attributes['data-js-key'] = $instance['_sow_form_id'];
		}

		// Include '_sow_form_id' in generation of 'instance_hash' to allow multiple instances of the same form on a page.
		$template_vars['instance_hash'] = md5( serialize( $instance ) );
		$template_vars['result'] = $this->contact_form_action( $instance, $template_vars['instance_hash'] );
		unset( $instance['_sow_form_id'] );

		if ( ! empty( $instance['settings']['submit_id'] ) ) {
			$submit_attributes['id'] = $instance['settings']['submit_id'];
		}

		$template_vars['recaptcha'] = self::is_recaptcha_enabled( $instance['spam']['recaptcha'] );

		if ( $template_vars['recaptcha'] ) {
			// reCAPTCHA v3
			if ( self::is_recaptcha_enabled( $instance['spam']['recaptcha'], true ) ) {
				$submit_attributes['data-sitekey'] = $instance['spam']['recaptcha']['site_key_v3'];
				$submit_attributes['data-callback'] = 'soContactFormSubmit';
				$submit_attributes['data-action'] = 'submit';
			} else { // reCAPTCHA v2
				$template_vars['recaptcha_v2'] = array(
					'sitekey' => $instance['spam']['recaptcha']['site_key'],
					'theme'   => $instance['spam']['recaptcha']['theme'],
					'type'    => $instance['spam']['recaptcha']['type'],
					'size'    => $instance['spam']['recaptcha']['size'],
				);
			}
		}
		$template_vars['submit_attributes'] = $submit_attributes;

		if ( ! empty( $instance['spam']['simple'] ) && ! empty( $instance['spam']['simple']['enabled'] ) ) {
			if ( ! class_exists( 'ReallySimpleCaptcha' ) || ! function_exists( 'imagecreatetruecolor' ) ) {
				$template_vars['really_simple_spam'] = 'missing';
			} else {
				$template_vars['really_simple_spam'] = new ReallySimpleCaptcha();

				// Apply the RSC colors.
				if ( ! class_exists( 'SiteOrigin_Widgets_Color_Object' ) ) {
					require plugin_dir_path( SOW_BUNDLE_BASE_FILE ) . 'base/inc/color.php';
				}

				if ( ! empty( $instance['spam']['simple']['background'] ) ) {
					$color = new SiteOrigin_Widgets_Color_Object( $instance['spam']['simple']['background'], 'hex' );
					$template_vars['really_simple_spam']->bg = $color->__get( 'rgb' );
				}

				if ( ! empty( $instance['spam']['simple']['color'] ) ) {
					$color = new SiteOrigin_Widgets_Color_Object( $instance['spam']['simple']['color'], 'hex' );
					$template_vars['really_simple_spam']->fg = $color->__get( 'rgb' );
				}

				// Allow other plugins to adjust Really Simple Captcha settings.
				$template_vars['really_simple_spam'] = apply_filters( 'siteorigin_widgets_contact_really_simple_captcha', $template_vars['really_simple_spam'] );
				$template_vars['really_simple_spam_prefix'] = mt_rand() . $template_vars['instance_hash'];
				$template_vars['really_simple_spam_image'] = $template_vars['really_simple_spam']->generate_image(
					$template_vars['really_simple_spam_prefix'],
					$template_vars['really_simple_spam']->generate_random_word()
				);

				if (
					! empty( $template_vars['result'] ) &&
					! empty( $template_vars['result'] ) &&
					! empty( $template_vars['result']['errors'] ) &&
					! empty( $template_vars['result']['errors']['_general'] ) &&
					! empty( $template_vars['result']['errors']['_general']['simple'] )
				) {
					$template_vars['really_simple_spam_error'] = $template_vars['result']['errors']['_general']['simple'];
					unset( $template_vars['result']['errors'] );
				}
			}
		}

		$template_vars['honeypot'] = ! empty( $instance['spam']['honeypot'] );

		return $template_vars;
	}

	public function get_settings_form() {
		return array(
			'responsive_breakpoint' => array(
				'type'        => 'measurement',
				'label'       => __( 'Responsive Breakpoint', 'so-widgets-bundle' ),
				'default'     => '780px',
				'description' => __( 'This setting controls when the field max width will be disabled. The default value is 780px', 'so-widgets-bundle' ),
			),
			'scrollto' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Scroll top', 'so-widgets-bundle' ),
				'default'     => true,
				'description' => __( 'After submission, scroll the user to the top of the contact form.', 'so-widgets-bundle' ),
			),
		);
	}

	public function get_less_variables( $instance ) {
		if ( empty( $instance['design'] ) ) {
			return;
		}

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
			'label_font_size'            => $instance['design']['labels']['size'],
			'label_font_color'           => $instance['design']['labels']['color'],
			'label_position'             => $label_position,
			'label_width'                => $instance['design']['labels']['width'],
			'label_align'                => $instance['design']['labels']['align'],

			// Fields
			'field_font_family'          => $field_font['family'],
			'field_font_size'            => $instance['design']['fields']['font_size'],
			'field_font_color'           => $instance['design']['fields']['color'],
			'field_margin'               => $instance['design']['fields']['margin'],
			'field_padding'              => $instance['design']['fields']['padding'],
			'field_max_width'            => ! empty( $instance['design']['fields']['max_width'] ) ? $instance['design']['fields']['max_width'] : '',
			'field_height'               => $instance['design']['fields']['height'],
			'field_height_textarea'      => ! empty( $instance['design']['fields']['height_textarea'] ) ? $instance['design']['fields']['height_textarea'] : '',
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
			'submit_width'               => ! empty( $instance['design']['submit']['width'] ) ? $instance['design']['submit']['width'] : '',
			'submit_align'               => ! empty( $instance['design']['submit']['align'] ) ? $instance['design']['submit']['align'] : '',
			'submit_inset_highlight'     => $instance['design']['submit']['inset_highlight'] . '%',

			// Input focus styles
			'outline_style'              => $instance['design']['focus']['style'],
			'outline_color'              => $instance['design']['focus']['color'],
			'outline_width'              => $instance['design']['focus']['width'],
		);

		if ( ! empty( $label_font['weight'] ) ) {
			$vars['label_font_weight'] = $label_font['weight_raw'];
			$lessvars_vars['label_font_style'] = $label_font['style'];
		}

		if ( ! empty( $field_font['weight'] ) ) {
			$vars['field_font_weight'] = $field_font['weight_raw'];
			$lessvars_vars['field_font_style'] = $field_font['style'];
		}

		$global_settings = $this->get_global_settings();

		if ( ! empty( $global_settings['responsive_breakpoint'] ) ) {
			$less_vars['responsive_breakpoint'] = $global_settings['responsive_breakpoint'];
		}

		return $vars;
	}

	public static function name_from_label( $label, & $ids ) {
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
	 * @param array $errors
	 */
	public function render_form_fields( $fields, $errors, $instance ) {
		$field_ids = array();
		$label_position = $instance['design']['labels']['position'];

		$indicate_required_fields = $instance['settings']['required_field_indicator'];

		if ( ! empty( $indicate_required_fields ) ) {
			?>
            <p><em><?php echo esc_html( $instance['settings']['required_field_indicator_message'] ); ?></em></p>
			<?php
		}

		foreach ( $fields as $i => $field ) {
			if ( empty( $field['type'] ) ) {
				continue;
			}
			// Using `$instance['_sow_form_id']` to uniquely identify contact form fields across widgets.
			// I.e. if there are many contact form widgets on a page this will prevent field name conflicts.
			$field_name = $this->name_from_label( ! empty( $field['label'] ) ? $field['label'] : $i, $field_ids ) . '-' . $instance['_sow_form_id'];
			$field_id = 'sow-contact-form-field-' . $field_name;

			$value = '';

			if ( ! empty( $_POST[ $field_name ] ) && wp_verify_nonce( $_POST['_wpnonce'], '_contact_form_submit' ) ) {
				$value = stripslashes_deep( $_POST[ $field_name ] );
			}

			?>
            <div class="sow-form-field sow-form-field-<?php echo sanitize_html_class( $field['type'] ); ?>">
            	<?php

				$label = $field['label'];
				$indicate_as_required = $indicate_required_fields && ! empty( $field['required']['required'] );
				$no_placeholder_support = ( $field['type'] != 'radio' && $field['type'] != 'checkboxes' );
				// label should be rendered before the field, then CSS will do the exact positioning.
				$render_label_before_field = ( $label_position != 'below' && $label_position != 'inside' ) || ( $label_position == 'inside' && ! $no_placeholder_support );

				if ( empty( $label_position ) || $render_label_before_field ) {
					$this->render_form_label( $field_id, $label, $label_position, $indicate_as_required );
				}

				$show_placeholder = $label_position == 'inside';

				if ( $show_placeholder && $indicate_as_required ) {
					$label .= '*';
				}

				if ( is_array( $errors ) && ! empty( $errors[ $field_name ] ) ) {
					?>
	                <div class="sow-error">
						<?php echo wp_kses_post( $errors[ $field_name ] ); ?>
	                </div>
					<?php
				}
				?>
				<span class="sow-field-container">
					<?php
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
						$contact_field = new $class_name( $field_input_options );
						$contact_field->render();
					} else {
						echo '<input type="text" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '"  value="' . esc_attr( $value ) . '"  class="sow-text-field" ' . ( $show_placeholder ? 'placeholder="' . esc_attr( $label ) . '"' : '' ) . '/>';
					}
					?>
				</span>
				<?php

				if ( ! empty( $label_position ) && $label_position == 'below' ) {
					$this->render_form_label( $field_id, $label, $instance, $indicate_as_required );
				}

				if ( ! empty( $field['description'] ) ) {
					?>
	                <div class="sow-form-field-description">
						<?php echo wp_kses_post( $field['description'] ); ?>
	                </div>
					<?php
				}

				?>
			</div>
			<?php
		}
	}

	public function render_form_label( $field_id, $label, $position, $indicate_as_required = false ) {
		if ( ! empty( $label ) ) {
			$label_class = '';

			if ( ! empty( $position ) ) {
				$label_class = ' class="sow-form-field-label-' . $position . '"';
			}
			?><label<?php if ( ! empty( $label_class ) ) {
				echo $label_class;
			} ?> for="<?php echo esc_attr( $field_id ); ?>">
				<strong>
					<?php echo esc_html( $label ); ?>
					<?php if ( $indicate_as_required ) { ?>
						<span class="sow-form-field-required">*</span>
					<?php } ?>
				</strong>
			</label>
			<?php
		}
	}

	/**
	 * Ajax action handler to send the form
	 */
	public function contact_form_action( $instance, $storage_hash ) {
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], '_contact_form_submit' ) ) {
			// Using `return false;` instead of `wp_die` because this function may sometimes be called as a side effect
			// of trying to enqueue scripts required for the front end or when previewing widgets e.g. in the block editor.
			// In those cases `$_POST['_wpnonce']` doesn't exist and calling `wp_die` will halt script execution and break things.
			return false;
		}

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

		$errors = array();
		$email_fields = array();
		$post_vars = stripslashes_deep( $_POST );

		$field_ids = array();

		foreach ( $instance['fields'] as $i => $field ) {
			if ( empty( $field['type'] ) ) {
				continue;
			}

			$field_name = $this->name_from_label( ! empty( $field['label'] ) ? $field['label'] : $i, $field_ids ) . '-' . ( ! empty( $instance['_sow_form_id'] ) ? $instance['_sow_form_id'] : '' );
			$value = isset( $post_vars[ $field_name ] ) ? $post_vars[ $field_name ] : '';

			// Can't just use `strlen` here as $value could be an array. E.g. for checkboxes field.
			if ( empty( $value ) && $value !== '0' ) {
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

				case 'tel':
					// Somewhat arbitrary basic phone number validation, checking for at least 3 digits, ignoring all
					// non-digit characters. Apparently, the lower limit for phone numbers is 3. See
					// https://github.com/siteorigin/so-widgets-bundle/issues/958#issuecomment-573139753
					$digits = preg_replace( '/\D/', '', $value );

					if ( strlen( $digits ) < 3 ) {
						$errors[ $field_name ] = __( 'Invalid phone number. It should contain at least three digits.', 'so-widgets-bundle' );
					} else {
						$email_fields['message'][] = array(
							'label' => $field['label'],
							'value' => $value,
						);
					}
					break;

				case 'number':
					if ( ! is_numeric( $value ) ) {
						$errors[ $field_name ] = __( 'Invalid number.', 'so-widgets-bundle' );
					} else {
						$email_fields[ $field['type'] ] = $value;
					}
					break;

				case 'select':
					if ( ! empty( $field['multiple_select'] ) && is_array( $value ) ) {
						$value = implode( ', ', $value );
					}

					$email_fields['message'][] = array(
						'label' => $field['label'],
						'value' => $value,
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

		// Add in a default email address if no email field is defined in the form at all.
		if ( ! isset( $email_fields['email'] ) && ! empty( $instance['settings']['from'] ) ) {
			$email_fields['email'] = $instance['settings']['from'];
		}

		// Add in the default subject if no subject field is defined in the form at all.
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

		$errors = apply_filters( 'siteorigin_widgets_contact_validation', $errors, $post_vars, $email_fields, $instance );

		if ( empty( $errors ) ) {
			// We can send the email
			$success = $this->send_mail( $email_fields, $instance );

			if ( is_wp_error( $success ) ) {
				$errors['_general'] = array( 'send' => $success->get_error_message() );
			} elseif ( empty( $success ) ) {
				$errors['_general'] = array( 'send' => __( 'Error sending email, please try again later.', 'so-widgets-bundle' ) );
			} else {
				// This action will allow other plugins to run code when contact form has successfully been sent
				do_action( 'siteorigin_widgets_contact_sent', $instance, $email_fields );
			}
		}

		if ( ! empty( $errors ) ) {
			// This action will allow other plugins to run code when the contact form submission has resulted in error
			do_action( 'siteorigin_widgets_contact_error', $instance, $email_fields, $errors );
		}

		$send_cache[ $send_cache_hash ] = array(
			'status' => empty( $errors ) ? 'success' : 'fail',
			'errors' => $errors,
		);

		return $send_cache[ $send_cache_hash ];
	}

	/**
	 * Validate fields of an email message
	 */
	public function validate_mail( $email_fields ) {
		$errors = array();

		if ( empty( $email_fields['email'] ) ) {
			$errors['email'] = __( 'A valid email is required', 'so-widgets-bundle' );
		} elseif ( function_exists( 'filter_var' ) && ! filter_var( $email_fields['email'], FILTER_VALIDATE_EMAIL ) ) {
			$errors['email'] = __( 'The email address is invalid', 'so-widgets-bundle' );
		}

		if ( ! isset( $email_fields['subject'] ) ) {
			$errors['subject'] = __( 'Missing subject', 'so-widgets-bundle' );
		}

		return $errors;
	}

	/**
	 * Check the email for spam
	 *
	 * @return array
	 */
	public function spam_check( $post_vars, $email_fields, $instance ) {
		$errors = array();

		if ( self::is_recaptcha_enabled( $instance['spam']['recaptcha'] ) ) {
			$result = wp_remote_post(
				'https://www.google.com/recaptcha/api/siteverify',
				array(
					'body' => array(
						'secret'   => $instance['spam']['recaptcha']['use_captcha'] == 'v2' ? $instance['spam']['recaptcha']['secret_key'] : $instance['spam']['recaptcha']['secret_key_v3'],
						'response' => isset( $post_vars['g-recaptcha-response'] ) ? $post_vars['g-recaptcha-response'] : '',
						'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null,
					),
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

			if ( ! empty( $email_fields['message'] ) ) {
				foreach ( $email_fields['message'] as $m ) {
					$message_text[] = $m['value'];
				}
			}

			$comment['comment_content'] = $email_fields['subject'] . "\n\n" . implode( "\n\n", $message_text );
			$comment['comment_author'] = ! empty( $email_fields['name'] ) ? $email_fields['name'] : '';
			$comment['comment_author_email'] = $email_fields['email'];
			$comment['comment_post_ID'] = get_the_ID();

			$comment['comment_type'] = 'contact-form';

			$comment['user_ip'] = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null;
			$comment['user_agent'] = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : null;
			$comment['referrer'] = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : null;
			$comment['blog'] = get_option( 'home' );
			$comment['blog_lang'] = get_locale();
			$comment['blog_charset'] = get_option( 'blog_charset' );

			// Pretend to check with Akismet
			$response = Akismet::http_post( Akismet::build_query( $comment ), 'comment-check' );
			$is_spam = ! empty( $response[1] ) && $response[1] == 'true';

			if ( $is_spam ) {
				$errors['akismet'] = __( 'Unfortunately our system identified your message as spam.', 'so-widgets-bundle' );
			}
		}

		if ( ! empty( $instance['spam']['simple'] ) && ! empty( $instance['spam']['simple']['enabled'] ) ) {
			if ( ! class_exists( 'ReallySimpleCaptcha' ) ) {
				$template_vars['really_simple_spam'] = 'missing';
				$errors['simple'] = __( 'Error validating your Captcha response. Really Simple CAPTCHA missing.', 'so-widgets-bundle' );
			} else {
				$captcha = new ReallySimpleCaptcha();
				$prefix = $post_vars['really-simple-captcha-prefix-' . $post_vars['instance_hash'] ];

				if ( ! $captcha->check(
					$prefix,
					$post_vars['really-simple-captcha-' . $post_vars['instance_hash'] ]
				) ) {
					$errors['simple'] = __( 'Error validating your Captcha response. Please try again.', 'so-widgets-bundle' );
				}
				$captcha->remove( $prefix );
			}
		}

		if ( ! empty( $instance['spam']['honeypot'] ) && ! empty( $_POST[ 'sow-' . $instance['_sow_form_id'] ] ) ) {
			$errors['spam-js'] = __( 'Unfortunately, our system identified your message as spam.', 'so-widgets-bundle' );
		}

		if ( ! empty( $instance['spam']['browser_check'] ) ) {
			if (
				empty( $_POST[ 'sow-js-' . $instance['_sow_form_id'] ] ) ||
				$_POST[ 'sow-js-' . $instance['_sow_form_id'] ] != $instance['_sow_form_id']
			) {
				$errors['spam-honeypot'] = __( 'Unfortunately, our system identified your message as spam.', 'so-widgets-bundle' );
			}
		}

		return $errors;
	}

	public function send_mail( $email_fields, $instance ) {
		if ( ! empty( $email_fields['name'] ) || ! empty( $email_fields['email'] ) ) {
			$body = '<strong>' . _x( 'From', 'The name of who sent this email', 'so-widgets-bundle' ) . ':</strong> ';

			if ( ! empty( $email_fields['email'] ) ) {
				$body .= '<a href="mailto:' . sanitize_email( $email_fields['email'] ) . '">';
			}

			if ( ! empty( $email_fields['name'] ) ) {
				$body .= esc_html( $email_fields['name'] ) . ' ';
			}

			if ( ! empty( $email_fields['email'] ) ) {
				$body .= '&#60;' . sanitize_email( $email_fields['email'] ) . '&#62; </a> ';
			}

			if ( ! empty( $instance['settings']['log_ip_address'] ) ) {
				$body .= '( ' . $_SERVER['REMOTE_ADDR'] . ' )';
			}
			$body .= "\n\n";
		}

		if ( ! empty( $email_fields['message'] ) ) {
			foreach ( $email_fields['message'] as $m ) {
				$body .= '<strong>' . $m['label'] . ':</strong>';
				$body .= "\n";
				$body .= htmlspecialchars( $m['value'] );
				$body .= "\n\n";
			}
		}
		$body = wpautop( trim( $body ) );

		if ( $this->is_dev_email( $instance['settings']['to'] ) || empty( $instance['settings']['to'] ) ) {
			// Replace default and empty email address.
			// Also replaces the email address that comes from the prebuilt layout directory and SiteOrigin Support Email
			$instance['settings']['to'] = get_option( 'admin_email' );
		}

		if (
			$this->is_dev_email( $instance['settings']['from'] ) ||
			empty( $instance['settings']['from'] ) ||
			$instance['settings']['from'] == $instance['settings']['to']
		) {
			$instance['settings']['from'] = $this->default_from_address();
		}

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . ( ! empty( $email_fields['name'] ) ? $this->sanitize_header( $email_fields['name'] ) : '' ) . ' <' . sanitize_email( $instance['settings']['from'] ) . '>',
			'Reply-To: ' . ( ! empty( $email_fields['name'] ) ? $this->sanitize_header( $email_fields['name'] ) : '' ) . ' <' . sanitize_email( $email_fields['email'] ) . '>',
		);

		// Check if this is a duplicated send
		$hash = md5( json_encode( array(
			'to'      => $instance['settings']['to'],
			'subject' => $email_fields['subject'],
			'body'    => $body,
			'headers' => $headers,
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

		$mail_success = wp_mail(
			$instance['settings']['to'],
			$email_fields['subject'],
			$body,
			apply_filters( 'siteorigin_widgets_contact_email_headers', $headers )
		);

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
	 * @return mixed
	 */
	public static function sanitize_header( $value ) {
		return preg_replace( '=((<CR>|<LF>|0x0A/%0A|0x0D/%0D|\\n|\\r)\S).*=i', null, $value );
	}

	private function is_dev_email( $email ) {
		return $email == 'ibrossiter@gmail.com' ||
			   $email == 'amisplon@gmail.com' ||
			   $email == 'test@example.com' ||
			   $email == 'greg@siteorigin.com' ||
			   $email == 'support@siteorigin.com';
	}

	private function default_from_address() {
		// Get the site domain and get rid of www.
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );

		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		return apply_filters( 'siteorigin_widgets_contact_default_email', 'wordpress@' . $sitename );
	}

	public function get_form_teaser() {
		if ( class_exists( 'SiteOrigin_Premium' ) ) {
			return false;
		}

		return array(
			sprintf(
				__( 'Add a form autoresponder and additional fields, including a date and time picker with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/contact-form-fields" target="_blank">',
				'</a>'
			),
			sprintf(
				__( 'Use Google Fonts right inside the Contact Form Widget with %sSiteOrigin Premium%s', 'so-widgets-bundle' ),
				'<a href="https://siteorigin.com/downloads/premium/?featured_addon=plugin/contact-form-fields" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
		);
	}
}

siteorigin_widget_register( 'sow-contact-form', __FILE__, 'SiteOrigin_Widgets_ContactForm_Widget' );

// Tell the autoloader where to look for contactform field classes.
function contactform_fields_class_paths( $class_paths ) {
	$loader = SiteOrigin_Widget_Field_Class_Loader::single();

	$loader->add_class_prefixes(
		apply_filters( 'siteorigin_widgets_contact_form_field_class_prefixes', array(
			'SiteOrigin_Widget_ContactForm_Field_',
		) ),
		'contact-form'
	);

	$loader->add_class_paths(
		apply_filters( 'siteorigin_widgets_contact_form_field_class_paths', array(
			plugin_dir_path( __FILE__ ) . 'fields/',
		) ),
		'contact-form'
	);

	return $class_paths;
}

add_filter( 'init', 'contactform_fields_class_paths' );

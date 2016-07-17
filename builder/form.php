<?php

class SiteOrigin_Widgets_Builder_Form extends SiteOrigin_Widget {

	function __construct( ) {
		parent::__construct(
			'widget-builder-form',
			__( 'SiteOrigin Widget Builder', 'siteorigin-premium' ),
			array(
				'has_preview' => false,
			),
			array(),
			array(),
			plugin_dir_path(__FILE__)
		);

		static $form_number = 1;
		$this->number = $form_number++;
	}

	/**
	 * Initialize the Builder Form field
	 *
	 * @return array
	 */
	function initialize_form() {

		return array(
			'description' => array(
				'label' => __( 'Widget Description', 'so-widgets-bundle' ),
				'type' => 'text',
				'default' => '',
			),

			'has_title' => array(
				'label' => __( 'Title Field', 'so-widgets-bundle' ),
				'description' => __( 'Does this widget need a title field', 'so-widgets-bundle' ),
				'type' => 'checkbox',
				'default' => true,
			),

			'fields' => array(
				'type' => 'repeater',
				'label' => __( 'Widget Form Fields', 'so-widgets-bundle' ),
				'fields' => $this->get_field_array( 4 ),
			),

			'scripts' => array(
				'type' => 'repeater',
				'label' => __( 'Javascript Scripts', 'so-widgets-bundle' ),
				'fields' => array(
					'file' => array(
						'type' => 'media',
						'label' => __( 'Javascript File', 'so-widgets-bundle' ),
						'choose' => __( 'Choose Script', 'so-widgets-bundle' ),
						'update' => __( 'Update Script', 'so-widgets-bundle' ),
						'library' => 'file',
					),
					'jquery' => array(
						'type' => 'checkbox',
						'label' => __( 'Requires jQuery', 'so-widgets-bundle' ),
						'default' => false
					)
				)
			),

			'styles' => array(
				'type' => 'repeater',
				'label' => __( 'CSS Styles', 'so-widgets-bundle' ),
				'fields' => array(
					'file' => array(
						'type' => 'media',
						'label' => __( 'CSS File', 'so-widgets-bundle' ),
						'choose' => __( 'Choose Style', 'so-widgets-bundle' ),
						'update' => __( 'Update Style', 'so-widgets-bundle' ),
						'library' => 'file',
					),
				)
			),

			'template_code' => array(
				'type' => 'code',
				'rows' => 8,
				'label' => __( 'Template HTML Code', 'so-widgets-bundle' ),
			),

			'less_code' => array(
				'type' => 'code',
				'rows' => 8,
				'label' => __( 'Template LESS Code', 'so-widgets-bundle' ),
			),
		);
	}

	/**
	 * These are fields that all field types will have
	 *
	 * @return array
	 */
	private function get_general_field_fields(){
		return array(
			'type' => array(
				'label' => __( 'Field Type', 'so-widgets-bundle' ),
				'type' => 'select',
				'default' => 'text',
			),
			'label' => array(
				'label' => __( 'Field Label', 'so-widgets-bundle' ),
				'type' => 'text',
				'default' => '',
			),
			'description' => array(
				'label' => __( 'Field Description', 'so-widgets-bundle' ),
				'type' => 'text',
				'default' => '',
			),
			'variable' => array(
				'label' => __( 'Variable Name', 'so-widgets-bundle' ),
				'type' => 'text',
				'description' => __( 'Machine readable name for this field. Should only consist of lowercase characters and _ characters.', 'so-widgets-bundle' ),
				'default' => '',
			),
			'default' => array(
				'label' => __( 'Default Value', 'so-widgets-bundle' ),
				'type' => 'text',
				'default' => '',
			),
		);
	}

	private function get_specific_fields(){
		return array(
			'placeholder' => array(
				'label' => __( 'Placeholder', 'so-widgets-bundle' ),
				'type' => 'text',
				'default' => '',
				'_for_fields' => array(),
			),

			// Number fields
			'min' => array(
				'label' => __( 'Minimum Value', 'so-widgets-bundle' ),
				'type' => 'number',
				'default' => 0,
				'_for_fields' => array(),
			),
			'max' => array(
				'label' => __( 'Maximum Value', 'so-widgets-bundle' ),
				'type' => 'number',
				'default' => 100,
				'_for_fields' => array(),
			),

			// Select fields
			'prompt' => array(
				'label' => __( 'Prompt', 'so-widgets-bundle' ),
				'description' => __( 'Text that prompts a user on select values', 'so-widgets-bundle' ),
				'type' => 'text',
				'default' => '',
				'_for_fields' => array(),
			),
			'options' => array(
				'label' => __( 'Options', 'so-widgets-bundle' ),
				'description' => __( 'Select options available to this field', 'so-widgets-bundle' ),
				'type' => 'repeater',
				'fields' => array(
					'label' => array(
						'label' => __( 'Label', 'so-widgets-bundle' ),
						'type' => 'text',
					),
					'value' => array(
						'label' => __( 'Value', 'so-widgets-bundle' ),
						'type' => 'text',
					),
				),
				'_for_fields' => array(),
			),

			// Media field
			'choose' => array(
				'label' => __( 'Choose Label', 'so-widgets-bundle' ),
				'description' => __( 'A label for the title of the media selector dialog.', 'so-widgets-bundle' ),
				'type' => 'text',
				'default' => '',
				'_for_fields' => array(),
			),
			'update' => array(
				'label' => __( 'Update Label', 'so-widgets-bundle' ),
				'description' => __( 'A label for the confirmation button of the media selector dialog.', 'so-widgets-bundle' ),
				'type' => 'text',
				'default' => '',
				'_for_fields' => array(),
			),
			'library' => array(
				'label' => __( 'Library', 'so-widgets-bundle' ),
				'description' => __( 'What type of fields are allowed.', 'so-widgets-bundle' ),
				'type' => 'select',
				'default' => 'file',
				'options' => array(
					'file' => __( 'File', 'so-widgets-bundle' ),
					'image' => __( 'Image	', 'so-widgets-bundle' ),
					'audio' => __( 'Audio', 'so-widgets-bundle' ),
					'video' => __( 'Video', 'so-widgets-bundle' ),
				),
				'_for_fields' => array(),
			),

			// The widget field
			'class' => array(
				'label' => __( 'Widget Class', 'so-widgets-bundle' ),
				'description' => __( 'The class name for a sub widget.', 'so-widgets-bundle' ),
				'type' => 'text',
				'default' => '',
				'_for_fields' => array(),
			)
		);
	}

	private function get_field_array( $depth ){
		if( $depth == 0 ) return array();

		static $fields;
		if( empty( $fields ) ) {
			$fields = include plugin_dir_path( __FILE__ ) . '/data/fields.php';
		}

		$return = array_merge(
			$this->get_general_field_fields(),
			$this->get_specific_fields()
		);
		$type_array = array();
		foreach( $fields as $k => $field ) {
			$type_array[$k] = $field['label'];

			if( ! empty( $field[ 'fields' ] ) ) {

			}
		}

		$return['type']['options'] = $type_array;

		if( $depth >= 1 ) {
			$return['sub_fields'] = array(
				'type' => 'repeater',
				'label' => __( 'Fields', 'so-widgets-bundle' ),
				'fields' => $this->get_field_array( --$depth )
			);
		}

		return $return;
	}

	/**
	 * Get a specially prefixed name
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	function get_field_name( $field_name ){
		return 'so_custom_widget[' . $field_name . ']';
	}

	/**
	 * Chance the message displayed while loading the form.
	 */
	function scripts_loading_message(){
		?><p><strong><?php _e('Scripts and styles for this form are loading.', 'siteorigin-premium') ?></strong></p><?php
	}

	/**
	 * This widget will never be rendered on the frontend, so add a noop.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	function widget( $args, $instance ) {

	}
}
<?php
/**
 * Minimal widgets for exercising SiteOrigin_Widget's save and render paths.
 *
 * SiteOrigin_Test_Widget declares one field of every container type the
 * bundle ships — section, toggle, repeater, widget and posts — around plain
 * text fields, and reads a nested section value raw in get_less_variables(),
 * which is the shape of read the save path fatals on when the section is
 * stored as an empty string. It has no modify_instance() repair, so whatever
 * update() stores for an absent container is what the base class stored.
 */
if ( ! class_exists( 'SiteOrigin_Test_Sub_Widget' ) ) {
	class SiteOrigin_Test_Sub_Widget extends SiteOrigin_Widget {
		public function __construct() {
			parent::__construct(
				'so-test-sub-widget',
				'Test Sub Widget',
				array( 'has_preview' => false ),
				array(),
				false,
				plugin_dir_path( __FILE__ )
			);
		}

		public function get_widget_form() {
			return array(
				'label' => array(
					'type' => 'text',
					'label' => 'Label',
				),
				'icon' => array(
					'type' => 'section',
					'label' => 'Icon',
					'fields' => array(
						'name' => array(
							'type' => 'text',
							'label' => 'Name',
						),
					),
				),
			);
		}

		public function get_less_variables( $instance ) {
			return array();
		}
	}
}

if ( ! class_exists( 'SiteOrigin_Test_Widget' ) ) {
	class SiteOrigin_Test_Widget extends SiteOrigin_Widget {
		/**
		 * How many times the sub-widget form filter ran, across every instance.
		 */
		public static $form_filter_calls = 0;

		/**
		 * Optional callable that modify_instance() applies, so a test can plant
		 * the write-through shape real widgets use without subclassing.
		 */
		public static $modify_instance_callback = null;

		public function __construct() {
			parent::__construct(
				'so-test-widget',
				'Test Widget',
				array( 'has_preview' => false ),
				array(),
				false,
				plugin_dir_path( __FILE__ )
			);
		}

		public function get_widget_form() {
			return array(
				'title' => array(
					'type' => 'text',
					'label' => 'Title',
				),
				'design' => array(
					'type' => 'section',
					'label' => 'Design',
					'fields' => array(
						'text' => array(
							'type' => 'text',
							'label' => 'Text',
						),
						'box' => array(
							'type' => 'section',
							'label' => 'Box',
							'fields' => array(
								'color' => array(
									'type' => 'text',
									'label' => 'Color',
								),
							),
						),
					),
				),
				'shadow' => array(
					'type' => 'toggle',
					'label' => 'Shadow',
					'fields' => array(
						'color' => array(
							'type' => 'text',
							'label' => 'Color',
						),
					),
				),
				'items' => array(
					'type' => 'repeater',
					'label' => 'Items',
					'item_name' => 'Item',
					'fields' => array(
						'text' => array(
							'type' => 'text',
							'label' => 'Text',
						),
						'meta' => array(
							'type' => 'section',
							'label' => 'Meta',
							'fields' => array(
								'note' => array(
									'type' => 'text',
									'label' => 'Note',
								),
							),
						),
					),
				),
				'button' => array(
					'type' => 'widget',
					'label' => 'Button',
					'class' => 'SiteOrigin_Test_Sub_Widget',
					'form_filter' => array( 'SiteOrigin_Test_Widget', 'filter_sub_widget_form' ),
				),
				'query' => array(
					'type' => 'posts',
					'label' => 'Query',
				),
			);
		}

		public static function filter_sub_widget_form( $form ) {
			self::$form_filter_calls++;
			unset( $form['label'] );

			return $form;
		}

		public function modify_instance( $instance ) {
			if ( is_callable( self::$modify_instance_callback ) ) {
				$instance = call_user_func( self::$modify_instance_callback, $instance );
			}

			return $instance;
		}

		/**
		 * Reads the nested section raw, as the widgets named in #2367 do.
		 */
		public function get_less_variables( $instance ) {
			return array(
				'box_color' => $instance['design']['box']['color'],
			);
		}

		public function get_template_variables( $instance, $args ) {
			return array( 'instance' => $instance );
		}
	}
}

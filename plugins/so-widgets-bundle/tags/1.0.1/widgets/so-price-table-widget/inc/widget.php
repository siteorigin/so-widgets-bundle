<?php

class SiteOrigin_Widget_PriceTable_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-price-table',
			__('SiteOrigin Price Table', 'sow-pt'),
			array(
				'description' => __('A simple Price Table.', 'sow-pt'),
				'help' => 'http://siteorigin.com/price-table-widget/'
			),
			array(

			),
			array(
				'title' => array(
					'type' => 'text',
					'label' => __('Title', 'sow-pt'),
				),

				'columns' => array(
					'type' => 'repeater',
					'label' => __('Columns', 'sow-pt'),
					'item_name' => __('Column', 'sow-pt'),
					'fields' => array(
						'featured' => array(
							'type' => 'checkbox',
							'label' => __('Featured', 'sow-pt'),
						),
						'title' => array(
							'type' => 'text',
							'label' => __('Title', 'sow-pt'),
						),
						'subtitle' => array(
							'type' => 'text',
							'label' => __('Sub Title', 'sow-pt'),
						),

						'image' => array(
							'type' => 'media',
							'label' => __('Image', 'sow-pt'),
						),

						'price' => array(
							'type' => 'text',
							'label' => __('Price', 'sow-pt'),
						),
						'per' => array(
							'type' => 'text',
							'label' => __('Per', 'sow-pt'),
						),
						'button' => array(
							'type' => 'text',
							'label' => __('Button Text', 'sow-pt'),
						),
						'url' => array(
							'type' => 'text',
							'sanitize' => 'url',
							'label' => __('Button URL', 'sow-pt'),
						),
						'features' => array(
							'type' => 'repeater',
							'label' => __('Features', 'sow-pt'),
							'item_name' => __('Feature', 'sow-pt'),
							'fields' => array(
								'text' => array(
									'type' => 'text',
									'label' => __('Text', 'sow-pt'),
								),
								'hover' => array(
									'type' => 'text',
									'label' => __('Hover Text', 'sow-pt'),
								),
								'icon_new' => array(
									'type' => 'icon',
									'label' => __('Icon', 'sow-pt'),
								),
								'icon_color' => array(
									'type' => 'color',
									'label' => __('Icon Color', 'sow-pt'),
								),
							),
						),
					),
				),

				'theme' => array(
					'type' => 'select',
					'label' => __('Price Table Theme', 'sow-pt'),
					'options' => array(
						'atom' => __('Atom', 'sow-pt'),
					),
				),

				'header_color' => array(
					'type' => 'color',
					'label' => __('Header Color', 'sow-pt'),
				),

				'featured_header_color' => array(
					'type' => 'color',
					'label' => __('Featured Header Color', 'sow-pt'),
				),

				'button_color' => array(
					'type' => 'color',
					'label' => __('Button Color', 'sow-pt'),
				),

				'featured_button_color' => array(
					'type' => 'color',
					'label' => __('Featured Button Color', 'sow-pt'),
				),
			),
			plugin_dir_path(__FILE__).'../'
		);
	}

	function get_column_classes($column, $i, $columns) {
		$classes = array();
		if($i == 0) $classes[] = 'ow-pt-first';
		if($i == count($columns) -1 ) $classes[] = 'ow-pt-last';
		if(!empty($column['featured'])) $classes[] = 'ow-pt-featured';

		if($i % 2 == 0) $classes[] = 'ow-pt-even';
		else $classes[] = 'ow-pt-odd';

		return implode(' ', $classes);
	}

	function column_image($image){
		$src = wp_get_attachment_image_src($image, 'full');
		?><img src="<?php echo $src[0] ?>" /> <?php
	}

	function get_template_name($instance) {
		return $this->get_style_name($instance);
	}

	function get_style_name($instance) {
		if(empty($instance['theme'])) return 'atom';
		return $instance['theme'];
	}

	/**
	 * Get the LESS variables for the price table widget.
	 *
	 * @param $instance
	 *
	 * @return array
	 */
	function get_less_variables($instance){
		$instance = wp_parse_args($instance, array(
			'header_color' => '',
			'featured_header_color' => '',
			'button_color' => '',
			'featured_button_color' => '',
		));

		$colors = array(
			'header_color' => $instance['header_color'],
			'featured_header_color' => $instance['featured_header_color'],
			'button_color' => $instance['button_color'],
			'featured_button_color' => $instance['featured_button_color'],
		);

		if( !class_exists('SiteOrigin_Widgets_Color_Object') ) require plugin_dir_path( SITEORIGIN_WIDGETS_BASE_PARENT_FILE ).'base/inc/color.php';

		if( !empty( $instance['button_color'] ) ) {
			$color = new SiteOrigin_Widgets_Color_Object( $instance['button_color'] );
			$color->lum += ($color->lum > 0.75 ? -0.5 : 0.8);
			$colors['button_text_color'] = $color->hex;
		}

		if( !empty( $instance['featured_button_color'] ) ) {
			$color = new SiteOrigin_Widgets_Color_Object( $instance['featured_button_color'] );
			$color->lum += ($color->lum > 0.75 ? -0.5 : 0.8);
			$colors['featured_button_text_color'] = $color->hex;
		}

		return $colors;
	}

	/**
	 * Load the front end scripts for the price table.
	 */
	function enqueue_frontend_scripts(){
		wp_enqueue_script( 'siteorigin-pricetable', siteorigin_widget_get_plugin_dir_url('price-table').'js/pricetable.js', array('jquery') );
	}

	/**
	 * Modify the instance to use the new icon.
	 */
	function modify_instance($instance){
		if( empty( $instance['columns'] ) || !is_array($instance['columns']) ) return $instance;

		foreach( $instance['columns'] as &$column) {
			if( empty($column['features']) || !is_array($column['features']) ) continue;

			foreach($column['features'] as &$feature) {

				if( empty($feature['icon_new']) && !empty($feature['icon']) ) {
					$feature['icon_new'] = 'fontawesome-'.$feature['icon'];
				}

			}
		}

		return $instance;
	}
}
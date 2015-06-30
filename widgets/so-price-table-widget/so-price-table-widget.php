<?php
/*
Widget Name: Price table widget
Description: A powerful yet simple price table widget for your sidebars or Page Builder pages.
Author: Greg Priday
Author URI: http://siteorigin.com
Widget URI: http://siteorigin.com/price-table-widget/
*/

class SiteOrigin_Widget_PriceTable_Widget extends SiteOrigin_Widget {
	function __construct() {
		parent::__construct(
			'sow-price-table',
			__('SiteOrigin Price Table', 'siteorigin-widgets'),
			array(
				'description' => __('A simple Price Table.', 'siteorigin-widgets'),
				'help' => 'http://siteorigin.com/widgets-bundle/price-table-widget/'
			),
			array(

			),
			array(
				'title' => array(
					'type' => 'text',
					'label' => __('Title', 'siteorigin-widgets'),
				),

				'columns' => array(
					'type' => 'repeater',
					'label' => __('Columns', 'siteorigin-widgets'),
					'item_name' => __('Column', 'siteorigin-widgets'),
					'item_label' => array(
						'selector' => "[id*='columns-title']",
						'update_event' => 'change',
						'value_method' => 'val'
					),
					'fields' => array(
						'featured' => array(
							'type' => 'checkbox',
							'label' => __('Featured', 'siteorigin-widgets'),
						),
						'title' => array(
							'type' => 'text',
							'label' => __('Title', 'siteorigin-widgets'),
						),
						'subtitle' => array(
							'type' => 'text',
							'label' => __('Subtitle', 'siteorigin-widgets'),
						),

						'image' => array(
							'type' => 'media',
							'label' => __('Image', 'siteorigin-widgets'),
						),

						'price' => array(
							'type' => 'text',
							'label' => __('Price', 'siteorigin-widgets'),
						),
						'per' => array(
							'type' => 'text',
							'label' => __('Per', 'siteorigin-widgets'),
						),
						'button' => array(
							'type' => 'text',
							'label' => __('Button text', 'siteorigin-widgets'),
						),
						'url' => array(
							'type' => 'link',
							'label' => __('Button URL', 'siteorigin-widgets'),
						),
						'features' => array(
							'type' => 'repeater',
							'label' => __('Features', 'siteorigin-widgets'),
							'item_name' => __('Feature', 'siteorigin-widgets'),
							'item_label' => array(
								'selector' => "[id*='columns-features-text']",
								'update_event' => 'change',
								'value_method' => 'val'
							),
							'fields' => array(
								'text' => array(
									'type' => 'text',
									'label' => __('Text', 'siteorigin-widgets'),
								),
								'hover' => array(
									'type' => 'text',
									'label' => __('Hover text', 'siteorigin-widgets'),
								),
								'icon_new' => array(
									'type' => 'icon',
									'label' => __('Icon', 'siteorigin-widgets'),
								),
								'icon_color' => array(
									'type' => 'color',
									'label' => __('Icon color', 'siteorigin-widgets'),
								),
							),
						),
					),
				),

				'theme' => array(
					'type' => 'select',
					'label' => __('Price table theme', 'siteorigin-widgets'),
					'options' => array(
						'atom' => __('Atom', 'siteorigin-widgets'),
					),
				),

				'header_color' => array(
					'type' => 'color',
					'label' => __('Header color', 'siteorigin-widgets'),
				),

				'featured_header_color' => array(
					'type' => 'color',
					'label' => __('Featured header color', 'siteorigin-widgets'),
				),

				'button_color' => array(
					'type' => 'color',
					'label' => __('Button color', 'siteorigin-widgets'),
				),

				'featured_button_color' => array(
					'type' => 'color',
					'label' => __('Featured button color', 'siteorigin-widgets'),
				),

				'button_new_window' => array(
					'type' => 'checkbox',
					'label' => __('Open Button URL in a new window', 'siteorigin-widgets'),
				),
			),
			plugin_dir_path(__FILE__).'../'
		);
	}

	function initialize() {
		$this->register_frontend_scripts(
			array(
				array(
					'siteorigin-pricetable',
					siteorigin_widget_get_plugin_dir_url( 'price-table' ) . 'js/pricetable' . SOW_BUNDLE_JS_SUFFIX . '.js',
					array( 'jquery' )
				)
			)
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

		if( !class_exists('SiteOrigin_Widgets_Color_Object') ) require plugin_dir_path( SOW_BUNDLE_BASE_FILE ).'base/inc/color.php';

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

siteorigin_widget_register('price-table', __FILE__);
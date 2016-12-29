<?php

class SiteOrigin_Widgets_Bundle_Visual_Composer {

	/**
	 * Get the singleton instance
	 *
	 * @return SiteOrigin_Widgets_Bundle_Visual_Composer
	 */
	public static function single() {
		static $single;
		return empty( $single ) ? $single = new self() : $single;
	}

	function __construct() {
		add_action('vc_after_init', array( $this, 'init' ) );

		add_action( 'wp_ajax_sowb_vc_widget_render_form', array( $this, 'sowb_vc_widget_render_form' ) );
	}

	function init() {

		global $wp_widget_factory;

		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
			if ( ! empty( $widget_obj ) && is_object( $widget_obj ) && is_subclass_of( $widget_obj, 'SiteOrigin_Widget' ) ) {
				/* @var $widget_obj SiteOrigin_Widget */
				$widget_obj->enqueue_scripts( 'widget' );
			}
		}

		vc_add_shortcode_param(
			'siteorigin_widget',
			array( $this, 'siteorigin_widget_form' ),
			plugin_dir_url( __FILE__ ) . 'sowb-visual-composer.js'
		);

		// Note that all keys=values in mapped shortcode can be used with javascript variable vc.map, and php shortcode settings variable.
		$settings = array(
			'name'                    => __( 'SiteOrigin Widget', 'so-widgets-bundle' ),
			// shortcode name
			'base'                    => 'siteorigin_widget',
			// shortcode base [test_element]
			'category'                => __( 'SiteOrigin Widgets', 'so-widgets-bundle' ),
			// param category tab in add elements view
//			'description'             => __( 'Test element description', 'js_composer' ),
			// element description in add elements view
			'show_settings_on_create' => false,
			// don't show params window after adding
			'weight'                  => - 5,
			// Depends on ordering in list, Higher weight first
			'html_template'           => dirname( __FILE__ ) . '/vc_templates/test_element.php',
			// if you extend VC within your theme then you don't need this, VC will look for shortcode template in 'wp-content/themes/your_theme/vc_templates/test_element.php' automatically. In this example we are extending VC from plugin, so we rewrite template
			'admin_enqueue_js'        => preg_replace( '/\s/', '%20', plugins_url( 'assets/admin_enqueue_js.js', __FILE__ ) ),
			// This will load extra js file in backend (when you edit page with VC)
			// use preg replace to be sure that 'space' will not break logic
			'admin_enqueue_css'       => preg_replace( '/\s/', '%20', plugins_url( 'assets/admin_enqueue_css.css', __FILE__ ) ),
			// This will load extra css file in backend (when you edit page with VC)
			'front_enqueue_js'        => preg_replace( '/\s/', '%20', plugins_url( 'assets/front_enqueue_js.js', __FILE__ ) ),
			// This will load extra js file in frontend editor (when you edit page with VC)
			'front_enqueue_css'       => preg_replace( '/\s/', '%20', plugins_url( 'assets/front_enqueue_css.css', __FILE__ ) ),
			// This will load extra css file in frontend editor (when you edit page with VC)
			'js_view'                 => 'ViewTestElement',
			// JS View name for backend. Can be used to override or add some logic for shortcodes in backend (cloning/rendering/deleting/editing).
			'params'                  => array(
				array(
					'type'        => 'siteorigin_widget',
					'heading'     => __( 'SiteOrigin Widget', 'so-widgets-bundle' ),
					'param_name'  => 'so_widget_data',
				),
			)
		);
		vc_map( $settings );
	}

	function siteorigin_widget_form( $settings, $value ) {
		$so_widget_names = array();

		global $wp_widget_factory;

		foreach($wp_widget_factory->widgets as $class => $widget_obj) {
			if ( ! empty( $widget_obj ) && is_object( $widget_obj ) && is_subclass_of( $widget_obj, 'SiteOrigin_Widget' ) ) {
				$so_widget_names[ $class ] = $widget_obj->name;
			}
		}

		/* @var $select SiteOrigin_Widget_Field_Select */
		$select = new SiteOrigin_Widget_Field_Select(
			'so_widget_class',
			'so_widget_class',
			'so_widget_class',
			array(
				'type' => 'select',
				'options' => $so_widget_names,
			)
		);

		global $wp_widget_factory;

		$parsed_value = json_decode( $value, true );

		$widget = !empty($wp_widget_factory->widgets[$parsed_value['widget_class']]) ? $wp_widget_factory->widgets[$parsed_value['widget_class']] : false;

		ob_start();
		$select->render( $parsed_value['widget_class'] ); ?>
		<input type="hidden" name="ajaxurl" data-ajax-url="<?php echo wp_nonce_url( admin_url('admin-ajax.php'), 'sowb_vc_widget_render_form', '_sowbnonce' ) ?>">
		<input type="hidden" name="so_widget_data" class="wpb_vc_param_value" value="<?php echo $parsed_value ?>">
		<div class="siteorigin_widget_form_container">
			<?php
			if ( ! empty( $widget ) && is_object( $widget ) && is_subclass_of( $widget, 'SiteOrigin_Widget' ) ) {
				/* @var $widget SiteOrigin_Widget */
				$widget->form( $parsed_value['widget_data'] );
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	function sowb_vc_widget_render_form(  ) {
		if( empty( $_REQUEST['widget'] ) ) wp_die();
		if( empty( $_REQUEST['_sowbnonce'] ) || !wp_verify_nonce($_REQUEST['_sowbnonce'], 'sowb_vc_widget_render_form') ) wp_die();

		$request = array_map('stripslashes_deep', $_REQUEST);
		$widget_class = $request['widget'];


		global $wp_widget_factory;

		$widget = !empty($wp_widget_factory->widgets[$widget_class]) ? $wp_widget_factory->widgets[$widget_class] : false;

		if ( ! empty( $widget ) && is_object( $widget ) && is_subclass_of( $widget, 'SiteOrigin_Widget' ) ) {
			/* @var $widget SiteOrigin_Widget */
			$widget->form( array() );
		}

		wp_die();
	}
}

SiteOrigin_Widgets_Bundle_Visual_Composer::single();


if ( class_exists( 'WPBakeryShortCode' ) ) {
	// Class Name should be WPBakeryShortCode_Your_Short_Code
	// See more in vc_composer/includes/classes/shortcodes/shortcodes.php
	class WPBakeryShortCode_Test_Element extends WPBakeryShortCode {
		public function __construct( $settings ) {
			parent::__construct( $settings ); // !Important to call parent constructor to active all logic for shortcode.
			$this->jsCssScripts();
		}
		public function vcLoadIframeJsCss() {
			wp_enqueue_style( 'test_element_iframe' );
		}
		public function contentInline( $atts, $content ) {
			$this->vcLoadIframeJsCss();
		}
		// Register scripts and styles there (for preview and frontend editor mode).
		public function jsCssScripts() {
			wp_register_script( 'test_element', plugins_url( 'assets/js/test_element.js', __FILE__ ), array( 'jquery' ), time(), false );
			wp_register_style( 'test_element_iframe', plugins_url( 'assets/front_enqueue_iframe_css.css', __FILE__ ) );
		}
		// Some custom helper function that can be used in content element template (vc_templates/test_element.php)
		// This function check some string if it matches 'yes','true',1,'1' return TRUE if yes, false if NOT.
		public function checkBool( $in ) {
			if ( strlen( $in ) > 0 && in_array( strtolower( $in ), array(
					'yes',
					'true',
					'1',
					1
				) )
			) {
				return true;
			}
			return false;
		}
	}
} // End Class


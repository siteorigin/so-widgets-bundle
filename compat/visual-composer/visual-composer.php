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
		add_action( 'vc_after_init', array( $this, 'init' ) );

		add_action( 'wp_ajax_sowb_vc_widget_render_form', array( $this, 'sowb_vc_widget_render_form' ) );

		add_filter( 'siteorigin_widgets_form_show_preview_button', array( $this, '__return_false' ) );

		add_filter( 'content_save_pre', array( $this, 'update_widget_data' ) );
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
			'sowb_json_escaped',
			array( $this, 'siteorigin_widget_form' ),
			plugin_dir_url( __FILE__ ) . 'sowb-visual-composer' . SOW_BUNDLE_JS_SUFFIX . '.js'
		);

		$settings = array(
			'name'                    => __( 'SiteOrigin Widget', 'so-widgets-bundle' ),
			'base'                    => 'siteorigin_widget',
			'category'                => __( 'SiteOrigin Widgets', 'so-widgets-bundle' ),
			'description'             => __( 'Allows you to add any active SiteOrigin Widgets Bundle widgets.', 'so-widgets-bundle' ),
			// element description in add elements view
			'show_settings_on_create' => true,
			'weight'                  => - 5,
			// Depends on ordering in list, Higher weight first
			'html_template'           => dirname( __FILE__ ) . '/siteorigin_widget_vc_template.php',
			'admin_enqueue_css'       => preg_replace( '/\s/', '%20', plugins_url( 'styles.css', __FILE__ ) ),
			'front_enqueue_css'       => preg_replace( '/\s/', '%20', plugins_url( 'styles.css', __FILE__ ) ),
			'params'                  => array(
				array(
					'type'       => 'sowb_json_escaped',
					'heading'    => __( 'SiteOrigin Widget', 'so-widgets-bundle' ),
					'param_name' => 'so_widget_data',
				),
			)
		);
		vc_map( $settings );
	}

	function siteorigin_widget_form( $settings, $value ) {
		$so_widget_names = array();

		global $wp_widget_factory;

		foreach ( $wp_widget_factory->widgets as $class => $widget_obj ) {
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
				'type'    => 'select',
				'options' => $so_widget_names,
			)
		);

		global $wp_widget_factory;

		$parsed_value = json_decode( $value, true );
		if ( empty( $parsed_value ) ) {
			//Get the first value as the default.
			reset( $so_widget_names );
			$widget_class = key( $so_widget_names );
		} else {
			$widget_class = $parsed_value['widget_class'];
		}

		$widget = ! empty( $wp_widget_factory->widgets[ $widget_class ] ) ? $wp_widget_factory->widgets[ $widget_class ] : false;

		ob_start();
		$select->render( $widget_class ); ?>
		<input type="hidden" name="ajaxurl"
		       data-ajax-url="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php' ), 'sowb_vc_widget_render_form', '_sowbnonce' ) ?>">
		<input type="hidden" name="so_widget_data" class="wpb_vc_param_value" value="<?php echo esc_attr( $value ); ?>">
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

	function sowb_vc_widget_render_form() {
		if ( empty( $_REQUEST['widget'] ) ) {
			wp_die();
		}
		if ( empty( $_REQUEST['_sowbnonce'] ) || ! wp_verify_nonce( $_REQUEST['_sowbnonce'], 'sowb_vc_widget_render_form' ) ) {
			wp_die();
		}

		$request      = array_map( 'stripslashes_deep', $_REQUEST );
		$widget_class = $request['widget'];

		global $wp_widget_factory;

		$widget = ! empty( $wp_widget_factory->widgets[ $widget_class ] ) ? $wp_widget_factory->widgets[ $widget_class ] : false;

		if ( ! empty( $widget ) && is_object( $widget ) && is_subclass_of( $widget, 'SiteOrigin_Widget' ) ) {
			/* @var $widget SiteOrigin_Widget */
			$widget->form( array() );
		}

		wp_die();
	}

	function update_widget_data( $content ) {

		$content = preg_replace_callback( '/\[siteorigin_widget [^\]]*\]/', array(
			$this,
			'update_shortcode'
		), $content );

		return $content;
	}

	function update_shortcode( $shortcode ) {

		preg_match( '/so_widget_data="([^"]*)"/', stripslashes( $shortcode[0] ), $widget_json );

		$widget_json = html_entity_decode( $widget_json[1] );

		$widget_atts = json_decode( $widget_json, true );

		global $wp_widget_factory;

		$widget = ! empty( $wp_widget_factory->widgets[ $widget_atts['widget_class'] ] ) ? $wp_widget_factory->widgets[ $widget_atts['widget_class'] ] : false;

		if ( ! empty( $widget ) && is_object( $widget ) && is_subclass_of( $widget, 'SiteOrigin_Widget' ) ) {
			/* @var $widget SiteOrigin_Widget */
			$widget_atts['widget_data'] = $widget->update( $widget_atts['widget_data'], $widget_atts['widget_data'] );
		}

		$widget_json = json_encode( $widget_atts );

		$widget_json = htmlentities( $widget_json );

		$widget_json = str_replace( array(
			'[',
			']',
		), array(
			'&#91;',
			'&#93;',
		), $widget_json );

		$slashed = addslashes( 'so_widget_data="' . $widget_json . '"' );

		preg_replace( '/so_widget_data="([^"]*)"/', $slashed, $shortcode );

		return '[siteorigin_widget ' . $slashed . ']';
	}
}

SiteOrigin_Widgets_Bundle_Visual_Composer::single();

if ( class_exists( 'WPBakeryShortCode' ) ) {
	class WPBakeryShortCode_SiteOrigin_Widget extends WPBakeryShortCode {
		public function __construct( $settings ) {
			parent::__construct( $settings );
		}

		public function contentInline( $atts, $content ) {
			$widget_settings = $this->get_widget_settings( $atts );
			ob_start();
			$instance = $this->update_widget( $widget_settings['widget_class'], $widget_settings['widget_data'] );
			$this->render_widget( $widget_settings['widget_class'], $instance );

			return ob_get_clean();
		}

		public function get_widget_settings( $atts ) {
			$unesc = html_entity_decode( $atts['so_widget_data'] );
			return json_decode( $unesc, true );
		}

		private function get_so_widget( $widget_class ) {
			global $wp_widget_factory;

			$widget = ! empty( $wp_widget_factory->widgets[ $widget_class ] ) ? $wp_widget_factory->widgets[ $widget_class ] : false;

			if ( ! empty( $widget ) && is_object( $widget ) && is_subclass_of( $widget, 'SiteOrigin_Widget' ) ) {
				/* @var $widget SiteOrigin_Widget */
				return $widget;
			} else {
				return null;
			}
		}

		public function render_widget( $widget_class, $widget_instance ) {

			if ( empty( $widget_instance ) ) {
				return;
			}

			/* @var $widget SiteOrigin_Widget */
			$widget = $this->get_so_widget( $widget_class );

			if ( ! empty( $widget ) ) {
				$widget->widget( array(), $widget_instance );
			}
		}

		public function update_widget( $widget_class, $widget_instance ) {

			if ( empty( $widget_instance ) ) {
				return;
			}

			/* @var $widget SiteOrigin_Widget */
			$widget = $this->get_so_widget( $widget_class );

			if ( ! empty( $widget ) ) {
				return $widget->update( $widget_instance, $widget_instance );
			} else {
				return $widget_instance;
			}
		}

		/**
		 * @param $atts
		 *
		 * @return array
		 */
		protected function prepareAtts( $atts ) {
			$return = array();
			if ( is_array( $atts ) ) {
				foreach ( $atts as $key => $val ) {
					$return[ $key ] = html_entity_decode( $val );
				}
			}

			return $return;
		}
	}
} // End Class


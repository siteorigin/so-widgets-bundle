<?php
/**
 * PHPUnit bootstrap.
 *
 * Brain Monkey can only redefine a function whose defining file Patchwork
 * rewrote as it was included, and the Composer autoloader does not load
 * Patchwork itself (Brain Monkey pulls it in lazily from its first setUp()).
 * So Patchwork is required explicitly here, before any file that declares a
 * WordPress function; a function declared earlier, or in this bootstrap file
 * itself, throws DefinedTooEarly when a test tries to mock it.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/antecedent/patchwork/Patchwork.php';

/**
 * WordPress functions plugin files call while being required.
 */
require_once __DIR__ . '/fixtures/wp-stubs.php';

if ( ! defined( 'ABSPATH' ) ) {
	// Points at the fixture tree so the base class's
	// require_once ABSPATH . 'wp-admin/includes/file.php' resolves to a fake
	// filesystem instead of WordPress.
	define( 'ABSPATH', __DIR__ . '/fixtures/wp/' );
}

if ( ! defined( 'SOW_BUNDLE_BASE_FILE' ) ) {
	define( 'SOW_BUNDLE_BASE_FILE', __DIR__ . '/../so-widgets-bundle.php' );
}

if ( ! defined( 'SOW_BUNDLE_JS_SUFFIX' ) ) {
	define( 'SOW_BUNDLE_JS_SUFFIX', '' );
}

if ( ! defined( 'SOW_BUNDLE_VERSION' ) ) {
	define( 'SOW_BUNDLE_VERSION', 'dev' );
}

/**
 * Stand-in for WordPress's WP_Widget, the parent of SiteOrigin_Widget. Carries
 * the properties the base class reads and the two field-naming helpers that
 * SiteOrigin_Widget::so_get_field_name() / so_get_field_id() build on, with
 * the same output as WordPress.
 */
if ( ! class_exists( 'WP_Widget' ) ) {
	class WP_Widget {
		public $id_base;
		public $name;
		public $option_name;
		public $widget_options;
		public $control_options;
		public $number = false;
		public $id;

		public function __construct( $id_base, $name, $widget_options = array(), $control_options = array() ) {
			$this->id_base         = strtolower( $id_base );
			$this->name            = $name;
			$this->option_name     = 'widget_' . $this->id_base;
			$this->widget_options  = array_merge(
				array(
					'classname'                   => $this->option_name,
					'customize_selective_refresh' => false,
				),
				(array) $widget_options
			);
			$this->control_options = array_merge( array( 'id_base' => $this->id_base ), (array) $control_options );
		}

		public function get_field_name( $field_name ) {
			$pos = strpos( $field_name, '[' );

			if ( false !== $pos ) {
				$field_name = '[' . substr_replace( $field_name, '][', $pos, strlen( '[' ) );
			} else {
				$field_name = '[' . $field_name . ']';
			}

			return 'widget-' . $this->id_base . '[' . $this->number . ']' . $field_name;
		}

		public function get_field_id( $field_name ) {
			$field_name = str_replace( array( '[]', '[', ']' ), array( '', '-', '' ), $field_name );
			$field_name = trim( $field_name, '-' );

			return 'widget-' . $this->id_base . '-' . $this->number . '-' . $field_name;
		}
	}
}

/**
 * The real widget base class and the field machinery update() depends on. The
 * class loader autoloads every SiteOrigin_Widget_Field_* class from
 * base/inc/fields on first use.
 */
require_once __DIR__ . '/../base/inc/fields/siteorigin-widget-field-class-loader.class.php';
SiteOrigin_Widget_Field_Class_Loader::single();
require_once __DIR__ . '/../base/inc/fields/factory.class.php';
require_once __DIR__ . '/../base/inc/array-utils.php';
require_once __DIR__ . '/../base/siteorigin-widget.class.php';

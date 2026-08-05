<?php
/**
 * Bootstrap for the real-widget-update-chain suite (phpunit-widget.xml).
 *
 * Loads the REAL SiteOrigin_Widget base class, field factory and field
 * autoloader so tests exercise the genuine update() → update_fields() →
 * factory → field sanitize() chain. Everything WordPress is a genuine minimal
 * function definition (Brain Monkey is NOT used in this suite — the real
 * classes call these functions at load time and via function_exists checks).
 *
 * This suite runs in its OWN phpunit process: the default suite defines a
 * SiteOrigin_Widget shim which cannot coexist with the real class.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// ---- WordPress function stand-ins (genuine definitions) ----

function plugin_dir_path( $file ) {
	return rtrim( dirname( $file ), '/\\' ) . '/';
}

function add_action( $tag, $callback = null, $priority = 10, $args = 1 ) {
	return true;
}

function add_filter( $tag, $callback = null, $priority = 10, $args = 1 ) {
	return true;
}

function do_action( $tag, ...$args ) {
}

function apply_filters( $tag, $value = null, ...$args ) {
	return $value;
}

function __( $text, $domain = null ) {
	return $text;
}

function esc_html__( $text, $domain = null ) {
	return $text;
}

function esc_html( $text ) {
	return $text;
}

function esc_attr( $text ) {
	return $text;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, (array) $args );
}

function wp_cache_get( $key, $group = '' ) {
	return false;
}

function wp_cache_set( $key, $value, $group = '', $expire = 0 ) {
	return true;
}

function wp_cache_add( $key, $value, $group = '', $expire = 0 ) {
	return true;
}

// Capability gate consulted by the tinymce field and the base 'text'
// sanitize; per-test controlled.
function current_user_can( $cap, ...$args ) {
	return ! empty( $GLOBALS['sowb_test_user_can'] );
}

function is_user_logged_in() {
	return ! empty( $GLOBALS['sowb_test_logged_in'] );
}

// wp_kses_post(): one shared emulation for every suite — see
// SiteOrigin\Tests\KsesEmulation for what it does and does not guarantee.
// Notably it DOES strip iframes, which the previous two-regex stub did not.
require_once __DIR__ . '/KsesEmulation.php';

function wp_kses_post( $value ) {
	return \SiteOrigin\Tests\KsesEmulation::filter( $value );
}

function sanitize_text_field( $value ) {
	return is_string( $value ) ? trim( strip_tags( $value ) ) : $value;
}

function balanceTags( $text, $force = false ) {
	return $text;
}

function get_the_id() {
	return 0;
}

function is_admin() {
	return false;
}

// delete_css()/save_css() require ABSPATH . 'wp-admin/includes/file.php' and
// then gate all filesystem work on WP_Filesystem(); false makes them no-ops.
define( 'ABSPATH', __DIR__ . '/fixtures/fake-abspath/' );

function WP_Filesystem() {
	return false;
}

// Helpers normally defined in base/base.php, which cannot be loaded wholesale
// (it requires the widget manager, REST routes and meta-box managers with
// load-time side effects).
function siteorigin_widgets_strip_escape_sequences( $value ) {
	return $value;
}

function siteorigin_sanitize_json( $value ) {
	return $value;
}

function siteorigin_widget_onclick( $value ) {
	return $value;
}

// ---- Minimal WP_Widget parent (SiteOrigin_Widget extends it at parse time) ----

class WP_Widget {
	public $id_base;
	public $name;
	public $widget_options;
	public $control_options;

	public function __construct( $id_base, $name, $widget_options = array(), $control_options = array() ) {
		$this->id_base = $id_base;
		$this->name = $name;
		$this->widget_options = $widget_options;
		$this->control_options = $control_options;
	}

	public function get_field_id( $field_name ) {
		return 'widget-' . $this->id_base . '-__i__-' . $field_name;
	}

	public function get_field_name( $field_name ) {
		return 'widget-' . $this->id_base . '[__i__][' . $field_name . ']';
	}
}

// ---- Real production classes ----

require_once dirname( dirname( __DIR__ ) ) . '/base/inc/fields/siteorigin-widget-field-class-loader.class.php';
require_once dirname( dirname( __DIR__ ) ) . '/base/siteorigin-widget.class.php';
require_once dirname( dirname( __DIR__ ) ) . '/base/inc/fields/factory.class.php';

/**
 * Synthetic widget exercising the real update chain with a fixed schema of
 * text-bearing, boolean, repeater and section fields. A synthetic widget
 * avoids shipped-widget file-header/metadata coupling while running exactly
 * the real update() → update_fields() → factory → field sanitize() path.
 */
class SOWB_Test_Harness_Widget extends SiteOrigin_Widget {
	public function __construct() {
		parent::__construct(
			'sowb-test-harness',
			'SOWB Test Harness Widget',
			array(),
			array(),
			array(
				'title' => array(
					'type' => 'text',
					'label' => 'Title',
				),
				'content' => array(
					'type' => 'tinymce',
					'label' => 'Content',
				),
				'flag' => array(
					'type' => 'checkbox',
					'label' => 'Flag',
				),
				'items' => array(
					'type' => 'repeater',
					'label' => 'Items',
					'fields' => array(
						'label' => array(
							'type' => 'text',
							'label' => 'Label',
						),
						'body' => array(
							'type' => 'tinymce',
							'label' => 'Body',
						),
					),
				),
				'design' => array(
					'type' => 'section',
					'label' => 'Design',
					'fields' => array(
						'color' => array(
							'type' => 'text',
							'label' => 'Colour',
						),
					),
				),
			)
		);
	}
}

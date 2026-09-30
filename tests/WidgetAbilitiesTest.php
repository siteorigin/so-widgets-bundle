<?php

use Brain\Monkey\Functions;
use SiteOrigin\Tests\SiteOriginTests;

require_once __DIR__ . '/fixtures/TestWidget.php';

// WordPress and the plugin manager are intentionally absent from this suite.
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $data;

		public function __construct( $code, $message, $data = null ) {
			$this->code = $code;
			$this->data = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $value ) {
		return $value instanceof WP_Error;
	}
}

if ( ! function_exists( 'wp_register_ability' ) ) {
	function wp_register_ability( $name, $args ) {
		return true;
	}
}

if ( ! function_exists( 'wp_register_ability_category' ) ) {
	function wp_register_ability_category( $name, $args ) {
		return true;
	}
}

if ( ! class_exists( 'SiteOrigin_Widgets_Widget_Manager' ) ) {
	class SiteOrigin_Widgets_Widget_Manager {
		public static $instances = array();
		public static $paths = array();

		public static function single() {
			static $instance;
			return $instance ?: $instance = new self();
		}

		public function get_class_from_path( $path ) {
			return self::$paths[ $path ] ?? false;
		}

		public static function get_widget_instance( $class ) {
			return self::$instances[ $class ] ?? null;
		}
	}
}

require_once __DIR__ . '/../base/inc/abilities.php';

class WidgetAbilitiesTest extends SiteOriginTests {
	private $abilities;
	private $registered;

	protected function setUp(): void {
		parent::setUp();
		$this->abilities = SiteOrigin_Widgets_Abilities::single();
		$this->registered = array();

		$widget = new SiteOrigin_Test_Widget();
		SiteOrigin_Widgets_Widget_Manager::$instances = array( SiteOrigin_Test_Widget::class => $widget );
		SiteOrigin_Widgets_Widget_Manager::$paths = array(
			'/widgets/active.php' => SiteOrigin_Test_Widget::class,
			'/widgets/inactive.php' => SiteOrigin_Test_Widget::class,
		);
		SiteOrigin_Widgets_Bundle::single()->test_widgets_list = array(
			array( 'ID' => 'active', 'File' => '/widgets/active.php', 'Name' => 'Active widget', 'Description' => 'Available', 'Active' => true ),
			array( 'ID' => 'inactive', 'File' => '/widgets/inactive.php', 'Name' => 'Inactive widget', 'Description' => 'Unavailable', 'Active' => false ),
		);

		Functions\when( 'wp_normalize_path' )->returnArg();
		Functions\when( 'wp_register_ability' )->alias( function ( $name, $args ) {
			$this->registered[ $name ] = $args;
			return true;
		} );
		Functions\when( 'wp_register_ability_category' )->justReturn( true );
		Functions\expect( 'update_option' )->never();
		Functions\expect( 'wp_insert_post' )->never();
		Functions\expect( 'update_post_meta' )->never();
		$this->abilities->register_ability_category();
		$this->abilities->register_abilities();
	}

	protected function tearDown(): void {
		SiteOrigin_Widgets_Bundle::single()->test_widgets_list = array();
		SiteOrigin_Widgets_Widget_Manager::$instances = array();
		SiteOrigin_Widgets_Widget_Manager::$paths = array();
		parent::tearDown();
	}

	private function execute( $name, $input ) {
		return call_user_func( $this->registered[ $name ]['execute_callback'], $input );
	}

	public function test_registers_both_abilities_with_expected_schemas() {
		$this->assertSame( array( 'sowb/widget-list', 'sowb/widget-describe' ), array_keys( $this->registered ) );
		$list = $this->registered['sowb/widget-list'];
		$describe = $this->registered['sowb/widget-describe'];
		$this->assertSame( 'so-widgets-bundle', $list['category'] );
		$this->assertSame( 'so-widgets-bundle', $describe['category'] );
		$this->assertSame( array( 'type' => 'object', 'additionalProperties' => false, 'default' => array() ), $list['input_schema'] );
		$this->assertSame( 'object', $list['output_schema']['type'] );
		$this->assertSame( array( 'widgets' ), $list['output_schema']['required'] );
		$items = $list['output_schema']['properties']['widgets'];
		$this->assertSame( 'array', $items['type'] );
		$this->assertSame( 'object', $items['items']['type'] );
		$this->assertSame( array( 'id', 'class', 'name', 'description', 'block_name' ), $items['items']['required'] );
		foreach ( array( 'id', 'class', 'name', 'description' ) as $property ) {
			$this->assertSame( 'string', $items['items']['properties'][ $property ]['type'] );
		}
		$this->assertSame( array( 'string', 'null' ), $items['items']['properties']['block_name']['type'] );
		$this->assertSame( 'object', $describe['input_schema']['type'] );
		$this->assertSame( array( 'widget' ), $describe['input_schema']['required'] );
		$this->assertFalse( $describe['input_schema']['additionalProperties'] );
		$this->assertSame( 'string', $describe['input_schema']['properties']['widget']['type'] );
		$this->assertSame( 1, $describe['input_schema']['properties']['widget']['minLength'] );
		$this->assertSame( 'object', $describe['output_schema']['type'] );
		$this->assertSame( array( 'id', 'class', 'name', 'description', 'block_name', 'schema' ), $describe['output_schema']['required'] );
		foreach ( $items['items']['properties'] as $property => $schema ) {
			$this->assertSame( $schema, $describe['output_schema']['properties'][ $property ] );
		}
		$this->assertSame( 'object', $describe['output_schema']['properties']['schema']['type'] );
	}

	public function test_both_abilities_refuse_users_without_edit_posts_at_permission_and_execution() {
		Functions\when( 'current_user_can' )->alias( function ( $capability ) {
			$this->assertSame( 'edit_posts', $capability );
			return false;
		} );
		foreach ( array( 'sowb/widget-list' => array(), 'sowb/widget-describe' => array( 'widget' => 'active' ) ) as $name => $input ) {
			$permission = call_user_func( $this->registered[ $name ]['permission_callback'] );
			$this->assertInstanceOf( WP_Error::class, $permission );
			$this->assertSame( 'sowb_cannot_read_widgets', $permission->get_error_code() );
			$result = $this->execute( $name, $input );
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertSame( 'sowb_cannot_read_widgets', $result->get_error_code() );
		}
	}

	public function test_widget_list_returns_only_active_widgets() {
		$result = $this->execute( 'sowb/widget-list', array() );
		$this->assertSame( array( array(
			'id' => 'active',
			'class' => SiteOrigin_Test_Widget::class,
			'name' => 'Active widget',
			'description' => 'Available',
			'block_name' => null,
		) ), $result['widgets'] );
	}

	public function test_widget_describe_returns_the_describers_schema_for_an_active_widget() {
		$result = $this->execute( 'sowb/widget-describe', array( 'widget' => 'active' ) );
		$this->assertSame( 'active', $result['id'] );
		$this->assertSame( SiteOrigin_Test_Widget::class, $result['class'] );
		$this->assertSame( SiteOrigin_Widgets_Widget_Describer::single()->get_schema( SiteOrigin_Widgets_Widget_Manager::$instances[ SiteOrigin_Test_Widget::class ] ), $result['schema'] );
	}

	public function test_widget_describe_rejects_unknown_and_inactive_widgets() {
		foreach ( array( 'unknown', 'inactive' ) as $widget ) {
			$result = $this->execute( 'sowb/widget-describe', array( 'widget' => $widget ) );
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertSame( 'sowb_widget_unavailable', $result->get_error_code() );
			$this->assertSame( array( 'status' => 404 ), $result->get_error_data() );
		}
	}
}

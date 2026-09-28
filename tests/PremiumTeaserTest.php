<?php

use Brain\Monkey\Functions;
use SiteOrigin\Tests\SiteOriginTests;

if ( ! class_exists( 'SiteOrigin_Premium_Teaser_Test_Widget' ) ) {
	/**
	 * A widget with one teaser that points at the Tabs addon.
	 */
	class SiteOrigin_Premium_Teaser_Test_Widget extends SiteOrigin_Widget {
		public function __construct() {
			parent::__construct( 'so-premium-teaser-test', 'Premium Teaser Test', array( 'has_preview' => false ), array(), false, __DIR__ );
		}

		public function get_widget_form() {
			return array();
		}

		public function get_form_teaser() {
			return $this->premium_teaser(
				'Add tab features with %sSiteOrigin Premium%s',
				'plugin/tabs'
			);
		}
	}
}

/**
 * The Premium URL helper and the upgrade teaser filter.
 */
class PremiumTeaserTest extends SiteOriginTests {
	private $filters = array();

	protected function setUp(): void {
		parent::setUp();

		$this->filters = array();

		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value ) {
				return array_key_exists( $hook, $this->filters ) ? $this->filters[ $hook ] : $value;
			}
		);

		// A minimal add_query_arg() that appends each pair in order, in both
		// the ( $key, $value, $url ) and the ( $args, $url ) forms.
		Functions\when( 'add_query_arg' )->alias(
			function ( ...$args ) {
				if ( is_array( $args[0] ) ) {
					$pairs = $args[0];
					$url   = $args[1];
				} else {
					$pairs = array( $args[0] => $args[1] );
					$url   = $args[2];
				}

				foreach ( $pairs as $key => $value ) {
					$url .= ( strpos( $url, '?' ) === false ? '?' : '&' ) . $key . '=' . $value;
				}

				return $url;
			}
		);
		Functions\when( 'esc_url' )->alias(
			function ( $url ) {
				return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
			}
		);

		// Used by display_teaser_message().
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_user_meta' )->justReturn( '' );
		Functions\when( 'admin_url' )->alias(
			function ( $path = '' ) {
				return 'http://example.test/wp-admin/' . $path;
			}
		);
		Functions\when( 'wp_nonce_url' )->returnArg();
		Functions\when( 'wp_enqueue_style' )->justReturn( null );
		Functions\when( 'esc_html_e' )->echoArg();
	}

	public function test_url_features_the_widgets_bundle() {
		$this->assertSame(
			'https://siteorigin.com/downloads/premium/?featured_plugin=so-widgets-bundle',
			SiteOrigin_Widget::premium_url()
		);
	}

	public function test_url_features_the_addon() {
		$this->assertSame(
			'https://siteorigin.com/downloads/premium/?featured_plugin=so-widgets-bundle&featured_addon=plugin%2Ftabs',
			SiteOrigin_Widget::premium_url( 'plugin/tabs' )
		);
	}

	public function test_url_adds_the_affiliate_id() {
		$this->filters['siteorigin_premium_affiliate_id'] = 'partner 1';

		$this->assertSame(
			'https://siteorigin.com/downloads/premium/?featured_plugin=so-widgets-bundle&featured_addon=plugin%2Ftabs&ref=partner+1',
			SiteOrigin_Widget::premium_url( 'plugin/tabs' )
		);
	}

	public function test_teaser_wraps_the_link_text() {
		$widget = new SiteOrigin_Premium_Teaser_Test_Widget();

		$this->assertSame(
			'Add tab features with <a href="https://siteorigin.com/downloads/premium/?featured_plugin=so-widgets-bundle&amp;featured_addon=plugin%2Ftabs" target="_blank" rel="noopener noreferrer">SiteOrigin Premium</a>',
			$widget->get_form_teaser()
		);
	}

	public function test_teaser_displays_by_default() {
		$widget = new SiteOrigin_Premium_Teaser_Test_Widget();

		ob_start();
		$widget->display_teaser_message();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'siteorigin-widget-teaser', $output );
		$this->assertStringContainsString( 'featured_addon=plugin%2Ftabs', $output );
	}

	public function test_upgrade_teaser_filter_hides_the_teaser() {
		$this->filters['siteorigin_premium_upgrade_teaser'] = false;
		$widget = new SiteOrigin_Premium_Teaser_Test_Widget();

		ob_start();
		$widget->display_teaser_message();

		$this->assertSame( '', ob_get_clean() );
	}
}

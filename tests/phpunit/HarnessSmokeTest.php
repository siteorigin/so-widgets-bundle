<?php

namespace SiteOrigin\Tests;

use Brain\Monkey\Functions;

/**
 * Smoke test locking the harness itself: Brain Monkey stubbing works and the
 * base class's common WordPress mocks are in place.
 */
class HarnessSmokeTest extends SiteOriginTests {
	public function test_brain_monkey_function_stubbing_works() {
		Functions\when( 'get_option' )->justReturn( 'stubbed-value' );
		$this->assertSame( 'stubbed-value', get_option( 'anything' ) );
	}

	public function test_base_class_common_mocks_are_active() {
		$this->assertSame( 'translate-me', __( 'translate-me', 'so-widgets-bundle' ) );
		$this->assertSame( '&lt;tag&gt;', esc_html( '<tag>' ) );
	}
}

<?php

namespace SiteOrigin\Tests\Widget;

use PHPUnit\Framework\TestCase;

/**
 * Tests against the REAL SiteOrigin_Widget::update() chain (update_fields(),
 * field factory, real field sanitizers) using the synthetic harness widget
 * from bootstrap-widget.php.
 *
 * Properties locked:
 * (22) Unknown instance keys are stripped by update_fields(); bookkeeping
 *      keys (_sow_form_id, _sow_form_timestamp, panels_info) survive.
 * (23) The tinymce field bypasses kses for an `unfiltered_html`-capable user
 *      — the widget-level gap that makes the chokepoint's FORCED floor
 *      necessary (locked from the other side by the default suite's
 *      test_kses_floor_forced_despite_unfiltered_html).
 * (24) Repeater items are sanitized recursively; a scalar where an array is
 *      expected does not fatal.
 * (25) _sow_form_timestamp is stamped only when the instance changed.
 */
class WidgetUpdateChainTest extends TestCase {
	/** @var \SOWB_Test_Harness_Widget */
	private $widget;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['sowb_test_user_can'] = false;
		$GLOBALS['sowb_test_logged_in'] = false;
		$this->widget = new \SOWB_Test_Harness_Widget();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['sowb_test_user_can'], $GLOBALS['sowb_test_logged_in'] );
		parent::tearDown();
	}

	public function test_unknown_keys_are_stripped_by_update_fields() {
		$result = $this->widget->update(
			array(
				'title' => 'Hello',
				'evil_key' => 'injected',
				'_sow_form_id' => 'abc123',
				'panels_info' => array( 'class' => 'SOWB_Test_Harness_Widget' ),
			),
			array()
		);

		$this->assertArrayNotHasKey( 'evil_key', $result );
		$this->assertSame( 'Hello', $result['title'] );
		$this->assertSame( 'abc123', $result['_sow_form_id'] );
		$this->assertSame( array( 'class' => 'SOWB_Test_Harness_Widget' ), $result['panels_info'] );
		$this->assertArrayHasKey( '_sow_form_timestamp', $result );
	}

	public function test_tinymce_bypasses_kses_for_capable_user() {
		$hostile = '<script>alert(1)</script><b>ok</b>';

		// Capable credential: the raw value is stored untouched — this is the
		// widget-level `unfiltered_html` bypass the forced floor closes.
		$GLOBALS['sowb_test_user_can'] = true;
		$capable = $this->widget->update( array( 'content' => $hostile ), array() );
		$this->assertSame( $hostile, $capable['content'] );

		// Non-capable credential: wp_kses_post applies.
		$GLOBALS['sowb_test_user_can'] = false;
		$floored = $this->widget->update( array( 'content' => $hostile ), array() );
		$this->assertStringNotContainsString( '<script>', $floored['content'] );
		$this->assertStringContainsString( '<b>ok</b>', $floored['content'] );
	}

	public function test_repeater_items_sanitized_recursively() {
		$result = $this->widget->update(
			array(
				'items' => array(
					array(
						'label' => 'First',
						'body' => '<script>x</script><em>kept</em>',
					),
					'a scalar where an array is expected',
				),
			),
			array()
		);

		$this->assertStringNotContainsString( '<script>', $result['items'][0]['body'] );
		$this->assertStringContainsString( '<em>kept</em>', $result['items'][0]['body'] );
		// The scalar element is coerced by the container sanitize, not fataled on.
		$this->assertIsArray( $result['items'][1] );
	}

	public function test_timestamp_stamped_only_on_change() {
		$first = $this->widget->update(
			array(
				'title' => 'Stable',
				'flag' => true,
			),
			array()
		);
		$this->assertArrayHasKey( '_sow_form_timestamp', $first );

		// Feed the sanitizer's own output back with itself as old: nothing
		// changes, so no new timestamp is stamped.
		$clean = $first;
		unset( $clean['_sow_form_timestamp'] );

		$second = $this->widget->update( $clean, $clean );
		$this->assertArrayNotHasKey( '_sow_form_timestamp', $second );

		// A changed instance is stamped.
		$changed = $clean;
		$changed['title'] = 'Changed';
		$third = $this->widget->update( $changed, $clean );
		$this->assertArrayHasKey( '_sow_form_timestamp', $third );
	}
}

<?php

use SiteOrigin\Tests\SiteOriginTests;

require_once __DIR__ . '/fixtures/TestWidget.php';

/**
 * SiteOrigin_Widget::update() reads the stored instance before sanitizing:
 * modify_instance() migrates it, and delete_css() computes the style hash
 * of the old instance through get_less_variables(). Neither runs
 * add_defaults() first, so a container stored as an empty string reaches
 * widget code as a string.
 */
class WidgetUpdateSavePathTest extends SiteOriginTests {
	protected function setUp(): void {
		parent::setUp();

		SiteOrigin_Test_Widget::$modify_instance_callback = null;
	}

	private function widget() {
		return new SiteOrigin_Test_Widget();
	}

	private function complete_instance() {
		return array(
			'title' => 'Hello',
			'design' => array(
				'text' => 'body',
				'box' => array( 'color' => '#123456' ),
			),
			'shadow' => array( 'color' => '#000000' ),
			'items' => array(
				array( 'text' => 'one', 'meta' => array( 'note' => 'a' ) ),
			),
			'button' => array( 'icon' => array( 'name' => 'star' ) ),
			'query' => 'post_type=post',
		);
	}

	/**
	 * Harness self-check: the fake filesystem lets delete_css() run to the
	 * style hash, so the raw nested read in get_less_variables() executes
	 * against the stored old instance. Without this the save-path tests
	 * would prove nothing.
	 */
	public function test_save_path_reads_the_stored_old_instance() {
		$old = $this->complete_instance();
		$old['design'] = '';

		$this->expectException( TypeError::class );
		$this->expectExceptionMessage( 'Cannot access offset of type string on string' );

		$this->widget()->update( $this->complete_instance(), $old );
	}
}

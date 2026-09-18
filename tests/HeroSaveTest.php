<?php

use SiteOrigin\Tests\SiteOriginTests;

if ( ! class_exists( 'SiteOrigin_Widget_Hero_Widget' ) ) {
	require __DIR__ . '/../widgets/hero/hero.php';
}

/**
 * The Hero widget through the real SiteOrigin_Widget::update() with the
 * stored shape taken from a real site: design and layout sections stored as
 * empty strings and a frame whose button widget carries an empty-string
 * button_icon section. Saving the page holding it fataled at hero.php:552
 * ("Cannot access offset of type string on string") while delete_css()
 * hashed the stored instance.
 */
class HeroSaveTest extends SiteOriginTests {
	private function widget() {
		return new SiteOrigin_Widget_Hero_Widget();
	}

	private static function stored_instance() {
		return json_decode( file_get_contents( __DIR__ . '/fixtures/instances/hero-empty-design.json' ), true );
	}

	public function test_the_stored_shape_is_the_one_that_fataled() {
		$instance = self::stored_instance();

		$this->assertSame( '', $instance['design'] );
		$this->assertSame( '', $instance['layout'] );
		$this->assertSame( '', $instance['frames'][0]['buttons'][0]['button']['button_icon'] );
	}

	public function test_saving_the_stored_instance_repairs_its_empty_sections() {
		$instance = self::stored_instance();

		list( $stored ) = $this->run_capturing_errors(
			fn() => $this->widget()->update( $instance, $instance )
		);

		$this->assertIsArray( $stored['design'] );
		$this->assertIsArray( $stored['layout'] );
		$this->assertIsArray( $stored['frames'][0]['buttons'][0]['button']['button_icon'] );
		$this->assertSame( $instance['frames'][0]['content'], $stored['frames'][0]['content'] );
	}

	public function test_a_second_save_of_sanitised_output_is_byte_identical() {
		$widget = $this->widget();

		list( $first ) = $this->run_capturing_errors(
			fn() => $widget->update( self::stored_instance(), self::stored_instance() )
		);
		unset( $first['_sow_form_timestamp'] );

		list( $second, $errors ) = $this->run_capturing_errors( fn() => $widget->update( $first, $first ) );
		unset( $second['_sow_form_timestamp'] );

		$this->assertSame( $first, $second );
		$this->assertSame( array(), $errors );
	}
}

<?php

use Brain\Monkey\Functions;
use SiteOrigin\Tests\SiteOriginTests;

if ( ! class_exists( 'SiteOrigin_Widget_SocialMediaButtons_Widget' ) ) {
	require __DIR__ . '/../widgets/social-media-buttons/social-media-buttons.php';
}

/**
 * Unit tests for SiteOrigin_Widget_SocialMediaButtons_Widget::less_generate_calls_to().
 *
 * Each case asserts every call built, so a network value can neither add an
 * argument nor add a statement after the call.
 */
class SocialMediaButtonsLessCallsTest extends SiteOriginTests {
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'apply_filters' )->returnArg( 2 );
	}

	private function calls( $networks, $theme = 'atom' ) {
		$widget = new SiteOrigin_Widget_SocialMediaButtons_Widget();

		return $widget->less_generate_calls_to(
			array(
				'networks' => $networks,
				'design'   => array( 'theme' => $theme ),
			),
			array( '.m' )
		);
	}

	public function test_builds_calls_for_ordinary_networks() {
		$this->assertSame(
			".m( @name:facebook-0, @icon_color:#fff, @button_color:#3b5998, @icon_color_hover:#fff, @button_color_hover:#3b5998);\n" .
			'.m( @name:x-twitter-0, @icon_color:rgba(1,2,3,0.5), @button_color:#000, @icon_color_hover:#eee, @button_color_hover:rgb(10, 20, 30));',
			$this->calls(
				array(
					array(
						'name'         => 'facebook',
						'icon_color'   => '#fff',
						'button_color' => '#3b5998',
					),
					array(
						'name'               => 'x-twitter',
						'icon_color'         => 'rgba(1,2,3,0.5)',
						'button_color'       => '#000',
						'icon_color_hover'   => '#eee',
						'button_color_hover' => 'rgb(10, 20, 30)',
					),
				)
			)
		);
	}

	public function test_builds_border_arguments_for_the_wire_theme() {
		$this->assertSame(
			".m( @name:youtube-0, @button_color:hsl(0, 100%, 50%), @button_color_hover:hsl(0, 100%, 50%), @border_color:#123, @border_hover_color:#123);\n" .
			".m( @name:youtube-1, @border_hover_color:'');",
			$this->calls(
				array(
					array(
						'name'         => 'youtube',
						'button_color' => 'hsl(0, 100%, 50%)',
						'border_color' => '#123',
					),
					array( 'name' => 'youtube' ),
				),
				'wire'
			)
		);
	}

	public function test_refused_color_is_not_copied_into_the_hover_fallback() {
		$this->assertSame(
			'.m( @name:facebook-0, @button_color:#3b5998, @button_color_hover:#3b5998);',
			$this->calls(
				array(
					array(
						'name'         => 'facebook',
						'icon_color'   => 'datauri("probe.txt")',
						'button_color' => '#3b5998',
					),
				)
			)
		);
	}

	public function test_refused_hover_color_falls_back_to_the_base_color() {
		$this->assertSame(
			'.m( @name:facebook-0, @icon_color:#fff, @icon_color_hover:#fff);',
			$this->calls(
				array(
					array(
						'name'             => 'facebook',
						'icon_color'       => '#fff',
						'icon_color_hover' => 'red); .marker { a: b; } .m( @name: z',
					),
				)
			)
		);
	}

	public function test_refused_border_color_leaves_the_empty_border_fallback() {
		$this->assertSame(
			".m( @name:facebook-0, @border_hover_color:'');",
			$this->calls(
				array(
					array(
						'name'         => 'facebook',
						'border_color' => 'red, @icon_color:blue',
					),
				),
				'wire'
			)
		);
	}

	public function test_refused_class_drops_only_that_network() {
		$this->assertSame(
			'.m( @name:facebook-0, @icon_color:#fff, @icon_color_hover:#fff);',
			$this->calls(
				array(
					array(
						'name'       => 'q); .marker { a: b; } .m( @name: z',
						'icon_color' => '#fff',
					),
					array(
						'name'       => 'facebook',
						'icon_color' => '#fff',
					),
				)
			)
		);
	}

	public function test_keeps_the_tripadvisor_rename_and_repeat_suffix() {
		$this->assertSame(
			".m( @name:suitcase-0, @icon_color:#FFFFFF, @icon_color_hover:#FFFFFF);\n" .
			'.m( @name:suitcase-1, @icon_color:transparent, @icon_color_hover:transparent);',
			$this->calls(
				array(
					array(
						'name'       => 'suitcase',
						'icon_color' => '#FFFFFF',
					),
					array(
						'name'       => 'suitcase',
						'icon_color' => 'transparent',
					),
				)
			)
		);
	}
}

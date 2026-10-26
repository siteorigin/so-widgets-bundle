<?php

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use SiteOrigin\Tests\SiteOriginTests;

if ( ! class_exists( 'SiteOrigin_Widget_Features_Widget' ) ) {
	require __DIR__ . '/../widgets/features/features.php';
}

/**
 * Pins the CSS and LESS variables of the Features widget, and tests the
 * title margin and icon spacing settings.
 *
 * tests/fixtures/features-css.json holds the output captured before the
 * spacing settings existed. A saved widget that never sets them must keep
 * the same variables, and so the same style hash and CSS.
 *
 * Re-capture (only on a branch whose output is the intended baseline):
 *   SOW_CAPTURE_FIXTURES=1 vendor/bin/phpunit --filter FeaturesSpacingTest
 */
class FeaturesSpacingTest extends SiteOriginTests {
	private const FIXTURE_FILE = __DIR__ . '/fixtures/features-css.json';

	private static $captured = array();

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'siteorigin_widget_get_plugin_dir_path' )->justReturn( __DIR__ . '/../widgets/features/' );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'get_option' )->alias(
			function ( $option, $default = false ) {
				return $default;
			}
		);
	}

	public static function tearDownAfterClass(): void {
		if ( getenv( 'SOW_CAPTURE_FIXTURES' ) && ! empty( self::$captured ) ) {
			$fixture = file_exists( self::FIXTURE_FILE ) ? json_decode( file_get_contents( self::FIXTURE_FILE ), true ) : array();
			$fixture['layouts'] = self::$captured;
			ksort( $fixture['layouts'] );
			file_put_contents(
				self::FIXTURE_FILE,
				json_encode( $fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n"
			);
		}

		parent::tearDownAfterClass();
	}

	private static function fixture() {
		return json_decode( file_get_contents( self::FIXTURE_FILE ), true );
	}

	private function widget() {
		return new SiteOrigin_Widget_Features_Widget();
	}

	private static function instance( $overrides = array() ) {
		$features = array();

		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $position ) {
			$features[] = array(
				'icon' => 'fontawesome-star',
				'container_color' => '#404040',
				'container_position' => $position,
				'title' => 'Feature ' . $position,
				'text' => 'Text',
			);
		}

		$instance = array(
			'features' => $features,
			'fonts' => array(
				'title_options' => array(
					'tag' => 'h5',
					'font' => 'default',
					'size' => '28px',
					'color' => '#111111',
				),
				'text_options' => array(
					'font' => 'default',
					'size' => '16px',
					'color' => '#222222',
				),
				'more_text_options' => array(
					'font' => 'default',
					'size' => '',
					'color' => '',
				),
			),
			'container_size' => '84px',
			'icon_size' => '24px',
			'icon_size_custom' => false,
			'per_row' => 3,
			'responsive' => true,
			'more_text_bottom_align' => false,
			'feature_spacing' => '25px 25px',
			'feature_spacing_mobile' => '25px 25px',
		);

		return array_replace_recursive( $instance, $overrides );
	}

	private function css( $instance ) {
		$css = $this->widget()->get_instance_css( $instance );
		$css = preg_replace( '/\.so-widget-sow-features-default-[0-9a-f]{12} /', '', $css );

		return trim( preg_replace( '/\s+/', ' ', $css ) );
	}

	public static function layouts() {
		return array(
			'per row 3' => array( 'per row 3', array( 'per_row' => 3 ) ),
			'per row 1' => array( 'per row 1', array( 'per_row' => 1 ) ),
			'per row 3, bottom align' => array( 'per row 3, bottom align', array( 'per_row' => 3, 'more_text_bottom_align' => true ) ),
			'per row 1, bottom align' => array( 'per row 1, bottom align', array( 'per_row' => 1, 'more_text_bottom_align' => true ) ),
			'per row 3, custom icon size' => array( 'per row 3, custom icon size', array( 'per_row' => 3, 'icon_size_custom' => true ) ),
			'per row 3, h2 title' => array( 'per row 3, h2 title', array( 'fonts' => array( 'title_options' => array( 'tag' => 'h2' ) ) ) ),
		);
	}

	#[DataProvider( 'layouts' )]
	public function test_saved_widget_keeps_its_variables_and_css( $key, $overrides ) {
		$instance = self::instance( $overrides );
		$widget = $this->widget();

		$actual = array(
			'less_variables' => $widget->get_less_variables( $instance ),
			'css' => $this->css( $instance ),
		);

		if ( getenv( 'SOW_CAPTURE_FIXTURES' ) ) {
			self::$captured[ $key ] = $actual;
			$this->assertNotEmpty( $actual['css'] );

			return;
		}

		$fixture = self::fixture();

		$this->assertArrayHasKey( $key, $fixture['layouts'] );
		$this->assertSame( $fixture['layouts'][ $key ]['less_variables'], $actual['less_variables'] );
		$this->assertSame( $fixture['layouts'][ $key ]['css'], $actual['css'] );
	}

	private static function with_title_margins( $top, $bottom, $overrides = array() ) {
		return self::instance(
			array_replace_recursive(
				array(
					'fonts' => array(
						'title_options' => array(
							'margin_top' => $top,
							'margin_bottom' => $bottom,
						),
					),
				),
				$overrides
			)
		);
	}

	/**
	 * The declarations of the first rule whose selector ends with $selector.
	 */
	private function rule( $css, $selector ) {
		$this->assertSame( 1, preg_match( '/' . preg_quote( $selector, '/' ) . ' \{([^{}]*)\}/', $css, $match ), $selector . ' in ' . $css );

		return trim( $match[1] );
	}

	public function test_title_bottom_margin_only_sets_the_bottom_of_the_title() {
		$css = $this->css( self::with_title_margins( false, '8px' ) );
		$title = $this->rule( $css, '.sow-features-feature .sow-features-feature-title' );

		$this->assertStringContainsString( 'margin-bottom: 8px;', $title );
		$this->assertStringNotContainsString( 'margin-top', $title );
		$this->assertSame( 'margin: 1em 0;', $this->rule( $css, '.sow-features-feature h5' ) );
	}

	public function test_title_top_margin_only_sets_the_top_of_the_title() {
		$css = $this->css( self::with_title_margins( '0.5em', false ) );
		$title = $this->rule( $css, '.sow-features-feature .sow-features-feature-title' );

		$this->assertStringContainsString( 'margin-top: 0.5em;', $title );
		$this->assertStringNotContainsString( 'margin-bottom', $title );
	}

	public function test_zero_title_margin_is_kept() {
		$instance = self::with_title_margins( '0px', '0px' );
		$vars = $this->widget()->get_less_variables( $instance );
		$title = $this->rule( $this->css( $instance ), '.sow-features-feature .sow-features-feature-title' );

		$this->assertSame( '0px', $vars['title_margin_top'] );
		$this->assertSame( '0px', $vars['title_margin_bottom'] );
		$this->assertStringContainsString( 'margin-top: 0px;', $title );
		$this->assertStringContainsString( 'margin-bottom: 0px;', $title );
	}

	public function test_empty_title_margins_add_no_variable_and_no_declaration() {
		foreach ( array( false, '', null ) as $empty ) {
			$instance = self::with_title_margins( $empty, $empty );
			$vars = $this->widget()->get_less_variables( $instance );
			$title = $this->rule( $this->css( $instance ), '.sow-features-feature .sow-features-feature-title' );

			$this->assertArrayNotHasKey( 'title_margin_top', $vars );
			$this->assertArrayNotHasKey( 'title_margin_bottom', $vars );
			$this->assertStringNotContainsString( 'margin', $title );
		}
	}

	public function test_title_margin_fields_are_measurements_in_the_title_group() {
		$form = $this->widget()->get_widget_form();
		$fields = $form['fonts']['fields']['title_options']['fields'];

		$this->assertSame( array( 'tag', 'font', 'size', 'color', 'margin_top', 'margin_bottom' ), array_keys( $fields ) );

		foreach ( array( 'margin_top', 'margin_bottom' ) as $name ) {
			$this->assertSame( 'measurement', $fields[ $name ]['type'] );
			$this->assertSame( array( 'px', 'em', 'rem' ), $fields[ $name ]['units'] );
			$this->assertArrayNotHasKey( 'default', $fields[ $name ] );
		}

		$this->assertNotEmpty( $fields['margin_bottom']['description'] );
	}

	/**
	 * With the bottom aligned More link the text column is a flex container,
	 * so the title and the text block are flex items. Their margins add up
	 * instead of collapsing, and the first paragraph of the text keeps its
	 * own top margin below the title.
	 */
	public function test_bottom_aligned_text_column_is_flex_so_margins_add() {
		$css = $this->css( self::with_more_text( self::instance( array( 'more_text_bottom_align' => true ) ) ) );

		$this->assertStringContainsString( '.sow-features-feature .textwidget { display: flex; flex-direction: column;', $css );
	}

	public function test_title_bottom_margin_is_the_whole_gap_to_a_text_that_starts_with_a_paragraph() {
		foreach ( array( false, true ) as $bottom_align ) {
			$css = $this->css( self::with_title_margins( null, '8px', array( 'more_text_bottom_align' => $bottom_align ) ) );
			$rules = $this->rules( $css )['base'];
			$first_paragraph = array_filter(
				$rules,
				function ( $rule ) {
					return substr( $rule[0], -strlen( '.sow-features-feature-title + .sow-features-feature-text > p:first-child' ) ) === '.sow-features-feature-title + .sow-features-feature-text > p:first-child';
				}
			);

			$this->assertCount( 1, $first_paragraph );
			$this->assertSame( 'margin-top: 0;', reset( $first_paragraph )[1] );
		}
	}

	public function test_zero_title_bottom_margin_also_clears_the_first_paragraph_margin() {
		$this->assertStringContainsString( '.sow-features-feature-title + .sow-features-feature-text > p:first-child { margin-top: 0; }', $this->css( self::with_title_margins( null, '0px' ) ) );
	}

	public function test_first_paragraph_margin_stays_when_the_title_bottom_margin_is_empty() {
		foreach ( array( null, '' ) as $bottom ) {
			$this->assertStringNotContainsString( 'p:first-child', $this->css( self::with_title_margins( '4px', $bottom, array() ) ) );
		}
	}

	private static function with_icon_spacing( $spacing, $overrides = array() ) {
		return self::instance( array_replace_recursive( array( 'icon_spacing' => $spacing ), $overrides ) );
	}

	/**
	 * Every compiled rule as selector => declarations, with the rules inside
	 * the media query listed under "@media" and the rest under "base".
	 */
	private function rules( $css ) {
		$media_start = strpos( $css, '@media (max-width' );
		$base = $media_start === false ? $css : substr( $css, 0, $media_start );
		$media = $media_start === false ? '' : substr( $css, $media_start );

		return array(
			'base' => $this->split_rules( $base ),
			'media' => $this->split_rules( $media ),
		);
	}

	private function split_rules( $css ) {
		preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $css, $matches, PREG_SET_ORDER );
		$rules = array();

		foreach ( $matches as $match ) {
			$rules[] = array( trim( $match[1] ), trim( $match[2] ) );
		}

		return $rules;
	}

	/**
	 * Declarations that apply to a position's icon container: the
	 * declarations of every rule that names the position's class and ends in
	 * the icon container, in order.
	 */
	private function icon_declarations( $rules, $position ) {
		$out = array();

		foreach ( $rules as $rule ) {
			foreach ( explode( ',', $rule[0] ) as $selector ) {
				$selector = trim( $selector );

				if ( strpos( $selector, 'sow-icon-container-position-' . $position ) !== false && substr( $selector, -strlen( ' .sow-icon-container' ) ) === ' .sow-icon-container' ) {
					$out[] = $rule[1];
					break;
				}
			}
		}

		return implode( ' ', $out );
	}

	public function test_icon_spacing_field_is_a_measurement_after_the_custom_icon_size() {
		$form = $this->widget()->get_widget_form();
		$keys = array_keys( $form );
		$field = $form['icon_spacing'];

		$this->assertSame( array_search( 'icon_size_custom', $keys ) + 1, array_search( 'icon_spacing', $keys ) );
		$this->assertSame( 'measurement', $field['type'] );
		$this->assertSame( array( 'px', 'rem' ), $field['units'] );
		$this->assertArrayNotHasKey( 'default', $field );
		$this->assertNotEmpty( $field['description'] );
	}

	public function test_icon_spacing_is_passed_to_less_as_the_string_true() {
		$vars = $this->widget()->get_less_variables( self::with_icon_spacing( '20px' ) );

		$this->assertSame( '20px', $vars['icon_spacing'] );
		$this->assertSame( 'true', $vars['icon_spacing_custom'] );
	}

	public function test_empty_icon_spacing_adds_no_variable() {
		foreach ( array( false, '', null ) as $empty ) {
			$vars = $this->widget()->get_less_variables( self::with_icon_spacing( $empty ) );

			$this->assertArrayNotHasKey( 'icon_spacing', $vars );
			$this->assertArrayNotHasKey( 'icon_spacing_custom', $vars );
		}
	}

	public function test_icon_spacing_sets_the_text_side_gap_for_each_position() {
		$rules = $this->rules( $this->css( self::with_icon_spacing( '20px' ) ) )['base'];

		$this->assertStringContainsString( 'margin: 0 auto 20px;', $this->icon_declarations( $rules, 'top' ) );
		$this->assertStringContainsString( 'margin-right: 20px;', $this->icon_declarations( $rules, 'right' ) );
		$this->assertStringContainsString( 'margin-left: 20px;', $this->icon_declarations( $rules, 'left' ) );
		$this->assertStringContainsString( 'margin-top: 20px;', $this->icon_declarations( $rules, 'bottom' ) );
		$this->assertStringNotContainsString( '10px', $this->css( self::with_icon_spacing( '20px' ) ) );
	}

	public function test_icon_spacing_sets_the_gap_beside_the_text_with_the_bottom_aligned_more_link() {
		$css = $this->css( self::with_icon_spacing( '20px', array( 'more_text_bottom_align' => true ) ) );

		$this->assertStringNotContainsString( '10px', $css );
		$this->assertStringContainsString( '.sow-icon-container-position-right .sow-features-feature-right-left-container .sow-icon-container { margin-right: 20px; margin-left: 0; }', $css );
		$this->assertStringContainsString( '.sow-icon-container-position-left .sow-features-feature-right-left-container .sow-icon-container { margin-left: 20px; margin-right: 0; }', $css );
	}

	public function test_icon_spacing_applies_to_one_feature_per_row_after_the_auto_margin() {
		$rules = $this->rules( $this->css( self::with_icon_spacing( '20px', array( 'per_row' => 1 ) ) ) )['base'];

		$this->assertMatchesRegularExpression( '/margin: auto;.*margin-bottom: 20px;/', $this->icon_declarations( $rules, 'top' ) );
		$this->assertMatchesRegularExpression( '/margin: auto;.*margin-top: 20px;/', $this->icon_declarations( $rules, 'bottom' ) );
	}

	public function test_icon_spacing_holds_on_mobile_for_left_and_right_icons() {
		$media = $this->rules( $this->css( self::with_icon_spacing( '20px' ) ) )['media'];

		foreach ( array( 'left', 'right' ) as $position ) {
			$this->assertStringContainsString( 'margin-bottom: 20px;', $this->icon_declarations( $media, $position ) );
		}

		$this->assertSame( '', $this->icon_declarations( $media, 'top' ) );
		$this->assertSame( '', $this->icon_declarations( $media, 'bottom' ) );
	}

	/**
	 * The rendered list for an instance, so the wrapper markup is real.
	 */
	private function render( $instance ) {
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'do_shortcode' )->returnArg();
		Functions\when( 'sow_esc_url' )->returnArg();
		Functions\when( 'siteorigin_widget_get_icon' )->justReturn( '<span class="sow-icon"></span>' );
		Functions\when( 'siteorigin_widget_valid_tag' )->returnArg();
		Functions\when( 'wp_enqueue_style' )->justReturn( null );

		$instance += array(
			'icon_size_unit' => 'px',
			'icon_link' => false,
			'title_link' => false,
			'new_window' => false,
		);
		$widget = $this->widget();
		$variables = $widget->get_template_variables( $instance, array() );

		return ( function () use ( $widget, $instance, $variables ) {
			extract( $variables );
			ob_start();
			include __DIR__ . '/../widgets/features/tpl/default.php';

			return ob_get_clean();
		} )->call( $widget );
	}

	private static function with_more_text( $instance ) {
		foreach ( $instance['features'] as $i => $feature ) {
			$instance['features'][ $i ]['more_text'] = 'Read more';
		}

		return $instance;
	}

	private static function specificity( $selector ) {
		return preg_match_all( '/\.[\w-]+|:(?!:)[\w-]+/', $selector );
	}

	/**
	 * The value that wins for a property on the icon container inside the
	 * right-left wrapper, in the stacked layout (list is responsive, the
	 * media query is active): the highest specificity, then the later rule.
	 */
	private function winning_wrapper_icon_value( $css, $position, $property ) {
		$rules = $this->rules( $css );
		$best = null;
		$order = 0;

		foreach ( array_merge( $rules['base'], $rules['media'] ) as $rule ) {
			$order++;

			foreach ( explode( ',', $rule[0] ) as $selector ) {
				$selector = trim( $selector );

				if ( substr( $selector, -strlen( ' .sow-icon-container' ) ) !== ' .sow-icon-container' || strpos( $selector, '.sow-icon-container-position-' . $position ) === false ) {
					continue;
				}

				if ( strpos( $selector, '.sow-features-responsive' ) !== false && strpos( $selector, '.sow-features-responsive' ) !== strpos( $selector, '.sow-features-list' ) + strlen( '.sow-features-list' ) ) {
					continue;
				}

				if ( ! preg_match( '/(?:^|;|\s)' . preg_quote( $property, '/' ) . ':\s*([^;]+);?/', $rule[1] . ';', $m ) ) {
					continue;
				}

				$key = array( self::specificity( $selector ), $order );

				if ( $best === null || $key >= $best[0] ) {
					$best = array( $key, trim( $m[1] ) );
				}
			}
		}

		return $best === null ? null : $best[1];
	}

	/**
	 * XPath for the elements a simple selector matches: tag, classes,
	 * :first-child, and the descendant, child and adjacent sibling
	 * combinators. Returns null for any other syntax, so a selector this
	 * does not understand never counts as a match.
	 */
	private static function selector_to_xpath( $selector ) {
		$parts = preg_split( '/\s*([>+])\s*|\s+/', trim( $selector ), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		$xpath = null;
		$combinator = null;

		foreach ( $parts as $part ) {
			if ( $part === '>' || $part === '+' ) {
				$combinator = $part;
				continue;
			}

			if ( ! preg_match( '/^([a-z][a-z0-9]*)?((?:\.[\w-]+)*)((?::first-child)?)$/i', $part, $m ) ) {
				return null;
			}

			$tests = array();

			if ( $m[1] !== '' ) {
				$tests[] = 'self::' . strtolower( $m[1] );
			}

			foreach ( array_filter( explode( '.', $m[2] ) ) as $class ) {
				$tests[] = "contains(concat(' ', normalize-space(@class), ' '), ' " . $class . " ')";
			}

			if ( $m[3] !== '' ) {
				$tests[] = 'not(preceding-sibling::*)';
			}

			if ( $xpath !== null ) {
				$axis = array( '>' => 'parent::*', '+' => 'preceding-sibling::*[1]' );
				$tests[] = ( $combinator === null ? 'ancestor::*' : $axis[ $combinator ] ) . '[' . $xpath . ']';
			}

			$xpath = $tests ? implode( ' and ', $tests ) : 'true()';
			$combinator = null;
		}

		return $xpath;
	}

	private static function dom( $html ) {
		$doc = new DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8"?><html><body>' . $html . '</body></html>' );
		libxml_clear_errors();

		return $doc;
	}

	/**
	 * Whether a selector matches the element.
	 */
	private static function selector_matches( DOMDocument $doc, DOMElement $element, $selector ) {
		$xpath = self::selector_to_xpath( $selector );

		if ( $xpath === null ) {
			return false;
		}

		$found = ( new DOMXPath( $doc ) )->query( '//*[' . $xpath . ']' );

		foreach ( $found as $node ) {
			if ( $node->isSameNode( $element ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The value that wins for a property on an element: among the rules
	 * whose selector matches the element, the highest specificity, then the
	 * later rule. $mobile adds the rules inside the media query, as when the
	 * viewport is below the breakpoint. Null when no rule sets the property.
	 */
	private function winning_value( $css, DOMDocument $doc, DOMElement $element, $property, $mobile ) {
		$rules = $this->rules( $css );
		$best = null;
		$order = 0;

		foreach ( $mobile ? array_merge( $rules['base'], $rules['media'] ) : $rules['base'] as $rule ) {
			$order++;

			if ( ! preg_match( '/(?:^|;|\s)' . preg_quote( $property, '/' ) . ':\s*([^;]+);?/', $rule[1] . ';', $value ) ) {
				continue;
			}

			foreach ( explode( ',', $rule[0] ) as $selector ) {
				if ( ! self::selector_matches( $doc, $element, trim( $selector ) ) ) {
					continue;
				}

				$key = array( self::specificity( $selector ), $order );

				if ( $best === null || $key >= $best[0] ) {
					$best = array( $key, trim( $value[1] ) );
				}
			}
		}

		return $best === null ? null : $best[1];
	}

	/**
	 * The first paragraph of each feature's text, as a list of
	 * array( has a title, paragraph element ).
	 */
	private static function first_paragraphs( DOMDocument $doc ) {
		$out = array();

		foreach ( $doc->getElementsByTagName( 'li' ) as $feature ) {
			$title = ( new DOMXPath( $doc ) )->query( './/*[contains(@class, "sow-features-feature-title")]', $feature )->item( 0 );
			$paragraph = ( new DOMXPath( $doc ) )->query( './/*[contains(@class, "sow-features-feature-text")]/p[1]', $feature )->item( 0 );
			$out[] = array( $title !== null, $paragraph );
		}

		return $out;
	}

	private static function mixed_title_instance( $overrides = array() ) {
		$instance = self::instance( $overrides );

		foreach ( $instance['features'] as $i => $feature ) {
			$instance['features'][ $i ]['text'] = '<p>Text ' . $feature['container_position'] . '</p>';

			if ( $i % 2 ) {
				$instance['features'][ $i ]['title'] = '';
			}
		}

		return $instance;
	}

	/**
	 * The title Bottom margin is the gap to the text, so it clears the
	 * paragraph margin only for a feature that has a title. A feature with
	 * no title keeps the paragraph margin.
	 */
	public function test_title_bottom_margin_clears_the_paragraph_margin_only_below_a_title() {
		foreach ( array( false, true ) as $bottom_align ) {
			$instance = self::mixed_title_instance( array( 'fonts' => array( 'title_options' => array( 'margin_bottom' => '8px' ) ), 'more_text_bottom_align' => $bottom_align ) );
			$css = $this->css( $instance );
			$doc = self::dom( $this->render( $instance ) );
			$paragraphs = self::first_paragraphs( $doc );

			$this->assertCount( 4, $paragraphs );
			$this->assertSame( array( true, false, true, false ), array_column( $paragraphs, 0 ) );

			foreach ( $paragraphs as $i => list( $has_title, $paragraph ) ) {
				$this->assertSame( $has_title ? '0' : null, $this->winning_value( $css, $doc, $paragraph, 'margin-top', false ), 'first paragraph of feature ' . $i );
			}
		}
	}

	public function test_bottom_aligned_more_link_puts_the_side_icons_in_a_wrapper() {
		$html = $this->render( self::with_more_text( self::with_icon_spacing( '20px', array( 'more_text_bottom_align' => true ) ) ) );

		$this->assertSame( 2, substr_count( $html, 'sow-features-feature-right-left-container' ) );
	}

	public function test_mobile_stacked_side_icon_in_the_wrapper_loses_its_side_margin_and_gets_the_spacing_below() {
		$css = $this->css( self::with_more_text( self::with_icon_spacing( '20px', array( 'more_text_bottom_align' => true ) ) ) );

		$this->assertSame( '0', $this->winning_wrapper_icon_value( $css, 'left', 'margin-left' ) );
		$this->assertSame( '0', $this->winning_wrapper_icon_value( $css, 'right', 'margin-right' ) );
		$this->assertSame( '20px', $this->winning_wrapper_icon_value( $css, 'left', 'margin-bottom' ) );
		$this->assertSame( '20px', $this->winning_wrapper_icon_value( $css, 'right', 'margin-bottom' ) );
	}

	public function test_empty_icon_spacing_adds_no_guarded_rule() {
		$one_per_row = $this->css( self::instance( array( 'per_row' => 1 ) ) );
		$rules = $this->rules( $one_per_row );

		$this->assertStringEndsWith( 'margin: auto;', $this->icon_declarations( $rules['base'], 'top' ) );
		$this->assertStringEndsWith( 'margin: auto;', $this->icon_declarations( $rules['base'], 'bottom' ) );
		$this->assertStringNotContainsString( 'margin-bottom', $this->icon_declarations( $rules['media'], 'left' ) );
		$this->assertStringNotContainsString( 'margin-bottom', $this->icon_declarations( $rules['media'], 'right' ) );
	}
}

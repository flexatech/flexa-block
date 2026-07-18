<?php
/**
 * Tests for the CSS_Builder engine — the core every block reuses.
 *
 * @package Flexa\Block
 */

use PHPUnit\Framework\TestCase;
use Flexa\Block\CSS_Builder;

/**
 * @covers \Flexa\Block\CSS_Builder
 */
class CssBuilderTest extends TestCase {

	public function test_renders_basic_rule(): void {
		$css = new CSS_Builder();
		$css->set_selector( '.a' )->add_property( 'color', 'red' );
		$this->assertSame( '.a{color:red;}', $css->get_output() );
	}

	public function test_merges_properties_under_one_selector(): void {
		$css = new CSS_Builder();
		$css->set_selector( '.a' )
			->add_property( 'color', 'red' )
			->add_property( 'padding', '10px' );
		$this->assertSame( '.a{color:red;padding:10px;}', $css->get_output() );
	}

	public function test_skips_empty_value_but_keeps_zero(): void {
		$css = new CSS_Builder();
		$css->set_selector( '.x' )
			->add_property( 'margin', '' )   // dropped
			->add_property( 'z-index', 0 );  // kept
		$this->assertSame( '.x{z-index:0;}', $css->get_output() );
	}

	public function test_strips_angle_brackets_to_prevent_style_breakout(): void {
		$css = new CSS_Builder();
		$css->set_selector( '.x' )->add_property( 'content', '</style><script>' );
		$out = $css->get_output();
		$this->assertStringNotContainsString( '<', $out );
		$this->assertStringNotContainsString( '>', $out );
	}

	public function test_selector_strips_left_angle_bracket_to_prevent_style_breakout(): void {
		// Defense in depth: even a selector fed unsanitized must not be able to close
		// the inline <style> block. Only "<" is stripped (no "<" → no "</style>");
		// ">" stays because it is a legitimate child combinator.
		$css = new CSS_Builder();
		$css->set_selector( '.flexa-container-</style><script>alert(1)</script>' )->add_property( 'color', 'red' );
		$out = $css->get_output();
		$this->assertStringNotContainsString( '<', $out );
		$this->assertStringNotContainsString( '</style>', $out );
		$this->assertStringNotContainsString( '<script', $out );
	}

	public function test_selector_keeps_child_combinator(): void {
		// The ">" child combinator must survive the "<"-strip.
		$css = new CSS_Builder();
		$css->set_selector( '.a > .b' )->add_property( 'color', 'red' );
		$this->assertSame( '.a > .b{color:red;}', $css->get_output() );
	}

	public function test_value_strips_rule_breakout_chars(): void {
		// A value carrying "}", "{" or ";" must not be able to inject extra rules.
		$css = new CSS_Builder();
		$css->set_selector( '.a' )->add_property( 'background', 'red;}body{display:none' );
		$out = $css->get_output();
		$this->assertStringNotContainsString( '}body{', $out );
		$this->assertSame( '.a{background:redbodydisplay:none;}', $out );
	}

	public function test_tablet_wraps_in_max_width_media_query(): void {
		$css = new CSS_Builder();
		$css->start_media_query( 'tablet' );
		$css->set_selector( '.a' )->add_property( 'display', 'block' );
		$css->end_media_query();
		$this->assertSame( '@media (max-width: 1024px){.a{display:block;}}', $css->get_output() );
	}

	public function test_mobile_wraps_in_max_width_media_query(): void {
		$css = new CSS_Builder();
		$css->start_media_query( 'mobile' );
		$css->set_selector( '.a' )->add_property( 'display', 'block' );
		$css->end_media_query();
		$this->assertSame( '@media (max-width: 767px){.a{display:block;}}', $css->get_output() );
	}

	public function test_base_rules_render_before_media_queries(): void {
		$css = new CSS_Builder();
		$css->set_selector( '.base' )->add_property( 'color', 'red' );
		$css->start_media_query( 'tablet' );
		$css->set_selector( '.tab' )->add_property( 'color', 'blue' );
		$css->end_media_query();

		$out = $css->get_output();
		$this->assertLessThan(
			strpos( $out, '@media' ),
			strpos( $out, '.base' ),
			'Base rule must render before the media query.'
		);
	}
}

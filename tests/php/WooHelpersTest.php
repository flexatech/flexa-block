<?php
/**
 * Tests for Woo_Helpers::trim_words_html().
 *
 * The point of this trimmer is what `wp_trim_words()` cannot do: cap an excerpt
 * at N words while leaving the shop owner's markup intact. A capped excerpt used
 * to come back as plain text, which silently threw away every link — and with
 * it, every link color the block had been given.
 *
 * @package Flexa\Block
 */

use PHPUnit\Framework\TestCase;
use Flexa\Block\Woo_Helpers;

/**
 * @covers \Flexa\Block\Woo_Helpers::trim_words_html
 */
class WooHelpersTest extends TestCase {

	public function test_zero_or_negative_limit_returns_the_input(): void {
		$html = '<p>One two three four five</p>';
		$this->assertSame( $html, Woo_Helpers::trim_words_html( $html, 0 ) );
		$this->assertSame( $html, Woo_Helpers::trim_words_html( $html, -5 ) );
	}

	public function test_empty_input_returns_the_input(): void {
		$this->assertSame( '', Woo_Helpers::trim_words_html( '', 5 ) );
	}

	public function test_text_shorter_than_the_cap_is_untouched(): void {
		// Nothing was dropped, so nothing gains an ellipsis.
		$html = '<p>One two three</p>';
		$this->assertSame( $html, Woo_Helpers::trim_words_html( $html, 10 ) );
	}

	public function test_text_exactly_at_the_cap_is_untouched(): void {
		// The off-by-one that used to add "…" to an excerpt that already fitted.
		$html = '<p>One two three</p>';
		$this->assertSame( $html, Woo_Helpers::trim_words_html( $html, 3 ) );
	}

	public function test_a_link_inside_the_cap_survives(): void {
		// The whole reason this helper exists.
		$html = '<p>One two <a href="/x">three four</a> five six seven</p>';
		$out  = Woo_Helpers::trim_words_html( $html, 4 );
		$this->assertStringContainsString( '<a href="/x">three four</a>', $out );
		$this->assertStringNotContainsString( 'five', $out );
	}

	public function test_open_tags_are_closed_at_the_cut(): void {
		$html = '<p>Alpha <strong>beta <em>gamma</em></strong> delta epsilon</p>';
		$out  = Woo_Helpers::trim_words_html( $html, 3 );
		$this->assertStringEndsWith( '</p>', $out );
		$this->assertSame( substr_count( $out, '<strong' ), substr_count( $out, '</strong>' ) );
		$this->assertSame( substr_count( $out, '<p' ), substr_count( $out, '</p>' ) );
	}

	public function test_a_cut_inside_a_link_closes_the_link(): void {
		$html = '<p>One <a href="/x">two three four</a> five</p>';
		$out  = Woo_Helpers::trim_words_html( $html, 2 );
		$this->assertSame( 1, substr_count( $out, '</a>' ) );
		$this->assertStringNotContainsString( 'three', $out );
	}

	public function test_the_ellipsis_lands_inside_the_markup(): void {
		// Outside the closing tag it would render as a stray glyph on its own line.
		$out = Woo_Helpers::trim_words_html( '<p>One two three four</p>', 2 );
		$this->assertStringContainsString( 'two&hellip;</p>', $out );
	}

	public function test_a_custom_more_string_is_used(): void {
		$out = Woo_Helpers::trim_words_html( '<p>One two three four</p>', 2, ' [more]' );
		$this->assertStringContainsString( 'two [more]</p>', $out );
	}

	public function test_void_elements_are_not_treated_as_open_tags(): void {
		// An <img> needs no closing tag; counting it would corrupt the output.
		$out = Woo_Helpers::trim_words_html( '<p>Image <img src="a.png"> after words here</p>', 3 );
		$this->assertStringNotContainsString( '</img>', $out );
		$this->assertStringEndsWith( '</p>', $out );
	}

	public function test_self_closing_tags_are_not_treated_as_open_tags(): void {
		$out = Woo_Helpers::trim_words_html( '<p>One<br /> two three four five</p>', 3 );
		$this->assertStringNotContainsString( '</br>', $out );
		$this->assertStringEndsWith( '</p>', $out );
	}

	public function test_multiple_blocks_are_capped_across_the_whole_excerpt(): void {
		// The cap is a word budget for the excerpt, not per paragraph.
		$out = Woo_Helpers::trim_words_html( '<p>One two three</p><p>four five six</p>', 4 );
		$this->assertStringContainsString( 'One two three', $out );
		$this->assertStringContainsString( 'four', $out );
		$this->assertStringNotContainsString( 'five', $out );
	}

	public function test_plain_text_without_markup_still_trims(): void {
		$out = Woo_Helpers::trim_words_html( 'One two three four', 2 );
		$this->assertSame( 'One two&hellip;', $out );
	}
}

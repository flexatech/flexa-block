<?php
/**
 * Tests for the Slide block CSS generator (child of flexa/slides).
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Slide_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Slide_CSS
 */
class SlideCssTest extends CssTestCase {

	private const SLIDE   = '.flexa-slide-a';
	private const CONTENT = '.flexa-slide-a > .flexa-slide__content';

	/**
	 * Convenience wrapper around the Slide generator.
	 *
	 * @param array $attrs Slide attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Slide_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_slide_emits_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_padding_and_margin_on_slide(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'desktop' => [
				'padding' => [ 'top' => '40', 'right' => '40', 'bottom' => '40', 'left' => '40', 'unit' => 'px' ],
				'margin'  => [ 'top' => '0', 'right' => '10', 'bottom' => '0', 'left' => '10', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, self::SLIDE, 'padding:40px 40px 40px 40px' );
		$this->assertCssHas( $css, self::SLIDE, 'margin:0px 10px 0px 10px' );
	}

	public function test_content_alignment_on_slide(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'layout'  => [ 'desktop' => [ 'justifyContent' => 'flex-end', 'alignItems' => 'flex-start' ] ],
		] );
		$this->assertCssHas( $css, self::SLIDE, 'justify-content:flex-end' );
		$this->assertCssHas( $css, self::SLIDE, 'align-items:flex-start' );
	}

	public function test_gap_on_content_wrapper(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'layout'  => [ 'desktop' => [ 'gap' => [ 'row' => '16', 'column' => '24', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'row-gap:16px' );
		$this->assertCssHas( $css, self::CONTENT, 'column-gap:24px' );
	}

	public function test_content_max_width_base_and_responsive(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'contentMaxWidth' => [
				'desktop' => [ 'value' => '800', 'unit' => 'px' ],
				'tablet'  => [ 'value' => '90', 'unit' => '%' ],
			],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'max-width:800px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::CONTENT, 'max-width:90%' );
	}

	public function test_background_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#f9fafb', 'dark' => '#111827' ] ],
		] );
		$this->assertCssHas( $css, self::SLIDE, 'background-color:#f9fafb' );
		$this->assertCssHasInDark( $css, self::SLIDE, 'background-color:#111827' );
	}

	public function test_background_gradient_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'gradient', 'gradient' => [ 'light' => 'linear-gradient(90deg,#fff,#000)', 'dark' => 'linear-gradient(90deg,#000,#fff)' ] ],
		] );
		$this->assertCssHas( $css, self::SLIDE, 'background-image:linear-gradient(90deg,#fff,#000)' );
		$this->assertCssHasInDark( $css, self::SLIDE, 'background-image:linear-gradient(90deg,#000,#fff)' );
	}

	public function test_background_none_emits_nothing(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'none', 'color' => [ 'light' => '#f9fafb' ] ],
		] );
		$this->assertStringNotContainsString( 'background', $css );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#f9fafb', 'dark' => '#111827' ] ],
		] );
		$this->assertCssHasInDark( $css, self::SLIDE, 'background-color:#111827', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}

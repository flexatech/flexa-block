<?php
/**
 * Tests for the Product Excerpt block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Product_Excerpt_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Product_Excerpt_CSS
 */
class ProductExcerptCssTest extends CssTestCase {

	private const WRAP    = '.flexa-product-excerpt-a';
	private const CONTENT = '.flexa-product-excerpt-a .flexa-product-excerpt__content';
	private const LINK    = '.flexa-product-excerpt-a .flexa-product-excerpt__content a';
	private const HOVER   = '.flexa-product-excerpt-a .flexa-product-excerpt__content a:hover';

	/**
	 * Convenience wrapper around the Product Excerpt generator.
	 *
	 * @param array $attrs Product-excerpt attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Product_Excerpt_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Only a blockId: the theme should style everything, so no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_alignment_on_wrapper(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
	}

	public function test_alignment_tablet_in_media_query(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'tablet' => 'right' ] ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'text-align:right' );
	}

	public function test_typography_on_content(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '16', 'unit' => 'px' ], 'fontWeight' => '400' ] ],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'font-size:16px' );
		$this->assertCssHas( $css, self::CONTENT, 'font-weight:400' );
	}

	public function test_typography_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [ 'mobile' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::CONTENT, 'font-size:14px' );
	}

	public function test_clamp_lines_on_content(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'enableClamp' => true, 'clampLines' => 3 ] );
		$this->assertCssHas( $css, self::CONTENT, '-webkit-line-clamp:3' );
	}

	public function test_clamp_lines_tablet_in_media_query(): void {
		// The clamp is emitted inside every device block, so the media queries
		// carry it too even though the line count itself is not responsive.
		$css = $this->gen( [ 'blockId' => 'a', 'enableClamp' => true, 'clampLines' => 2 ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::CONTENT, '-webkit-line-clamp:2' );
	}

	public function test_clamp_gated_off_when_disabled(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'enableClamp' => false, 'clampLines' => 3 ] );
		$this->assertStringNotContainsString( '-webkit-line-clamp', $css );
	}

	public function test_clamp_zero_lines_emits_nothing(): void {
		// A zero line count would clamp the excerpt away entirely.
		$css = $this->gen( [ 'blockId' => 'a', 'enableClamp' => true, 'clampLines' => 0 ] );
		$this->assertStringNotContainsString( '-webkit-line-clamp', $css );
	}

	public function test_text_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'textColor' => [ 'light' => '#333333', 'dark' => '#dddddd' ],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'color:#333333' );
		$this->assertCssHasInDark( $css, self::CONTENT, 'color:#dddddd' );
	}

	public function test_link_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'linkColor' => [ 'light' => '#0a66c2', 'dark' => '#79b8ff' ],
		] );
		$this->assertCssHas( $css, self::LINK, 'color:#0a66c2' );
		$this->assertCssHasInDark( $css, self::LINK, 'color:#79b8ff' );
	}

	public function test_link_hover_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'linkColorHover' => [ 'light' => '#004182', 'dark' => '#a5d6ff' ],
		] );
		$this->assertCssHas( $css, self::HOVER, 'color:#004182' );
		$this->assertCssHasInDark( $css, self::HOVER, 'color:#a5d6ff' );
	}

	public function test_spacing_padding_and_margin_on_wrapper(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'desktop' => [
				'padding' => [ 'top' => '10', 'right' => '20', 'bottom' => '10', 'left' => '20', 'unit' => 'px' ],
				'margin'  => [ 'top' => '0', 'right' => 'auto', 'bottom' => '30', 'left' => 'auto', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:10px 20px 10px 20px' );
		$this->assertCssHas( $css, self::WRAP, 'margin:0px auto 30px auto' );
	}

	public function test_spacing_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'mobile' => [ 'padding' => [ 'top' => '5', 'right' => '5', 'bottom' => '5', 'left' => '5', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::WRAP, 'padding:5px 5px 5px 5px' );
	}

	public function test_advanced_layout_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'overflow:hidden' );
		$this->assertCssHas( $css, self::WRAP, 'position:relative' );
		$this->assertCssHas( $css, self::WRAP, 'z-index:5' );
	}

	public function test_background_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#f5f5f5', 'dark' => '#101010' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#f5f5f5' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#101010' );
	}

	public function test_border_on_wrapper_light_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => '#cccccc', 'dark' => '#333333' ],
					'radius' => [ 'topLeft' => '6', 'topRight' => '6', 'bottomRight' => '6', 'bottomLeft' => '6', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'border-style:solid' );
		$this->assertCssHas( $css, self::WRAP, 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, self::WRAP, 'border-color:#cccccc' );
		$this->assertCssHas( $css, self::WRAP, 'border-radius:6px 6px 6px 6px' );
		$this->assertCssHasInDark( $css, self::WRAP, 'border-color:#333333' );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '4', 'blur' => '12', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'box-shadow:0px 4px 12px 0px #000000' );
		$this->assertCssHasInDark( $css, self::WRAP, 'box-shadow:0px 4px 12px 0px #ffffff' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );
		$css = $this->gen( [
			'blockId'   => 'a',
			'textColor' => [ 'light' => '#333333', 'dark' => '#dddddd' ],
		] );
		$this->assertCssHasInDark( $css, self::CONTENT, 'color:#dddddd', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}

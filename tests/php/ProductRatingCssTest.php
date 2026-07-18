<?php
/**
 * Tests for the Product Rating block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Product_Rating_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Product_Rating_CSS
 */
class ProductRatingCssTest extends CssTestCase {

	private const WRAP  = '.flexa-product-rating-a';
	private const STARS = '.flexa-product-rating-a .flexa-product-rating__stars';
	private const STAR  = '.flexa-product-rating-a .flexa-product-rating__star';
	private const FILL  = '.flexa-product-rating-a .flexa-product-rating__stars-fill';
	private const BASE   = '.flexa-product-rating-a .flexa-product-rating__stars-base';
	private const NUMBER = '.flexa-product-rating-a .flexa-product-rating__number';
	private const COUNT  = '.flexa-product-rating-a .flexa-product-rating__count';

	/**
	 * Convenience wrapper around the Product Rating generator.
	 *
	 * @param array $attrs Product-rating attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Product_Rating_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Only a blockId: the theme should style everything, so no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'displayType' => 'stars' ] ) );
	}

	public function test_alignment_on_wrapper(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
	}

	public function test_alignment_tablet_in_media_query(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'tablet' => 'right' ] ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'text-align:right' );
	}

	public function test_star_size_on_star(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'starSize' => [ 'desktop' => [ 'value' => '20', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::STAR, 'width:20px' );
		$this->assertCssHas( $css, self::STAR, 'height:20px' );
	}

	public function test_star_size_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'starSize' => [ 'tablet' => [ 'value' => '16', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::STAR, 'width:16px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::STAR, 'height:16px' );
	}

	public function test_star_gap_on_stars_row(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'starGap' => [ 'desktop' => [ 'value' => '6', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::STARS, 'column-gap:6px' );
	}

	public function test_star_gap_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'starGap' => [ 'mobile' => [ 'value' => '3', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::STARS, 'column-gap:3px' );
	}

	public function test_star_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'starColor' => [ 'light' => '#ffb900', 'dark' => '#ffd75e' ],
		] );
		$this->assertCssHas( $css, self::FILL, 'color:#ffb900' );
		$this->assertCssHasInDark( $css, self::FILL, 'color:#ffd75e' );
	}

	public function test_star_empty_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'starEmptyColor' => [ 'light' => '#cccccc', 'dark' => '#444444' ],
		] );
		$this->assertCssHas( $css, self::BASE, 'color:#cccccc' );
		$this->assertCssHasInDark( $css, self::BASE, 'color:#444444' );
	}

	public function test_number_colour_and_typography(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'numberColor'      => [ 'light' => '#222222', 'dark' => '#eeeeee' ],
			'numberTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '18', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, self::NUMBER, 'color:#222222' );
		$this->assertCssHasInDark( $css, self::NUMBER, 'color:#eeeeee' );
		$this->assertCssHas( $css, self::NUMBER, 'font-size:18px' );
	}

	public function test_count_colour_when_shown(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'countColor' => [ 'light' => '#333333', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, self::COUNT, 'color:#333333' );
		$this->assertCssHasInDark( $css, self::COUNT, 'color:#eeeeee' );
	}

	public function test_count_colour_gated_off_when_hidden(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'showReviewCount' => false,
			'countColor'      => [ 'light' => '#333333', 'dark' => '#eeeeee' ],
		] );
		$this->assertStringNotContainsString( 'flexa-product-rating__count', $css );
	}

	public function test_count_typography_scoped(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'countTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ], 'fontWeight' => '600' ] ],
		] );
		$this->assertCssHas( $css, self::COUNT, 'font-size:14px' );
		$this->assertCssHas( $css, self::COUNT, 'font-weight:600' );
	}

	public function test_count_typography_gated_off_when_hidden(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'showReviewCount' => false,
			'countTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ] ] ],
		] );
		$this->assertStringNotContainsString( 'font-size:14px', $css );
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
			'starColor' => [ 'light' => '#ffb900', 'dark' => '#ffd75e' ],
		] );
		$this->assertCssHasInDark( $css, self::FILL, 'color:#ffd75e', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}

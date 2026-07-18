<?php
/**
 * Tests for the Product Price block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Product_Price_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Product_Price_CSS
 */
class ProductPriceCssTest extends CssTestCase {

	private const WRAP    = '.flexa-product-price-a';
	private const REGULAR = '.flexa-product-price-a .flexa-product-price__regular';
	private const SALE    = '.flexa-product-price-a .flexa-product-price__sale';

	/**
	 * Convenience wrapper around the Product Price generator.
	 *
	 * @param array $attrs Product-price attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Product_Price_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Only a blockId: the theme should style everything, so no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'salePricePosition' => 'after' ] ) );
	}

	public function test_alignment_on_wrapper(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
	}

	public function test_alignment_tablet_in_media_query(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'tablet' => 'right' ] ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'text-align:right' );
	}

	public function test_regular_typography_on_regular_amount(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'regularTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '24', 'unit' => 'px' ], 'fontWeight' => '700' ] ],
		] );
		$this->assertCssHas( $css, self::REGULAR, 'font-size:24px' );
		$this->assertCssHas( $css, self::REGULAR, 'font-weight:700' );
	}

	public function test_regular_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'regularTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '18', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::REGULAR, 'font-size:18px' );
	}

	public function test_sale_typography_on_sale_amount(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'saleTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '20', 'unit' => 'px' ], 'lineHeight' => '1.4' ] ],
		] );
		$this->assertCssHas( $css, self::SALE, 'font-size:20px' );
		$this->assertCssHas( $css, self::SALE, 'line-height:1.4' );
	}

	public function test_sale_typography_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'saleTypography' => [ 'mobile' => [ 'fontSize' => [ 'value' => '16', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::SALE, 'font-size:16px' );
	}

	public function test_regular_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'regularColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, self::REGULAR, 'color:#111111' );
		$this->assertCssHasInDark( $css, self::REGULAR, 'color:#eeeeee' );
	}

	public function test_sale_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'saleColor' => [ 'light' => '#cc0000', 'dark' => '#ff6666' ],
		] );
		$this->assertCssHas( $css, self::SALE, 'color:#cc0000' );
		$this->assertCssHasInDark( $css, self::SALE, 'color:#ff6666' );
	}

	public function test_strike_line_colour_and_thickness(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'strikeColor'     => [ 'light' => '#ff0000', 'dark' => '#ff8888' ],
			'strikeThickness' => 3,
		] );
		$this->assertCssHas( $css, self::REGULAR, 'text-decoration-color:#ff0000' );
		$this->assertCssHasInDark( $css, self::REGULAR, 'text-decoration-color:#ff8888' );
		$this->assertCssHas( $css, self::REGULAR, 'text-decoration-thickness:3px' );
	}

	public function test_strike_thickness_ignored_when_zero(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'strikeThickness' => 0 ] );
		$this->assertStringNotContainsString( 'text-decoration-thickness', $css );
	}

	public function test_legacy_prefix_suffix_attributes_are_ignored(): void {
		// Prefix/suffix were removed — the price is pulled from WooCommerce and
		// the user types nothing. Content saved while those options existed still
		// carries the old attributes; the generator must ignore them entirely.
		$css = $this->gen( [
			'blockId'     => 'a',
			'showPrefix'  => true,
			'prefixText'  => 'From',
			'prefixColor' => [ 'light' => '#008800', 'dark' => '#00cc00' ],
			'showSuffix'  => true,
			'suffixText'  => 'each',
			'suffixColor' => [ 'light' => '#000088', 'dark' => '#4444ff' ],
		] );
		$this->assertStringNotContainsString( '__prefix', $css );
		$this->assertStringNotContainsString( '__suffix', $css );
		$this->assertStringNotContainsString( '#008800', $css );
		$this->assertStringNotContainsString( '#000088', $css );
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

	public function test_background_image_sub_properties_and_lazy(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'image', 'lazyLoad' => true, 'image' => [ 'url' => 'https://x/y.jpg', 'position' => 'top left', 'size' => 'contain', 'repeat' => 'repeat-x', 'attachment' => 'fixed' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-position:top left' );
		$this->assertCssHas( $css, self::WRAP, 'background-size:contain' );
		$this->assertCssHas( $css, self::WRAP, 'background-repeat:repeat-x' );
		$this->assertCssHas( $css, self::WRAP, 'background-attachment:fixed' );
		// Lazy background: the image URL is emitted once, after the .flexa-bg-loaded marker.
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
		$this->assertStringContainsString( self::WRAP . '.flexa-bg-loaded', $css );
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
			'blockId'      => 'a',
			'regularColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::REGULAR, 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}

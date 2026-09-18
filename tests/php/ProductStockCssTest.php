<?php
/**
 * Tests for the Product Stock block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Product_Stock_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Product_Stock_CSS
 */
class ProductStockCssTest extends CssTestCase {

	private const WRAP   = '.flexa-product-stock-a';
	private const STATUS = '.flexa-product-stock-a .flexa-product-stock__status';
	private const ICON   = '.flexa-product-stock-a .flexa-product-stock__icon';
	private const IN     = '.flexa-product-stock-a .flexa-product-stock__status--in-stock';
	private const OUT    = '.flexa-product-stock-a .flexa-product-stock__status--out-of-stock';
	private const BACK   = '.flexa-product-stock-a .flexa-product-stock__status--on-backorder';
	private const LOW    = '.flexa-product-stock-a .flexa-product-stock__status--low-stock';

	/**
	 * Convenience wrapper around the Product Stock generator.
	 *
	 * @param array $attrs Product-stock attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Product_Stock_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Only a blockId: the theme should style everything, so no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'displayType' => 'text' ] ) );
	}

	public function test_alignment_on_wrapper(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
	}

	public function test_alignment_tablet_in_media_query(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'tablet' => 'right' ] ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'text-align:right' );
	}

	public function test_typography_on_status(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ], 'fontWeight' => '600' ] ],
		] );
		$this->assertCssHas( $css, self::STATUS, 'font-size:15px' );
		$this->assertCssHas( $css, self::STATUS, 'font-weight:600' );
	}

	public function test_typography_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [ 'mobile' => [ 'fontSize' => [ 'value' => '13', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::STATUS, 'font-size:13px' );
	}

	public function test_gap_on_status(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'gap'     => [ 'desktop' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::STATUS, 'gap:8px' );
	}

	public function test_gap_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'gap'     => [ 'tablet' => [ 'value' => '4', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::STATUS, 'gap:4px' );
	}

	public function test_icon_size_when_icon_shown(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'showIcon' => true,
			'iconSize' => [ 'desktop' => [ 'value' => '18', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::ICON, 'width:18px' );
		$this->assertCssHas( $css, self::ICON, 'height:18px' );
	}

	public function test_icon_size_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'showIcon' => true,
			'iconSize' => [ 'mobile' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::ICON, 'width:12px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::ICON, 'height:12px' );
	}

	public function test_icon_size_gated_off_when_icon_hidden(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'showIcon' => false,
			'iconSize' => [ 'desktop' => [ 'value' => '18', 'unit' => 'px' ] ],
		] );
		$this->assertStringNotContainsString( 'flexa-product-stock__icon', $css );
	}

	public function test_badge_padding_and_radius_on_status(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'displayType'  => 'badge',
			'badgePadding' => [ 'desktop' => [ 'top' => '4', 'right' => '10', 'bottom' => '4', 'left' => '10', 'unit' => 'px' ] ],
			'badgeRadius'  => [ 'desktop' => [ 'value' => '999', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::STATUS, 'padding:4px 10px 4px 10px' );
		$this->assertCssHas( $css, self::STATUS, 'border-radius:999px' );
	}

	public function test_badge_padding_and_radius_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'displayType'  => 'badge',
			'badgePadding' => [ 'tablet' => [ 'top' => '2', 'right' => '6', 'bottom' => '2', 'left' => '6', 'unit' => 'px' ] ],
			'badgeRadius'  => [ 'tablet' => [ 'value' => '4', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::STATUS, 'padding:2px 6px 2px 6px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::STATUS, 'border-radius:4px' );
	}

	public function test_badge_padding_and_radius_gated_off_in_text_mode(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'displayType'  => 'text',
			'badgePadding' => [ 'desktop' => [ 'top' => '4', 'right' => '10', 'bottom' => '4', 'left' => '10', 'unit' => 'px' ] ],
			'badgeRadius'  => [ 'desktop' => [ 'value' => '999', 'unit' => 'px' ] ],
		] );
		$this->assertStringNotContainsString( 'padding:', $css );
		$this->assertStringNotContainsString( 'border-radius:', $css );
	}

	public function test_in_stock_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'inStockColor'      => [ 'light' => '#1a7f37', 'dark' => '#3fb950' ],
			'inStockBackground' => [ 'light' => '#e8f5ec', 'dark' => '#0d2a15' ],
		] );
		$this->assertCssHas( $css, self::IN, 'color:#1a7f37' );
		$this->assertCssHas( $css, self::IN, 'background-color:#e8f5ec' );
		$this->assertCssHasInDark( $css, self::IN, 'color:#3fb950' );
		$this->assertCssHasInDark( $css, self::IN, 'background-color:#0d2a15' );
	}

	public function test_out_of_stock_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'              => 'a',
			'outOfStockColor'      => [ 'light' => '#b42318', 'dark' => '#f97066' ],
			'outOfStockBackground' => [ 'light' => '#fef3f2', 'dark' => '#2c0f0d' ],
		] );
		$this->assertCssHas( $css, self::OUT, 'color:#b42318' );
		$this->assertCssHas( $css, self::OUT, 'background-color:#fef3f2' );
		$this->assertCssHasInDark( $css, self::OUT, 'color:#f97066' );
		$this->assertCssHasInDark( $css, self::OUT, 'background-color:#2c0f0d' );
	}

	public function test_backorder_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'             => 'a',
			'backorderColor'      => [ 'light' => '#b54708', 'dark' => '#fdb022' ],
			'backorderBackground' => [ 'light' => '#fffaeb', 'dark' => '#2b1a05' ],
		] );
		$this->assertCssHas( $css, self::BACK, 'color:#b54708' );
		$this->assertCssHas( $css, self::BACK, 'background-color:#fffaeb' );
		$this->assertCssHasInDark( $css, self::BACK, 'color:#fdb022' );
		$this->assertCssHasInDark( $css, self::BACK, 'background-color:#2b1a05' );
	}

	public function test_low_stock_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'            => 'a',
			'lowStockColor'      => [ 'light' => '#854708', 'dark' => '#f7c948' ],
			'lowStockBackground' => [ 'light' => '#fff8e1', 'dark' => '#2a2005' ],
		] );
		$this->assertCssHas( $css, self::LOW, 'color:#854708' );
		$this->assertCssHas( $css, self::LOW, 'background-color:#fff8e1' );
		$this->assertCssHasInDark( $css, self::LOW, 'color:#f7c948' );
		$this->assertCssHasInDark( $css, self::LOW, 'background-color:#2a2005' );
	}

	public function test_status_colours_still_apply_in_text_mode(): void {
		// Only padding / radius are badge-gated; colours belong to both display types.
		$css = $this->gen( [
			'blockId'      => 'a',
			'displayType'  => 'text',
			'inStockColor' => [ 'light' => '#1a7f37' ],
		] );
		$this->assertCssHas( $css, self::IN, 'color:#1a7f37' );
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
			'blockId'      => 'a',
			'inStockColor' => [ 'light' => '#1a7f37', 'dark' => '#3fb950' ],
		] );
		$this->assertCssHasInDark( $css, self::IN, 'color:#3fb950', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}

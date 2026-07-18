<?php
/**
 * Tests for the Product Details block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Product_Detail_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Product_Detail_CSS
 */
class ProductDetailCssTest extends CssTestCase {

	private const WRAP       = '.flexa-product-detail-a';
	private const NAV        = '.flexa-product-detail-a .flexa-product-detail__nav';
	private const TAB        = '.flexa-product-detail-a .flexa-product-detail__tab';
	private const TAB_ACTIVE = '.flexa-product-detail-a .flexa-product-detail__tab.is-active';
	private const CONTENT    = '.flexa-product-detail-a .flexa-product-detail__content';
	private const PAGE       = '.flexa-product-detail-a .flexa-pagination .page-numbers';
	private const PAGE_CUR   = '.flexa-product-detail-a .flexa-pagination .page-numbers.current';
	private const MORE_BTN   = '.flexa-product-detail-a .flexa-pagination-loadmore__btn';
	private const RV_TITLE   = '.flexa-product-detail-a .flexa-product-detail__reviews .woocommerce-Reviews-title';
	private const RV_DATE    = '.flexa-product-detail-a .flexa-product-detail__reviews .woocommerce-review__published-date';
	private const RV_STARS   = '.flexa-product-detail-a .flexa-product-detail__reviews .star-rating';
	private const AT_LABEL   = '.flexa-product-detail-a .woocommerce-product-attributes .woocommerce-product-attributes-item__label';
	private const AT_VALUE   = '.flexa-product-detail-a .woocommerce-product-attributes .woocommerce-product-attributes-item__value';

	/**
	 * Convenience wrapper around the Product Details generator.
	 *
	 * @param array $attrs Product-detail attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Product_Detail_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Only a blockId: the theme should style everything, so no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_tab_title_typography_scoped_to_tab(): void {
		$css = $this->gen( [
			'blockId'            => 'a',
			'tabTitleTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '18', 'unit' => 'px' ], 'fontWeight' => '700', 'lineHeight' => '1.5' ] ],
		] );
		$this->assertCssHas( $css, self::TAB, 'font-size:18px' );
		$this->assertCssHas( $css, self::TAB, 'font-weight:700' );
		$this->assertCssHas( $css, self::TAB, 'line-height:1.5' );
	}

	public function test_tab_title_padding_responsive_in_media_query(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'tabTitlePadding' => [ 'tablet' => [ 'top' => '8', 'right' => '12', 'bottom' => '8', 'left' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::TAB, 'padding:8px 12px 8px 12px' );
	}

	public function test_tab_title_padding_not_emitted_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'padding', $css );
	}

	public function test_tab_gap_column_gap_on_nav(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'tabGap'  => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::NAV, 'column-gap:24px' );
	}

	public function test_tab_gap_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'tabGap'  => [ 'tablet' => [ 'value' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::NAV, 'column-gap:10px' );
	}

	public function test_tab_title_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'tabTitleColor' => [ 'light' => '#222222', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, self::TAB, 'color:#222222' );
		$this->assertCssHasInDark( $css, self::TAB, 'color:#eeeeee' );
	}

	public function test_tab_title_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'tabTitleBg' => [ 'light' => '#f5f5f5', 'dark' => '#101010' ],
		] );
		$this->assertCssHas( $css, self::TAB, 'background-color:#f5f5f5' );
		$this->assertCssHasInDark( $css, self::TAB, 'background-color:#101010' );
	}

	public function test_tab_active_colour_scoped_to_active(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'tabActiveColor' => [ 'light' => '#0000ff', 'dark' => '#00ffff' ],
		] );
		$this->assertCssHas( $css, self::TAB_ACTIVE, 'color:#0000ff' );
		$this->assertCssHasInDark( $css, self::TAB_ACTIVE, 'color:#00ffff' );
	}

	public function test_tab_active_background_scoped_to_active(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'tabActiveBg' => [ 'light' => '#ffffff', 'dark' => '#000000' ],
		] );
		$this->assertCssHas( $css, self::TAB_ACTIVE, 'background-color:#ffffff' );
		$this->assertCssHasInDark( $css, self::TAB_ACTIVE, 'background-color:#000000' );
	}

	public function test_content_typography_scoped_to_content(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'contentTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'font-size:15px' );
	}

	public function test_content_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'contentColor' => [ 'light' => '#333333', 'dark' => '#dddddd' ],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'color:#333333' );
		$this->assertCssHasInDark( $css, self::CONTENT, 'color:#dddddd' );
	}

	public function test_pagination_link_colours_and_radius(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'paginationColor'       => [ 'light' => '#123456', 'dark' => '#abcdef' ],
			'paginationActiveColor' => [ 'light' => '#654321', 'dark' => '' ],
			'paginationRadius'      => [ 'value' => '4', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::PAGE, 'color:#123456' );
		$this->assertCssHasInDark( $css, self::PAGE, 'color:#abcdef' );
		$this->assertCssHas( $css, self::PAGE, 'border-radius:4px' );
		$this->assertCssHas( $css, self::PAGE_CUR, 'color:#654321' );
	}

	public function test_reviews_title_colour_typography_and_star_size(): void {
		$css = $this->gen( [
			'blockId'                => 'a',
			'reviewsTitleColor'      => [ 'light' => '#1a1a1a', 'dark' => '#f0f0f0' ],
			'reviewsTitleTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '22', 'unit' => 'px' ] ] ],
			'reviewStarsSize'        => [ 'value' => '18', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::RV_TITLE, 'color:#1a1a1a' );
		$this->assertCssHasInDark( $css, self::RV_TITLE, 'color:#f0f0f0' );
		$this->assertCssHas( $css, self::RV_TITLE, 'font-size:22px' );
		$this->assertCssHas( $css, self::RV_STARS, 'font-size:18px' );
	}

	public function test_additional_info_table_styling(): void {
		$css = $this->gen( [
			'blockId'                   => 'a',
			'additionalLabelColor'      => [ 'light' => '#101010', 'dark' => '#f5f5f5' ],
			'additionalValueColor'      => [ 'light' => '#444444', 'dark' => '' ],
			'additionalBorderColor'     => [ 'light' => '#dddddd', 'dark' => '' ],
			'additionalCellPadding'     => [ 'desktop' => [ 'top' => '8', 'right' => '12', 'bottom' => '8', 'left' => '12', 'unit' => 'px' ] ],
			'additionalLabelTypography' => [ 'desktop' => [ 'fontWeight' => '600' ] ],
		] );
		$this->assertCssHas( $css, self::AT_LABEL, 'color:#101010' );
		$this->assertCssHasInDark( $css, self::AT_LABEL, 'color:#f5f5f5' );
		$this->assertCssHas( $css, self::AT_LABEL, 'font-weight:600' );
		$this->assertCssHas( $css, self::AT_LABEL, 'border-color:#dddddd' );
		$this->assertCssHas( $css, self::AT_VALUE, 'color:#444444' );
		$this->assertCssHas( $css, self::AT_VALUE, 'padding:8px 12px 8px 12px' );
	}

	public function test_review_date_colour(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'reviewDateColor'  => [ 'light' => '#888888', 'dark' => '' ],
		] );
		$this->assertCssHas( $css, self::RV_DATE, 'color:#888888' );
	}

	public function test_loadmore_button_colours(): void {
		$css = $this->gen( [
			'blockId'            => 'a',
			'loadMoreColor'      => [ 'light' => '#111111', 'dark' => '' ],
			'loadMoreBackground' => [ 'light' => '#eeeeee', 'dark' => '' ],
		] );
		$this->assertCssHas( $css, self::MORE_BTN, 'color:#111111' );
		$this->assertCssHas( $css, self::MORE_BTN, 'background:#eeeeee' );
	}

	public function test_content_padding_responsive_in_media_query(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'contentPadding' => [ 'mobile' => [ 'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::CONTENT, 'padding:16px 16px 16px 16px' );
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

	public function test_background_image_sub_properties(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'image', 'image' => [ 'url' => 'https://x/y.jpg', 'position' => 'top left', 'size' => 'contain', 'repeat' => 'repeat-x', 'attachment' => 'fixed' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-image:url(https://x/y.jpg)' );
		$this->assertCssHas( $css, self::WRAP, 'background-position:top left' );
		$this->assertCssHas( $css, self::WRAP, 'background-size:contain' );
		$this->assertCssHas( $css, self::WRAP, 'background-repeat:repeat-x' );
		$this->assertCssHas( $css, self::WRAP, 'background-attachment:fixed' );
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
			'blockId'       => 'a',
			'tabTitleColor' => [ 'light' => '#222222', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::TAB, 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}

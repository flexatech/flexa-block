<?php
/**
 * Tests for the Related Products block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Product_Related_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Product_Related_CSS
 */
class ProductRelatedCssTest extends CssTestCase {

	private const WRAP    = '.flexa-product-related-a';
	private const HEADING = '.flexa-product-related-a .flexa-product-related__heading';
	private const LIST    = '.flexa-product-related-a .flexa-product-related__list';
	private const ITEM    = '.flexa-product-related-a .flexa-product-related__item';
	private const IMAGE   = '.flexa-product-related-a .flexa-product-related__image img';
	private const TITLE   = '.flexa-product-related-a .flexa-product-related__title';
	private const PRICE   = '.flexa-product-related-a .flexa-product-related__price';
	private const STARS   = '.flexa-product-related-a .flexa-product-related__rating';
	private const BUTTON  = '.flexa-product-related-a .flexa-product-related__button';
	private const BTN_HOV = '.flexa-product-related-a .flexa-product-related__button:hover';

	/**
	 * Convenience wrapper around the Related Products generator.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Product_Related_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Only a blockId: the theme should style everything, so no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	// --- Grid geometry -------------------------------------------------------

	public function test_columns_on_list(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'columns' => [ 'desktop' => '4' ] ] );
		$this->assertCssHas( $css, self::LIST, 'grid-template-columns:repeat(4, minmax(0, 1fr))' );
	}

	public function test_columns_tablet_and_mobile_in_media_queries(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'columns' => [ 'tablet' => '3', 'mobile' => '2' ] ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::LIST, 'grid-template-columns:repeat(3, minmax(0, 1fr))' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::LIST, 'grid-template-columns:repeat(2, minmax(0, 1fr))' );
	}

	public function test_columns_clamped_to_one_to_eight(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'columns' => [ 'desktop' => '99', 'tablet' => '0' ] ] );
		$this->assertCssHas( $css, self::LIST, 'grid-template-columns:repeat(8, minmax(0, 1fr))' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::LIST, 'grid-template-columns:repeat(1, minmax(0, 1fr))' );
	}

	public function test_columns_gated_off_in_list_layout(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'relatedLayout' => 'list', 'columns' => [ 'desktop' => '4' ] ] );
		$this->assertStringNotContainsString( 'grid-template-columns', $css );
	}

	public function test_row_and_column_gap_on_list(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'rowGap'    => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ] ],
			'columnGap' => [ 'desktop' => [ 'value' => '16', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::LIST, 'row-gap:24px' );
		$this->assertCssHas( $css, self::LIST, 'column-gap:16px' );
	}

	public function test_gaps_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'rowGap'    => [ 'mobile' => [ 'value' => '12', 'unit' => 'px' ] ],
			'columnGap' => [ 'mobile' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::LIST, 'row-gap:12px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::LIST, 'column-gap:8px' );
	}

	// --- Card box ------------------------------------------------------------

	public function test_card_padding_radius_and_border_width_on_item(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'cardPadding'     => [ 'desktop' => [ 'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16', 'unit' => 'px' ] ],
			'cardRadius'      => [ 'desktop' => [ 'value' => '8', 'unit' => 'px' ] ],
			'cardBorderWidth' => [ 'desktop' => [ 'value' => '1', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::ITEM, 'padding:16px 16px 16px 16px' );
		$this->assertCssHas( $css, self::ITEM, 'border-radius:8px' );
		$this->assertCssHas( $css, self::ITEM, 'border-style:solid' );
		$this->assertCssHas( $css, self::ITEM, 'border-width:1px' );
	}

	public function test_card_box_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'cardPadding'     => [ 'tablet' => [ 'top' => '8', 'right' => '8', 'bottom' => '8', 'left' => '8', 'unit' => 'px' ] ],
			'cardRadius'      => [ 'tablet' => [ 'value' => '4', 'unit' => 'px' ] ],
			'cardBorderWidth' => [ 'tablet' => [ 'value' => '2', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::ITEM, 'padding:8px 8px 8px 8px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::ITEM, 'border-radius:4px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::ITEM, 'border-width:2px' );
	}

	public function test_card_background_and_border_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'cardBackground'  => [ 'light' => '#ffffff', 'dark' => '#111111' ],
			'cardBorderColor' => [ 'light' => '#e5e5e5', 'dark' => '#2f2f2f' ],
		] );
		$this->assertCssHas( $css, self::ITEM, 'background:#ffffff' );
		$this->assertCssHas( $css, self::ITEM, 'border-color:#e5e5e5' );
		$this->assertCssHasInDark( $css, self::ITEM, 'background:#111111' );
		$this->assertCssHasInDark( $css, self::ITEM, 'border-color:#2f2f2f' );
	}

	public function test_card_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'cardShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '6', 'blur' => '18', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::ITEM, 'box-shadow:0px 6px 18px 0px #000000' );
		$this->assertCssHasInDark( $css, self::ITEM, 'box-shadow:0px 6px 18px 0px #ffffff' );
	}

	public function test_card_shadow_gated_off_when_disabled(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'cardShadow' => [ 'enabled' => false, 'vertical' => '6', 'color' => [ 'light' => '#000000' ] ],
		] );
		$this->assertSame( '', $css );
	}

	public function test_content_align_on_item(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'contentAlign' => 'center' ] );
		$this->assertCssHas( $css, self::ITEM, 'text-align:center' );
	}

	public function test_content_align_rejects_unknown_value(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'contentAlign' => 'sideways' ] );
		$this->assertSame( '', $css );
	}

	// --- Image ---------------------------------------------------------------

	public function test_image_ratio_on_image(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'imageRatio' => '4/3' ] );
		$this->assertCssHas( $css, self::IMAGE, 'aspect-ratio:4/3' );
		$this->assertCssHas( $css, self::IMAGE, 'object-fit:cover' );
	}

	public function test_image_ratio_gated_off_when_image_hidden(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'showImage' => false, 'imageRatio' => '4/3' ] );
		$this->assertStringNotContainsString( 'aspect-ratio', $css );
	}

	public function test_image_ratio_rejects_malformed_value(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'imageRatio' => 'cover;color:red' ] );
		$this->assertSame( '', $css );
	}

	// --- Heading -------------------------------------------------------------

	public function test_heading_typography_and_colour(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'headingTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '28', 'unit' => 'px' ], 'fontWeight' => '700' ] ],
			'headingColor'      => [ 'light' => '#101828', 'dark' => '#f2f4f7' ],
		] );
		$this->assertCssHas( $css, self::HEADING, 'font-size:28px' );
		$this->assertCssHas( $css, self::HEADING, 'font-weight:700' );
		$this->assertCssHas( $css, self::HEADING, 'color:#101828' );
		$this->assertCssHasInDark( $css, self::HEADING, 'color:#f2f4f7' );
	}

	public function test_heading_typography_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'headingTypography' => [ 'mobile' => [ 'fontSize' => [ 'value' => '20', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::HEADING, 'font-size:20px' );
	}

	public function test_heading_gated_off_when_hidden(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'showHeading'       => false,
			'headingTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '28', 'unit' => 'px' ] ] ],
			'headingColor'      => [ 'light' => '#101828', 'dark' => '#f2f4f7' ],
		] );
		$this->assertStringNotContainsString( 'flexa-product-related__heading', $css );
	}

	// --- Title / price / rating ---------------------------------------------

	public function test_title_typography_and_colour(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'titleTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '16', 'unit' => 'px' ], 'lineHeight' => '1.4' ] ],
			'titleColor'      => [ 'light' => '#222222', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, self::TITLE, 'font-size:16px' );
		$this->assertCssHas( $css, self::TITLE, 'line-height:1.4' );
		$this->assertCssHas( $css, self::TITLE, 'color:#222222' );
		$this->assertCssHasInDark( $css, self::TITLE, 'color:#eeeeee' );
	}

	public function test_title_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'titleTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::TITLE, 'font-size:15px' );
	}

	public function test_title_gated_off_when_hidden(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'showTitle'       => false,
			'titleTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '16', 'unit' => 'px' ] ] ],
			'titleColor'      => [ 'light' => '#222222' ],
		] );
		$this->assertStringNotContainsString( 'flexa-product-related__title', $css );
	}

	public function test_price_typography_and_colour(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'priceTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '18', 'unit' => 'px' ], 'letterSpacing' => [ 'value' => '1', 'unit' => 'px' ] ] ],
			'priceColor'      => [ 'light' => '#b12704', 'dark' => '#ff8a65' ],
		] );
		$this->assertCssHas( $css, self::PRICE, 'font-size:18px' );
		$this->assertCssHas( $css, self::PRICE, 'letter-spacing:1px' );
		$this->assertCssHas( $css, self::PRICE, 'color:#b12704' );
		$this->assertCssHasInDark( $css, self::PRICE, 'color:#ff8a65' );
	}

	public function test_price_typography_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'priceTypography' => [ 'mobile' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::PRICE, 'font-size:14px' );
	}

	public function test_price_gated_off_when_hidden(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'showPrice'       => false,
			'priceTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '18', 'unit' => 'px' ] ] ],
			'priceColor'      => [ 'light' => '#b12704' ],
		] );
		$this->assertStringNotContainsString( 'flexa-product-related__price', $css );
	}

	public function test_star_colour_light_and_dark_when_rating_shown(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'showRating' => true,
			'starColor'  => [ 'light' => '#f5a623', 'dark' => '#ffd166' ],
		] );
		$this->assertCssHas( $css, self::STARS, 'color:#f5a623' );
		$this->assertCssHasInDark( $css, self::STARS, 'color:#ffd166' );
	}

	public function test_star_colour_gated_off_when_rating_hidden(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'showRating' => false,
			'starColor'  => [ 'light' => '#f5a623', 'dark' => '#ffd166' ],
		] );
		$this->assertStringNotContainsString( 'flexa-product-related__rating', $css );
	}

	// --- Button (CSS_Helpers::add_button) ------------------------------------

	public function test_button_colours_base_and_hover(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'showButton'            => true,
			'buttonTextColor'       => [ 'light' => '#ffffff', 'dark' => '#000000' ],
			'buttonBackground'      => [ 'light' => '#7f54b3', 'dark' => '#3a2a55' ],
			'buttonTextColorHover'  => [ 'light' => '#f0f0f0', 'dark' => '#111111' ],
			'buttonBackgroundHover' => [ 'light' => '#5f3f8a', 'dark' => '#221733' ],
		] );
		$this->assertCssHas( $css, self::BUTTON, 'color:#ffffff' );
		$this->assertCssHas( $css, self::BUTTON, 'background:#7f54b3' );
		$this->assertCssHas( $css, self::BTN_HOV, 'color:#f0f0f0' );
		$this->assertCssHas( $css, self::BTN_HOV, 'background:#5f3f8a' );
		$this->assertCssHasInDark( $css, self::BUTTON, 'color:#000000' );
		$this->assertCssHasInDark( $css, self::BUTTON, 'background:#3a2a55' );
		$this->assertCssHasInDark( $css, self::BTN_HOV, 'color:#111111' );
		$this->assertCssHasInDark( $css, self::BTN_HOV, 'background:#221733' );
	}

	public function test_button_radius_padding_and_alignment(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'showButton'    => true,
			'buttonRadius'  => [ 'value' => '6', 'unit' => 'px' ],
			'buttonPadding' => [ 'top' => '10', 'right' => '18', 'bottom' => '10', 'left' => '18', 'unit' => 'px' ],
			'buttonAlign'   => 'center',
		] );
		$this->assertCssHas( $css, self::BUTTON, 'border-radius:6px' );
		$this->assertCssHas( $css, self::BUTTON, 'padding:10px 18px 10px 18px' );
		$this->assertCssHas( $css, self::BUTTON, 'align-self:center' );
	}

	public function test_button_full_width_stretch(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'showButton' => true, 'buttonWidth' => 'full' ] );
		$this->assertCssHas( $css, self::BUTTON, 'display:flex' );
		$this->assertCssHas( $css, self::BUTTON, 'width:100%' );
		$this->assertCssHas( $css, self::BUTTON, 'justify-content:center' );
	}

	public function test_button_gated_off_when_hidden(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'showButton'       => false,
			'buttonTextColor'  => [ 'light' => '#ffffff' ],
			'buttonBackground' => [ 'light' => '#7f54b3' ],
			'buttonRadius'     => [ 'value' => '6', 'unit' => 'px' ],
			'buttonWidth'      => 'full',
		] );
		$this->assertStringNotContainsString( 'flexa-product-related__button', $css );
	}

	// --- Foundation ----------------------------------------------------------

	public function test_alignment_on_wrapper(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
	}

	public function test_alignment_tablet_in_media_query(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'tablet' => 'right' ] ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'text-align:right' );
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
			'blockId'    => 'a',
			'titleColor' => [ 'light' => '#222222', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::TITLE, 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}

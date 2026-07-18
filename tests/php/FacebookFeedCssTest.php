<?php
/**
 * Tests for the Facebook Feed block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional columns / gaps / colours / typography and light/dark parity. The
 * grid columns (or masonry column-count) and the gap between posts are structural
 * (emitted when set); the card colours and typography follow the theme-first rule.
 * The Graph API fetch itself is exercised by WordPress, not here.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Facebook_Feed_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Facebook_Feed_CSS
 */
class FacebookFeedCssTest extends CssTestCase {

	private const OUTER     = '.flexa-facebook-feed-a';
	private const INNER     = '.flexa-facebook-feed-a > .flexa-facebook-feed__inner';
	private const GRID      = '.flexa-facebook-feed-a .flexa-facebook-feed__grid';
	private const ITEM      = '.flexa-facebook-feed-a .flexa-facebook-feed__item';
	private const PAGE_NAME = '.flexa-facebook-feed-a .flexa-facebook-feed__page-name';
	private const MESSAGE   = '.flexa-facebook-feed-a .flexa-facebook-feed__message';
	private const TIMESTAMP = '.flexa-facebook-feed-a .flexa-facebook-feed__timestamp';
	private const ACTIONS   = '.flexa-facebook-feed-a .flexa-facebook-feed__actions';
	private const PAGER     = '.flexa-facebook-feed-a .flexa-pagination';
	private const PAGE_NUM  = '.flexa-facebook-feed-a .flexa-pagination .page-numbers';
	private const PAGE_CUR  = '.flexa-facebook-feed-a .flexa-pagination .page-numbers.current';
	private const LM_WRAP   = '.flexa-facebook-feed-a .flexa-pagination-loadmore';
	private const LM_BTN    = '.flexa-facebook-feed-a .flexa-pagination-loadmore__btn';

	/**
	 * Convenience wrapper around the Facebook Feed generator.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Facebook_Feed_CSS::class, 'generate' ], $attrs );
	}

	// --- 1 & 2: guards / theme-first --------------------------------------

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Theme-first: with nothing chosen the generator emits no declarations at
		// all, so untouched cards inherit the theme.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	// --- 3 & 4: columns + gap (structural) --------------------------------

	public function test_grid_columns_emit_grid_template(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'feedLayout' => 'grid',
			'columns'    => [ 'desktop' => [ 'value' => '4', 'unit' => '' ] ],
		] );
		$this->assertCssHas( $css, self::GRID, 'grid-template-columns:repeat(4, minmax(0, 1fr))' );
	}

	public function test_masonry_columns_emit_column_count(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'feedLayout' => 'masonry',
			'columns'    => [ 'desktop' => [ 'value' => '3', 'unit' => '' ] ],
		] );
		$this->assertCssHas( $css, self::GRID, 'column-count:3' );
		$this->assertStringNotContainsString( 'grid-template-columns', $css );
	}

	public function test_columns_absent_for_list_and_carousel(): void {
		$list = $this->gen( [
			'blockId'    => 'a',
			'feedLayout' => 'list',
			'columns'    => [ 'desktop' => [ 'value' => '4', 'unit' => '' ] ],
		] );
		$this->assertStringNotContainsString( 'grid-template-columns', $list );
		$this->assertStringNotContainsString( 'column-count', $list );

		$carousel = $this->gen( [
			'blockId'    => 'a',
			'feedLayout' => 'carousel',
			'columns'    => [ 'desktop' => [ 'value' => '4', 'unit' => '' ] ],
		] );
		$this->assertStringNotContainsString( 'grid-template-columns', $carousel );
	}

	public function test_grid_gap_on_grid(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'feedLayout' => 'grid',
			'itemGap'    => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::GRID, 'gap:24px' );
	}

	public function test_masonry_gap_is_column_gap_plus_item_margin(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'feedLayout' => 'masonry',
			'itemGap'    => [ 'desktop' => [ 'value' => '18', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::GRID, 'column-gap:18px' );
		$this->assertCssHas( $css, self::ITEM, 'margin-bottom:18px' );
	}

	public function test_gap_responsive_in_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'feedLayout' => 'grid',
			'itemGap'    => [ 'tablet' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::GRID, 'gap:12px' );
	}

	public function test_columns_responsive_in_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'feedLayout' => 'grid',
			'columns'    => [ 'tablet' => [ 'value' => '2', 'unit' => '' ], 'mobile' => [ 'value' => '1', 'unit' => '' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::GRID, 'grid-template-columns:repeat(2, minmax(0, 1fr))' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::GRID, 'grid-template-columns:repeat(1, minmax(0, 1fr))' );
	}

	// --- 7: foundational (padding / margin / width / advanced / background) --

	public function test_wrapper_spacing_padding_on_inner_margin_on_outer(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px' ],
					'margin'  => [ 'top' => '0', 'right' => 'auto', 'bottom' => '40', 'left' => 'auto', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::INNER, 'padding:20px 20px 20px 20px' );
		$this->assertCssHas( $css, self::OUTER, 'margin:0px auto 40px auto' );
	}

	public function test_boxed_width_is_max_width_on_inner(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'boxed',
			'widthBoxed'    => [ 'desktop' => [ 'value' => '1100', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::INNER, 'max-width:1100px' );
	}

	public function test_full_width_is_width_on_inner(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'containerType'  => 'full-width',
			'widthFullWidth' => [ 'desktop' => [ 'value' => '90', 'unit' => '%' ] ],
		] );
		$this->assertCssHas( $css, self::INNER, 'width:90%' );
	}

	public function test_advanced_layout_on_inner(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '2' ] ],
		] );
		$this->assertCssHas( $css, self::INNER, 'overflow:hidden' );
		$this->assertCssHas( $css, self::INNER, 'position:relative' );
		$this->assertCssHas( $css, self::INNER, 'z-index:2' );
	}

	public function test_wrapper_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#f8fafc', 'dark' => '#0f172a' ] ],
		] );
		$this->assertCssHas( $css, self::INNER, 'background-color:#f8fafc' );
		$this->assertCssHasInDark( $css, self::INNER, 'background-color:#0f172a' );
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $css );
	}

	public function test_wrapper_background_image_sub_properties(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'  => 'image',
				'image' => [ 'url' => 'https://example.com/bg.jpg', 'position' => 'top left', 'size' => 'contain', 'repeat' => 'repeat-x', 'attachment' => 'fixed' ],
			],
		] );
		$this->assertCssHas( $css, self::INNER, 'background-image:url(https://example.com/bg.jpg)' );
		$this->assertCssHas( $css, self::INNER, 'background-position:top left' );
		$this->assertCssHas( $css, self::INNER, 'background-size:contain' );
		$this->assertCssHas( $css, self::INNER, 'background-repeat:repeat-x' );
		$this->assertCssHas( $css, self::INNER, 'background-attachment:fixed' );
	}

	public function test_background_image_prints_url_once(): void {
		// The block ships no lazy-bg class, so the url applies once on the base
		// selector (never duplicated).
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'  => 'image',
				'image' => [ 'url' => 'https://example.com/bg.jpg', 'position' => 'center center', 'repeat' => 'no-repeat', 'size' => 'cover' ],
			],
		] );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	// --- 9 + border/shadow sub-properties on the card ---------------------

	public function test_border_all_sub_properties_plus_dark_on_card(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => '#e5e7eb', 'dark' => '#374151' ],
					'radius' => [ 'topLeft' => '8', 'topRight' => '8', 'bottomRight' => '8', 'bottomLeft' => '8', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::ITEM, 'border-style:solid' );
		$this->assertCssHas( $css, self::ITEM, 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, self::ITEM, 'border-color:#e5e7eb' );
		$this->assertCssHas( $css, self::ITEM, 'border-radius:8px 8px 8px 8px' );
		$this->assertCssHasInDark( $css, self::ITEM, 'border-color:#374151' );
		$this->assertStringNotContainsString( self::INNER . '{border-style', $css );
	}

	public function test_box_shadow_light_and_dark_on_card(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [
				'enabled'    => true,
				'horizontal' => '0',
				'vertical'   => '6',
				'blur'       => '16',
				'spread'     => '0',
				'color'      => [ 'light' => 'rgba(0,0,0,0.12)', 'dark' => 'rgba(0,0,0,0.6)' ],
				'inset'      => false,
			],
		] );
		$this->assertCssHas( $css, self::ITEM, 'box-shadow:0px 6px 16px 0px rgba(0,0,0,0.12)' );
		$this->assertCssHasInDark( $css, self::ITEM, 'box-shadow:0px 6px 16px 0px rgba(0,0,0,0.6)' );
	}

	// --- Typography per part (base + responsive) --------------------------

	public function test_card_typography_on_each_part(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'headerTypography'  => [ 'desktop' => [ 'fontSize' => [ 'value' => '18', 'unit' => 'px' ], 'fontWeight' => '700' ] ],
			'messageTypography' => [ 'desktop' => [ 'lineHeight' => '1.6' ] ],
			'metaTypography'    => [ 'desktop' => [ 'fontSize' => [ 'value' => '13', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, self::PAGE_NAME, 'font-size:18px' );
		$this->assertCssHas( $css, self::PAGE_NAME, 'font-weight:700' );
		$this->assertCssHas( $css, self::MESSAGE, 'line-height:1.6' );
		$this->assertCssHas( $css, self::TIMESTAMP, 'font-size:13px' );
		$this->assertCssHas( $css, self::ACTIONS, 'font-size:13px' );
	}

	public function test_header_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'headerTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::PAGE_NAME, 'font-size:15px' );
	}

	public function test_typography_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'font-size:', $css );
	}

	// --- Colours light + dark ---------------------------------------------

	public function test_card_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'headerColor'  => [ 'light' => '#111827', 'dark' => '#f9fafb' ],
			'messageColor' => [ 'light' => '#374151', 'dark' => '#d1d5db' ],
			'metaColor'    => [ 'light' => '#6b7280', 'dark' => '#9ca3af' ],
		] );
		$this->assertCssHas( $css, self::PAGE_NAME, 'color:#111827' );
		$this->assertCssHas( $css, self::MESSAGE, 'color:#374151' );
		$this->assertCssHas( $css, self::TIMESTAMP, 'color:#6b7280' );
		$this->assertCssHas( $css, self::ACTIONS, 'color:#6b7280' );
		$this->assertCssHasInDark( $css, self::PAGE_NAME, 'color:#f9fafb' );
		$this->assertCssHasInDark( $css, self::MESSAGE, 'color:#d1d5db' );
		$this->assertCssHasInDark( $css, self::TIMESTAMP, 'color:#9ca3af' );
	}

	public function test_card_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'cardBackground' => [ 'light' => '#ffffff', 'dark' => '#1f2937' ],
		] );
		$this->assertCssHas( $css, self::ITEM, 'background:#ffffff' );
		$this->assertCssHasInDark( $css, self::ITEM, 'background:#1f2937' );
	}

	public function test_content_padding_and_gap_on_card_responsive(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'contentPadding' => [
				'desktop' => [ 'top' => '14', 'right' => '14', 'bottom' => '14', 'left' => '14', 'unit' => 'px' ],
				'tablet'  => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ],
			],
			'contentGap'     => [ 'desktop' => [ 'value' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::ITEM, 'padding:14px 14px 14px 14px' );
		$this->assertCssHas( $css, self::ITEM, 'gap:10px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::ITEM, 'padding:10px 10px 10px 10px' );
	}

	public function test_card_colours_absent_when_unset(): void {
		// Only the header colour is set → no message / meta / card rules.
		$css = $this->gen( [
			'blockId'     => 'a',
			'headerColor' => [ 'light' => '#000000' ],
		] );
		$this->assertStringNotContainsString( '__message{', $css );
		$this->assertStringNotContainsString( '__timestamp{', $css );
		$this->assertStringNotContainsString( '__actions{', $css );
	}

	// --- Pagination -------------------------------------------------------

	public function test_pagination_link_and_active_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'                    => 'a',
			'paginationType'             => 'numbered',
			'paginationAlign'            => 'right',
			'paginationColor'            => [ 'light' => '#374151', 'dark' => '#d1d5db' ],
			'paginationBackground'       => [ 'light' => '#f3f4f6', 'dark' => '#111827' ],
			'paginationActiveColor'      => [ 'light' => '#ffffff', 'dark' => '#f9fafb' ],
			'paginationActiveBackground' => [ 'light' => '#2563eb', 'dark' => '#1d4ed8' ],
			'paginationRadius'           => [ 'value' => '4', 'unit' => 'px' ],
			'paginationFontSize'         => [ 'value' => '18', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::PAGER, 'justify-content:flex-end' );
		$this->assertCssHas( $css, self::PAGE_NUM, 'color:#374151' );
		$this->assertCssHas( $css, self::PAGE_NUM, 'background:#f3f4f6' );
		$this->assertCssHas( $css, self::PAGE_NUM, 'border-radius:4px' );
		$this->assertCssHas( $css, self::PAGE_NUM, 'font-size:18px' );
		$this->assertCssHas( $css, self::PAGE_CUR, 'color:#ffffff' );
		$this->assertCssHas( $css, self::PAGE_CUR, 'background:#2563eb' );
		$this->assertCssHasInDark( $css, self::PAGE_NUM, 'color:#d1d5db' );
		$this->assertCssHasInDark( $css, self::PAGE_CUR, 'background:#1d4ed8' );
	}

	public function test_pagination_absent_when_type_none(): void {
		// Numbered/load-more styling is gated on the pagination type.
		$css = $this->gen( [
			'blockId'         => 'a',
			'paginationType'  => 'none',
			'paginationColor' => [ 'light' => '#374151' ],
		] );
		$this->assertStringNotContainsString( 'flexa-pagination', $css );
	}

	public function test_loadmore_button_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'            => 'a',
			'paginationType'     => 'loadmore',
			'paginationAlign'    => 'right',
			'loadMoreColor'      => [ 'light' => '#ffffff', 'dark' => '#f3f4f6' ],
			'loadMoreBackground' => [ 'light' => '#2563eb', 'dark' => '#1d4ed8' ],
			'paginationFontSize' => [ 'value' => '17', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::LM_WRAP, 'justify-content:flex-end' );
		$this->assertCssHas( $css, self::LM_BTN, 'color:#ffffff' );
		$this->assertCssHas( $css, self::LM_BTN, 'background:#2563eb' );
		$this->assertCssHas( $css, self::LM_BTN, 'font-size:17px' );
		$this->assertCssHasInDark( $css, self::LM_BTN, 'background:#1d4ed8' );
	}

	// --- 8: data-theme dark-mode branch -----------------------------------

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'     => 'a',
			'headerColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::PAGE_NAME, 'color:#eeeeee', true );
		$this->assertStringContainsString( '[data-theme="dark"] ' . self::PAGE_NAME, $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}

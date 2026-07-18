<?php
/**
 * Tests for the RSS block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional grid columns, colours, typography and ratio and light/dark parity.
 * The grid columns and gaps are structural (emitted when set, grid layout only);
 * the card colours and typography follow the theme-first rule. The feed fetch
 * itself is exercised by WordPress, not here.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Rss_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Rss_CSS
 */
class RssCssTest extends CssTestCase {

	private const OUTER    = '.flexa-rss-a';
	private const INNER    = '.flexa-rss-a > .flexa-rss__inner';
	private const GRID     = '.flexa-rss-a .flexa-rss__grid';
	private const ITEM     = '.flexa-rss-a .flexa-rss__item';
	private const BODY     = '.flexa-rss-a .flexa-rss__body';
	private const IMAGE    = '.flexa-rss-a .flexa-rss__image';
	private const TITLE    = '.flexa-rss-a .flexa-rss__title';
	private const META     = '.flexa-rss-a .flexa-rss__meta';
	private const EXCERPT  = '.flexa-rss-a .flexa-rss__excerpt';
	private const READMORE = '.flexa-rss-a .flexa-rss__readmore';
	private const RM_HOVER = '.flexa-rss-a .flexa-rss__readmore:hover';

	/**
	 * Convenience wrapper around the RSS generator.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Rss_CSS::class, 'generate' ], $attrs );
	}

	// --- 1 & 2: guards / theme-first --------------------------------------

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_minimal_emits_only_equal_height_default(): void {
		// equalHeight defaults on (grid layout), so a bare block emits just the
		// height rule — no columns, no colours, no typography (theme decides).
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertCssHas( $css, self::ITEM, 'height:100%' );
		$this->assertStringNotContainsString( 'grid-template-columns', $css );
		$this->assertStringNotContainsString( 'color:', $css );
		$this->assertStringNotContainsString( 'font-size:', $css );
	}

	// --- 3 & 4: grid columns + gaps (structural, grid layout) -------------

	public function test_columns_emit_grid_template(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'columns' => [ 'desktop' => [ 'value' => '4', 'unit' => '' ] ],
		] );
		$this->assertCssHas( $css, self::GRID, 'grid-template-columns:repeat(4, minmax(0, 1fr))' );
	}

	public function test_columns_responsive_in_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'columns' => [ 'tablet' => [ 'value' => '2', 'unit' => '' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::GRID, 'grid-template-columns:repeat(2, minmax(0, 1fr))' );
	}

	public function test_columns_absent_in_list_layout(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'feedLayout' => 'list',
			'columns' => [ 'desktop' => [ 'value' => '4', 'unit' => '' ] ],
		] );
		$this->assertStringNotContainsString( 'grid-template-columns', $css );
	}

	public function test_row_and_column_gaps(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'rowGap'    => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ] ],
			'columnGap' => [ 'desktop' => [ 'value' => '16', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::GRID, 'row-gap:24px' );
		$this->assertCssHas( $css, self::GRID, 'column-gap:16px' );
	}

	public function test_column_gap_absent_in_list_layout(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'feedLayout' => 'list',
			'rowGap'    => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ] ],
			'columnGap' => [ 'desktop' => [ 'value' => '16', 'unit' => 'px' ] ],
		] );
		// Row gap still applies to the stacked list; column gap does not.
		$this->assertCssHas( $css, self::GRID, 'row-gap:24px' );
		$this->assertStringNotContainsString( 'column-gap', $css );
	}

	// --- 5, 6, 7: alignment / padding / spacing ---------------------------

	public function test_content_alignment_maps_to_text_align_and_flex(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'contentAlign' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, self::ITEM, 'text-align:center' );
		$this->assertCssHas( $css, self::ITEM, 'align-items:center' );

		$right = $this->gen( [ 'blockId' => 'a', 'contentAlign' => [ 'desktop' => 'right' ] ] );
		$this->assertCssHas( $right, self::ITEM, 'text-align:right' );
		$this->assertCssHas( $right, self::ITEM, 'align-items:flex-end' );
	}

	public function test_content_padding_on_body(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'contentPadding' => [ 'desktop' => [ 'top' => '12', 'right' => '16', 'bottom' => '12', 'left' => '16', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::BODY, 'padding:12px 16px 12px 16px' );
	}

	public function test_content_padding_responsive_in_media_query(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'contentPadding' => [ 'mobile' => [ 'top' => '8', 'right' => '8', 'bottom' => '8', 'left' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::BODY, 'padding:8px 8px 8px 8px' );
	}

	public function test_content_gap_on_body(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'contentGap' => [ 'desktop' => [ 'value' => '14', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::BODY, 'gap:14px' );
	}

	public function test_content_gap_responsive_in_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'contentGap' => [ 'tablet' => [ 'value' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::BODY, 'gap:10px' );
	}

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

	// --- border / advanced / image / equal height -------------------------

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

	public function test_image_ratio_emits_aspect_ratio_and_fit(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'imageRatio' => '16/9' ] );
		$this->assertCssHas( $css, self::IMAGE, 'aspect-ratio:16/9' );
		$this->assertCssHas( $css, self::IMAGE, 'object-fit:cover' );
		$this->assertCssHas( $css, self::IMAGE, 'width:100%' );
	}

	public function test_image_ratio_absent_when_empty_or_invalid(): void {
		$this->assertStringNotContainsString( 'aspect-ratio', $this->gen( [ 'blockId' => 'a', 'imageRatio' => '' ] ) );
		$this->assertStringNotContainsString( 'aspect-ratio', $this->gen( [ 'blockId' => 'a', 'imageRatio' => 'wide' ] ) );
	}

	public function test_equal_height_gating(): void {
		$on = $this->gen( [ 'blockId' => 'a', 'equalHeight' => true ] );
		$this->assertCssHas( $on, self::ITEM, 'height:100%' );

		$off = $this->gen( [ 'blockId' => 'a', 'equalHeight' => false ] );
		$this->assertStringNotContainsString( 'height:100%', $off );
	}

	public function test_equal_height_absent_in_list_layout(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'feedLayout' => 'list', 'equalHeight' => true ] );
		$this->assertStringNotContainsString( 'height:100%', $css );
	}

	// --- typography / colours ---------------------------------------------

	public function test_card_typography_on_each_part(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'titleTypography'   => [ 'desktop' => [ 'fontSize' => [ 'value' => '22', 'unit' => 'px' ], 'fontWeight' => '700' ] ],
			'metaTypography'    => [ 'desktop' => [ 'fontSize' => [ 'value' => '13', 'unit' => 'px' ] ] ],
			'excerptTypography' => [ 'desktop' => [ 'lineHeight' => '1.6' ] ],
		] );
		$this->assertCssHas( $css, self::TITLE, 'font-size:22px' );
		$this->assertCssHas( $css, self::TITLE, 'font-weight:700' );
		$this->assertCssHas( $css, self::META, 'font-size:13px' );
		$this->assertCssHas( $css, self::EXCERPT, 'line-height:1.6' );
	}

	public function test_title_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'titleTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '18', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::TITLE, 'font-size:18px' );
	}

	public function test_card_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'titleColor'   => [ 'light' => '#111827', 'dark' => '#f9fafb' ],
			'metaColor'    => [ 'light' => '#6b7280', 'dark' => '#9ca3af' ],
			'excerptColor' => [ 'light' => '#374151', 'dark' => '#d1d5db' ],
		] );
		$this->assertCssHas( $css, self::TITLE, 'color:#111827' );
		$this->assertCssHas( $css, self::META, 'color:#6b7280' );
		$this->assertCssHas( $css, self::EXCERPT, 'color:#374151' );
		$this->assertCssHasInDark( $css, self::TITLE, 'color:#f9fafb' );
		$this->assertCssHasInDark( $css, self::META, 'color:#9ca3af' );
		$this->assertCssHasInDark( $css, self::EXCERPT, 'color:#d1d5db' );
	}

	public function test_card_background_light_and_dark(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'cardBackground' => [ 'light' => '#ffffff', 'dark' => '#1f2937' ] ] );
		$this->assertCssHas( $css, self::ITEM, 'background:#ffffff' );
		$this->assertCssHasInDark( $css, self::ITEM, 'background:#1f2937' );
	}

	public function test_colours_unset_emit_nothing(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'titleColor' => [ 'light' => '', 'dark' => '' ] ] );
		$this->assertStringNotContainsString( 'color:', $css );
	}

	// --- read-more button --------------------------------------------------

	public function test_read_more_button_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'buttonTextColor'  => [ 'light' => '#ffffff', 'dark' => '#f3f4f6' ],
			'buttonBackground' => [ 'light' => '#2563eb', 'dark' => '#1d4ed8' ],
		] );
		$this->assertCssHas( $css, self::READMORE, 'color:#ffffff' );
		$this->assertCssHas( $css, self::READMORE, 'background:#2563eb' );
		$this->assertCssHasInDark( $css, self::READMORE, 'color:#f3f4f6' );
		$this->assertCssHasInDark( $css, self::READMORE, 'background:#1d4ed8' );
	}

	public function test_read_more_button_hover_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'buttonTextColorHover'  => [ 'light' => '#111827', 'dark' => '#e5e7eb' ],
			'buttonBackgroundHover' => [ 'light' => '#1d4ed8', 'dark' => '#1e40af' ],
		] );
		$this->assertCssHas( $css, self::RM_HOVER, 'color:#111827' );
		$this->assertCssHas( $css, self::RM_HOVER, 'background:#1d4ed8' );
		$this->assertCssHasInDark( $css, self::RM_HOVER, 'color:#e5e7eb' );
		$this->assertCssHasInDark( $css, self::RM_HOVER, 'background:#1e40af' );
	}

	public function test_read_more_button_typography_radius_padding(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'buttonTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ], 'fontWeight' => '600' ] ],
			'buttonRadius'     => [ 'value' => '6', 'unit' => 'px' ],
			'buttonPadding'    => [ 'top' => '8', 'right' => '16', 'bottom' => '8', 'left' => '16', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::READMORE, 'font-size:15px' );
		$this->assertCssHas( $css, self::READMORE, 'font-weight:600' );
		$this->assertCssHas( $css, self::READMORE, 'border-radius:6px' );
		$this->assertCssHas( $css, self::READMORE, 'padding:8px 16px 8px 16px' );
	}

	public function test_button_alignment_and_full_width(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'buttonAlign' => 'center' ] );
		$this->assertCssHas( $css, self::READMORE, 'align-self:center' );

		$full = $this->gen( [ 'blockId' => 'a', 'buttonWidth' => 'full' ] );
		$this->assertCssHas( $full, self::READMORE, 'display:flex' );
		$this->assertCssHas( $full, self::READMORE, 'width:100%' );
		$this->assertCssHas( $full, self::READMORE, 'justify-content:center' );
	}

	// --- pagination -------------------------------------------------------

	public function test_numbered_pagination_alignment_and_colours(): void {
		$css = $this->gen( [
			'blockId'                    => 'a',
			'paginationType'             => 'numbered',
			'paginationAlign'            => 'right',
			'paginationColor'            => [ 'light' => '#333333', 'dark' => '#eeeeee' ],
			'paginationBackground'       => [ 'light' => '#f0f0f0', 'dark' => '#222222' ],
			'paginationActiveColor'      => [ 'light' => '#ffffff', 'dark' => '#000000' ],
			'paginationActiveBackground' => [ 'light' => '#2563eb', 'dark' => '#1d4ed8' ],
			'paginationRadius'           => [ 'value' => '4', 'unit' => 'px' ],
			'paginationFontSize'         => [ 'value' => '18', 'unit' => 'px' ],
		] );
		$pager = '.flexa-rss-a .flexa-pagination';
		$num   = $pager . ' .page-numbers';
		$cur   = $pager . ' .page-numbers.current';
		$this->assertCssHas( $css, $pager, 'justify-content:flex-end' );
		$this->assertCssHas( $css, $num, 'color:#333333' );
		$this->assertCssHas( $css, $num, 'background:#f0f0f0' );
		$this->assertCssHas( $css, $num, 'border-radius:4px' );
		$this->assertCssHas( $css, $num, 'font-size:18px' );
		$this->assertCssHasInDark( $css, $num, 'color:#eeeeee' );
		$this->assertCssHas( $css, $cur, 'color:#ffffff' );
		$this->assertCssHas( $css, $cur, 'background:#2563eb' );
		$this->assertCssHasInDark( $css, $cur, 'background:#1d4ed8' );
	}

	public function test_loadmore_alignment_and_button_colours(): void {
		$css = $this->gen( [
			'blockId'            => 'a',
			'paginationType'     => 'loadmore',
			'paginationAlign'    => 'left',
			'loadMoreColor'      => [ 'light' => '#ffffff', 'dark' => '#f3f4f6' ],
			'loadMoreBackground' => [ 'light' => '#2563eb', 'dark' => '#1d4ed8' ],
			'paginationFontSize' => [ 'value' => '17', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, '.flexa-rss-a .flexa-pagination-loadmore', 'justify-content:flex-start' );
		$this->assertCssHas( $css, '.flexa-rss-a .flexa-pagination-loadmore__btn', 'color:#ffffff' );
		$this->assertCssHas( $css, '.flexa-rss-a .flexa-pagination-loadmore__btn', 'background:#2563eb' );
		$this->assertCssHas( $css, '.flexa-rss-a .flexa-pagination-loadmore__btn', 'font-size:17px' );
		$this->assertCssHasInDark( $css, '.flexa-rss-a .flexa-pagination-loadmore__btn', 'background:#1d4ed8' );
	}

	public function test_pagination_absent_when_none(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'paginationType' => 'none', 'paginationAlign' => 'right', 'paginationColor' => [ 'light' => '#333333', 'dark' => '' ] ] );
		$this->assertStringNotContainsString( 'flexa-pagination', $css );
	}

	// --- foundational background + box shadow -----------------------------

	public function test_foundational_background_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertCssHas( $css, self::INNER, 'background-color:#ffffff' );
		$this->assertCssHasInDark( $css, self::INNER, 'background-color:#000000' );
	}

	public function test_background_image_emits_url_eagerly(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'image', 'image' => [ 'url' => 'https://example.com/a.jpg', 'size' => 'cover' ] ],
		] );
		$this->assertCssHas( $css, self::INNER, 'background-image:url(https://example.com/a.jpg)' );
		$this->assertStringNotContainsString( 'flexa-bg-loaded', $css );
	}

	public function test_box_shadow_light_and_dark_on_card(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::ITEM, 'box-shadow:0px 2px 8px 0px #000000' );
		$this->assertCssHasInDark( $css, self::ITEM, 'box-shadow:0px 2px 8px 0px #ffffff' );
	}

	// --- data-theme dark-mode branch --------------------------------------

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [ 'blockId' => 'a', 'titleColor' => [ 'light' => '#111827', 'dark' => '#f9fafb' ] ] );

		$this->assertCssHas( $css, self::TITLE, 'color:#111827' );
		$this->assertCssHasInDark( $css, self::TITLE, 'color:#f9fafb', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}

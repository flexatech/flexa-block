<?php
/**
 * Tests for the Post Grid block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional colours / typography / ratio and light/dark parity. The grid
 * columns and gaps are structural (emitted when set); the card colours and
 * typography follow the theme-first rule (emitted only when the user picks a
 * value). The WP_Query itself is exercised by WordPress, not here.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Post_Grid_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Post_Grid_CSS
 */
class PostGridCssTest extends CssTestCase {

	private const OUTER    = '.flexa-post-grid-a';
	private const INNER    = '.flexa-post-grid-a > .flexa-post-grid__inner';
	private const GRID     = '.flexa-post-grid-a .flexa-post-grid__grid';
	private const ITEM     = '.flexa-post-grid-a .flexa-post-grid__item';
	private const IMAGE    = '.flexa-post-grid-a .flexa-post-grid__image';
	private const TITLE    = '.flexa-post-grid-a .flexa-post-grid__title';
	private const META     = '.flexa-post-grid-a .flexa-post-grid__meta';
	private const EXCERPT  = '.flexa-post-grid-a .flexa-post-grid__excerpt';
	private const READMORE = '.flexa-post-grid-a .flexa-post-grid__readmore';
	private const RM_HOVER = '.flexa-post-grid-a .flexa-post-grid__readmore:hover';
	private const PAGER    = '.flexa-post-grid-a .flexa-pagination';
	private const PAGE_NUM = '.flexa-post-grid-a .flexa-pagination .page-numbers';
	private const PAGE_CUR = '.flexa-post-grid-a .flexa-pagination .page-numbers.current';

	/**
	 * Convenience wrapper around the post-grid generator.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Post_Grid_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_minimal_emits_only_equal_height_default(): void {
		// equalHeight defaults on, so a bare block emits just the height rule — no
		// grid columns, no colours, no typography (those come from the theme).
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertCssHas( $css, self::ITEM, 'height:100%' );
		$this->assertStringNotContainsString( 'grid-template-columns', $css );
		$this->assertStringNotContainsString( 'color:', $css );
		$this->assertStringNotContainsString( 'font-size:', $css );
		$this->assertStringNotContainsString( 'background', $css );
	}

	public function test_columns_emit_grid_template(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'columns' => [ 'desktop' => [ 'value' => '4', 'unit' => '' ] ],
		] );
		$this->assertCssHas( $css, self::GRID, 'grid-template-columns:repeat(4, minmax(0, 1fr))' );
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

	public function test_content_alignment_maps_to_text_align_and_flex(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'contentAlign' => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, self::ITEM, 'text-align:center' );
		$this->assertCssHas( $css, self::ITEM, 'align-items:center' );

		$right = $this->gen( [
			'blockId'      => 'a',
			'contentAlign' => [ 'desktop' => 'right' ],
		] );
		$this->assertCssHas( $right, self::ITEM, 'text-align:right' );
		$this->assertCssHas( $right, self::ITEM, 'align-items:flex-end' );
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

	public function test_border_all_sub_properties_plus_dark_on_card(): void {
		// Border targets each post card (like the card background), not the wrapper.
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
		// Not on the wrapper inner element.
		$this->assertStringNotContainsString( self::INNER . '{border-style', $css );
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
		$css = $this->gen( [
			'blockId'    => 'a',
			'imageRatio' => '16/9',
		] );
		$this->assertCssHas( $css, self::IMAGE, 'aspect-ratio:16/9' );
		$this->assertCssHas( $css, self::IMAGE, 'object-fit:cover' );
		$this->assertCssHas( $css, self::IMAGE, 'width:100%' );
	}

	public function test_image_ratio_absent_when_empty_or_invalid(): void {
		$empty = $this->gen( [ 'blockId' => 'a', 'imageRatio' => '' ] );
		$this->assertStringNotContainsString( 'aspect-ratio', $empty );

		$bad = $this->gen( [ 'blockId' => 'a', 'imageRatio' => 'wide' ] );
		$this->assertStringNotContainsString( 'aspect-ratio', $bad );
	}

	public function test_equal_height_gating(): void {
		$on = $this->gen( [ 'blockId' => 'a', 'equalHeight' => true ] );
		$this->assertCssHas( $on, self::ITEM, 'height:100%' );

		$off = $this->gen( [ 'blockId' => 'a', 'equalHeight' => false ] );
		$this->assertStringNotContainsString( 'height:100%', $off );
	}

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
		$css = $this->gen( [
			'blockId'        => 'a',
			'cardBackground' => [ 'light' => '#ffffff', 'dark' => '#1f2937' ],
		] );
		$this->assertCssHas( $css, self::ITEM, 'background:#ffffff' );
		$this->assertCssHasInDark( $css, self::ITEM, 'background:#1f2937' );
	}

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

	public function test_read_more_button_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'buttonTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '13', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::READMORE, 'font-size:13px' );
	}

	public function test_button_alignment_maps_to_align_self(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'buttonAlign' => 'center' ] );
		$this->assertCssHas( $css, self::READMORE, 'align-self:center' );

		$right = $this->gen( [ 'blockId' => 'a', 'buttonAlign' => 'right' ] );
		$this->assertCssHas( $right, self::READMORE, 'align-self:flex-end' );
	}

	public function test_button_alignment_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'align-self', $css );
	}

	public function test_button_full_width_gated_on(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'buttonWidth' => 'full' ] );
		$this->assertCssHas( $css, self::READMORE, 'width:100%' );
		$this->assertCssHas( $css, self::READMORE, 'justify-content:center' );

		$auto = $this->gen( [ 'blockId' => 'a', 'buttonWidth' => 'auto' ] );
		$this->assertStringNotContainsString( 'width:100%', $auto );
	}

	public function test_content_padding_on_body_responsive(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'contentPadding' => [
				'desktop' => [ 'top' => '16', 'right' => '20', 'bottom' => '16', 'left' => '20', 'unit' => 'px' ],
				'tablet'  => [ 'top' => '10', 'right' => '12', 'bottom' => '10', 'left' => '12', 'unit' => 'px' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-post-grid-a .flexa-post-grid__body', 'padding:16px 20px 16px 20px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-post-grid-a .flexa-post-grid__body', 'padding:10px 12px 10px 12px' );
	}

	public function test_content_padding_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( '.flexa-post-grid__body{', $css );
	}

	public function test_content_gap_on_body_responsive(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'contentGap' => [
				'desktop' => [ 'value' => '8', 'unit' => 'px' ],
				'tablet'  => [ 'value' => '4', 'unit' => 'px' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-post-grid-a .flexa-post-grid__body', 'gap:8px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-post-grid-a .flexa-post-grid__body', 'gap:4px' );
	}

	public function test_pagination_alignment_maps_to_justify_content(): void {
		$left = $this->gen( [ 'blockId' => 'a', 'paginationAlign' => 'left' ] );
		$this->assertCssHas( $left, self::PAGER, 'justify-content:flex-start' );

		$right = $this->gen( [ 'blockId' => 'a', 'paginationAlign' => 'right' ] );
		$this->assertCssHas( $right, self::PAGER, 'justify-content:flex-end' );

		$center = $this->gen( [ 'blockId' => 'a', 'paginationAlign' => 'center' ] );
		$this->assertCssHas( $center, self::PAGER, 'justify-content:center' );
	}

	public function test_pagination_alignment_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'justify-content', $css );
	}

	public function test_pagination_link_and_active_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'                    => 'a',
			'paginationColor'            => [ 'light' => '#374151', 'dark' => '#d1d5db' ],
			'paginationBackground'       => [ 'light' => '#f3f4f6', 'dark' => '#111827' ],
			'paginationActiveColor'      => [ 'light' => '#ffffff', 'dark' => '#f9fafb' ],
			'paginationActiveBackground' => [ 'light' => '#2563eb', 'dark' => '#1d4ed8' ],
			'paginationRadius'           => [ 'value' => '4', 'unit' => 'px' ],
			'paginationFontSize'         => [ 'value' => '18', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::PAGE_NUM, 'color:#374151' );
		$this->assertCssHas( $css, self::PAGE_NUM, 'background:#f3f4f6' );
		$this->assertCssHas( $css, self::PAGE_NUM, 'border-radius:4px' );
		$this->assertCssHas( $css, self::PAGE_NUM, 'font-size:18px' );
		$this->assertCssHas( $css, self::PAGE_CUR, 'color:#ffffff' );
		$this->assertCssHas( $css, self::PAGE_CUR, 'background:#2563eb' );
		$this->assertCssHasInDark( $css, self::PAGE_NUM, 'color:#d1d5db' );
		$this->assertCssHasInDark( $css, self::PAGE_NUM, 'background:#111827' );
		$this->assertCssHasInDark( $css, self::PAGE_CUR, 'color:#f9fafb' );
		$this->assertCssHasInDark( $css, self::PAGE_CUR, 'background:#1d4ed8' );
	}

	/**
	 * Hover previews the active state: pointing at a page shows the colours it will
	 * wear once it IS the current page. So there is no third colour pair, and no way to
	 * configure a hover that contradicts the page it leads to. Only the LINKS react —
	 * `.current` already wears them, and a page hovering into its own colours reads as
	 * inert.
	 */
	public function test_pagination_hover_takes_the_active_colours(): void {
		$hover = '.flexa-post-grid-a .flexa-pagination a.page-numbers:hover, .flexa-post-grid-a .flexa-pagination a.page-numbers:focus-visible';

		$css = $this->gen( [
			'blockId'                    => 'a',
			'paginationActiveColor'      => [ 'light' => '#ffffff', 'dark' => '#f9fafb' ],
			'paginationActiveBackground' => [ 'light' => '#2563eb', 'dark' => '#1d4ed8' ],
		] );

		$this->assertCssHas( $css, $hover, 'color:#ffffff' );
		$this->assertCssHas( $css, $hover, 'background:#2563eb' );
		$this->assertCssHasInDark( $css, $hover, 'color:#f9fafb' );
		$this->assertCssHasInDark( $css, $hover, 'background:#1d4ed8' );
	}

	public function test_pagination_colours_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
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
		$this->assertCssHas( $css, '.flexa-post-grid-a .flexa-pagination-loadmore', 'justify-content:flex-end' );
		$this->assertCssHas( $css, '.flexa-post-grid-a .flexa-pagination-loadmore__btn', 'color:#ffffff' );
		$this->assertCssHas( $css, '.flexa-post-grid-a .flexa-pagination-loadmore__btn', 'background:#2563eb' );
		$this->assertCssHas( $css, '.flexa-post-grid-a .flexa-pagination-loadmore__btn', 'font-size:17px' );
		$this->assertCssHasInDark( $css, '.flexa-post-grid-a .flexa-pagination-loadmore__btn', 'background:#1d4ed8' );
	}

	public function test_card_colours_absent_when_unset(): void {
		// Only the title colour is set → no meta / excerpt / card / button rules.
		$css = $this->gen( [
			'blockId'    => 'a',
			'titleColor' => [ 'light' => '#000000' ],
		] );
		$this->assertStringNotContainsString( '__meta{', $css );
		$this->assertStringNotContainsString( '__excerpt{', $css );
		$this->assertStringNotContainsString( '__readmore', $css );
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

	public function test_lazy_background_image_prints_url_once_behind_loaded_class(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'image',
				'lazyLoad' => true,
				'image'    => [ 'url' => 'https://example.com/bg.jpg', 'position' => 'center center', 'repeat' => 'no-repeat', 'size' => 'cover' ],
			],
		] );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
		$this->assertCssHas( $css, self::INNER . '.flexa-bg-loaded', 'background-image:url(https://example.com/bg.jpg)' );
	}

	public function test_box_shadow_light_and_dark_on_card(): void {
		// Box shadow targets each post card (like the card background), not the wrapper.
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

	public function test_responsive_columns_and_gap_in_media_queries(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'columns'   => [ 'tablet' => [ 'value' => '2', 'unit' => '' ], 'mobile' => [ 'value' => '1', 'unit' => '' ] ],
			'rowGap'    => [ 'tablet' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::GRID, 'grid-template-columns:repeat(2, minmax(0, 1fr))' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::GRID, 'row-gap:12px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::GRID, 'grid-template-columns:repeat(1, minmax(0, 1fr))' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'    => 'a',
			'titleColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::TITLE, 'color:#eeeeee', true );
		$this->assertStringContainsString( '[data-theme="dark"] ' . self::TITLE, $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}

	/**
	 * The result count is styled only where it can be seen. Left screen-reader-only,
	 * `screen-reader-text` clips it to one pixel — a font size on that is a rule about
	 * nothing, and it would fight the clip if it ever grew a `line-height`.
	 */
	public function test_result_count_is_styled_only_in_its_shown_state(): void {
		$status = '.flexa-post-grid-a .flexa-post-grid__status:not(.screen-reader-text)';

		$css = $this->gen( [
			'blockId'               => 'a',
			'resultCountTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '18', 'unit' => 'px' ] ], 'tablet' => [], 'mobile' => [] ],
			'resultCountColor'      => [ 'light' => '#334155', 'dark' => '#94a3b8' ],
			'resultCountAlign'      => 'center',
		] );

		$this->assertCssHas( $css, $status, 'font-size:18px' );
		$this->assertCssHas( $css, $status, 'color:#334155' );
		$this->assertCssHas( $css, $status, 'text-align:center' );
		$this->assertCssHasInDark( $css, $status, 'color:#94a3b8' );
	}

	public function test_result_count_emits_nothing_when_unstyled(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'flexa-post-grid__status', $css );
	}
}

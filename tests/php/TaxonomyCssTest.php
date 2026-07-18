<?php
/**
 * Tests for the Taxonomy block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional grid columns / colours / typography and light/dark parity. The grid
 * columns are structural (emitted when set, grid display only); the chip colours
 * and typography follow the theme-first rule (emitted only when the user picks a
 * value). The term query itself is exercised by WordPress, not here.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Taxonomy_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Taxonomy_CSS
 */
class TaxonomyCssTest extends CssTestCase {

	private const WRAP    = '.flexa-taxonomy-a';
	private const LIST    = '.flexa-taxonomy-a .flexa-taxonomy__list';
	private const ITEM    = '.flexa-taxonomy-a .flexa-taxonomy__item';
	private const ITEM_ON = '.flexa-taxonomy-a .flexa-taxonomy__item:hover';
	private const LINK    = '.flexa-taxonomy-a .flexa-taxonomy__link';
	private const LINK_ON = '.flexa-taxonomy-a .flexa-taxonomy__link:hover';
	private const COUNT   = '.flexa-taxonomy-a .flexa-taxonomy__count';
	private const PREFIX  = '.flexa-taxonomy-a .flexa-taxonomy__prefix';
	private const SUFFIX  = '.flexa-taxonomy-a .flexa-taxonomy__suffix';
	private const SEP     = '.flexa-taxonomy-a .flexa-taxonomy__separator';

	/**
	 * Convenience wrapper around the taxonomy generator.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Taxonomy_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// No values picked → no CSS: the structural look comes from style.scss.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_columns_emit_grid_template_only_in_grid_display(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'displayStyle' => 'grid',
			'columns'      => [ 'desktop' => [ 'value' => '3', 'unit' => '' ] ],
		] );
		$this->assertCssHas( $css, self::LIST, 'grid-template-columns:repeat(3, minmax(0, 1fr))' );
	}

	public function test_columns_absent_when_not_grid(): void {
		// Same columns, but a list display → no grid template is emitted.
		$css = $this->gen( [
			'blockId'      => 'a',
			'displayStyle' => 'list',
			'columns'      => [ 'desktop' => [ 'value' => '3', 'unit' => '' ] ],
		] );
		$this->assertStringNotContainsString( 'grid-template-columns', $css );
	}

	public function test_columns_responsive_in_media_queries(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'displayStyle' => 'grid',
			'columns'      => [ 'tablet' => [ 'value' => '2', 'unit' => '' ], 'mobile' => [ 'value' => '1', 'unit' => '' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::LIST, 'grid-template-columns:repeat(2, minmax(0, 1fr))' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::LIST, 'grid-template-columns:repeat(1, minmax(0, 1fr))' );
	}

	public function test_alignment_maps_to_text_align_and_justify_content(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, self::LIST, 'text-align:center' );
		$this->assertCssHas( $css, self::LIST, 'justify-content:center' );

		$right = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'right' ],
		] );
		$this->assertCssHas( $right, self::LIST, 'text-align:right' );
		$this->assertCssHas( $right, self::LIST, 'justify-content:flex-end' );
	}

	public function test_gap_on_list_responsive(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'gap'     => [
				'desktop' => [ 'value' => '12', 'unit' => 'px' ],
				'tablet'  => [ 'value' => '6', 'unit' => 'px' ],
			],
		] );
		$this->assertCssHas( $css, self::LIST, 'gap:12px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::LIST, 'gap:6px' );
	}

	public function test_item_padding_on_link_responsive(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'itemPadding' => [
				'desktop' => [ 'top' => '6', 'right' => '12', 'bottom' => '6', 'left' => '12', 'unit' => 'px' ],
				'tablet'  => [ 'top' => '4', 'right' => '8', 'bottom' => '4', 'left' => '8', 'unit' => 'px' ],
			],
		] );
		$this->assertCssHas( $css, self::LINK, 'padding:6px 12px 6px 12px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::LINK, 'padding:4px 8px 4px 8px' );
	}

	public function test_item_padding_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'padding', $css );
	}

	public function test_item_border_radius(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'itemBorderRadius' => [ 'value' => '8', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::ITEM, 'border-radius:8px' );
	}

	public function test_item_border_radius_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'border-radius', $css );
	}

	public function test_typography_on_link(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [
				'desktop' => [
					'fontSize'   => [ 'value' => '15', 'unit' => 'px' ],
					'fontWeight' => '600',
				],
			],
		] );
		$this->assertCssHas( $css, self::LINK, 'font-size:15px' );
		$this->assertCssHas( $css, self::LINK, 'font-weight:600' );
	}

	public function test_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '13', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::LINK, 'font-size:13px' );
	}

	public function test_item_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'itemColor' => [ 'light' => '#1e40af', 'dark' => '#93c5fd' ],
		] );
		$this->assertCssHas( $css, self::LINK, 'color:#1e40af' );
		$this->assertCssHasInDark( $css, self::LINK, 'color:#93c5fd' );
	}

	public function test_item_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'itemBackground' => [ 'light' => '#f3f4f6', 'dark' => '#1f2937' ],
		] );
		$this->assertCssHas( $css, self::ITEM, 'background:#f3f4f6' );
		$this->assertCssHasInDark( $css, self::ITEM, 'background:#1f2937' );
	}

	public function test_hover_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'             => 'a',
			'itemHoverColor'      => [ 'light' => '#111827', 'dark' => '#f9fafb' ],
			'itemHoverBackground' => [ 'light' => '#e5e7eb', 'dark' => '#374151' ],
		] );
		$this->assertCssHas( $css, self::ITEM_ON, 'color:#111827' );
		$this->assertCssHas( $css, self::LINK_ON, 'color:#111827' );
		$this->assertCssHas( $css, self::ITEM_ON, 'background:#e5e7eb' );
		$this->assertCssHasInDark( $css, self::ITEM_ON, 'color:#f9fafb' );
		$this->assertCssHasInDark( $css, self::LINK_ON, 'color:#f9fafb' );
		$this->assertCssHasInDark( $css, self::ITEM_ON, 'background:#374151' );
	}

	public function test_count_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'countColor' => [ 'light' => '#6b7280', 'dark' => '#9ca3af' ],
		] );
		$this->assertCssHas( $css, self::COUNT, 'color:#6b7280' );
		$this->assertCssHasInDark( $css, self::COUNT, 'color:#9ca3af' );
	}

	public function test_prefix_and_suffix_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'prefixColor' => [ 'light' => '#2563eb', 'dark' => '#60a5fa' ],
			'suffixColor' => [ 'light' => '#dc2626', 'dark' => '#f87171' ],
		] );
		$this->assertCssHas( $css, self::PREFIX, 'color:#2563eb' );
		$this->assertCssHas( $css, self::SUFFIX, 'color:#dc2626' );
		$this->assertCssHasInDark( $css, self::PREFIX, 'color:#60a5fa' );
		$this->assertCssHasInDark( $css, self::SUFFIX, 'color:#f87171' );
	}

	public function test_separator_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'separatorColor' => [ 'light' => '#9ca3af', 'dark' => '#6b7280' ],
		] );
		$this->assertCssHas( $css, self::SEP, 'color:#9ca3af' );
		$this->assertCssHasInDark( $css, self::SEP, 'color:#6b7280' );
	}

	public function test_colours_absent_when_unset(): void {
		// Only the term colour is set → no count / affix / separator / hover rules.
		$css = $this->gen( [
			'blockId'   => 'a',
			'itemColor' => [ 'light' => '#000000' ],
		] );
		$this->assertStringNotContainsString( '__count', $css );
		$this->assertStringNotContainsString( '__prefix', $css );
		$this->assertStringNotContainsString( '__suffix', $css );
		$this->assertStringNotContainsString( '__separator', $css );
		$this->assertStringNotContainsString( ':hover', $css );
	}

	public function test_prefix_and_suffix_icon_size_responsive(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'prefixIconSize' => [
				'desktop' => [ 'value' => '20', 'unit' => 'px' ],
				'tablet'  => [ 'value' => '16', 'unit' => 'px' ],
			],
			'suffixIconSize' => [ 'desktop' => [ 'value' => '18', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::PREFIX, 'font-size:20px' );
		$this->assertCssHas( $css, self::PREFIX, 'width:20px' );
		$this->assertCssHas( $css, self::SUFFIX, 'font-size:18px' );
		$this->assertCssHas( $css, self::SUFFIX, 'width:18px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::PREFIX, 'font-size:16px' );
	}

	public function test_wrapper_spacing_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '8', 'right' => '12', 'bottom' => '8', 'left' => '12', 'unit' => 'px' ],
					'margin'  => [ 'top' => '16', 'right' => '0', 'bottom' => '16', 'left' => '0', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:8px 12px 8px 12px' );
		$this->assertCssHas( $css, self::WRAP, 'margin:16px 0px 16px 0px' );
	}

	public function test_advanced_layout_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '3' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'overflow:hidden' );
		$this->assertCssHas( $css, self::WRAP, 'position:relative' );
		$this->assertCssHas( $css, self::WRAP, 'z-index:3' );
	}

	public function test_item_border_all_sub_properties_plus_dark(): void {
		// Border targets each term chip (not the wrapper).
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => '#e5e7eb', 'dark' => '#374151' ],
					'radius' => [ 'topLeft' => '6', 'topRight' => '6', 'bottomRight' => '6', 'bottomLeft' => '6', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::ITEM, 'border-style:solid' );
		$this->assertCssHas( $css, self::ITEM, 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, self::ITEM, 'border-color:#e5e7eb' );
		$this->assertCssHas( $css, self::ITEM, 'border-radius:6px 6px 6px 6px' );
		$this->assertCssHasInDark( $css, self::ITEM, 'border-color:#374151' );
		// Not on the wrapper.
		$this->assertStringNotContainsString( self::WRAP . '{border-style', $css );
	}

	public function test_wrapper_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#f8fafc', 'dark' => '#0f172a' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#f8fafc' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#0f172a' );
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
		$this->assertCssHas( $css, self::WRAP, 'background-image:url(https://example.com/bg.jpg)' );
		$this->assertCssHas( $css, self::WRAP, 'background-position:top left' );
		$this->assertCssHas( $css, self::WRAP, 'background-size:contain' );
		$this->assertCssHas( $css, self::WRAP, 'background-repeat:repeat-x' );
		$this->assertCssHas( $css, self::WRAP, 'background-attachment:fixed' );
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
		$this->assertCssHas( $css, self::WRAP . '.flexa-bg-loaded', 'background-image:url(https://example.com/bg.jpg)' );
	}

	public function test_box_shadow_light_and_dark_on_item(): void {
		// Box shadow targets each term chip (not the wrapper).
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

	public function test_icon_hover_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'iconHoverColor' => [ 'light' => '#7c3aed', 'dark' => '#a78bfa' ],
		] );
		$this->assertCssHas( $css, self::ITEM_ON . ' .flexa-taxonomy__prefix', 'color:#7c3aed' );
		$this->assertCssHas( $css, self::ITEM_ON . ' .flexa-taxonomy__suffix', 'color:#7c3aed' );
		$this->assertCssHasInDark( $css, self::ITEM_ON . ' .flexa-taxonomy__prefix', 'color:#a78bfa' );
		$this->assertCssHasInDark( $css, self::ITEM_ON . ' .flexa-taxonomy__suffix', 'color:#a78bfa' );
	}

	public function test_icon_hover_colour_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'prefixColor' => [ 'light' => '#2563eb' ] ] );
		$this->assertStringNotContainsString( '__item:hover .flexa-taxonomy__prefix', $css );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'   => 'a',
			'itemColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::LINK, 'color:#eeeeee', true );
		$this->assertStringContainsString( '[data-theme="dark"] ' . self::LINK, $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}

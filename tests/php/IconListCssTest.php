<?php
/**
 * Tests for the Icon List block CSS generator.
 *
 * Mirrors ContainerCssTest: copy genCss()/assertCssHas*() from CssTestCase and
 * assert one declaration per add_property() in Icon_List_CSS (guide §3.1).
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Icon_List_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Icon_List_CSS
 */
class IconListCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Icon List generator.
	 *
	 * @param array $attrs Icon List attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Icon_List_CSS::class, 'generate' ], $attrs );
	}

	// --- 1. Empty id -------------------------------------------------------

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	// --- 2. Untouched → nothing (theme-first) ------------------------------

	public function test_untouched_block_emits_nothing(): void {
		// Only an id + the default enum shapes — no picked values → no CSS.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'view' => 'list', 'iconView' => 'default', 'iconShape' => 'square' ] ) );
	}

	// --- 3/4. Layout: grid columns, gaps, alignment ------------------------

	public function test_grid_columns_target_list(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'view'    => 'grid',
			'columns' => [ 'desktop' => [ 'value' => '3', 'unit' => '' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a .flexa-icon-list__list', 'grid-template-columns:repeat(3, minmax(0, 1fr))' );
	}

	public function test_columns_ignored_in_list_view(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'view'    => 'list',
			'columns' => [ 'desktop' => [ 'value' => '3', 'unit' => '' ] ],
		] );
		$this->assertStringNotContainsString( 'grid-template-columns', $css );
	}

	public function test_columns_responsive_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'view'    => 'grid',
			'columns' => [ 'tablet' => [ 'value' => '2', 'unit' => '' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-icon-list-a .flexa-icon-list__list', 'grid-template-columns:repeat(2, minmax(0, 1fr))' );
	}

	public function test_item_gap_targets_list(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'gap'     => [ 'desktop' => [ 'value' => '16', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a .flexa-icon-list__list', 'gap:16px' );
	}

	public function test_item_gap_responsive_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'gap'     => [ 'mobile' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', '.flexa-icon-list-a .flexa-icon-list__list', 'gap:8px' );
	}

	public function test_icon_gap_targets_item(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'iconGap' => [ 'desktop' => [ 'value' => '14', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a .flexa-icon-list__item', 'gap:14px' );
	}

	public function test_alignment_maps_to_justify_content(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a .flexa-icon-list__item', 'justify-content:center' );
	}

	// --- Icon size / padding -----------------------------------------------

	public function test_icon_size_sets_font_size(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'iconSize' => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a .flexa-icon-list__icon', 'font-size:24px' );
	}

	public function test_icon_padding(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'iconPadding' => [ 'desktop' => [ 'value' => '6', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a .flexa-icon-list__icon', 'padding:6px' );
	}

	public function test_icon_padding_not_emitted_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'padding:', $css );
	}

	// --- Icon shape (gated by view) ----------------------------------------

	public function test_icon_shape_radius_emitted_when_framed(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'iconView'  => 'framed',
			'iconShape' => 'circle',
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a .flexa-icon-list__icon', 'border-radius:50%' );
	}

	public function test_icon_shape_radius_gated_by_default_view(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'iconView'  => 'default',
			'iconShape' => 'circle',
		] );
		$this->assertStringNotContainsString( 'border-radius', $css );
	}

	// --- Icon colours + hover ----------------------------------------------

	public function test_icon_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'iconColor' => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a .flexa-icon-list__icon', 'color:#2563eb' );        // light at base
		$this->assertCssHasInDark( $css, '.flexa-icon-list-a .flexa-icon-list__icon', 'color:#93c5fd' );  // dark full value
	}

	public function test_icon_color_hover_targets_item_hover(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'iconColorHover' => [ 'light' => '#111111', 'dark' => '' ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a .flexa-icon-list__item:hover .flexa-icon-list__icon', 'color:#111111' );
	}

	public function test_icon_background_and_hover(): void {
		$css = $this->gen( [
			'blockId'             => 'a',
			'iconBackground'      => [ 'light' => '#eef2ff', 'dark' => '' ],
			'iconBackgroundHover' => [ 'light' => '#e0e7ff', 'dark' => '' ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a .flexa-icon-list__icon', 'background:#eef2ff' );
		$this->assertCssHas( $css, '.flexa-icon-list-a .flexa-icon-list__item:hover .flexa-icon-list__icon', 'background:#e0e7ff' );
	}

	public function test_icon_border_color(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'iconBorderColor' => [ 'light' => '#cccccc', 'dark' => '#444444' ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a .flexa-icon-list__icon', 'border-color:#cccccc' );
		$this->assertCssHasInDark( $css, '.flexa-icon-list-a .flexa-icon-list__icon', 'border-color:#444444' );
	}

	public function test_icon_color_not_emitted_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'color:', $css );
		$this->assertStringNotContainsString( 'background:', $css );
	}

	// --- Text typography + colours -----------------------------------------

	public function test_text_typography_sub_properties(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [
				'desktop' => [
					'fontSize'      => [ 'value' => '18', 'unit' => 'px' ],
					'fontWeight'    => '600',
					'letterSpacing' => [ 'value' => '1', 'unit' => 'px' ],
					'textTransform' => 'uppercase',
					'lineHeight'    => '1.4',
				],
			],
		] );
		$text = '.flexa-icon-list-a .flexa-icon-list__text';
		$this->assertCssHas( $css, $text, 'font-size:18px' );
		$this->assertCssHas( $css, $text, 'font-weight:600' );
		$this->assertCssHas( $css, $text, 'letter-spacing:1px' );
		$this->assertCssHas( $css, $text, 'text-transform:uppercase' );
		$this->assertCssHas( $css, $text, 'line-height:1.4' );
	}

	public function test_text_color_light_and_dark_plus_hover(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'textColor'      => [ 'light' => '#1f2937', 'dark' => '#f9fafb' ],
			'textColorHover' => [ 'light' => '#2563eb', 'dark' => '' ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a .flexa-icon-list__text', 'color:#1f2937' );
		$this->assertCssHasInDark( $css, '.flexa-icon-list-a .flexa-icon-list__text', 'color:#f9fafb' );
		$this->assertCssHas( $css, '.flexa-icon-list-a .flexa-icon-list__item:hover .flexa-icon-list__text', 'color:#2563eb' );
	}

	// --- 7. Foundational: spacing / border / advancedLayout ----------------

	public function test_wrapper_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ],
					'margin'  => [ 'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a', 'padding:10px 10px 10px 10px' );
		$this->assertCssHas( $css, '.flexa-icon-list-a', 'margin:20px 20px 20px 20px' );
	}

	public function test_wrapper_border_light_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => '#dddddd', 'dark' => '#333333' ],
					'radius' => [ 'topLeft' => '6', 'topRight' => '6', 'bottomRight' => '6', 'bottomLeft' => '6', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-icon-list-a', 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, '.flexa-icon-list-a', 'border-color:#dddddd' );
		$this->assertCssHas( $css, '.flexa-icon-list-a', 'border-radius:6px 6px 6px 6px' );
		$this->assertCssHasInDark( $css, '.flexa-icon-list-a', 'border-color:#333333' );
	}

	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-icon-list-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-icon-list-a', 'z-index:5' );
	}

	// --- Wrapper background (light + dark) + lazy --------------------------

	public function test_background_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a', 'background-color:#ffffff' );
		$this->assertCssHasInDark( $css, '.flexa-icon-list-a', 'background-color:#000000' );
	}

	public function test_non_lazy_background_image_emits_url_inline(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'image', 'image' => [ 'url' => 'https://example.com/a.jpg', 'size' => 'cover' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertCssHas( $css, '.flexa-icon-list-a', 'background-size:cover' );
		$this->assertStringNotContainsString( 'flexa-bg-loaded', $css );
	}

	public function test_lazy_background_image_gates_url_behind_loaded_class(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'image', 'lazyLoad' => true, 'image' => [ 'url' => 'https://example.com/a.jpg', 'size' => 'cover' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a', 'background-size:cover' );
		$this->assertCssHas( $css, '.flexa-icon-list-a.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	// --- Box shadow (light + dark) -----------------------------------------

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-list-a', 'box-shadow:0px 2px 8px 0px #000000' );
		$this->assertCssHasInDark( $css, '.flexa-icon-list-a', 'box-shadow:0px 2px 8px 0px #ffffff' );
	}

	// --- 8. data-theme dark-mode branch ------------------------------------

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'   => 'a',
			'iconColor' => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
		] );

		$this->assertStringContainsString( '[data-theme="dark"] .flexa-icon-list-a .flexa-icon-list__icon', $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
		$this->assertCssHasInDark( $css, '.flexa-icon-list-a .flexa-icon-list__icon', 'color:#93c5fd', true );
	}
}

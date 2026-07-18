<?php
/**
 * Tests for the Table of Contents block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional bits (colours, border, background) and light/dark parity.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Table_Of_Content_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Table_Of_Content_CSS
 */
class TableOfContentCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the TOC generator.
	 *
	 * @param array $attrs TOC attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Table_Of_Content_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Theme-first: with nothing chosen the generator must emit no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_title_typography(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'titleTypography' => [
				'desktop' => [
					'fontSize'      => [ 'value' => '22', 'unit' => 'px' ],
					'fontWeight'    => '700',
					'letterSpacing' => [ 'value' => '1', 'unit' => 'px' ],
					'textTransform' => 'uppercase',
					'lineHeight'    => '1.3',
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-table-of-content-a .flexa-toc__title', 'font-size:22px' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a .flexa-toc__title', 'font-weight:700' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a .flexa-toc__title', 'letter-spacing:1px' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a .flexa-toc__title', 'text-transform:uppercase' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a .flexa-toc__title', 'line-height:1.3' );
	}

	public function test_link_typography(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'linkTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-table-of-content-a .flexa-toc__link', 'font-size:15px' );
	}

	public function test_item_gap_and_indent_variables(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'itemGap' => [ 'desktop' => [ 'value' => '10', 'unit' => 'px' ] ],
			'indent'  => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', '--flexa-toc-gap:10px' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', '--flexa-toc-indent:24px' );
	}

	public function test_max_width_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'maxWidth' => [ 'desktop' => [ 'value' => '360', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'max-width:360px' );
	}

	public function test_list_max_height_scrolls_the_body(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'listMaxHeight' => [ 'desktop' => [ 'value' => '80', 'unit' => 'vh' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-table-of-content-a .flexa-toc__body', 'max-height:80vh' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a .flexa-toc__body', 'overflow-y:auto' );
	}

	public function test_wrapper_spacing_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px' ],
					'margin'  => [ 'top' => '0', 'right' => '0', 'bottom' => '24', 'left' => '0', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'padding:20px 20px 20px 20px' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'margin:0px 0px 24px 0px' );
	}

	public function test_title_link_hover_and_marker_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'titleColor'     => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
			'linkColor'      => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
			'linkHoverColor' => [ 'light' => '#1e40af', 'dark' => '#bfdbfe' ],
			'markerColor'    => [ 'light' => '#888888', 'dark' => '#555555' ],
		] );
		// Light values at the base.
		$this->assertCssHas( $css, '.flexa-table-of-content-a .flexa-toc__title', 'color:#111111' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a .flexa-toc__link', 'color:#2563eb' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a .flexa-toc__link:hover', 'color:#1e40af' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a .flexa-toc__item::marker', 'color:#888888' );
		// Dark values under the dark-mode branch (full property:value, not bare hex).
		$this->assertCssHasInDark( $css, '.flexa-table-of-content-a .flexa-toc__title', 'color:#eeeeee' );
		$this->assertCssHasInDark( $css, '.flexa-table-of-content-a .flexa-toc__link', 'color:#93c5fd' );
		$this->assertCssHasInDark( $css, '.flexa-table-of-content-a .flexa-toc__link:hover', 'color:#bfdbfe' );
		$this->assertCssHasInDark( $css, '.flexa-table-of-content-a .flexa-toc__item::marker', 'color:#555555' );
	}

	public function test_colours_absent_when_not_set(): void {
		// Gating: no colour attributes → no colour declarations at all.
		$css = $this->gen( [ 'blockId' => 'a', 'maxWidth' => [ 'desktop' => [ 'value' => '300', 'unit' => 'px' ] ] ] );
		$this->assertStringNotContainsString( '__title', $css );
		$this->assertStringNotContainsString( '::marker', $css );
	}

	public function test_border_outlines_wrapper_with_all_subproperties(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => '#dddddd', 'dark' => '#444444' ],
					'radius' => [ 'topLeft' => '8', 'topRight' => '8', 'bottomRight' => '8', 'bottomLeft' => '8', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'border-color:#dddddd' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'border-radius:8px 8px 8px 8px' );
		// Dark border colour under the dark-mode branch.
		$this->assertCssHasInDark( $css, '.flexa-table-of-content-a', 'border-color:#444444' );
	}

	public function test_border_absent_when_no_style(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [ 'desktop' => [ 'style' => '' ] ],
		] );
		$this->assertStringNotContainsString( 'border-style:', $css );
	}

	public function test_advanced_layout_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'sticky', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'position:sticky' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'z-index:5' );
	}

	public function test_tablet_values_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'itemGap' => [ 'tablet' => [ 'value' => '6', 'unit' => 'px' ] ],
			'indent'  => [ 'tablet' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-table-of-content-a', '--flexa-toc-gap:6px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-table-of-content-a', '--flexa-toc-indent:12px' );
	}

	public function test_wrapper_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'background-color:#ffffff' );
		// Full property:value in dark (asserting "#000000" alone could match a light value elsewhere).
		$this->assertCssHasInDark( $css, '.flexa-table-of-content-a', 'background-color:#000000' );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000', 'dark' => '#fff' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'box-shadow:0px 2px 8px 0px #000' );
		$this->assertCssHasInDark( $css, '.flexa-table-of-content-a', 'box-shadow:0px 2px 8px 0px #fff' );
	}

	public function test_lazy_background_image_prints_url_once_behind_loaded_class(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'image',
				'lazyLoad' => true,
				'image'    => [ 'url' => 'https://example.com/bg.jpg', 'position' => 'center center', 'repeat' => 'no-repeat', 'size' => 'cover', 'attachment' => 'scroll' ],
			],
		] );
		// The URL prints exactly once, gated behind the loaded class.
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
		$this->assertCssHas( $css, '.flexa-table-of-content-a.flexa-bg-loaded', 'background-image:url(https://example.com/bg.jpg)' );
		// The image sub-properties are all emitted on the wrapper.
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'background-position:center center' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'background-size:cover' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'background-repeat:no-repeat' );
		$this->assertCssHas( $css, '.flexa-table-of-content-a', 'background-attachment:scroll' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'   => 'a',
			'linkColor' => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
		] );
		$this->assertCssHasInDark( $css, '.flexa-table-of-content-a .flexa-toc__link', 'color:#93c5fd', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
